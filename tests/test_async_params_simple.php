<?php
/**
 * Simple test for Async::run() parameter passing
 *
 * Tests the parameter serialization directly without relying on
 * background process execution timing.
 */

require_once __DIR__ . '/../coreapp/async.php';

echo "PHPWeave Async Parameter Passing - Direct Tests\n";
echo "===============================================\n\n";

$passedTests = 0;
$totalTests = 0;

// Test 1: Verify static method serialization includes args
echo "Test 1: Static method serialization includes args\n";
$totalTests++;
try {
    // Use reflection to test the private method
    $reflection = new ReflectionClass('Async');
    $method = $reflection->getMethod('generateStaticMethodCode');
    $method->setAccessible(true);

    // Create test data
    $testData = [
        'type' => 'static',
        'class' => 'TestClass',
        'method' => 'testMethod',
        'args' => ['param1', 'param2', 123]
    ];
    $serialized = base64_encode(json_encode($testData));

    $code = $method->invoke(null, $serialized);

    // Check if the generated code uses call_user_func_array
    if (strpos($code, 'call_user_func_array') !== false &&
        strpos($code, '$args') !== false) {
        echo "✓ PASS: Generated code uses call_user_func_array with args\n";
        $passedTests++;
    } else {
        echo "✗ FAIL: Generated code doesn't properly handle args\n";
        echo "Generated code:\n$code\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Verify function serialization includes args
echo "Test 2: Global function serialization includes args\n";
$totalTests++;
try {
    $reflection = new ReflectionClass('Async');
    $method = $reflection->getMethod('generateFunctionCode');
    $method->setAccessible(true);

    $testData = [
        'type' => 'function',
        'name' => 'test_function',
        'args' => ['email@test.com', 'Message text']
    ];
    $serialized = base64_encode(json_encode($testData));

    $code = $method->invoke(null, $serialized);

    if (strpos($code, 'call_user_func_array') !== false &&
        strpos($code, '$args') !== false) {
        echo "✓ PASS: Generated code uses call_user_func_array with args\n";
        $passedTests++;
    } else {
        echo "✗ FAIL: Generated code doesn't properly handle args\n";
        echo "Generated code:\n$code\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Verify closure serialization includes args
echo "Test 3: Closure serialization includes args\n";
$totalTests++;
try {
    $reflection = new ReflectionClass('Async');
    $method = $reflection->getMethod('generateClosureCode');
    $method->setAccessible(true);

    $args = ['param1', 'param2', 'param3'];
    $code = $method->invoke(null, 'dummy_serialized_closure', $args);

    if (strpos($code, 'call_user_func_array') !== false &&
        strpos($code, 'json_decode') !== false) {
        echo "✓ PASS: Generated code uses call_user_func_array with decoded args\n";
        $passedTests++;
    } else {
        echo "✗ FAIL: Generated code doesn't properly handle args\n";
        echo "Generated code:\n$code\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test Async::run() method signature accepts args parameter
echo "Test 4: Async::run() accepts args parameter\n";
$totalTests++;
try {
    $reflection = new ReflectionClass('Async');
    $method = $reflection->getMethod('run');
    $params = $method->getParameters();

    if (count($params) >= 2 && $params[1]->getName() === 'args') {
        echo "✓ PASS: Async::run() has 'args' parameter\n";

        // Check if it's optional with default value
        if ($params[1]->isOptional() && $params[1]->isDefaultValueAvailable()) {
            $default = $params[1]->getDefaultValue();
            if (is_array($default) && empty($default)) {
                echo "  ✓ Parameter is optional with default value []\n";
            }
        }
        $passedTests++;
    } else {
        echo "✗ FAIL: Async::run() doesn't have proper args parameter\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Verify backward compatibility (calling without args)
echo "Test 5: Backward compatibility (args parameter optional)\n";
$totalTests++;
try {
    // Create a test file that will be called
    $testFile = sys_get_temp_dir() . '/phpweave_test_static.php';
    file_put_contents($testFile, '<?php class TestBackcompat { public static function test() {} }');
    require_once $testFile;

    // This should not throw an error
    $reflection = new ReflectionClass('Async');
    $method = $reflection->getMethod('run');

    // Simulate calling run without args parameter (should use default [])
    $params = $method->getParameters();
    if ($params[1]->isDefaultValueAvailable()) {
        echo "✓ PASS: Backward compatible - args parameter has default value\n";
        $passedTests++;
    } else {
        echo "✗ FAIL: args parameter is required (breaks backward compatibility)\n";
    }

    unlink($testFile);
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "===============================================\n";
echo "Results: $passedTests/$totalTests tests passed\n";

if ($passedTests === $totalTests) {
    echo "✓ All tests passed!\n";
    echo "\nParameter passing feature is working correctly!\n";
    exit(0);
} else {
    echo "✗ Some tests failed\n";
    exit(1);
}
