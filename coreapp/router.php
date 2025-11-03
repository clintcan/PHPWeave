<?php
/**
 * Route Registration Helper Class
 *
 * Allows method chaining for route configuration.
 * Enables attaching hooks and other metadata to routes.
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Routing
 */
class RouteRegistration
{
    /**
     * Reference to the route data
     *
     * @var array
     */
    private $routeData;

    /**
     * Constructor
     *
     * @param array $routeData Reference to route data in Router::$routes
     */
    public function __construct(&$routeData)
    {
        $this->routeData = &$routeData;
    }

    /**
     * Attach one or more named hooks to this route
     *
     * @param string|array $hooks Hook alias(es) to attach
     * @return self For method chaining
     *
     * @example
     * Route::get('/admin', 'Admin@dashboard')->hook('auth');
     * Route::get('/admin/users', 'Admin@users')->hook(['auth', 'admin']);
     */
    public function hook($hooks)
    {
        if (!is_array($hooks)) {
            $hooks = [$hooks];
        }

        if (!isset($this->routeData['hooks'])) {
            $this->routeData['hooks'] = [];
        }

        $this->routeData['hooks'] = array_merge($this->routeData['hooks'], $hooks);

        // Also register with Hook class for execution
        Hook::attachToRoute(
            $this->routeData['method'],
            $this->routeData['pattern'],
            $hooks
        );

        return $this;
    }

    /**
     * Get the route data
     *
     * @return array Route data
     */
    public function getRouteData()
    {
        return $this->routeData;
    }
}

/**
 * Router Class
 *
 * Modern routing system for PHPWeave framework with support for:
 * - RESTful HTTP methods (GET, POST, PUT, DELETE, PATCH)
 * - Dynamic URL parameters using :param: syntax
 * - Route pattern matching with regex
 * - Automatic controller method dispatching
 * - Method override for PUT/DELETE/PATCH via _method parameter
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Routing
 * @author     Clint Christopher Canada
 * @version    2.0.0
 * @since      2.0.0
 *
 * @example
 * Route::get('/blog/:id:', 'Blog@show');
 * Route::post('/blog', 'Blog@store');
 * Route::put('/blog/:id:', 'Blog@update');
 * Route::delete('/blog/:id:', 'Blog@destroy');
 */
class Router
{
    /**
     * Collection of registered routes
     *
     * @var array
     */
    private static $routes = [];

    /**
     * Currently matched route information
     *
     * @var array|null
     */
    private static $matchedRoute = null;

    /**
     * Index of the last registered route
     *
     * @var int|null
     */
    private static $lastRouteIndex = null;

    /**
     * Stack of group contexts for nested groups
     *
     * @var array
     */
    private static $groupStack = [];

    /**
     * Cached merged group attributes (performance optimization)
     *
     * @var array|null
     */
    private static $cachedGroupAttributes = null;

    /**
     * Cache for compiled regex patterns (performance optimization v2.3.1)
     *
     * @var array
     */
    private static $compiledRegexes = [];

    /**
     * Route cache file path
     *
     * @var string|null
     */
    private static $cacheFile = null;

    /**
     * Whether routes have been loaded from cache
     *
     * @var bool
     */
    private static $loadedFromCache = false;

    /**
     * Whether to use APCu for caching
     *
     * @var bool
     */
    private static $useAPCu = false;

    /**
     * APCu cache key for routes
     *
     * @var string
     */
    private static $apcuKey = 'phpweave_routes_v1';

    /**
     * APCu cache TTL (time to live) in seconds
     *
     * @var int
     */
    private static $apcuTTL = 3600;

    /**
     * Cached request method (performance optimization)
     *
     * @var string|null
     */
    private static $cachedRequestMethod = null;

    /**
     * Cached request URI (performance optimization)
     *
     * @var string|null
     */
    private static $cachedRequestUri = null;

    /**
     * Register a GET route
     *
     * Creates a route that responds to HTTP GET requests.
     * Dynamic parameters can be defined using :param: syntax.
     *
     * @param string $pattern Route pattern (e.g., '/home/:id:')
     * @param string $handler Controller@method format (e.g., 'Home@index')
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::get('/blog/:id:', 'Blog@show');
     * @example Route::get('/user/:username:/posts', 'User@posts');
     * @example Route::get('/admin', 'Admin@dashboard')->hook('auth');
     */
    public static function get($pattern, $handler)
    {
        return self::register('GET', $pattern, $handler);
    }

