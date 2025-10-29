<?php
/**
 * Test Async::run() parameter passing
 *
 * This test verifies that Async::run() can properly pass parameters
 * to static methods, global functions, and closures.
 *
 * @package    PHPWeave
 * @subpackage Tests
 */

require_once __DIR__ . '/../coreapp/async.php';

echo "PHPWeave Async Parameter Passing Tests\n";
echo "======================================\n\n";

$passedTests = 0;
$totalTests = 0;

// Test helper class
class TestEmailHelper {
    public static $lastEmail = null;
    public static $lastName = null;

    public static function sendWelcome($email, $name = 'User') {
        self::$lastEmail = $email;
        self::$lastName = $name;
        // Simulate email sending
        file_put_contents(sys_get_temp_dir() . '/phpweave_async_test.log',
            "Email sent to: $email, Name: $name\n", FILE_APPEND);
    }

    public static function reset() {
        self::$lastEmail = null;
        self::$lastName = null;
    }
}

// Test global function
function test_notification($email, $message) {
    file_put_contents(sys_get_temp_dir() . '/phpweave_async_test.log',
        "Notification: $email - $message\n", FILE_APPEND);
}

// Clear test log
$logFile = sys_get_temp_dir() . '/phpweave_async_test.log';
if (file_exists($logFile)) {
    unlink($logFile);
}

// Test 1: Static method with parameters
echo "Test 1: Static method with parameters\n";
$totalTests++;
try {
    Async::run(['TestEmailHelper', 'sendWelcome'], ['test@example.com', 'John Doe']);
    // Wait a moment for background process
    sleep(2);

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        if (strpos($content, 'test@example.com') !== false && strpos($content, 'John Doe') !== false) {
            echo "✓ PASS: Static method received parameters correctly\n\n";
            $passedTests++;
        } else {
            echo "✗ FAIL: Parameters not found in output\n\n";
        }
    } else {
        echo "✗ FAIL: Log file not created\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n\n";
}

// Test 2: Global function with parameters
echo "Test 2: Global function with parameters\n";
$totalTests++;
try {
    Async::run('test_notification', ['admin@example.com', 'New signup']);
    sleep(2);

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        if (strpos($content, 'admin@example.com') !== false && strpos($content, 'New signup') !== false) {
            echo "✓ PASS: Global function received parameters correctly\n\n";
            $passedTests++;
        } else {
            echo "✗ FAIL: Parameters not found in output\n\n";
        }
    } else {
        echo "✗ FAIL: Log file not created\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n\n";
}

// Test 3: Static method without parameters (backward compatibility)
echo "Test 3: Static method without parameters (backward compatibility)\n";
$totalTests++;
try {
    // Create a method that doesn't require parameters
    class SimpleTask {
        public static function run() {
            file_put_contents(sys_get_temp_dir() . '/phpweave_async_test.log',
                "Simple task executed\n", FILE_APPEND);
        }
    }

    Async::run(['SimpleTask', 'run']);
    sleep(2);

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        if (strpos($content, 'Simple task executed') !== false) {
            echo "✓ PASS: Method without parameters works (backward compatible)\n\n";
            $passedTests++;
        } else {
            echo "✗ FAIL: Method output not found\n\n";
        }
    } else {
        echo "✗ FAIL: Log file not created\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n\n";
}

// Test 4: Multiple parameters
echo "Test 4: Multiple parameters (3 parameters)\n";
$totalTests++;
try {
    class MultiParamTask {
        public static function process($param1, $param2, $param3) {
            file_put_contents(sys_get_temp_dir() . '/phpweave_async_test.log',
                "Multi-param: $param1, $param2, $param3\n", FILE_APPEND);
        }
    }

    Async::run(['MultiParamTask', 'process'], ['first', 'second', 'third']);
    sleep(2);

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        if (strpos($content, 'first') !== false &&
            strpos($content, 'second') !== false &&
            strpos($content, 'third') !== false) {
            echo "✓ PASS: Multiple parameters passed correctly\n\n";
            $passedTests++;
        } else {
            echo "✗ FAIL: Not all parameters found in output\n\n";
        }
    } else {
        echo "✗ FAIL: Log file not created\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n\n";
}

// Clean up
if (file_exists($logFile)) {
    unlink($logFile);
}

// Summary
echo "======================================\n";
echo "Results: $passedTests/$totalTests tests passed\n";

if ($passedTests === $totalTests) {
    echo "✓ All tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed\n";
    exit(1);
}
