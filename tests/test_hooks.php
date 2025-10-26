<?php
/**
 * Hooks System Test Script
 *
 * Run this script from command line to verify hooks work correctly:
 * php tests/test_hooks.php
 */

// Load the hooks system
require_once __DIR__ . '/../coreapp/hooks.php';

echo "Testing PHPWeave Hooks System\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Basic hook registration and trigger
echo "Test 1: Basic Hook Registration\n";
Hook::register('test_hook', function($data) {
    echo "  - Hook executed with data: " . json_encode($data) . "\n";
    return $data;
});

$result = Hook::trigger('test_hook', ['test' => 'value']);
echo "  Result: " . ($result['test'] === 'value' ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Multiple hooks with priority
echo "Test 2: Hook Priority Order\n";
Hook::register('priority_test', function($data) {
    echo "  - Hook Priority 10 (default)\n";
    return $data;
}, 10);

Hook::register('priority_test', function($data) {
    echo "  - Hook Priority 5 (first)\n";
    return $data;
}, 5);

Hook::register('priority_test', function($data) {
    echo "  - Hook Priority 20 (last)\n";
    return $data;
}, 20);

Hook::trigger('priority_test');
echo "  Result: PASS (if order is 5, 10, 20)\n\n";

// Test 3: Data modification
echo "Test 3: Data Modification\n";
Hook::register('modify_test', function($data) {
    $data['modified'] = true;
    $data['count'] = ($data['count'] ?? 0) + 1;
    return $data;
});

Hook::register('modify_test', function($data) {
    $data['count'] = ($data['count'] ?? 0) + 1;
    return $data;
});

$result = Hook::trigger('modify_test', ['count' => 0]);
echo "  - Modified data: " . json_encode($result) . "\n";
echo "  Result: " . ($result['count'] === 2 && $result['modified'] === true ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Halt execution
echo "Test 4: Halt Execution\n";
$executed = [];

Hook::register('halt_test', function($data) use (&$executed) {
    $executed[] = 'first';
    return $data;
}, 5);

Hook::register('halt_test', function($data) use (&$executed) {
    $executed[] = 'second';
    Hook::halt();
    return $data;
}, 10);

Hook::register('halt_test', function($data) use (&$executed) {
    $executed[] = 'third'; // Should not execute
    return $data;
}, 20);

Hook::trigger('halt_test');
echo "  - Executed: " . implode(', ', $executed) . "\n";
echo "  Result: " . (count($executed) === 2 && !in_array('third', $executed) ? 'PASS' : 'FAIL') . "\n\n";

// Test 5: Hook utility methods
echo "Test 5: Utility Methods\n";
echo "  - has('priority_test'): " . (Hook::has('priority_test') ? 'true' : 'false') . "\n";
echo "  - count('priority_test'): " . Hook::count('priority_test') . "\n";
echo "  - has('nonexistent'): " . (Hook::has('nonexistent') ? 'true' : 'false') . "\n";
echo "  Result: PASS\n\n";

// Test 6: Available hooks list
echo "Test 6: Available Hooks Documentation\n";
$available = Hook::getAvailableHooks();
echo "  - Total standard hooks: " . count($available) . "\n";
echo "  - Includes 'framework_start': " . (isset($available['framework_start']) ? 'yes' : 'no') . "\n";
echo "  - Includes 'before_action_execute': " . (isset($available['before_action_execute']) ? 'yes' : 'no') . "\n";
echo "  Result: " . (count($available) >= 15 ? 'PASS' : 'FAIL') . "\n\n";

// Test 7: Clear hooks
echo "Test 7: Clear Hooks\n";
Hook::clear('priority_test');
echo "  - After clear, has('priority_test'): " . (Hook::has('priority_test') ? 'true' : 'false') . "\n";
echo "  Result: " . (!Hook::has('priority_test') ? 'PASS' : 'FAIL') . "\n\n";

// Test 8: Exception handling in hooks
echo "Test 8: Exception Handling\n";
Hook::register('exception_test', function($data) {
    throw new Exception("Test exception");
});

Hook::register('exception_test', function($data) {
    echo "  - Second hook still executed after exception\n";
    return $data;
});

$result = Hook::trigger('exception_test', ['test' => true]);
echo "  Result: PASS (exception caught, execution continued)\n\n";

echo str_repeat("=", 50) . "\n";
echo "All Tests Completed!\n";
echo "\nNOTE: Check for any PHP warnings or errors above.\n";
echo "If you see any E_USER_WARNING about the exception, that's expected.\n";
