# PHPWeave Hooks System

The PHPWeave hooks system provides an event-driven architecture that allows you to execute custom code at specific points in the framework's request lifecycle.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
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

Hook files are automatically loaded at framework startup.

### Registering a Hook

```php
Hook::register($hookName, $callback, $priority);
```

**Parameters:**
- `$hookName` (string): Name of the hook point
- `$callback` (callable): Function to execute
- `$priority` (int, optional): Execution priority (default: 10, lower runs first)

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

### Hook::register($hookName, $callback, $priority = 10)
Register a callback for a hook point.

### Hook::trigger($hookName, $data = null)
Trigger all callbacks for a hook point. Returns modified data.

### Hook::halt()
Stop executing remaining hooks for the current hook point.

### Hook::has($hookName)
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

### Hook::getAvailableHooks()
Get list of all available hook points with descriptions.

```php
$available = Hook::getAvailableHooks();
foreach ($available as $name => $description) {
    echo "$name: $description\n";
}
```

### Hook::getExecutionLog()
Get execution log (only when DEBUG is enabled).

```php
if ($_GET['debug']) {
    print_r(Hook::getExecutionLog());
}
```

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
