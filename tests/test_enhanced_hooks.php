<?php
/**
 * Enhanced Hooks System Test Suite
 *
 * Tests the new middleware-like hooks functionality:
 * - Class-based hooks
 * - Route-specific hooks
 * - Route groups
 * - Backward compatibility with callback-based hooks
 *
 * Usage: php tests/test_enhanced_hooks.php
 */

// Bootstrap
define('PHPWEAVE_ROOT', dirname(__DIR__));
require_once PHPWEAVE_ROOT . '/coreapp/hooks.php';
require_once PHPWEAVE_ROOT . '/coreapp/router.php';

// Load test hook classes
require_once __DIR__ . '/fixtures/TestAuthHook.php';
require_once __DIR__ . '/fixtures/TestLogHook.php';

// Test utilities
class TestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function test($name, $callback)
    {
        echo "\n🧪 Test: {$name}\n";

        try {
            $callback();
            $this->passed++;
            $this->tests[] = ['name' => $name, 'status' => 'PASS'];
            echo "✅ PASS\n";
        } catch (Exception $e) {
            $this->failed++;
            $this->tests[] = ['name' => $name, 'status' => 'FAIL', 'error' => $e->getMessage()];
            echo "❌ FAIL: " . $e->getMessage() . "\n";
        }
    }

    public function assert($condition, $message = "Assertion failed")
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public function assertEqual($expected, $actual, $message = "")
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true);
            throw new Exception($msg);
        }
    }

    public function summary()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo str_repeat("=", 60) . "\n";

        if ($this->failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->tests as $test) {
                if ($test['status'] === 'FAIL') {
                    echo "  - {$test['name']}: {$test['error']}\n";
                }
            }
        }

        return $this->failed === 0;
    }
}

$runner = new TestRunner();

// ============================================================================
// CLASS-BASED HOOKS TESTS
// ============================================================================

$runner->test("Register class-based hook", function() use ($runner) {
    Hook::clearAll();

    Hook::registerClass('test-auth', TestAuthHook::class);

    $runner->assert(Hook::hasNamed('test-auth'), "Named hook should be registered");
});

$runner->test("Register class-based hook with parameters", function() use ($runner) {
    Hook::clearAll();

    Hook::registerClass('test-log', TestLogHook::class, 'before_action_execute', 10, [
        'prefix' => 'TEST:'
    ]);

    $namedHooks = Hook::getNamedHooks();
    $runner->assert(isset($namedHooks['test-log']), "Named hook should exist");
    $runner->assertEqual(TestLogHook::class, $namedHooks['test-log']['class']);
    $runner->assertEqual(['prefix' => 'TEST:'], $namedHooks['test-log']['params']);
});

$runner->test("Trigger route-specific hook", function() use ($runner) {
    Hook::clearAll();

    // Reset TestLogHook state
    TestLogHook::$lastLog = null;

    // Register hook
    Hook::registerClass('test-log', TestLogHook::class, 'before_action_execute', 10, [
        'prefix' => 'ROUTE:'
    ]);

    // Attach to route
    Hook::attachToRoute('GET', '/test', 'test-log');

    // Trigger route hooks
    $data = [
        'controller' => 'Test',
        'method' => 'index',
        'params' => []
    ];

    $result = Hook::triggerRouteHooks('GET', '/test', $data);

    $runner->assert(TestLogHook::$lastLog !== null, "Hook should have been called");
    $runner->assert(str_contains(TestLogHook::$lastLog, 'ROUTE:'), "Hook should have received parameters");
    $runner->assert(str_contains(TestLogHook::$lastLog, 'Test@index'), "Hook should have received route data");
});

$runner->test("Multiple hooks on same route", function() use ($runner) {
    Hook::clearAll();
    TestAuthHook::$callCount = 0;
    TestLogHook::$callCount = 0;

    Hook::registerClass('test-auth', TestAuthHook::class);
    Hook::registerClass('test-log', TestLogHook::class);

    Hook::attachToRoute('GET', '/admin', ['test-auth', 'test-log']);

    $data = ['controller' => 'Admin', 'method' => 'index', 'params' => []];
    Hook::triggerRouteHooks('GET', '/admin', $data);

    $runner->assertEqual(1, TestAuthHook::$callCount, "Auth hook should be called once");
    $runner->assertEqual(1, TestLogHook::$callCount, "Log hook should be called once");
});

// ============================================================================
// ROUTE GROUPS TESTS
// ============================================================================

$runner->test("Route::group() applies hooks to all routes", function() use ($runner) {
    // Create a simple test by checking if group context is properly tracked
    // We can't fully test dispatch without HTTP context, but we can test route registration

    // Clear routes
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);

    // Register routes in a group
    Route::group(['hooks' => ['test-auth']], function() {
        Route::get('/profile', 'User@profile');
        Route::get('/settings', 'User@settings');
    });

    $routes = Router::getRoutes();

    $runner->assertEqual(2, count($routes), "Should have 2 routes");
    $runner->assertEqual(['test-auth'], $routes[0]['hooks'], "First route should have auth hook");
    $runner->assertEqual(['test-auth'], $routes[1]['hooks'], "Second route should have auth hook");
});

