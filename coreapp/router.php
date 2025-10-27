<?php
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
     * Register a GET route
     *
     * Creates a route that responds to HTTP GET requests.
     * Dynamic parameters can be defined using :param: syntax.
     *
     * @param string $pattern Route pattern (e.g., '/home/:id:')
     * @param string $handler Controller@method format (e.g., 'Home@index')
     * @return void
     *
     * @example Route::get('/blog/:id:', 'Blog@show');
     * @example Route::get('/user/:username:/posts', 'User@posts');
     */
    public static function get($pattern, $handler)
    {
        self::register('GET', $pattern, $handler);
    }

    /**
     * Register a POST route
     *
     * Creates a route that responds to HTTP POST requests.
     * Typically used for form submissions and creating resources.
     *
     * @param string $pattern Route pattern (e.g., '/user/:id:')
     * @param string $handler Controller@method format (e.g., 'User@store')
     * @return void
     *
     * @example Route::post('/blog', 'Blog@store');
     * @example Route::post('/login', 'Auth@login');
     */
    public static function post($pattern, $handler)
    {
        self::register('POST', $pattern, $handler);
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
     * @return void
     *
     * @example Route::put('/blog/:id:', 'Blog@update');
     */
    public static function put($pattern, $handler)
    {
        self::register('PUT', $pattern, $handler);
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
     * @return void
     *
     * @example Route::delete('/blog/:id:', 'Blog@destroy');
     */
    public static function delete($pattern, $handler)
    {
        self::register('DELETE', $pattern, $handler);
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
     * @return void
     *
     * @example Route::patch('/blog/:id:', 'Blog@partialUpdate');
     */
    public static function patch($pattern, $handler)
    {
        self::register('PATCH', $pattern, $handler);
    }

    /**
     * Register a route for any HTTP method
     *
     * Creates a route that responds to any HTTP method (GET, POST, PUT, DELETE, PATCH).
     * Useful for webhooks or flexible endpoints.
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method format
     * @return void
     *
     * @example Route::any('/webhook', 'Webhook@handle');
     */
    public static function any($pattern, $handler)
    {
        self::register('ANY', $pattern, $handler);
    }

    /**
     * Internal method to register routes
     *
     * Normalizes route patterns and stores them with compiled regex for matching.
     * Automatically prepends leading slash and removes trailing slashes.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, ANY)
     * @param string $pattern Route pattern with optional :param: placeholders
     * @param string $handler Controller@method format
     * @return void
     */
    private static function register($method, $pattern, $handler)
    {
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
            'params' => self::extractParamNames($pattern)
        ];
    }

    /**
     * Convert route pattern to regex
     *
     * Transforms route patterns with :param: placeholders into regex patterns
     * for matching against request URIs.
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
        // Escape forward slashes
        $regex = str_replace('/', '\/', $pattern);

        // Replace :param: with named capture group
        $regex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*):/', '([^\/]+)', $regex);

        return '/^' . $regex . '$/';
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
        $requestMethod = self::getRequestMethod();
        $requestUri = self::getRequestUri();

        // Trigger before route match hook
        Hook::trigger('before_route_match', [
            'method' => $requestMethod,
            'uri' => $requestUri
        ]);

        foreach (self::$routes as $route) {
            // Check if method matches
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
                    'uri' => $requestUri
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
                $uri = substr($uri, strlen($baseurl));
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
        $parts = explode('@', $handler);

        if (count($parts) !== 2) {
            throw new Exception("Invalid handler format: {$handler}. Expected 'Controller@method'");
        }

        return [
            'controller' => $parts[0],
            'method' => $parts[1]
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

            // Trigger before action execute hook
            $actionData = Hook::trigger('before_action_execute', [
                'controller' => $controllerName,
                'method' => $methodName,
                'instance' => $controller,
                'params' => $match['params']
            ]);

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

        // Show detailed error in development
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            echo "Error: " . $e->getMessage() . "<br>";
            echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
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

        $cached = @unserialize(file_get_contents(self::$cacheFile));

        if ($cached === false) {
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

        return @file_put_contents(self::$cacheFile, serialize(self::$routes)) !== false;
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
        $success = true;

        // Clear APCu cache
        if (self::$useAPCu) {
            $success = $success && @apcu_delete(self::$apcuKey);
        }

        // Clear file cache
        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            $success = $success && @unlink(self::$cacheFile);
        }

        return $success;
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
