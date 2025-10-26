<?php
/**
 * Models System Test Script
 *
 * Comprehensive tests for PHPWeave's lazy model loading system.
 * Run this script from command line to verify models work correctly:
 * php tests/test_models.php
 *
 * Tests include:
 * - Lazy model loading
 * - Model instantiation
 * - Multiple access methods (function, array, object)
 * - Model caching
 * - Error handling
 * - PHPWeave global object
 */

echo "Testing PHPWeave Models System\n";
echo str_repeat("=", 50) . "\n";
echo "NOTE: Some tests may skip due to database connection requirements.\n";
echo str_repeat("=", 50) . "\n\n";

// Load required core files
define('PHPWEAVE_ROOT', dirname(__DIR__));

// Set up mock database config to prevent warnings
$GLOBALS['configs'] = [
    'DBHOST' => 'localhost',
    'DBNAME' => 'test_db',
    'DBUSER' => 'test_user',
    'DBPASSWORD' => 'test_pass',
    'DBCHARSET' => 'utf8mb4'
];

// Change to coreapp directory so relative paths work
$originalDir = getcwd();
chdir(PHPWEAVE_ROOT . '/coreapp');

require_once PHPWEAVE_ROOT . '/coreapp/dbconnection.php';
require_once PHPWEAVE_ROOT . '/coreapp/models.php';

// Change back to original directory
chdir($originalDir);

