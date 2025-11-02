<?php
/**
 * Hook Manager Class
 *
 * Event-driven hooks system that allows developers to register callbacks
 * at key lifecycle points in the PHPWeave framework.
 *
 * Features:
 * - Multiple hooks per event with priority ordering
 * - Data passing and modification between hooks
 * - Conditional execution control (halt propagation)
 * - Simple registration API
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Hooks
 * @author     Clint Christopher Canada
 * @version    2.0.0
 * @since      2.0.0
 *
 * @example
 * // Register a hook
 * Hook::register('before_action_execute', function($data) {
 *     // Your logic here
 *     return $data; // Optionally modify and return data
 * }, 10);
 *
 * // Trigger a hook
 * $data = Hook::trigger('before_action_execute', $data);
 */
class Hook
{
    /**
     * Registered hooks storage
     *
     * Format: [
     *     'hook_name' => [
     *         ['callback' => callable, 'priority' => int],
     *         ...
     *     ]
     * ]
     *
     * @var array
     */
    private static $hooks = [];

    /**
     * Flag to track if execution should be halted
     *
     * @var bool
     */
    private static $halted = false;

    /**
     * Hook execution log for debugging
     *
     * @var array
     */
    private static $executionLog = [];

    /**
     * Track which hooks have been sorted
     *
     * @var array
     */
    private static $hooksSorted = [];

    /**
     * Cached debug mode flag (performance optimization)
     *
     * @var bool|null
     */
    private static $debugMode = null;

    /**
     * Named hook classes (middleware-like)
     *
     * Format: [
     *     'hook_alias' => [
     *         'class' => 'ClassName',
     *         'hook_point' => 'before_action_execute',
     *         'priority' => 10,
     *         'params' => []
     *     ]
     * ]
     *
     * @var array
     */
    private static $namedHooks = [];

    /**
     * Route-specific hooks storage
     *
     * Format: [
     *     'GET:/admin' => ['auth', 'admin'],
     *     'POST:/api/users' => ['api-auth', 'rate-limit']
     * ]
     *
     * @var array
     */
    private static $routeHooks = [];

    /**
     * Cached resolved hook instances (performance optimization)
     *
     * Format: [
     *     'auth' => ['instance' => AuthHook, 'params' => []],
     *     'admin' => ['instance' => AdminHook, 'params' => []]
     * ]
     *
     * @var array
     */
    private static $resolvedHooks = [];

    /**
     * Register a hook callback
     *
     * Registers a callback function to be executed when a specific hook is triggered.
     * Multiple callbacks can be registered for the same hook with different priorities.
     * Lower priority numbers execute first (10 executes before 20).
     *
     * @param string   $hookName  Name of the hook to register for
     * @param callable $callback  Function to execute when hook is triggered
     * @param int      $priority  Execution priority (default: 10, lower = earlier)
     * @return void
     *
     * @example
     * Hook::register('before_action_execute', function($data) {
     *     error_log("Action executing: " . $data['method']);
     *     return $data;
     * }, 10);
     */
    public static function register($hookName, $callback, $priority = 10)
    {
        if (!is_callable($callback)) {
            trigger_error("Hook callback for '{$hookName}' is not callable", E_USER_WARNING);
            return;
        }

        if (!isset(self::$hooks[$hookName])) {
            self::$hooks[$hookName] = [];
        }

        self::$hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Mark this hook as needing sorting (lazy sort on first trigger)
        self::$hooksSorted[$hookName] = false;
    }

