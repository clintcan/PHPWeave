<?php
/**
 * Class-Based Hooks Registration Example
 *
 * This file demonstrates how to register and use class-based hooks
 * (middleware-style hooks) in PHPWeave.
 *
 * Class-based hooks are more structured, testable, and reusable than
 * callback-based hooks. They work like middleware in other frameworks.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 *
 * USAGE:
 * ------
 * 1. Uncomment the sections below to enable the hooks you want
 * 2. Adjust parameters as needed for your application
 * 3. Use the hook aliases in your routes (see examples below)
 */

// ============================================================================
// LOAD HOOK CLASSES
// ============================================================================

// Load all hook classes from hooks/classes/ directory
$hookClassFiles = glob(__DIR__ . '/classes/*.php');
foreach ($hookClassFiles as $file) {
    require_once $file;
}

// ============================================================================
// REGISTER HOOK CLASSES
// ============================================================================

/**
 * Authentication Hook
 * -------------------
 * Checks if user is logged in. Redirects to /login if not authenticated.
 */
Hook::registerClass('auth', AuthHook::class, 'before_action_execute', 5);

/**
 * Admin Authorization Hook
 * -------------------------
 * Checks if authenticated user has admin privileges.
 * Use in combination with 'auth' hook.
 */
Hook::registerClass('admin', AdminHook::class, 'before_action_execute', 6);

/**
 * Request Logging Hook
 * ---------------------
 * Logs all requests including timestamp, user, IP, and route.
 * Useful for debugging and audit trails.
 */
Hook::registerClass('log', LogHook::class, 'before_action_execute', 10);

/**
 * Rate Limiting Hook (Default)
 * -----------------------------
 * Limits to 100 requests per 60 seconds per client.
 * Suitable for general API protection.
 */
Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 100,
    'window' => 60
]);

/**
 * Rate Limiting Hook (Strict)
 * ----------------------------
 * Limits to 10 requests per 60 seconds per client.
 * Use for sensitive operations like login, password reset.
 */
Hook::registerClass('rate-limit-strict', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 10,
    'window' => 60
]);

/**
 * CORS Hook (Allow All)
 * ----------------------
 * Allows cross-origin requests from any domain.
 * Use for public APIs.
 */
Hook::registerClass('cors', CorsHook::class, 'before_action_execute', 1);

/**
 * CORS Hook (Restricted)
 * -----------------------
 * Allows cross-origin requests only from specific domains.
 * Use for production APIs with known client domains.
 */
Hook::registerClass('cors-api', CorsHook::class, 'before_action_execute', 1, [
    'origins' => ['https://example.com', 'https://app.example.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
    'credentials' => true,
    'max_age' => 3600
]);

// ============================================================================
// USAGE EXAMPLES (in routes.php)
// ============================================================================

/*
// Example 1: Single hook on a route
Route::get('/profile', 'User@profile')->hook('auth');

// Example 2: Multiple hooks on a route (executed in order)
Route::get('/admin/dashboard', 'Admin@dashboard')->hook(['auth', 'admin', 'log']);

// Example 3: Route group with shared hooks
Route::group(['hooks' => ['auth']], function() {
    Route::get('/profile', 'User@profile');
    Route::get('/settings', 'User@settings');
    Route::post('/update-profile', 'User@updateProfile');
});

// Example 4: Nested groups (cumulative hooks)
Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'log']], function() {
    // These routes have: auth + log hooks
    Route::get('/dashboard', 'Admin@dashboard');

    // Nested group adds admin hook
    Route::group(['hooks' => ['admin']], function() {
        // These routes have: auth + log + admin hooks
        Route::get('/users', 'Admin@users');
        Route::post('/users/:id:/delete', 'Admin@deleteUser');
    });
});

// Example 5: API endpoints with CORS and rate limiting
Route::group(['prefix' => '/api', 'hooks' => ['cors', 'rate-limit', 'log']], function() {
    Route::get('/users', 'Api@users');
    Route::post('/users', 'Api@createUser');
    Route::get('/posts', 'Api@posts');
});

// Example 6: Strict rate limiting for sensitive endpoints
Route::post('/login', 'Auth@login')->hook('rate-limit-strict');
Route::post('/reset-password', 'Auth@resetPassword')->hook('rate-limit-strict');

// Example 7: Public API with specific CORS settings
Route::group(['prefix' => '/public-api', 'hooks' => ['cors-api']], function() {
    Route::get('/products', 'PublicApi@products');
    Route::get('/products/:id:', 'PublicApi@productDetail');
});
*/

// ============================================================================
// CREATING CUSTOM HOOK CLASSES
// ============================================================================

/*
Custom hook classes should implement a handle() method:

class MyCustomHook {
    public function handle($data, ...$params) {
        // Your logic here

        // Access route data:
        // $data['controller'] - Controller name
        // $data['method'] - Method name
        // $data['instance'] - Controller instance
        // $data['params'] - Route parameters

        // Modify data if needed
        $data['params']['custom_value'] = 'something';

        // Halt execution if needed (e.g., for redirects)
        if ($shouldHalt) {
            Hook::halt();
            exit;
        }

        // Always return $data
        return $data;
    }
}

// Register your custom hook
Hook::registerClass('my-hook', MyCustomHook::class, 'before_action_execute', 10, [
    'param1' => 'value1',
    'param2' => 'value2'
]);

// Use in routes
Route::get('/custom', 'Custom@action')->hook('my-hook');
*/
