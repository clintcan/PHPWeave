# PHPWeave Hooks System

The PHPWeave hooks system provides an event-driven architecture that allows you to execute custom code at specific points in the framework's request lifecycle.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Middleware-Style Hooks](#middleware-style-hooks)
  - [Class-Based Hooks](#class-based-hooks)
  - [Route-Specific Hooks](#route-specific-hooks)
  - [Route Groups](#route-groups)
  - [Built-in Hook Classes](#built-in-hook-classes)
- [Available Hook Points](#available-hook-points)
- [Hook Priority](#hook-priority)
- [Modifying Data](#modifying-data)
- [Halting Execution](#halting-execution)
- [Common Use Cases](#common-use-cases)
- [API Reference](#api-reference)

## Overview

Hooks allow you to:
- Execute code at specific lifecycle points
- Modify data flowing through the framework
- Implement cross-cutting concerns (authentication, logging, etc.)
- Extend framework functionality without modifying core files

**📚 Related Documentation:**
- [HOOKS_LOADING_EXPLAINED.md](HOOKS_LOADING_EXPLAINED.md) - How hook files are auto-loaded and executed

## Basic Usage

### Creating a Hook File

Create a PHP file in the `hooks/` directory:

```php
<?php
// hooks/my_hooks.php

Hook::register('before_action_execute', function($data) {
    // Your code here
    error_log("Controller executing: {$data['controller']}@{$data['method']}");

    // Return data (optionally modified)
    return $data;
});
```

**Hook files are automatically loaded at framework startup.** See [HOOKS_LOADING_EXPLAINED.md](HOOKS_LOADING_EXPLAINED.md) for details on how auto-loading works.

### Registering a Hook

```php
Hook::register($hookName, $callback, $priority);
```

**Parameters:**
- `$hookName` (string): Name of the hook point
- `$callback` (callable): Function to execute
- `$priority` (int, optional): Execution priority (default: 10, lower runs first)

## Middleware-Style Hooks

PHPWeave 2.1+ includes middleware-like functionality, allowing you to attach hooks to specific routes or route groups. This provides a cleaner, more structured approach to cross-cutting concerns like authentication, logging, and rate limiting.

### Class-Based Hooks

Class-based hooks are reusable, testable, and more maintainable than callback-based hooks. They work like middleware in Laravel, Express, and other frameworks.

#### Creating a Hook Class

```php
<?php
// hooks/classes/AuthHook.php

class AuthHook
{
    /**
     * Handle the hook execution
     *
     * @param array $data Route data (controller, method, instance, params)
     * @return array Modified data
     */
    public function handle($data)
    {
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['user'])) {
            // Redirect to login
            header('Location: /login');
            Hook::halt();
            exit;
        }

        // Add user data to params
        $data['params']['authenticated_user'] = $_SESSION['user'];

        return $data;
    }
}
```

#### Registering Hook Classes

```php
<?php
// hooks/example_class_based_hooks.php

// Load hook class
require_once __DIR__ . '/classes/AuthHook.php';

// Register hook with alias
Hook::registerClass('auth', AuthHook::class);

// Register with custom priority
Hook::registerClass('auth', AuthHook::class, 'before_action_execute', 5);

// Register with parameters
Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 100,
    'window' => 60
]);
```

**Parameters:**
- `$alias` (string): Unique name for the hook (e.g., 'auth', 'admin', 'log')
- `$className` (string): Fully qualified class name
- `$hookPoint` (string, optional): Hook point to attach to (default: 'before_action_execute')
- `$priority` (int, optional): Execution priority (default: 10)
- `$params` (array, optional): Parameters to pass to handle() method

### Route-Specific Hooks

Attach hooks to specific routes using the `->hook()` method:

```php
<?php
// routes.php

// Single hook
Route::get('/profile', 'User@profile')->hook('auth');

// Multiple hooks (executed in order)
Route::get('/admin/dashboard', 'Admin@dashboard')->hook(['auth', 'admin', 'log']);

// POST route with rate limiting
Route::post('/api/users', 'Api@createUser')->hook(['auth', 'rate-limit']);

// DELETE route with multiple protections
Route::delete('/blog/:id:', 'Blog@destroy')->hook(['auth', 'owner', 'log']);
```

**Benefits:**
- Hooks only execute for specific routes
- No performance impact on other routes
- Cleaner, more explicit routing definitions
- Easy to see which protections apply to each route

### Route Groups

Apply hooks to multiple routes at once using `Route::group()`:

```php
<?php
// routes.php

// Group with shared hooks
Route::group(['hooks' => ['auth']], function() {
    Route::get('/profile', 'User@profile');
    Route::get('/settings', 'User@settings');
    Route::post('/update-profile', 'User@updateProfile');
});

// Group with prefix and hooks
Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin', 'log']], function() {
    Route::get('/dashboard', 'Admin@dashboard');
    Route::get('/users', 'Admin@users');
    Route::post('/users/:id:/delete', 'Admin@deleteUser');
});

// Nested groups (hooks are cumulative)
Route::group(['prefix' => '/api', 'hooks' => ['cors', 'rate-limit']], function() {
    // Public API endpoints (cors + rate-limit)
    Route::get('/products', 'Api@products');

    // Protected endpoints (cors + rate-limit + auth)
    Route::group(['hooks' => ['auth']], function() {
        Route::get('/orders', 'Api@orders');
        Route::post('/orders', 'Api@createOrder');
    });
});
```

**Features:**
- **Shared hooks**: Apply hooks to all routes in group
- **URL prefixes**: Group routes by URL prefix
- **Nested groups**: Groups can be nested, hooks are cumulative
- **Clean organization**: Group related routes together

### Built-in Hook Classes

PHPWeave includes several ready-to-use hook classes in `hooks/classes/`:

#### AuthHook
Checks if user is authenticated. Redirects to `/login` if not.

```php
Hook::registerClass('auth', AuthHook::class);

// Usage
Route::get('/profile', 'User@profile')->hook('auth');
```

#### AdminHook
Checks if user has admin privileges. Returns 403 if not authorized.

```php
Hook::registerClass('admin', AdminHook::class);

// Usage (combine with auth)
Route::get('/admin/users', 'Admin@users')->hook(['auth', 'admin']);
```

#### LogHook
Logs request details (timestamp, user, IP, route, params).

```php
Hook::registerClass('log', LogHook::class);

// Usage
Route::get('/api/users', 'Api@users')->hook('log');
```

#### RateLimitHook
Limits requests per time window to prevent abuse.

```php
// Default: 100 requests per 60 seconds
Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 100,
    'window' => 60
]);

// Strict: 10 requests per 60 seconds (for login, etc.)
Hook::registerClass('rate-limit-strict', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 10,
    'window' => 60
]);

// Usage
Route::post('/login', 'Auth@login')->hook('rate-limit-strict');
```

#### CorsHook
Handles CORS headers for cross-origin API requests.

```php
// Allow all origins
Hook::registerClass('cors', CorsHook::class);

// Restrict to specific origins
Hook::registerClass('cors-api', CorsHook::class, 'before_action_execute', 1, [
    'origins' => ['https://example.com', 'https://app.example.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
    'credentials' => true,
    'max_age' => 3600
]);

// Usage
Route::group(['prefix' => '/api', 'hooks' => ['cors']], function() {
    Route::get('/users', 'Api@users');
});
```

### Creating Custom Hook Classes

```php
<?php
// hooks/classes/MyCustomHook.php

class MyCustomHook
{
    /**
     * Handle hook execution
     *
     * @param array $data Route data
     * @param mixed ...$params Custom parameters from registration
     * @return array Modified data
     */
    public function handle($data, ...$params)
    {
        // Access route information
        $controller = $data['controller'];  // Controller name
        $method = $data['method'];          // Method name
        $instance = $data['instance'];      // Controller instance
        $routeParams = $data['params'];     // Route parameters

        // Your custom logic here
        // ...

        // Modify data if needed
        $data['params']['custom_value'] = 'something';

        // Halt execution if needed
        if ($shouldHalt) {
            Hook::halt();
            exit;
        }

        // Always return $data
        return $data;
    }
}

// Register
Hook::registerClass('my-hook', MyCustomHook::class, 'before_action_execute', 10, [
    'config1' => 'value1',
    'config2' => 'value2'
]);

// Use
Route::get('/custom', 'Custom@action')->hook('my-hook');
```

## Available Hook Points

### Framework Lifecycle

#### `framework_start`
Triggered at the very start, after `.env` is loaded but before any core files.

```php
Hook::register('framework_start', function($data) {
    // Initialize session, set timezone, etc.
    session_start();
    date_default_timezone_set('UTC');
    return $data;
});
```

**Data:** `null`

---

#### `framework_shutdown`
Triggered at the end of the request (using PHP's `register_shutdown_function`).

```php
Hook::register('framework_shutdown', function($data) {
    // Cleanup, final logging, etc.
    error_log("Request completed");
    return $data;
});
```

**Data:** `null`

---

### Database Hooks

#### `before_db_connection`
Before database connection is initialized.

```php
Hook::register('before_db_connection', function($data) {
    // Modify database config, etc.
    return $data;
});
```

**Data:** `null`

---

#### `after_db_connection`
After database connection is ready.

```php
Hook::register('after_db_connection', function($data) {
    // Run database migrations, cache queries, etc.
    return $data;
});
```

**Data:** `null`

---

### Model Hooks

#### `before_models_load`
Before models directory is scanned.

```php
Hook::register('before_models_load', function($data) {
    return $data;
});
```

**Data:** `null`

---

#### `after_models_load`
After all models are loaded into `$GLOBALS['models']`.

```php
Hook::register('after_models_load', function($data) {
    // Access loaded models
    // $models = $GLOBALS['models'];
    return $data;
});
```

**Data:** `null`

---

### Router Hooks

#### `before_router_init`
Before router is loaded.

```php
Hook::register('before_router_init', function($data) {
    return $data;
});
```

**Data:** `null`

---

#### `after_routes_registered`
After `routes/routes.php` is loaded and all routes are registered.

```php
Hook::register('after_routes_registered', function($data) {
    // Access all routes via Router::getRoutes()
    return $data;
});
```

**Data:** `null`

---

#### `before_route_match`
Before route matching begins.

```php
Hook::register('before_route_match', function($data) {
    error_log("Matching route: {$data['method']} {$data['uri']}");
    return $data;
});
```

**Data:**
```php
[
    'method' => 'GET',           // HTTP method
    'uri' => '/blog/123'         // Request URI
]
```

---

#### `after_route_match`
After a route is successfully matched.

```php
Hook::register('after_route_match', function($data) {
    error_log("Route matched: {$data['handler']}");
    return $data;
});
```

**Data:**
```php
[
    'handler' => 'Blog@show',    // Controller@method
    'params' => ['id' => '123'], // Route parameters
    'method' => 'GET',           // HTTP method
    'uri' => '/blog/123'         // Request URI
]
```

---

### Controller Hooks

#### `before_controller_load`
Before controller file is included.

```php
Hook::register('before_controller_load', function($data) {
    error_log("Loading controller: {$data['controller']}");
    return $data;
});
```

**Data:**
```php
[
    'controller' => 'Blog',                           // Controller name
    'method' => 'show',                               // Method name
    'file' => '/path/to/controller/blog.php',        // Controller file path
    'params' => ['id' => '123']                      // Route parameters
]
```

---

#### `after_controller_instantiate`
After controller object is created.

```php
Hook::register('after_controller_instantiate', function($data) {
    // Access controller instance via $data['instance']
    return $data;
});
```

**Data:**
```php
[
    'controller' => 'Blog',              // Controller name
    'method' => 'show',                  // Method name
    'instance' => <Blog object>,         // Controller instance
    'params' => ['id' => '123']         // Route parameters
]
```

---

#### `before_action_execute`
Before controller method is called. **Most commonly used for authentication.**

```php
Hook::register('before_action_execute', function($data) {
    // Check authentication
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt(); // Stop execution
        exit;
    }
    return $data;
}, 5); // Priority 5 (runs early)
```

**Data:**
```php
[
    'controller' => 'Blog',              // Controller name
    'method' => 'show',                  // Method name
    'instance' => <Blog object>,         // Controller instance
    'params' => ['id' => '123']         // Route parameters (modifiable)
]
```

---

#### `after_action_execute`
After controller method completes.

```php
Hook::register('after_action_execute', function($data) {
    error_log("Action completed: {$data['controller']}@{$data['method']}");
    return $data;
});
```

**Data:**
```php
[
    'controller' => 'Blog',              // Controller name
    'method' => 'show',                  // Method name
    'params' => ['id' => '123']         // Route parameters
]
```

---

### View Hooks

#### `before_view_render`
Before view template is included.

```php
Hook::register('before_view_render', function($data) {
    // Add global data to all views
    if (!is_array($data['data'])) {
        $data['data'] = ['_content' => $data['data']];
    }
    $data['data']['site_name'] = 'My Site';
    return $data;
});
```

**Data:**
```php
[
    'template' => 'blog/show',                  // Template name
    'data' => [...],                            // View data (modifiable)
    'path' => '/path/to/views/blog/show.php'   // Template file path
]
```

---

#### `after_view_render`
After view is rendered.

```php
Hook::register('after_view_render', function($data) {
    error_log("View rendered: {$data['template']}");
    return $data;
});
```

**Data:**
```php
[
    'template' => 'blog/show',           // Template name
    'data' => [...]                      // View data
]
```

---

### Error Hooks

#### `on_404`
When no route matches the request.

```php
Hook::register('on_404', function($data) {
    error_log("404: {$data['method']} {$data['uri']}");
    // Optionally render custom 404 page
    return $data;
});
```

**Data:**
```php
[
    'uri' => '/unknown/path',            // Request URI
    'method' => 'GET'                    // HTTP method
]
```

---

#### `on_error`
When exceptions occur during routing/dispatch.

```php
Hook::register('on_error', function($data) {
    error_log("Error: {$data['message']}");
    // Send error notification, log to external service, etc.
    return $data;
});
```

**Data:**
```php
[
    'exception' => <Exception object>,   // Exception instance
    'message' => 'Error message',        // Exception message
    'file' => '/path/to/file.php',      // File where error occurred
    'line' => 123,                       // Line number
    'trace' => '...'                     // Stack trace
]
```

---

## Hook Priority

Multiple hooks can register for the same point with different priorities:

```php
// Runs first (priority 5)
Hook::register('before_action_execute', function($data) {
    echo "First\n";
    return $data;
}, 5);

// Runs second (priority 10, default)
Hook::register('before_action_execute', function($data) {
    echo "Second\n";
    return $data;
}, 10);

// Runs last (priority 20)
Hook::register('before_action_execute', function($data) {
    echo "Third\n";
    return $data;
}, 20);
```

**Lower priority numbers execute first.**

## Modifying Data

Hooks can modify data by returning it:

```php
Hook::register('before_action_execute', function($data) {
    // Add extra parameter
    $data['params']['user_id'] = $_SESSION['user_id'];
    return $data; // Modified data is passed to next hook/controller
});
```

```php
Hook::register('before_view_render', function($data) {
    // Ensure data is array
    if (!is_array($data['data'])) {
        $data['data'] = ['_content' => $data['data']];
    }

    // Add global variables
    $data['data']['current_user'] = $_SESSION['user'] ?? null;
    $data['data']['site_name'] = 'PHPWeave Site';

    return $data;
});
```

## Halting Execution

Use `Hook::halt()` to stop remaining hooks from executing:

```php
Hook::register('before_action_execute', function($data) {
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt(); // Stop other hooks and controller
        exit;
    }
    return $data;
}, 5);
```

## Common Use Cases

### Authentication

```php
// hooks/authentication.php
Hook::register('before_action_execute', function($data) {
    $publicRoutes = ['Auth@login', 'Auth@register', 'Home@index'];
    $handler = $data['controller'] . '@' . $data['method'];

    if (!in_array($handler, $publicRoutes) && !isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt();
        exit;
    }

    return $data;
}, 5);
```

### Request Logging

```php
// hooks/logging.php
Hook::register('after_route_match', function($data) {
    error_log(sprintf(
        "[%s] %s %s -> %s",
        date('Y-m-d H:i:s'),
        $data['method'],
        $data['uri'],
        $data['handler']
    ));
    return $data;
});
```

### CORS Headers

```php
// hooks/cors.php
Hook::register('framework_start', function($data) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    return $data;
}, 5);
```

### Global View Variables

```php
// hooks/view_globals.php
Hook::register('before_view_render', function($data) {
    if (!is_array($data['data'])) {
        $data['data'] = ['_content' => $data['data']];
    }

    $data['data']['site_name'] = 'My Site';
    $data['data']['current_year'] = date('Y');
    $data['data']['current_user'] = $_SESSION['user'] ?? null;

    return $data;
});
```

### Performance Monitoring

```php
// hooks/performance.php
$GLOBALS['_perf_start'] = null;

Hook::register('framework_start', function($data) {
    $GLOBALS['_perf_start'] = microtime(true);
    return $data;
}, 1);

Hook::register('framework_shutdown', function($data) {
    if ($GLOBALS['_perf_start']) {
        $elapsed = microtime(true) - $GLOBALS['_perf_start'];
        error_log("Request took: " . number_format($elapsed * 1000, 2) . "ms");
    }
    return $data;
}, 99);
```

## API Reference

### Callback-Based Hooks

#### Hook::register($hookName, $callback, $priority = 10)
Register a callback function for a hook point.

```php
Hook::register('before_action_execute', function($data) {
    // Your logic here
    return $data;
}, 10);
```

#### Hook::trigger($hookName, $data = null)
Trigger all callbacks for a hook point. Returns modified data.

```php
$data = Hook::trigger('before_action_execute', $data);
```

#### Hook::halt()
Stop executing remaining hooks for the current hook point.

```php
Hook::halt();
```

### Class-Based Hooks (Middleware-Style)

#### Hook::registerClass($alias, $className, $hookPoint = 'before_action_execute', $priority = 10, $params = [])
Register a class-based hook with an alias name.

```php
// Basic registration
Hook::registerClass('auth', AuthHook::class);

// With custom priority
Hook::registerClass('auth', AuthHook::class, 'before_action_execute', 5);

// With parameters
Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 100,
    'window' => 60
]);
```

**Parameters:**
- `$alias` - Unique name for the hook (used in routes)
- `$className` - Fully qualified class name
- `$hookPoint` - Hook point to attach to (default: 'before_action_execute')
- `$priority` - Execution priority (default: 10, lower runs first)
- `$params` - Array of parameters passed to handle() method

#### Hook::attachToRoute($method, $pattern, $hooks)
Attach named hooks to a specific route (called automatically by Router).

```php
Hook::attachToRoute('GET', '/admin', 'auth');
Hook::attachToRoute('POST', '/api/users', ['auth', 'rate-limit']);
```

#### Hook::triggerRouteHooks($method, $pattern, $data = null)
Trigger hooks attached to a specific route (called automatically by Router).

```php
$data = Hook::triggerRouteHooks('GET', '/admin', $data);
```

#### Hook::hasNamed($alias)
Check if a named hook is registered.

```php
if (Hook::hasNamed('auth')) {
    echo "Auth hook is registered";
}
```

#### Hook::getNamedHooks()
Get all registered named hooks.

```php
$namedHooks = Hook::getNamedHooks();
print_r($namedHooks);
```

#### Hook::getRouteHooks($method, $pattern)
Get hooks attached to a specific route.

```php
$hooks = Hook::getRouteHooks('GET', '/admin');
print_r($hooks); // ['auth', 'admin']
```

### Utility Methods

#### Hook::has($hookName)
Check if a hook has any registered callbacks.

```php
if (Hook::has('before_action_execute')) {
    echo "Authentication hooks are active";
}
```

### Hook::count($hookName)
Get number of registered callbacks for a hook.

```php
$count = Hook::count('before_action_execute');
echo "$count authentication hooks registered";
```

### Hook::clear($hookName)
Remove all callbacks for a specific hook.

```php
Hook::clear('before_action_execute');
```

### Hook::clearAll()
Remove all registered hooks (useful for testing).

```php
Hook::clearAll();
```

### Hook::getAll()
Get all registered hooks (debugging).

```php
$hooks = Hook::getAll();
print_r($hooks);
```

#### Hook::getAvailableHooks()
Get list of all available hook points with descriptions.

```php
$available = Hook::getAvailableHooks();
foreach ($available as $name => $description) {
    echo "$name: $description\n";
}
```

#### Hook::getExecutionLog()
Get execution log (only when DEBUG is enabled).

```php
if ($_GET['debug']) {
    print_r(Hook::getExecutionLog());
}
```

### Route API (for Hooks)

#### Route::group($attributes, $callback)
Define a route group with shared attributes.

```php
// Group with hooks
Route::group(['hooks' => ['auth']], function() {
    Route::get('/profile', 'User@profile');
    Route::get('/settings', 'User@settings');
});

// Group with prefix and hooks
Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin']], function() {
    Route::get('/dashboard', 'Admin@dashboard');
    Route::get('/users', 'Admin@users');
});

// Nested groups (cumulative)
Route::group(['hooks' => ['cors']], function() {
    Route::group(['prefix' => '/api', 'hooks' => ['rate-limit']], function() {
        Route::get('/users', 'Api@users'); // Has cors + rate-limit hooks
    });
});
```

**Attributes:**
- `hooks` - Array of hook aliases to apply to all routes in group
- `prefix` - URL prefix to prepend to all route patterns

#### RouteRegistration::hook($hooks)
Attach hooks to a specific route (method chaining).

```php
// Single hook
Route::get('/profile', 'User@profile')->hook('auth');

// Multiple hooks
Route::get('/admin/users', 'Admin@users')->hook(['auth', 'admin', 'log']);

// Chaining with other methods
Route::post('/api/users', 'Api@create')
    ->hook(['auth', 'rate-limit'])
    ->hook('log'); // Additional hooks can be chained
```

**Parameters:**
- `$hooks` - String or array of hook aliases

**Returns:** `RouteRegistration` for further chaining

## Disabling Hooks

To temporarily disable all hooks, rename the `hooks/` directory:

```bash
mv hooks hooks_disabled
```

To disable a specific hook file, rename it without `.php` extension:

```bash
mv hooks/authentication.php hooks/authentication.php.disabled
```

## Best Practices

1. **Use descriptive filenames**: `hooks/authentication.php`, `hooks/logging.php`
2. **Set appropriate priorities**: Authentication hooks should run early (priority 5)
3. **Always return data**: Even if unchanged, return `$data` from callbacks
4. **Use halt() sparingly**: Only for critical flow control (auth, redirects)
5. **Document your hooks**: Add comments explaining what each hook does
6. **Keep hooks focused**: One concern per hook file
7. **Handle edge cases**: Check if data exists before accessing it
8. **Avoid heavy operations**: Hooks run on every request

## Debugging

Enable DEBUG mode in `.env`:

```ini
DEBUG=1
```

Then check execution log:

```php
Hook::register('framework_shutdown', function($data) {
    print_r(Hook::getExecutionLog());
    return $data;
}, 99);
```

## Examples

See the `hooks/` directory for complete examples:
- `example_logging.php` - Request logging
- `example_authentication.php` - Authentication checks
- `example_performance.php` - Performance monitoring
- `example_global_data.php` - Global view variables
- `example_cors.php` - CORS headers

---

**Need help?** Inspect the `Hook` class at `coreapp/hooks.php`.