    /**
     * Register a named hook class (middleware-like)
     *
     * Registers a hook class that can be attached to specific routes.
     * This allows for reusable, testable, class-based hooks.
     *
     * @param string $alias       Unique name for this hook (e.g., 'auth', 'admin')
     * @param string $className   Fully qualified class name
     * @param string $hookPoint   Which hook point to attach to (default: 'before_action_execute')
     * @param int    $priority    Execution priority (default: 10)
     * @param array  $params      Optional parameters to pass to the hook
     * @return void
     *
     * @example
     * Hook::registerClass('auth', AuthHook::class, 'before_action_execute', 5);
     * // Then use in routes: Route::get('/admin', 'Admin@dashboard')->hook('auth');
     *
     * @example
     * // With parameters
     * Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
     *     'max' => 100,
     *     'window' => 60
     * ]);
     */
    public static function registerClass($alias, $className, $hookPoint = 'before_action_execute', $priority = 10, $params = [])
    {
        if (!class_exists($className)) {
            trigger_error("Hook class '{$className}' does not exist", E_USER_WARNING);
            return;
        }

        self::$namedHooks[$alias] = [
            'class' => $className,
            'hook_point' => $hookPoint,
            'priority' => $priority,
            'params' => $params
        ];
    }

    /**
     * Attach named hooks to a specific route
     *
     * Associates one or more named hooks with a route pattern.
     * These hooks will only execute for the specified route.
     *
     * @param string       $method HTTP method (GET, POST, etc.)
     * @param string       $pattern Route pattern (e.g., '/admin')
     * @param string|array $hooks   Hook alias(es) to attach
     * @return void
     *
     * @example
     * Hook::attachToRoute('GET', '/admin', 'auth');
     * Hook::attachToRoute('POST', '/api/users', ['api-auth', 'rate-limit']);
     */
    public static function attachToRoute($method, $pattern, $hooks)
    {
        $routeKey = self::makeRouteKey($method, $pattern);

        if (!is_array($hooks)) {
            $hooks = [$hooks];
        }

        if (!isset(self::$routeHooks[$routeKey])) {
            self::$routeHooks[$routeKey] = [];
        }

        self::$routeHooks[$routeKey] = array_merge(self::$routeHooks[$routeKey], $hooks);
    }

    /**
     * Trigger route-specific hooks
     *
     * Executes hooks that are attached to the current route.
     * This is called by the Router during request dispatch.
     * Uses cached hook instances for better performance.
     *
     * @param string $method  HTTP method
     * @param string $pattern Route pattern
     * @param mixed  $data    Data to pass to hooks
     * @return mixed Modified data
     */
    public static function triggerRouteHooks($method, $pattern, $data = null)
    {
        $routeKey = self::makeRouteKey($method, $pattern);

        if (!isset(self::$routeHooks[$routeKey])) {
            return $data;
        }

        foreach (self::$routeHooks[$routeKey] as $hookAlias) {
            if (!isset(self::$namedHooks[$hookAlias])) {
                continue;
            }

            try {
                // Use cached resolved hook instance for performance
                if (!isset(self::$resolvedHooks[$hookAlias])) {
                    $hookInfo = self::$namedHooks[$hookAlias];
                    $className = $hookInfo['class'];
                    $hookInstance = new $className();

                    self::$resolvedHooks[$hookAlias] = [
                        'instance' => $hookInstance,
                        'params' => $hookInfo['params']
                    ];
                }

                $resolved = self::$resolvedHooks[$hookAlias];
                $hookInstance = $resolved['instance'];
                $params = $resolved['params'];

                // Call handle() method with data and optional parameters
                if (method_exists($hookInstance, 'handle')) {
                    if (!empty($params)) {
                        $result = call_user_func_array(
                            [$hookInstance, 'handle'],
                            array_merge([$data], $params)
                        );
                    } else {
                        $result = $hookInstance->handle($data);
                    }

                    if ($result !== null) {
                        $data = $result;
                    }
                }

                // Check if execution should be halted
                if (self::$halted) {
                    break;
                }
            } catch (Exception $e) {
                trigger_error(
                    "Error in route hook '{$hookAlias}': " . $e->getMessage(),
                    E_USER_WARNING
                );
            }
        }

        return $data;
    }