$runner->test("Route::group() with prefix", function() use ($runner) {
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);

    Route::group(['prefix' => '/admin', 'hooks' => ['test-auth']], function() {
        Route::get('/users', 'Admin@users');
        Route::get('/settings', 'Admin@settings');
    });

    $routes = Router::getRoutes();

    $runner->assertEqual('/admin/users', $routes[0]['pattern'], "Pattern should include prefix");
    $runner->assertEqual('/admin/settings', $routes[1]['pattern'], "Pattern should include prefix");
    $runner->assertEqual(['test-auth'], $routes[0]['hooks'], "Should have hooks");
});

$runner->test("Nested Route::group() with cumulative hooks", function() use ($runner) {
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);
    Hook::registerClass('test-log', TestLogHook::class);

    Route::group(['hooks' => ['test-log']], function() {
        Route::group(['prefix' => '/admin', 'hooks' => ['test-auth']], function() {
            Route::get('/users', 'Admin@users');
        });
    });

    $routes = Router::getRoutes();

    $runner->assertEqual('/admin/users', $routes[0]['pattern'], "Should have cumulative prefix");
    $runner->assertEqual(['test-log', 'test-auth'], $routes[0]['hooks'], "Should have cumulative hooks");
});

// ============================================================================
// ROUTE-SPECIFIC HOOKS WITH ->hook() METHOD
// ============================================================================

$runner->test("Route->hook() attaches hook to single route", function() use ($runner) {
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);

    Route::get('/profile', 'User@profile')->hook('test-auth');

    $routes = Router::getRoutes();

    $runner->assertEqual(['test-auth'], $routes[0]['hooks'], "Route should have auth hook");
});

$runner->test("Route->hook() with multiple hooks", function() use ($runner) {
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);
    Hook::registerClass('test-log', TestLogHook::class);

    Route::get('/admin', 'Admin@index')->hook(['test-auth', 'test-log']);

    $routes = Router::getRoutes();

    $runner->assertEqual(['test-auth', 'test-log'], $routes[0]['hooks'], "Route should have both hooks");
});

$runner->test("Route->hook() chaining", function() use ($runner) {
    $reflectionClass = new ReflectionClass('Router');
    $routesProperty = $reflectionClass->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routesProperty->setValue(null, []);

    $groupStackProperty = $reflectionClass->getProperty('groupStack');
    $groupStackProperty->setAccessible(true);
    $groupStackProperty->setValue(null, []);

    Hook::clearAll();
    Hook::registerClass('test-auth', TestAuthHook::class);
    Hook::registerClass('test-log', TestLogHook::class);

    Route::get('/admin', 'Admin@index')
        ->hook('test-auth')
        ->hook('test-log');

    $routes = Router::getRoutes();

    $runner->assertEqual(['test-auth', 'test-log'], $routes[0]['hooks'], "Chained hooks should be merged");
});

// ============================================================================
// BACKWARD COMPATIBILITY TESTS
// ============================================================================

$runner->test("Callback-based hooks still work", function() use ($runner) {
    Hook::clearAll();

    $called = false;

    Hook::register('test_hook', function($data) use (&$called) {
        $called = true;
        return $data;
    });

    Hook::trigger('test_hook');

    $runner->assert($called, "Callback hook should be called");
});

$runner->test("Callback and class-based hooks can coexist", function() use ($runner) {
    Hook::clearAll();
    TestLogHook::$callCount = 0;

    $callbackCalled = false;

    // Register callback-based hook
    Hook::register('before_action_execute', function($data) use (&$callbackCalled) {
        $callbackCalled = true;
        return $data;
    });

    // Register class-based hook
    Hook::registerClass('test-log', TestLogHook::class);
    Hook::attachToRoute('GET', '/test', 'test-log');

    // Trigger both
    Hook::trigger('before_action_execute', []);

    $data = [
        'controller' => 'Test',
        'method' => 'index',
        'instance' => null,
        'params' => []
    ];

    // Check that hook is registered
    $routeHooks = Hook::getRouteHooks('GET', '/test');
    if (empty($routeHooks)) {
        throw new Exception("Route hooks not registered. Debug: " . var_export($routeHooks, true));
    }

    Hook::triggerRouteHooks('GET', '/test', $data);

    $runner->assert($callbackCalled, "Callback hook should be called");
    $runner->assertEqual(1, TestLogHook::$callCount, "Class-based hook should be called. CallCount: " . TestLogHook::$callCount);
});

$runner->test("Hook::has() still works with callback hooks", function() use ($runner) {
    Hook::clearAll();

    Hook::register('test_hook', function($data) {
        return $data;
    });

    $runner->assert(Hook::has('test_hook'), "Hook::has() should detect callback hooks");
});

$runner->test("Hook::clear() still works", function() use ($runner) {
    Hook::clearAll();

    Hook::register('test_hook', function($data) {
        return $data;
    });

    Hook::clear('test_hook');

    $runner->assert(!Hook::has('test_hook'), "Hook::clear() should remove hooks");
});

// ============================================================================
// RESULTS
// ============================================================================

$success = $runner->summary();
exit($success ? 0 : 1);