    /**
     * Register a POST route
     *
     * Creates a route that responds to HTTP POST requests.
     * Typically used for form submissions and creating resources.
     *
     * @param string $pattern Route pattern (e.g., '/user/:id:')
     * @param string $handler Controller@method format (e.g., 'User@store')
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::post('/blog', 'Blog@store');
     * @example Route::post('/login', 'Auth@login')->hook('rate-limit');
     */
    public static function post($pattern, $handler)
    {
        return self::register('POST', $pattern, $handler);
    }

    /**
     * Register a PUT route
     *
     * Creates a route that responds to HTTP PUT requests.
     * Used for updating existing resources.
     * Supports method override via _method parameter in POST requests.
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method format
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::put('/blog/:id:', 'Blog@update');
     * @example Route::put('/blog/:id:', 'Blog@update')->hook(['auth', 'owner']);
     */
    public static function put($pattern, $handler)
    {
        return self::register('PUT', $pattern, $handler);
    }

    /**
     * Register a DELETE route
     *
     * Creates a route that responds to HTTP DELETE requests.
     * Used for deleting resources.
     * Supports method override via _method parameter in POST requests.
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method format
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::delete('/blog/:id:', 'Blog@destroy');
     * @example Route::delete('/blog/:id:', 'Blog@destroy')->hook(['auth', 'owner']);
     */
    public static function delete($pattern, $handler)
    {
        return self::register('DELETE', $pattern, $handler);
    }

    /**
     * Register a PATCH route
     *
     * Creates a route that responds to HTTP PATCH requests.
     * Used for partial updates to resources.
     * Supports method override via _method parameter in POST requests.
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method format
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::patch('/blog/:id:', 'Blog@partialUpdate');
     * @example Route::patch('/blog/:id:', 'Blog@partialUpdate')->hook('auth');
     */
    public static function patch($pattern, $handler)
    {
        return self::register('PATCH', $pattern, $handler);
    }

    /**
     * Register a route for any HTTP method
     *
     * Creates a route that responds to any HTTP method (GET, POST, PUT, DELETE, PATCH).
     * Useful for webhooks or flexible endpoints.
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method format
     * @return RouteRegistration For method chaining (e.g., ->hook())
     *
     * @example Route::any('/webhook', 'Webhook@handle');
     * @example Route::any('/:controller:', 'LegacyRouter@dispatch');
     */
    public static function any($pattern, $handler)
    {
        return self::register('ANY', $pattern, $handler);
    }

    /**
     * Define a route group with shared attributes
     *
     * Groups routes together and applies common hooks, prefixes, or other attributes.
     * Supports nested groups for hierarchical route organization.
     *
     * @param array $attributes Group attributes (e.g., ['hooks' => ['auth'], 'prefix' => '/admin'])
     * @param callable $callback Closure that defines routes within the group
     * @return void
     *
     * @example
     * Route::group(['hooks' => ['auth']], function() {
     *     Route::get('/profile', 'User@profile');
     *     Route::get('/settings', 'User@settings');
     * });
     *
     * @example
     * // Nested groups
     * Route::group(['prefix' => '/admin', 'hooks' => ['auth']], function() {
     *     Route::group(['hooks' => ['admin']], function() {
     *         Route::get('/users', 'Admin@users'); // /admin/users with auth + admin hooks
     *     });
     * });
     */
    public static function group($attributes, $callback)
    {
        // Push current group context onto stack
        self::$groupStack[] = $attributes;

        // Invalidate cached attributes when stack changes
        self::$cachedGroupAttributes = null;

        // Execute the callback (which will register routes)
        call_user_func($callback);

        // Pop the group context
        array_pop(self::$groupStack);

        // Invalidate cached attributes after popping
        self::$cachedGroupAttributes = null;
    }