    /**
     * Create a unique key for route hook storage
     *
     * @param string $method  HTTP method
     * @param string $pattern Route pattern
     * @return string Route key (e.g., 'GET:/admin')
     */
    private static function makeRouteKey($method, $pattern)
    {
        return strtoupper($method) . ':' . $pattern;
    }

    /**
     * Get hooks attached to a specific route
     *
     * @param string $method  HTTP method
     * @param string $pattern Route pattern
     * @return array Array of hook aliases
     */
    public static function getRouteHooks($method, $pattern)
    {
        $routeKey = self::makeRouteKey($method, $pattern);
        return self::$routeHooks[$routeKey] ?? [];
    }

    /**
     * Check if a named hook is registered
     *
     * @param string $alias Hook alias
     * @return bool True if hook is registered
     */
    public static function hasNamed($alias)
    {
        return isset(self::$namedHooks[$alias]);
    }

    /**
     * Get all registered named hooks
     *
     * @return array All named hooks
     */
    public static function getNamedHooks()
    {
        return self::$namedHooks;
    }

    /**
     * Trigger a hook
     *
     * Executes all callbacks registered for the specified hook in priority order.
     * Passes data to each callback and allows them to modify it.
     * If a callback calls Hook::halt(), no further callbacks will execute.
     *
     * @param string $hookName Name of the hook to trigger
     * @param mixed  $data     Data to pass to callbacks (default: null)
     * @return mixed Modified data after all callbacks execute
     *
     * @example
     * $data = ['user' => 'john', 'action' => 'login'];
     * $data = Hook::trigger('before_action_execute', $data);
     */
    public static function trigger($hookName, $data = null)
    {
        // Reset halt flag
        self::$halted = false;

        if (!isset(self::$hooks[$hookName]) || empty(self::$hooks[$hookName])) {
            return $data;
        }

        // Lazy sort: only sort on first trigger, not on registration
        if (empty(self::$hooksSorted[$hookName])) {
            usort(self::$hooks[$hookName], function($a, $b) {
                return $a['priority'] - $b['priority'];
            });
            self::$hooksSorted[$hookName] = true;
        }

        // Log hook execution (use cached debug flag for performance)
        if (self::$debugMode === null) {
            self::$debugMode = self::isDebugEnabled();
        }

        if (self::$debugMode) {
            self::$executionLog[] = [
                'hook' => $hookName,
                'time' => microtime(true),
                'callbacks' => count(self::$hooks[$hookName])
            ];
        }

        foreach (self::$hooks[$hookName] as $hook) {
            // Check if halt() was called by a previous hook callback
            // PHPStan cannot detect that $halted may be set to true via call_user_func() below
            if (self::$halted) {
                break;
            }

            try {
                $result = call_user_func($hook['callback'], $data);

                // If callback returns a value, use it as new data
                if ($result !== null) {
                    $data = $result;
                }
            } catch (Exception $e) {
                trigger_error(
                    "Error in hook '{$hookName}': " . $e->getMessage(),
                    E_USER_WARNING
                );

                // Log the error if error class is available
                if (class_exists('ErrorClass')) {
                    error_log("Hook Error [{$hookName}]: " . $e->getMessage());
                }
            }
        }

        return $data;
    }

    /**
     * Halt hook execution
     *
     * Stops the execution of remaining callbacks for the current hook.
     * Useful for authentication checks or conditional processing.
     *
     * @return void
     *
     * @example
     * Hook::register('before_action_execute', function($data) {
     *     if (!isset($_SESSION['user'])) {
     *         header('Location: /login');
     *         Hook::halt(); // Stop other hooks from executing
     *         exit;
     *     }
     * });
     */
    public static function halt()
    {
        self::$halted = true;
    }

    /**
     * Check if hook execution was halted
     *
     * @return bool True if halt() was called
     */
    public static function isHalted()
    {
        return self::$halted;
    }

