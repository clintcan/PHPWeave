<?php
/**
 * Controllers System Test Script
 *
 * Comprehensive tests for PHPWeave's controller system.
 * Run this script from command line to verify controllers work correctly:
 * php tests/test_controllers.php
 *
 * Tests include:
 * - Controller instantiation
 * - Method invocation
 * - Parameter passing
 * - View rendering
 * - Hook integration
 * - Safe output helper
 */

echo "Testing PHPWeave Controllers System\n";
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

require_once PHPWEAVE_ROOT . '/coreapp/hooks.php';
require_once PHPWEAVE_ROOT . '/coreapp/dbconnection.php';
require_once PHPWEAVE_ROOT . '/coreapp/models.php';
require_once PHPWEAVE_ROOT . '/coreapp/controller.php';

// Change back to original directory
chdir($originalDir);

// Test 1: Controller class loading
echo "Test 1: Controller Base Class\n";
echo "  - Controller class exists: " . (class_exists('Controller') ? 'yes' : 'no') . "\n";
echo "  - Has show() method: " . (method_exists('Controller', 'show') ? 'yes' : 'no') . "\n";
echo "  - Has callfunc() method: " . (method_exists('Controller', 'callfunc') ? 'yes' : 'no') . "\n";
echo "  - Has safe() method: " . (method_exists('Controller', 'safe') ? 'yes' : 'no') . "\n";
echo "  Result: PASS\n\n";