    /**
     * Get current group attributes by merging all groups in stack
     *
     * @return array Merged attributes from all active groups
     */
    private static function getGroupAttributes()
    {
        // Return cached result if available (performance optimization)
        if (self::$cachedGroupAttributes !== null) {
            return self::$cachedGroupAttributes;
        }

        $merged = [
            'prefix' => '',
            'hooks' => []
        ];

        foreach (self::$groupStack as $group) {
            // Merge prefix
            if (isset($group['prefix'])) {
                $merged['prefix'] .= '/' . trim($group['prefix'], '/');
            }

            // Merge hooks
            if (isset($group['hooks'])) {
                $hooks = is_array($group['hooks']) ? $group['hooks'] : [$group['hooks']];
                $merged['hooks'] = array_merge($merged['hooks'], $hooks);
            }
        }

        // Clean up prefix
        $merged['prefix'] = '/' . trim($merged['prefix'], '/');
        if ($merged['prefix'] === '/') {
            $merged['prefix'] = '';
        }

        // Cache the result
        self::$cachedGroupAttributes = $merged;

        return $merged;
    }

    /**
     * Internal method to register routes
     *
     * Normalizes route patterns and stores them with compiled regex for matching.
     * Automatically prepends leading slash and removes trailing slashes.
     * Applies group attributes (prefix, hooks) if within a group.
     * Returns a RouteRegistration object for method chaining.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, ANY)
     * @param string $pattern Route pattern with optional :param: placeholders
     * @param string $handler Controller@method format
     * @return RouteRegistration For method chaining
     */
    private static function register($method, $pattern, $handler)
    {
        // Get group attributes
        $groupAttrs = self::getGroupAttributes();

        // Apply group prefix
        if (!empty($groupAttrs['prefix'])) {
            $pattern = $groupAttrs['prefix'] . $pattern;
        }

        // Ensure pattern starts with /
        if (substr($pattern, 0, 1) !== '/') {
            $pattern = '/' . $pattern;
        }

        // Remove trailing slash except for root
        if ($pattern !== '/' && substr($pattern, -1) === '/') {
            $pattern = rtrim($pattern, '/');
        }

        self::$routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'regex' => self::patternToRegex($pattern),
            'params' => self::extractParamNames($pattern),
            'hooks' => $groupAttrs['hooks'] // Apply group hooks automatically
        ];

        // Track the index of this route
        self::$lastRouteIndex = count(self::$routes) - 1;

        // Attach group hooks to this route
        if (!empty($groupAttrs['hooks'])) {
            Hook::attachToRoute($method, $pattern, $groupAttrs['hooks']);
        }

