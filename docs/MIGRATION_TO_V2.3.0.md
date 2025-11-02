# Migration Guide: Upgrading to PHPWeave v2.3.0

This guide helps you upgrade from PHPWeave v2.2.x to v2.3.0, which introduces **middleware-style hooks** for cleaner, more maintainable code.

## Table of Contents

- [What's New in v2.3.0](#whats-new-in-v230)
- [Breaking Changes](#breaking-changes)
- [Backward Compatibility](#backward-compatibility)
- [Migration Steps](#migration-steps)
- [Migrating Hooks to Middleware Style](#migrating-hooks-to-middleware-style)
- [New Features to Adopt](#new-features-to-adopt)
- [Testing Your Migration](#testing-your-migration)

## What's New in v2.3.0

### Middleware-Style Hooks

PHPWeave v2.3.0 introduces three powerful new hook paradigms:

1. **Class-Based Hooks** - Reusable, testable hook classes
2. **Route-Specific Hooks** - Attach hooks to individual routes with `->hook()`
3. **Route Groups** - Apply hooks to multiple routes with `Route::group()`

### Key Benefits

- ✅ **Cleaner code** - Explicit route protection
- ✅ **Better performance** - Hooks only run when needed
- ✅ **More maintainable** - Class-based hooks are testable
- ✅ **100% backward compatible** - All existing hooks still work

### Built-in Hook Classes

Five production-ready hook classes included:
- `AuthHook` - Authentication with redirect
- `AdminHook` - Admin authorization with 403
- `LogHook` - Request logging
- `RateLimitHook` - Rate limiting
- `CorsHook` - CORS headers

## Breaking Changes

**None!** Version 2.3.0 is 100% backward compatible with v2.2.x.

All existing callback-based hooks continue to work exactly as before. You can migrate incrementally.

## Backward Compatibility

### What Still Works

✅ All callback-based hooks (`Hook::register()`)
✅ All 18 hook points
✅ Hook priority system
✅ `Hook::halt()` and `Hook::trigger()`
✅ All utility methods (`has()`, `count()`, `clear()`)
✅ Existing hook files in `hooks/` directory

### What's New

- `Hook::registerClass()` - Register class-based hooks
- `Hook::attachToRoute()` - Attach hooks to routes (automatic)
- `Hook::triggerRouteHooks()` - Trigger route hooks (automatic)
- `Route::group()` - Route grouping
- `->hook()` - Route method chaining

## Migration Steps

### Step 1: Update Files

Replace these core files from v2.3.0:

- `coreapp/hooks.php` - Enhanced with middleware support
- `coreapp/router.php` - Added Route::group() and ->hook()

**Backup first!**

```bash
# Backup your files
cp coreapp/hooks.php coreapp/hooks.php.backup
cp coreapp/router.php coreapp/router.php.backup

# Copy new files from v2.3.0
# (download from GitHub or pull from git)
```

### Step 2: Copy Hook Classes (Optional)

If you want to use built-in hook classes:

```bash
# Create directory
mkdir -p hooks/classes

# Copy hook classes
cp path/to/v2.3.0/hooks/classes/*.php hooks/classes/

# Copy example registration file
cp path/to/v2.3.0/hooks/example_class_based_hooks.php hooks/
```

### Step 3: Test Existing Functionality

Run your application and verify all existing hooks still work:

```bash
# Run existing hooks tests (if you have them)
php tests/test_hooks.php

# Check your application
# Visit all protected routes
# Test authentication, logging, etc.
```

### Step 4: Incrementally Adopt New Features

You can adopt middleware-style hooks gradually. See examples below.

## Migrating Hooks to Middleware Style

### Example 1: Authentication Hook

**Before (v2.2.x - Callback Style):**

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

**After (v2.3.0 - Middleware Style):**

```php
// hooks/example_class_based_hooks.php

// Load hook class
require_once __DIR__ . '/classes/AuthHook.php';

// Register hook
Hook::registerClass('auth', AuthHook::class);
```

```php
// routes/routes.php

// Public routes (no hooks)
Route::get('/', 'Home@index');
Route::get('/login', 'Auth@login');
Route::post('/login', 'Auth@authenticate');

// Protected routes (with auth hook)
Route::get('/profile', 'User@profile')->hook('auth');
Route::get('/settings', 'User@settings')->hook('auth');

// Or use route groups
Route::group(['hooks' => ['auth']], function() {
    Route::get('/profile', 'User@profile');
    Route::get('/settings', 'User@settings');
    Route::post('/update-profile', 'User@updateProfile');
});
```

**Benefits:**
- ✅ Clear which routes are protected
- ✅ No need to maintain publicRoutes list
- ✅ Easier to see at a glance
- ✅ Better performance (auth only runs on protected routes)

### Example 2: Logging Hook

**Before (v2.2.x):**

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

**After (v2.3.0):**

```php
// hooks/example_class_based_hooks.php

Hook::registerClass('log', LogHook::class);
```

```php
// routes/routes.php

// Log specific routes
Route::get('/api/users', 'Api@users')->hook('log');

// Or log entire API
Route::group(['prefix' => '/api', 'hooks' => ['log']], function() {
    Route::get('/users', 'Api@users');
    Route::post('/users', 'Api@createUser');
    Route::get('/posts', 'Api@posts');
});
```

**Benefits:**
- ✅ Only log what you need
- ✅ No logging overhead on other routes
- ✅ Easy to enable/disable per route

### Example 3: Admin-Only Routes

**Before (v2.2.x):**

```php
// hooks/authorization.php

Hook::register('before_action_execute', function($data) {
    $adminRoutes = ['Admin@users', 'Admin@settings', 'Admin@deleteUser'];
    $handler = $data['controller'] . '@' . $data['method'];

    if (in_array($handler, $adminRoutes)) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('HTTP/1.0 403 Forbidden');
            echo "403 - Forbidden: Admin access required";
            Hook::halt();
            exit;
        }
    }

    return $data;
}, 6);
```

**After (v2.3.0):**

```php
// hooks/example_class_based_hooks.php

Hook::registerClass('auth', AuthHook::class);
Hook::registerClass('admin', AdminHook::class);
```

```php
// routes/routes.php

Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin', 'log']], function() {
    Route::get('/dashboard', 'Admin@dashboard');
    Route::get('/users', 'Admin@users');
    Route::post('/users/:id:/delete', 'Admin@deleteUser');
    Route::get('/settings', 'Admin@settings');
});
```

**Benefits:**
- ✅ No need to maintain adminRoutes list
- ✅ Clear that these routes require auth + admin
- ✅ Easy to add new admin routes
- ✅ URL prefix automatically applied

### Example 4: API with CORS and Rate Limiting

**Before (v2.2.x):**

```php
// hooks/cors.php

Hook::register('framework_start', function($data) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    return $data;
}, 5);

// hooks/rate_limiting.php
// (complex session-based rate limiting code here)
```

**After (v2.3.0):**

```php
// hooks/example_class_based_hooks.php

Hook::registerClass('cors', CorsHook::class);
Hook::registerClass('rate-limit', RateLimitHook::class, 'before_action_execute', 5, [
    'max' => 100,
    'window' => 60
]);
```

```php
// routes/routes.php

Route::group(['prefix' => '/api', 'hooks' => ['cors', 'rate-limit']], function() {
    Route::get('/users', 'Api@users');
    Route::post('/users', 'Api@createUser');
    Route::get('/posts', 'Api@posts');
    Route::get('/posts/:id:', 'Api@showPost');
});
```

**Benefits:**
- ✅ Cleaner, more maintainable
- ✅ Built-in rate limiting (no custom code needed)
- ✅ CORS only on API routes
- ✅ Easy to adjust rate limits

## New Features to Adopt

### 1. Route Groups

Use route groups to organize related routes:

```php
// Admin section
Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin']], function() {
    Route::get('/dashboard', 'Admin@dashboard');
    Route::get('/users', 'Admin@users');
});

// User section
Route::group(['hooks' => ['auth']], function() {
    Route::get('/profile', 'User@profile');
    Route::get('/settings', 'User@settings');
});

// API section
Route::group(['prefix' => '/api', 'hooks' => ['cors', 'rate-limit']], function() {
    Route::get('/users', 'Api@users');
    Route::post('/users', 'Api@createUser');
});
```

### 2. Route-Specific Hooks

Attach hooks to individual routes:

```php
// Single hook
Route::get('/profile', 'User@profile')->hook('auth');

// Multiple hooks
Route::get('/admin/dashboard', 'Admin@dashboard')->hook(['auth', 'admin', 'log']);

// Chained hooks
Route::post('/api/users', 'Api@createUser')
    ->hook('auth')
    ->hook('rate-limit')
    ->hook('log');
```

### 3. Custom Hook Classes

Create your own reusable hook classes:

```php
// hooks/classes/CustomHook.php

class CustomHook {
    public function handle($data, $param1 = null, $param2 = null) {
        // Your custom logic here

        // Access route data
        $controller = $data['controller'];
        $method = $data['method'];
        $params = $data['params'];

        // Modify data if needed
        $data['params']['custom'] = 'value';

        // Halt execution if needed
        if ($shouldHalt) {
            Hook::halt();
            exit;
        }

        return $data;
    }
}

// Register
Hook::registerClass('custom', CustomHook::class, 'before_action_execute', 10, [
    $param1,
    $param2
]);

// Use
Route::get('/custom', 'Custom@action')->hook('custom');
```

### 4. Nested Route Groups

Groups can be nested for hierarchical organization:

```php
Route::group(['prefix' => '/api', 'hooks' => ['cors']], function() {
    // Public API (cors only)
    Route::get('/products', 'Api@products');

    // Protected API (cors + auth)
    Route::group(['hooks' => ['auth']], function() {
        Route::get('/orders', 'Api@orders');

        // Admin API (cors + auth + admin)
        Route::group(['hooks' => ['admin']], function() {
            Route::get('/admin/users', 'Api@adminUsers');
        });
    });
});
```

## Testing Your Migration

### 1. Run Existing Tests

```bash
# Run existing hooks tests
php tests/test_hooks.php

# Should show: All Tests Completed!
```

### 2. Run New Middleware Tests

```bash
# Run middleware-style hooks tests
php tests/test_enhanced_hooks.php

# Should show:
# Total: 14
# ✅ Passed: 14
# ❌ Failed: 0
```

### 3. Manual Testing Checklist

- [ ] Test authentication (login/logout)
- [ ] Test protected routes (should redirect if not logged in)
- [ ] Test admin routes (should return 403 if not admin)
- [ ] Test API routes (CORS headers present)
- [ ] Test rate limiting (trigger limit and verify 429 response)
- [ ] Test logging (check logs for request entries)
- [ ] Test all existing callback hooks still work

### 4. Performance Testing

Middleware-style hooks should be faster (only run when needed):

```bash
# Benchmark before and after
php tests/benchmark_optimizations.php

# Routes with hooks should be slightly faster
# Routes without hooks should be significantly faster
```

## Troubleshooting

### Issue: Hooks not triggering

**Cause:** Hook class not loaded or not registered.

**Solution:**
```php
// Make sure hooks are loaded
require_once __DIR__ . '/classes/AuthHook.php';

// Make sure hooks are registered
Hook::registerClass('auth', AuthHook::class);

// Check if registered
if (Hook::hasNamed('auth')) {
    echo "Auth hook registered";
} else {
    echo "Auth hook NOT registered!";
}
```

### Issue: 404 on routes with groups

**Cause:** Incorrect prefix or pattern.

**Solution:**
```php
// Verify route pattern
$routes = Router::getRoutes();
print_r($routes);

// Check for leading/trailing slashes
// PHPWeave normalizes these automatically
Route::group(['prefix' => '/admin'], function() {
    // This becomes /admin/users (correct)
    Route::get('/users', 'Admin@users');
});
```

### Issue: Multiple hooks executing in wrong order

**Cause:** Priority not set correctly.

**Solution:**
```php
// Lower priority runs first
Hook::registerClass('auth', AuthHook::class, 'before_action_execute', 5);  // Runs first
Hook::registerClass('log', LogHook::class, 'before_action_execute', 10);   // Runs second
Hook::registerClass('other', OtherHook::class, 'before_action_execute', 20); // Runs third
```

### Issue: Existing callback hooks not working

**Cause:** This shouldn't happen - v2.3.0 is backward compatible.

**Solution:**
```bash
# Verify you're using the correct hooks.php
php -r "require 'coreapp/hooks.php'; var_dump(method_exists('Hook', 'registerClass'));"
# Should output: bool(true)

# Test callback hooks
php tests/test_hooks.php
# All tests should pass
```

## Getting Help

- **Documentation:** See `docs/HOOKS.md` for complete hook documentation
- **Examples:** Check `hooks/example_class_based_hooks.php` for usage examples
- **Tests:** See `tests/test_enhanced_hooks.php` for test examples
- **GitHub Issues:** https://github.com/anthropics/phpweave/issues

## Summary

PHPWeave v2.3.0 brings middleware-style hooks that make your code:

- ✅ **Cleaner** - Explicit route protection
- ✅ **Faster** - Hooks only run when needed
- ✅ **More maintainable** - Reusable, testable classes
- ✅ **100% backward compatible** - Migrate at your own pace

You can continue using callback hooks, adopt middleware-style hooks incrementally, or mix both approaches!

**Happy coding with PHPWeave v2.3.0!** 🚀