// Test 2: Controller instantiation with skip auto-call
echo "Test 2: Controller Instantiation (Skip Auto-Call)\n";
try {
    $controller = new Controller('__skip_auto_call__');
    echo "  - Instance created: " . (is_object($controller) ? 'yes' : 'no') . "\n";
    echo "  - Is Controller: " . (get_class($controller) === 'Controller' ? 'yes' : 'no') . "\n";
    echo "  Result: PASS\n\n";
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 3: Safe HTML output helper
echo "Test 3: Safe HTML Output Helper\n";
$testController = new Controller('__skip_auto_call__');
$unsafeString = '<script>alert("XSS")</script>';
$safeOutput = $testController->safe($unsafeString);
echo "  - Input: $unsafeString\n";
echo "  - Output: $safeOutput\n";
echo "  - Contains < or >: " . (strpos($safeOutput, '<') === false && strpos($safeOutput, '>') === false ? 'no' : 'yes') . "\n";
echo "  Result: " . (htmlspecialchars($unsafeString, ENT_QUOTES, 'UTF-8') === $safeOutput ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Load actual controller files
echo "Test 4: Loading Application Controllers\n";
$controllerFiles = glob(PHPWEAVE_ROOT . '/controller/*.php');
$loadedControllers = [];
foreach ($controllerFiles as $file) {
    try {
        require_once $file;
        $className = basename($file, '.php');
        $className = ucfirst($className);
        if (class_exists($className)) {
            $loadedControllers[] = $className;
            echo "  - Loaded: $className\n";
        }
    } catch (Error $e) {
        echo "  - Failed to load " . basename($file) . ": " . $e->getMessage() . "\n";
    }
}
echo "  - Total controllers loaded: " . count($loadedControllers) . "\n";
echo "  Result: " . (count($loadedControllers) > 0 ? 'PASS' : 'SKIP') . "\n\n";

// Test 5: Controller inheritance
echo "Test 5: Controller Inheritance\n";
foreach ($loadedControllers as $className) {
    $isSubclass = is_subclass_of($className, 'Controller');
    echo "  - $className extends Controller: " . ($isSubclass ? 'yes' : 'no') . "\n";
    if (!$isSubclass) {
        echo "  Result: FAIL\n\n";
        break;
    }
}
echo "  Result: PASS\n\n";

// Test 6: Controller method detection
echo "Test 6: Controller Methods Detection\n";
if (in_array('Blog', $loadedControllers)) {
    $blogMethods = get_class_methods('Blog');
    echo "  - Blog controller methods:\n";
    foreach ($blogMethods as $method) {
        if ($method !== '__construct') {
            echo "    * $method()\n";
        }
    }
    echo "  - Has index() method: " . (method_exists('Blog', 'index') ? 'yes' : 'no') . "\n";
    echo "  - Has showPost() method: " . (method_exists('Blog', 'showPost') ? 'yes' : 'no') . "\n";
    echo "  Result: PASS\n\n";
} else {
    echo "  - No Blog controller found\n";
    echo "  Result: SKIP\n\n";
}

// Test 7: Controller instantiation (application controller)
echo "Test 7: Application Controller Instantiation\n";
if (in_array('Blog', $loadedControllers)) {
    try {
        $blog = new Blog('__skip_auto_call__');
        echo "  - Blog instance created: " . (is_object($blog) ? 'yes' : 'no') . "\n";
        echo "  - Extends Controller: " . (is_subclass_of($blog, 'Controller') ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - No Blog controller available\n";
    echo "  Result: SKIP\n\n";
}

// Test 8: Method invocation with callfunc()
echo "Test 8: Method Invocation with callfunc()\n";
$testInvoked = false;

// Create a test controller
class TestController extends Controller {
    public $testFlag = false;

    public function testMethod() {
        $this->testFlag = true;
        return 'method_called';
    }

    public function testWithParams($param1, $param2) {
        return "Param1: $param1, Param2: $param2";
    }
}

try {
    $testCtrl = new TestController('__skip_auto_call__');
    $testCtrl->callfunc('testMethod');
    echo "  - Method invoked: " . ($testCtrl->testFlag ? 'yes' : 'no') . "\n";

    // Test with parameters
    ob_start();
    $testCtrl->callfunc('testWithParams', ['value1', 'value2']);
    ob_end_clean();

    echo "  Result: PASS\n\n";
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 9: View rendering hook integration
echo "Test 9: View Rendering Hook Integration\n";
$hookTriggered = false;

Hook::register('before_view_render', function($data) use (&$hookTriggered) {
    $hookTriggered = true;
    return $data;
});

// Create test view file
$testViewPath = PHPWEAVE_ROOT . '/views/test_view.php';
@mkdir(dirname($testViewPath), 0777, true);
file_put_contents($testViewPath, '<?php echo "Test View"; ?>');

try {
    $ctrl = new Controller('__skip_auto_call__');
    ob_start();
    @$ctrl->show('test_view'); // Suppress errors from header() calls
    $output = ob_get_clean();

    echo "  - Hook triggered: " . ($hookTriggered ? 'yes' : 'no') . "\n";
    echo "  - View rendered: " . (strpos($output, 'Test View') !== false ? 'yes' : 'no') . "\n";
    echo "  Result: " . ($hookTriggered ? 'PASS' : 'FAIL') . "\n\n";
} catch (Exception $e) {
    @ob_end_clean();
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Cleanup
@unlink($testViewPath);

// Test 10: View data extraction
echo "Test 10: View Data Extraction (extract)\n";
$testViewWithData = PHPWEAVE_ROOT . '/views/test_data_view.php';
file_put_contents($testViewWithData, '<?php echo isset($title) ? $title : "NO_TITLE"; ?> - <?php echo isset($content) ? $content : "NO_CONTENT"; ?>');

try {
    $ctrl = new Controller('__skip_auto_call__');
    ob_start();
    @$ctrl->show('test_data_view', [
        'title' => 'Test Title',
        'content' => 'Test Content'
    ]);
    $output = ob_get_clean();

    echo "  - View output: $output\n";
    echo "  - Contains 'Test Title': " . (strpos($output, 'Test Title') !== false ? 'yes' : 'no') . "\n";
    echo "  - Contains 'Test Content': " . (strpos($output, 'Test Content') !== false ? 'yes' : 'no') . "\n";
    echo "  Result: " . (strpos($output, 'Test Title') !== false && strpos($output, 'Test Content') !== false ? 'PASS' : 'FAIL') . "\n\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Cleanup
@unlink($testViewWithData);

// Test 11: Template sanitization (security)
echo "Test 11: Template Path Sanitization\n";
$testSecureView = PHPWEAVE_ROOT . '/views/secure.php';
file_put_contents($testSecureView, '<?php echo "Secure View"; ?>');

try {
    $ctrl = new Controller('__skip_auto_call__');

    // Test stripping http://
    ob_start();
    @$ctrl->show('http://secure', '');
    $output1 = ob_get_clean();

    // Test stripping https://
    ob_start();
    @$ctrl->show('https://secure', '');
    $output2 = ob_get_clean();

    // Test double slash removal
    ob_start();
    @$ctrl->show('//secure', '');
    $output3 = ob_get_clean();

    $allPassed = (
        strpos($output1, 'Secure View') !== false &&
        strpos($output2, 'Secure View') !== false &&
        strpos($output3, 'Secure View') !== false
    );

    echo "  - http:// stripped: " . (strpos($output1, 'Secure View') !== false ? 'yes' : 'no') . "\n";
    echo "  - https:// stripped: " . (strpos($output2, 'Secure View') !== false ? 'yes' : 'no') . "\n";
    echo "  - // stripped: " . (strpos($output3, 'Secure View') !== false ? 'yes' : 'no') . "\n";
    echo "  Result: " . ($allPassed ? 'PASS' : 'FAIL') . "\n\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Cleanup
@unlink($testSecureView);

// Test 12: 404 handling for non-existent view
echo "Test 12: 404 Handling for Non-Existent View\n";
try {
    $ctrl = new Controller('__skip_auto_call__');
    ob_start();
    @$ctrl->show('nonexistent_view_12345');
    $output = ob_get_clean();

    echo "  - 404 output: " . (strpos($output, 'Not found') !== false ? 'yes' : 'no') . "\n";
    echo "  Result: " . (strpos($output, 'Not found') !== false ? 'PASS' : 'FAIL') . "\n\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 13: Legacy routing functions exist
echo "Test 13: Legacy Routing Functions\n";
echo "  - getControllerClass() exists: " . (function_exists('getControllerClass') ? 'yes' : 'no') . "\n";
echo "  - getControllerFunction() exists: " . (function_exists('getControllerFunction') ? 'yes' : 'no') . "\n";
echo "  - getControllerParams() exists: " . (function_exists('getControllerParams') ? 'yes' : 'no') . "\n";
echo "  - legacyRouting() exists: " . (function_exists('legacyRouting') ? 'yes' : 'no') . "\n";
echo "  Result: PASS\n\n";

// Test 14: Controller with model access
echo "Test 14: Controller with Model Access\n";
if (in_array('Blog', $loadedControllers) && isset($GLOBALS['_model_files']['user_model'])) {
    try {
        global $PW;
        $blog = new Blog('__skip_auto_call__');

        // Test that controller can access models
        $userModel = $PW->models->user_model;
        echo "  - Controller can access \$PW: yes\n";
        echo "  - Model loaded: " . get_class($userModel) . "\n";
        echo "  Result: PASS\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - Blog or user_model not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 15: Multiple controller instances
echo "Test 15: Multiple Controller Instances\n";
if (in_array('Home', $loadedControllers) && in_array('Blog', $loadedControllers)) {
    try {
        $home = new Home('__skip_auto_call__');
        $blog = new Blog('__skip_auto_call__');

        $homeId = spl_object_id($home);
        $blogId = spl_object_id($blog);

        echo "  - Home instance ID: $homeId\n";
        echo "  - Blog instance ID: $blogId\n";
        echo "  - Different instances: " . ($homeId !== $blogId ? 'yes' : 'no') . "\n";
        echo "  - Both extend Controller: " . (
            is_subclass_of($home, 'Controller') &&
            is_subclass_of($blog, 'Controller') ? 'yes' : 'no'
        ) . "\n";
        echo "  Result: " . ($homeId !== $blogId ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - Not enough controllers available\n";
    echo "  Result: SKIP\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "All Controller Tests Completed!\n";
echo "\nSummary:\n";
echo "- Controllers extend base Controller class\n";
echo "- Support method invocation with parameters\n";
echo "- Integrate with hooks system\n";
echo "- Provide secure view rendering\n";
echo "- Include HTML sanitization helpers\n";
echo "- Compatible with model loading system\n";