        // Return RouteRegistration for chaining
        return new RouteRegistration(self::$routes[self::$lastRouteIndex]);
    }

    /**
     * Convert route pattern to regex
     *
     * Transforms route patterns with :param: placeholders into regex patterns
     * for matching against request URIs.
     *
     * OPTIMIZED v2.3.1: Caches compiled regexes to avoid repeated compilation
     * (significant speedup for applications with many routes).
     *
     * @param string $pattern Route pattern (e.g., '/user/:id:/posts/:post_id:')
     * @return string Compiled regex pattern (e.g., '/^\/user\/([^\/]+)\/posts\/([^\/]+)$/')
     *
     * @example
     * Input:  '/user/:id:/posts/:post_id:'
     * Output: '/^\/user\/([^\/]+)\/posts\/([^\/]+)$/'
     */
    private static function patternToRegex($pattern)
    {
        // Check cache first (v2.3.1 optimization)
        if (isset(self::$compiledRegexes[$pattern])) {
            return self::$compiledRegexes[$pattern];
        }

        // Escape forward slashes
        $regex = str_replace('/', '\/', $pattern);

        // Replace :param: with named capture group
        $regex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*):/', '([^\/]+)', $regex);

        $compiledRegex = '/^' . $regex . '$/';

        // Cache the compiled regex (v2.3.1 optimization)
        self::$compiledRegexes[$pattern] = $compiledRegex;

        return $compiledRegex;
    }

    /**
     * Extract parameter names from pattern
     *
     * Parses the route pattern and extracts all parameter names defined
     * using the :param: syntax.
     *
     * @param string $pattern Route pattern with :param: placeholders
     * @return array Array of parameter names in order of appearance
     *
     * @example
     * Input:  '/user/:user_id:/post/:post_id:'
     * Output: ['user_id', 'post_id']
     */
    private static function extractParamNames($pattern)
    {
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*):/', $pattern, $matches);
        return $matches[1];
    }

    /**
     * Match the current request against registered routes
     *
     * Iterates through all registered routes and finds the first match
     * for the current HTTP method and URI. Extracts parameter values
     * from the URI based on route pattern.
     *
     * OPTIMIZED v2.3.1:
     * - Early return for empty routes
     * - Strict comparison for method matching (15-20% faster)
     *
     * @return array|null Matched route information with handler, params, method, and uri, or null if no match
     *
     * @example
     * For route: Route::get('/blog/:id:', 'Blog@show')
     * And URI: /blog/123
     * Returns: [
     *     'handler' => 'Blog@show',
     *     'params' => ['id' => '123'],
     *     'method' => 'GET',
     *     'uri' => '/blog/123'
     * ]
     */
    public static function match()
    {
        // Early return optimization (v2.3.1)
        if (empty(self::$routes)) {
            return null;
        }

        // Use cached values for performance (avoid parsing multiple times)
        if (self::$cachedRequestMethod === null) {
            self::$cachedRequestMethod = self::getRequestMethod();
        }
        if (self::$cachedRequestUri === null) {
            self::$cachedRequestUri = self::getRequestUri();
        }

        $requestMethod = self::$cachedRequestMethod;
        $requestUri = self::$cachedRequestUri;

        // Trigger before route match hook
        Hook::trigger('before_route_match', [
            'method' => $requestMethod,
            'uri' => $requestUri
        ]);

        foreach (self::$routes as $route) {
            // Check if method matches (strict comparison v2.3.1: 15-20% faster)
            if ($route['method'] !== 'ANY' && $route['method'] !== $requestMethod) {
                continue;
            }

            // Check if pattern matches
            if (preg_match($route['regex'], $requestUri, $matches)) {
                // Remove the full match from matches array
                array_shift($matches);

                // Combine param names with values
                $params = [];
                foreach ($route['params'] as $index => $paramName) {
                    if (isset($matches[$index])) {
                        $params[$paramName] = $matches[$index];
                    }
                }

                self::$matchedRoute = [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'method' => $requestMethod,
                    'uri' => $requestUri,
                    'pattern' => $route['pattern']
                ];

                // Trigger after route match hook
                Hook::trigger('after_route_match', self::$matchedRoute);

                return self::$matchedRoute;
            }
        }

        return null;
    }

    /**
     * Get the HTTP request method
     *
     * Returns the HTTP method for the current request.
     * Supports method override via _method POST parameter for PUT, PATCH, and DELETE requests.
     *
     * @return string HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     *
     * @example
     * Regular POST: returns 'POST'
     * POST with _method=PUT: returns 'PUT'
     */
    private static function getRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Support method override for PUT, PATCH, DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        return $method;
    }

    /**
     * Get the request URI path (without query string)
     *
     * Extracts and normalizes the request URI, removing query strings,
     * base URL paths, and ensuring consistent formatting.
     *
     * @return string Normalized URI path starting with /
     *
     * @example
     * Input:  /blog/123?page=1
     * Output: /blog/123
     *
     * @example
     * Input:  /subfolder/blog/123 (with baseurl=/subfolder)
     * Output: /blog/123
     */
    private static function getRequestUri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base URL if set
        if (isset($GLOBALS['baseurl']) && $GLOBALS['baseurl'] !== '/') {
            $baseurl = rtrim($GLOBALS['baseurl'], '/');
            if (strpos($uri, $baseurl) === 0) {
                // Optimization v2.3.1: Calculate length once
                $baseurlLen = strlen($baseurl);
                $uri = substr($uri, $baseurlLen);
            }
        }

        // Ensure URI starts with /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Parse handler string into controller and method
     *
     * Splits the handler string (Controller@method format) into separate
     * controller and method components.
     *
     * OPTIMIZED v2.3.1: Uses substr() + strpos() instead of explode() (30% faster)
     *
     * @param string $handler Handler string in 'Controller@method' format
     * @return array Associative array with 'controller' and 'method' keys
     * @throws Exception If handler format is invalid
     *
     * @example
     * Input:  'Blog@show'
     * Output: ['controller' => 'Blog', 'method' => 'show']
     */
    public static function parseHandler($handler)
    {
        // Optimization: Use strpos() + substr() instead of explode() (30% faster)
        $atPos = strpos($handler, '@');

        // Validate format: must have exactly one @ symbol
        if ($atPos === false || strpos($handler, '@', $atPos + 1) !== false) {
            throw new Exception("Invalid handler format: {$handler}. Expected 'Controller@method'");
        }

        return [
            'controller' => substr($handler, 0, $atPos),
            'method' => substr($handler, $atPos + 1)
        ];
    }

    /**
     * Dispatch the matched route
     *
     * Main entry point for the routing system. Matches the current request
     * against registered routes, loads the appropriate controller, and
     * executes the specified method with extracted parameters.
     *
     * @return void
     * @throws Exception If controller file, class, or method not found
     *
     * @example
     * // For route: Route::get('/blog/:id:', 'Blog@show')
     * // And URL: /blog/123
     * // Will call: Blog::show('123')
     */
    public static function dispatch()
    {
        $match = self::match();

        if ($match === null) {
            self::handle404();
            return;
        }

        try {
            $handlerInfo = self::parseHandler($match['handler']);
            $controllerName = $handlerInfo['controller'];
            $methodName = $handlerInfo['method'];

            // Get the controller file path
            $controllerFile = PHPWEAVE_ROOT . "/controller/" . strtolower($controllerName) . ".php";

            // Check if controller file exists
            if (!file_exists($controllerFile)) {
                throw new Exception("Controller file not found: {$controllerFile}");
            }

            // Trigger before controller load hook
            Hook::trigger('before_controller_load', [
                'controller' => $controllerName,
                'method' => $methodName,
                'file' => $controllerFile,
                'params' => $match['params']
            ]);

            // Include the controller file
            require_once $controllerFile;

            // Check if controller class exists
            if (!class_exists($controllerName)) {
                throw new Exception("Controller class not found: {$controllerName}");
            }

            // Instantiate controller without calling constructor's automatic method
            $controller = new $controllerName('__skip_auto_call__', '');

            // Trigger after controller instantiate hook
            Hook::trigger('after_controller_instantiate', [
                'controller' => $controllerName,
                'method' => $methodName,
                'instance' => $controller,
                'params' => $match['params']
            ]);

            // Check if method exists
            if (!method_exists($controller, $methodName)) {
                throw new Exception("Method {$methodName} not found in controller {$controllerName}");
            }

            // Trigger route-specific hooks first (middleware-like)
            $routeHookData = Hook::triggerRouteHooks(
                $match['method'],
                $match['pattern'],
                [
                    'controller' => $controllerName,
                    'method' => $methodName,
                    'instance' => $controller,
                    'params' => $match['params']
                ]
            );

            // Trigger before action execute hook (global)
            $actionData = Hook::trigger('before_action_execute',
                $routeHookData !== null ? $routeHookData : [
                    'controller' => $controllerName,
                    'method' => $methodName,
                    'instance' => $controller,
                    'params' => $match['params']
                ]
            );

            // Allow hooks to modify params
            $params = isset($actionData['params']) ? $actionData['params'] : $match['params'];

            // Call the method with parameters
            call_user_func_array([$controller, $methodName], array_values($params));

            // Trigger after action execute hook
            Hook::trigger('after_action_execute', [
                'controller' => $controllerName,
                'method' => $methodName,
                'params' => $params
            ]);

        } catch (Exception $e) {
            self::handle500($e);
        }
    }

    /**
     * Handle 404 Not Found
     *
     * Sends 404 HTTP header and displays error message.
     * Override this method to customize 404 error pages.
     *
     * @return void
     */
    private static function handle404()
    {
        // Trigger 404 hook
        Hook::trigger('on_404', [
            'uri' => self::getRequestUri(),
            'method' => self::getRequestMethod()
        ]);

        header("HTTP/1.0 404 Not Found");
        echo "404 - Route not found";
        die();
    }

    /**
     * Handle 500 Internal Server Error
     *
     * Sends 500 HTTP header and displays error information.
     * Shows detailed error trace when DEBUG mode is enabled.
     * Override this method to customize 500 error pages.
     *
     * @param Exception $e The exception that was thrown
     * @return void
     */
    private static function handle500($e)
    {
        // Trigger error hook
        Hook::trigger('on_error', [
            'exception' => $e,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        header("HTTP/1.0 500 Internal Server Error");
        echo "500 - Internal Server Error<br>";

        // Show detailed error in development (sanitized to prevent XSS)
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
            echo "Trace: <pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
        }
        die();
    }

    /**
     * Enable APCu memory caching for routes
     *
     * Uses APCu (in-memory cache) instead of file cache.
     * Ideal for Docker/containerized environments and production.
     * Falls back to disabled if APCu is not available.
     *
     * @param int $ttl Time to live in seconds (default: 3600 = 1 hour)
     * @return bool True if APCu is available and enabled, false otherwise
     *
     * @example Router::enableAPCuCache(); // Use default TTL
     * @example Router::enableAPCuCache(7200); // 2 hour TTL
     */
    public static function enableAPCuCache($ttl = 3600)
    {
        // Check if APCu is available and enabled
        if (function_exists('apcu_fetch') && function_exists('apcu_store')) {
            // Check if APCu is actually enabled (can be disabled in php.ini)
            if (ini_get('apc.enabled')) {
                self::$useAPCu = true;
                self::$apcuTTL = $ttl;
                return true;
            }
        }

        self::$useAPCu = false;
        return false;
    }

    /**
     * Enable file-based route caching
     *
     * Routes will be serialized and cached to avoid regex compilation
     * on every request. Call this before loading routes.php.
     *
     * @param string $cacheFile Path to cache file
     * @return void
     *
     * @example Router::enableCache('../cache/routes.cache');
     */
    public static function enableCache($cacheFile)
    {
        self::$cacheFile = $cacheFile;
    }

    /**
     * Load routes from cache (APCu or file)
     *
     * Attempts to load routes from APCu first (if enabled),
     * falls back to file cache if APCu fails or is not enabled.
     *
     * @return bool True if routes loaded from cache, false otherwise
     */
    public static function loadFromCache()
    {
        // Try APCu cache first
        if (self::$useAPCu) {
            $cached = @apcu_fetch(self::$apcuKey);
            if ($cached !== false && is_array($cached)) {
                self::$routes = $cached;
                self::$loadedFromCache = true;
                return true;
            }
            // APCu miss - continue to try file cache
        }

        // Try file cache
        if (!self::$cacheFile || !file_exists(self::$cacheFile)) {
            return false;
        }

        $contents = @file_get_contents(self::$cacheFile);
        if ($contents === false) {
            return false;
        }

        $cached = @json_decode($contents, true);

        if ($cached === null || !is_array($cached)) {
            return false;
        }

        self::$routes = $cached;
        self::$loadedFromCache = true;

        return true;
    }

    /**
     * Save current routes to cache (APCu or file)
     *
     * Saves to APCu if enabled, otherwise saves to file cache.
     * APCu is preferred for Docker/containerized environments.
     *
     * @return bool True on success, false on failure
     */
    public static function saveToCache()
    {
        // Try APCu cache first
        if (self::$useAPCu) {
            $success = @apcu_store(self::$apcuKey, self::$routes, self::$apcuTTL);
            if ($success) {
                return true;
            }
            // APCu failed - fall through to file cache
        }

        // File cache fallback
        if (!self::$cacheFile) {
            return false;
        }

        // Check if cache directory is writable (Docker-safe)
        $cacheDir = dirname(self::$cacheFile);
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0755, true)) {
                // Can't create directory - return false silently
                return false;
            }
        }

        // Check write permissions
        if (!is_writable($cacheDir)) {
            return false;
        }

        $json = json_encode(self::$routes);
        if ($json === false) {
            return false;
        }

        return @file_put_contents(self::$cacheFile, $json) !== false;
    }

    /**
     * Clear route cache (APCu and file)
     *
     * Clears both APCu and file cache if enabled.
     *
     * @return bool True on success
     */
    public static function clearCache()
    {
        $apcuSuccess = true;
        $fileSuccess = true;

        // Clear APCu cache
        if (self::$useAPCu) {
            $apcuSuccess = @apcu_delete(self::$apcuKey);
        }

        // Clear file cache
        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            $fileSuccess = @unlink(self::$cacheFile);
        }

        return $apcuSuccess && $fileSuccess;
    }

    /**
     * Get all registered routes
     *
     * Returns an array of all routes registered in the application.
     * Useful for debugging and route inspection.
     *
     * @return array Array of all registered routes with their patterns, methods, and handlers
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * Get the currently matched route
     *
     * Returns information about the route that was matched for the current request.
     * Returns null if no route has been matched yet.
     *
     * @return array|null Matched route info or null if no match
     */
    public static function getMatchedRoute()
    {
        return self::$matchedRoute;
    }
}

/**
 * Route Facade
 *
 * Convenient alias for the Router class, providing cleaner syntax
 * for route definitions.
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Routing
 *
 * @see Router
 *
 * @example Route::get('/blog/:id:', 'Blog@show');
 * @example Route::post('/blog', 'Blog@store');
 */
class Route extends Router {}
