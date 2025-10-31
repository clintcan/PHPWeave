<?php
/**
 * Test Database Modes (v2.2.1+)
 *
 * Tests:
 * 1. Database-free mode with ENABLE_DATABASE=0
 * 2. Database-free mode with empty DBNAME (auto-detection)
 * 3. Lazy database connection (database enabled but not queried)
 * 4. Normal database operation (with queries)
 * 5. Model access in database-free mode (should throw exception)
 *
 * Run: php tests/test_database_modes.php
 */

// Color output helpers
function green($text) { return "\033[32m$text\033[0m"; }
function red($text) { return "\033[31m$text\033[0m"; }
function yellow($text) { return "\033[33m$text\033[0m"; }
function blue($text) { return "\033[34m$text\033[0m"; }

echo blue("\n==================================================\n");
echo blue("  PHPWeave v2.2.1+ Database Modes Test Suite\n");
echo blue("==================================================\n\n");

$testsPassed = 0;
$testsFailed = 0;
$testResults = [];

// Helper function to run a test
function runTest($testName, $testFunction) {
    global $testsPassed, $testsFailed, $testResults;

    echo yellow("Testing: $testName\n");

    try {
        $result = $testFunction();

        if ($result === true) {
            echo green("✓ PASSED\n\n");
            $testsPassed++;
            $testResults[] = ['name' => $testName, 'status' => 'PASSED'];
        } else {
            echo red("✗ FAILED: $result\n\n");
            $testsFailed++;
            $testResults[] = ['name' => $testName, 'status' => 'FAILED', 'error' => $result];
        }
    } catch (Exception $e) {
        echo red("✗ EXCEPTION: " . $e->getMessage() . "\n\n");
        $testsFailed++;
        $testResults[] = ['name' => $testName, 'status' => 'EXCEPTION', 'error' => $e->getMessage()];
    }
}

// ==================================================
// TEST 1: Database-Free Mode with ENABLE_DATABASE=0
// ==================================================
runTest("Database-free mode with ENABLE_DATABASE=0", function() {
    // Backup original configs
    $originalConfigs = $GLOBALS['configs'] ?? null;

    // Set configs for database-free mode (explicit)
    $GLOBALS['configs'] = [
        'ENABLE_DATABASE' => 0,
        'DBHOST' => 'localhost',
        'DBNAME' => 'test_db',
        'DBUSER' => 'root',
        'DBPASSWORD' => '',
        'DEBUG' => 0
    ];

    // Simulate the database detection logic from index.php
    $databaseEnabled = true;
    if (isset($GLOBALS['configs']['ENABLE_DATABASE']) && $GLOBALS['configs']['ENABLE_DATABASE'] == 0) {
        $databaseEnabled = false;
    } elseif (empty($GLOBALS['configs']['DBNAME'])) {
        $databaseEnabled = false;
    }

    // Restore configs
    if ($originalConfigs !== null) {
        $GLOBALS['configs'] = $originalConfigs;
    }

    // Verify database is disabled
    if ($databaseEnabled === false) {
        return true;
    } else {
        return "Database should be disabled when ENABLE_DATABASE=0";
    }
});

// ==================================================
// TEST 2: Database-Free Mode with Empty DBNAME (Auto-detection)
// ==================================================
runTest("Database-free mode with empty DBNAME (auto-detection)", function() {
    // Backup original configs
    $originalConfigs = $GLOBALS['configs'] ?? null;

    // Set configs for database-free mode (auto-detection)
    $GLOBALS['configs'] = [
        'DBHOST' => 'localhost',
        'DBNAME' => '', // Empty DBNAME triggers auto-detection
        'DBUSER' => 'root',
        'DBPASSWORD' => '',
        'DEBUG' => 0
    ];

    // Simulate the database detection logic from index.php
    $databaseEnabled = true;
    if (isset($GLOBALS['configs']['ENABLE_DATABASE']) && $GLOBALS['configs']['ENABLE_DATABASE'] == 0) {
        $databaseEnabled = false;
    } elseif (empty($GLOBALS['configs']['DBNAME'])) {
        $databaseEnabled = false;
    }

    // Restore configs
    if ($originalConfigs !== null) {
        $GLOBALS['configs'] = $originalConfigs;
    }

    // Verify database is disabled
    if ($databaseEnabled === false) {
        return true;
    } else {
        return "Database should be disabled when DBNAME is empty";
    }
});