    /**
     * Check if a hook has any registered callbacks
     *
     * @param string $hookName Name of the hook to check
     * @return bool True if hook has callbacks registered
     *
     * @example
     * if (Hook::has('before_action_execute')) {
     *     echo "Authentication hooks are registered";
     * }
     */
    public static function has($hookName)
    {
        return isset(self::$hooks[$hookName]) && !empty(self::$hooks[$hookName]);
    }

    /**
     * Get count of registered callbacks for a hook
     *
     * @param string $hookName Name of the hook
     * @return int Number of registered callbacks
     */
    public static function count($hookName)
    {
        return isset(self::$hooks[$hookName]) ? count(self::$hooks[$hookName]) : 0;
    }

    /**
     * Remove all callbacks for a hook
     *
     * @param string $hookName Name of the hook to clear
     * @return void
     *
     * @example
     * Hook::clear('before_action_execute');
     */
    public static function clear($hookName)
    {
        if (isset(self::$hooks[$hookName])) {
            unset(self::$hooks[$hookName]);
        }
    }

    /**
     * Remove all registered hooks
     *
     * Clears all callback-based hooks, named hooks, and route hooks.
     * Useful for testing.
     *
     * @return void
     */
    public static function clearAll()
    {
        self::$hooks = [];
        self::$namedHooks = [];
        self::$routeHooks = [];
        self::$executionLog = [];
        self::$hooksSorted = [];
        self::$debugMode = null; // Reset cached debug flag
        self::$resolvedHooks = []; // Clear resolved hook cache
    }

    /**
     * Get all registered hooks
     *
     * Returns array of all hooks and their callbacks.
     * Useful for debugging.
     *
     * @return array All registered hooks
     */
    public static function getAll()
    {
        return self::$hooks;
    }

    /**
     * Get execution log
     *
     * Returns log of all triggered hooks with timing information.
     * Only available when DEBUG mode is enabled.
     *
     * @return array Execution log entries
     */
    public static function getExecutionLog()
    {
        return self::$executionLog;
    }

    /**
     * Load hook files from hooks directory
     *
     * Automatically includes all PHP files from the hooks/ directory.
     * Hook files should register their callbacks using Hook::register().
     *
     * @param string $hooksDir Path to hooks directory
     * @return void
     */
    public static function loadHookFiles($hooksDir)
    {
        if (!is_dir($hooksDir)) {
            return;
        }

        $files = glob($hooksDir . '/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            try {
                require_once $file;
            } catch (Exception $e) {
                trigger_error(
                    "Error loading hook file '{$file}': " . $e->getMessage(),
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool True if DEBUG is enabled in config
     */
    private static function isDebugEnabled()
    {
        return isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'];
    }

    /**
     * Get list of available hook points in PHPWeave
     *
     * Returns documentation of all standard hook points in the framework.
     *
     * @return array List of hook names with descriptions
     */
    public static function getAvailableHooks()
    {
        return [
            'framework_start' => 'Triggered at the very start, after .env is loaded',
            'before_db_connection' => 'Before database connection is initialized',
            'after_db_connection' => 'After database connection is ready',
            'before_models_load' => 'Before models directory is scanned',
            'after_models_load' => 'After all models are loaded',
            'before_router_init' => 'Before router is loaded',
            'after_routes_registered' => 'After routes.php is loaded',
            'before_route_match' => 'Before route matching begins',
            'after_route_match' => 'After route is matched (includes matched route data)',
            'before_controller_load' => 'Before controller file is included',
            'after_controller_instantiate' => 'After controller object is created',
            'before_action_execute' => 'Before controller method is called',
            'after_action_execute' => 'After controller method completes',
            'before_view_render' => 'Before view template is included',
            'after_view_render' => 'After view is rendered',
            'on_404' => 'When no route matches',
            'on_error' => 'When exceptions occur',
            'framework_shutdown' => 'At the end of request'
        ];
    }
}