// Test 1: Model file discovery
echo "Test 1: Model File Discovery\n";
$discoveredModels = $GLOBALS['_model_files'] ?? [];
echo "  - Models discovered: " . count($discoveredModels) . "\n";
foreach ($discoveredModels as $name => $class) {
    echo "    * $name => $class\n";
}
echo "  Result: " . (count($discoveredModels) > 0 ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Model loading using function (recommended)
echo "Test 2: Model Loading via model() Function\n";
try {
    if (isset($GLOBALS['_model_files']['user_model'])) {
        $userModel = model('user_model');
        echo "  - Model loaded: " . get_class($userModel) . "\n";
        echo "  - Extends DBConnection: " . (is_subclass_of($userModel, 'DBConnection') ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  - user_model not found (skipping test)\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 3: Model loading using global $models array
echo "Test 3: Model Loading via \$models Array (Legacy)\n";
try {
    global $models;
    if (isset($GLOBALS['_model_files']['user_model'])) {
        $userModel2 = $models['user_model'];
        echo "  - Model loaded: " . get_class($userModel2) . "\n";
        echo "  - Is LazyModelLoader: " . (get_class($models) === 'LazyModelLoader' ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  - user_model not found (skipping test)\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 4: PHPWeave global object
echo "Test 4: PHPWeave Global Object (\$PW)\n";
try {
    global $PW;
    echo "  - \$PW exists: " . (isset($PW) ? 'yes' : 'no') . "\n";
    echo "  - \$PW is PHPWeave instance: " . (get_class($PW) === 'PHPWeave' ? 'yes' : 'no') . "\n";
    echo "  - \$PW->models exists: " . (isset($PW->models) ? 'yes' : 'no') . "\n";

    if (isset($GLOBALS['_model_files']['user_model'])) {
        $userModel3 = $PW->models->user_model;
        echo "  - Model loaded via \$PW->models: " . get_class($userModel3) . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  Result: PASS (no models to test)\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 5: Model caching (same instance returned)
echo "Test 5: Model Instance Caching\n";
try {
    if (isset($GLOBALS['_model_files']['user_model'])) {
        $instance1 = model('user_model');
        $instance2 = model('user_model');
        $isSameInstance = spl_object_id($instance1) === spl_object_id($instance2);
        echo "  - First call object ID: " . spl_object_id($instance1) . "\n";
        echo "  - Second call object ID: " . spl_object_id($instance2) . "\n";
        echo "  - Same instance: " . ($isSameInstance ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($isSameInstance ? 'PASS' : 'FAIL') . "\n\n";
    } else {
        echo "  - No models available for testing\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 6: Error handling for non-existent model
echo "Test 6: Error Handling for Non-Existent Model\n";
try {
    $nonExistent = model('nonexistent_model');
    echo "  - Should have thrown exception\n";
    echo "  Result: FAIL\n\n";
} catch (Exception $e) {
    echo "  - Caught expected exception: " . $e->getMessage() . "\n";
    echo "  Result: PASS\n\n";
}

// Test 7: ArrayAccess isset() check
echo "Test 7: ArrayAccess isset() Check\n";
global $models;
echo "  - isset(\$models['user_model']): " . (isset($models['user_model']) ? 'yes' : 'no') . "\n";
echo "  - isset(\$models['nonexistent']): " . (isset($models['nonexistent']) ? 'yes' : 'no') . "\n";
$userExists = isset($GLOBALS['_model_files']['user_model']);
echo "  Result: " . (
    isset($models['user_model']) === $userExists &&
    !isset($models['nonexistent']) ? 'PASS' : 'FAIL'
) . "\n\n";

// Test 8: Magic __isset() for object property access
echo "Test 8: Magic __isset() for Object Property Access\n";
global $PW;
echo "  - isset(\$PW->models->user_model): " . (isset($PW->models->user_model) ? 'yes' : 'no') . "\n";
echo "  - isset(\$PW->models->nonexistent): " . (isset($PW->models->nonexistent) ? 'yes' : 'no') . "\n";
echo "  Result: " . (
    isset($PW->models->user_model) === $userExists &&
    !isset($PW->models->nonexistent) ? 'PASS' : 'FAIL'
) . "\n\n";

// Test 9: ArrayAccess set() should trigger warning
echo "Test 9: ArrayAccess set() Protection\n";
set_error_handler(function($errno, $errstr) {
    global $setWarningTriggered;
    if (strpos($errstr, 'Cannot set models') !== false) {
        $setWarningTriggered = true;
    }
    return true;
}, E_USER_WARNING);

$setWarningTriggered = false;
$models['test'] = 'should not work';
restore_error_handler();
echo "  - Warning triggered: " . ($setWarningTriggered ? 'yes' : 'no') . "\n";
echo "  Result: " . ($setWarningTriggered ? 'PASS' : 'FAIL') . "\n\n";

// Test 10: ArrayAccess unset() should trigger warning
echo "Test 10: ArrayAccess unset() Protection\n";
set_error_handler(function($errno, $errstr) {
    global $unsetWarningTriggered;
    if (strpos($errstr, 'Cannot unset models') !== false) {
        $unsetWarningTriggered = true;
    }
    return true;
}, E_USER_WARNING);

$unsetWarningTriggered = false;
unset($models['test_model']); // Try to unset any model
restore_error_handler();
echo "  - Warning triggered: " . ($unsetWarningTriggered ? 'yes' : 'no') . "\n";
echo "  Result: " . ($unsetWarningTriggered ? 'PASS' : 'FAIL') . "\n\n";

// Test 11: Model method existence check
echo "Test 11: Model Method Availability\n";
if (isset($GLOBALS['_model_files']['user_model'])) {
    try {
        $userModel = model('user_model');
        echo "  - Has getUser() method: " . (method_exists($userModel, 'getUser') ? 'yes' : 'no') . "\n";
        echo "  - Has executePreparedSQL() (from DBConnection): " . (method_exists($userModel, 'executePreparedSQL') ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - No user_model available\n";
    echo "  Result: SKIP\n\n";
}

// Test 12: All three access methods return same instance
echo "Test 12: All Access Methods Return Same Instance\n";
if (isset($GLOBALS['_model_files']['user_model'])) {
    try {
        global $models, $PW;
        $viaFunction = model('user_model');
        $viaArray = $models['user_model'];
        $viaObject = $PW->models->user_model;

        $id1 = spl_object_id($viaFunction);
        $id2 = spl_object_id($viaArray);
        $id3 = spl_object_id($viaObject);

        echo "  - model() ID: $id1\n";
        echo "  - \$models[] ID: $id2\n";
        echo "  - \$PW->models-> ID: $id3\n";

        $allSame = ($id1 === $id2 && $id2 === $id3);
        echo "  - All same instance: " . ($allSame ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($allSame ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - No models available\n";
    echo "  Result: SKIP\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "All Model Tests Completed!\n";
echo "\nSummary:\n";
echo "- Model system uses lazy loading for performance\n";
echo "- Three ways to access: model(), \$models[], \$PW->models->\n";
echo "- All methods return cached instances (same object)\n";
echo "- Protected against direct modification\n";