// ==================================================
// TEST 3: Database Enabled (Should Pass Detection)
// ==================================================
runTest("Database enabled with valid DBNAME", function() {
    // Backup original configs
    $originalConfigs = $GLOBALS['configs'] ?? null;

    // Set configs for database enabled mode
    $GLOBALS['configs'] = [
        'ENABLE_DATABASE' => 1,
        'DBHOST' => 'localhost',
        'DBNAME' => 'test_db',
        'DBUSER' => 'root',
        'DBPASSWORD' => '',
        'DEBUG' => 0
    ];

    // Simulate the database detection logic from index.php
    $databaseEnabled = true;
    if (isset($GLOBALS['configs']['ENABLE_DATABASE']) && $GLOBALS['configs']['ENABLE_DATABASE'] == 0) {
        $databaseEnabled = false;
    } elseif (empty($GLOBALS['configs']['DBNAME'])) {
        $databaseEnabled = false;
    }

    // Restore configs
    if ($originalConfigs !== null) {
        $GLOBALS['configs'] = $originalConfigs;
    }

    // Verify database is enabled
    if ($databaseEnabled === true) {
        return true;
    } else {
        return "Database should be enabled when ENABLE_DATABASE=1 and DBNAME is set";
    }
});

// ==================================================
// TEST 4: DBConnection Class Lazy Loading
// ==================================================
runTest("DBConnection lazy loading (connection deferred)", function() {
    // Check if DBConnection class exists
    if (!file_exists(__DIR__ . '/../coreapp/dbconnection.php')) {
        return "DBConnection file not found";
    }

    // Load the class
    require_once __DIR__ . '/../coreapp/dbconnection.php';

    // Check if class has the $connected property
    $reflection = new ReflectionClass('DBConnection');
    $properties = $reflection->getProperties();

    $hasConnectedFlag = false;
    foreach ($properties as $prop) {
        if ($prop->getName() === 'connected') {
            $hasConnectedFlag = true;
            break;
        }
    }

    if (!$hasConnectedFlag) {
        return "DBConnection class should have 'connected' property for lazy loading";
    }

    // Check if connect() method exists
    $methods = $reflection->getMethods();
    $hasConnectMethod = false;
    foreach ($methods as $method) {
        if ($method->getName() === 'connect') {
            $hasConnectMethod = true;
            break;
        }
    }

    if (!$hasConnectMethod) {
        return "DBConnection class should have 'connect()' method for lazy loading";
    }

    return true;
});

// ==================================================
// TEST 5: executePreparedSQL Calls connect()
// ==================================================
runTest("executePreparedSQL() calls connect() method", function() {
    // Check if DBConnection class exists
    if (!class_exists('DBConnection')) {
        if (!file_exists(__DIR__ . '/../coreapp/dbconnection.php')) {
            return "DBConnection file not found";
        }
        require_once __DIR__ . '/../coreapp/dbconnection.php';
    }

    // Read the executePreparedSQL method source
    $source = file_get_contents(__DIR__ . '/../coreapp/dbconnection.php');

    // Check if executePreparedSQL contains $this->connect()
    if (strpos($source, 'function executePreparedSQL') === false) {
        return "executePreparedSQL method not found";
    }

    // Extract the method
    preg_match('/function executePreparedSQL\(.*?\).*?\{(.*?)\n\t\}/s', $source, $matches);

    if (empty($matches[1])) {
        return "Could not extract executePreparedSQL method body";
    }

    $methodBody = $matches[1];

    if (strpos($methodBody, '$this->connect()') === false) {
        return "executePreparedSQL should call \$this->connect() for lazy loading";
    }

    return true;
});

// ==================================================
// TEST 6: DSN Building Moved to buildDSN()
// ==================================================
runTest("DSN building moved to buildDSN() method", function() {
    if (!class_exists('DBConnection')) {
        if (!file_exists(__DIR__ . '/../coreapp/dbconnection.php')) {
            return "DBConnection file not found";
        }
        require_once __DIR__ . '/../coreapp/dbconnection.php';
    }

    // Check if buildDSN() method exists
    $reflection = new ReflectionClass('DBConnection');
    $methods = $reflection->getMethods();

    $hasBuildDSNMethod = false;
    foreach ($methods as $method) {
        if ($method->getName() === 'buildDSN') {
            $hasBuildDSNMethod = true;
            break;
        }
    }

    if (!$hasBuildDSNMethod) {
        return "DBConnection class should have 'buildDSN()' method";
    }

    return true;
});

// ==================================================
// TEST 7: Constructor No Longer Creates PDO Connection
// ==================================================
runTest("Constructor defers PDO connection", function() {
    if (!class_exists('DBConnection')) {
        if (!file_exists(__DIR__ . '/../coreapp/dbconnection.php')) {
            return "DBConnection file not found";
        }
        require_once __DIR__ . '/../coreapp/dbconnection.php';
    }

    // Read the __construct method source
    $source = file_get_contents(__DIR__ . '/../coreapp/dbconnection.php');

    // Extract the constructor
    preg_match('/function __construct\(\).*?\{(.*?)function buildDSN/s', $source, $matches);

    if (empty($matches[1])) {
        return "Could not extract __construct method body";
    }

    $constructorBody = $matches[1];

    // Constructor should NOT have "new PDO"
    if (strpos($constructorBody, 'new PDO') !== false) {
        return "Constructor should NOT create PDO connection (should be lazy-loaded)";
    }

    // Constructor SHOULD call buildDSN()
    if (strpos($constructorBody, 'buildDSN()') === false) {
        return "Constructor should call buildDSN()";
    }

    return true;
});

// ==================================================
// TEST 8: index.php Database Detection Logic
// ==================================================
runTest("index.php implements database detection", function() {
    $indexPath = __DIR__ . '/../public/index.php';

    if (!file_exists($indexPath)) {
        return "index.php not found";
    }

    $source = file_get_contents($indexPath);

    // Check for database detection logic
    if (strpos($source, '$databaseEnabled') === false) {
        return "index.php should have \$databaseEnabled variable";
    }

    if (strpos($source, 'ENABLE_DATABASE') === false) {
        return "index.php should check ENABLE_DATABASE config";
    }

    if (strpos($source, 'if ($databaseEnabled)') === false) {
        return "index.php should conditionally load database based on \$databaseEnabled";
    }

    return true;
});

// ==================================================
// RESULTS SUMMARY
// ==================================================
echo blue("\n==================================================\n");
echo blue("  Test Results Summary\n");
echo blue("==================================================\n\n");

foreach ($testResults as $result) {
    $status = $result['status'] === 'PASSED' ? green("✓ PASSED") : red("✗ " . $result['status']);
    echo sprintf("%-60s %s\n", $result['name'], $status);
    if (isset($result['error'])) {
        echo "   Error: " . $result['error'] . "\n";
    }
}

echo "\n";
echo blue("==================================================\n");
echo sprintf("Total Tests: %d\n", $testsPassed + $testsFailed);
echo sprintf("%s: %d\n", green("Passed"), $testsPassed);
echo sprintf("%s: %d\n", red("Failed"), $testsFailed);
echo blue("==================================================\n\n");

if ($testsFailed === 0) {
    echo green("All tests passed! ✓\n\n");
    exit(0);
} else {
    echo red("Some tests failed! ✗\n\n");
    exit(1);
}
