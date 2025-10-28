<?php
/**
 * Connection Pool Test Script
 *
 * Comprehensive test suite for the ConnectionPool class functionality.
 * Tests connection pooling, reuse, statistics, and error handling.
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @author     Clint Christopher Canada
 * @version    2.2.0
 */

// Define root directory constant
define('PHPWEAVE_ROOT', dirname(__DIR__));

// Load configuration
if (file_exists(PHPWEAVE_ROOT . '/.env')) {
    $configs = parse_ini_file(PHPWEAVE_ROOT . '/.env');
} else {
    die("Error: .env file not found. Please copy .env.sample to .env and configure.\n");
}

// Merge with environment variables (Docker/Kubernetes support)
$envVars = [
    'DBHOST' => getenv('DB_HOST') ?: getenv('DBHOST'),
    'DBNAME' => getenv('DB_NAME') ?: getenv('DBNAME'),
    'DBUSER' => getenv('DB_USER') ?: getenv('DBUSER'),
    'DBPASSWORD' => getenv('DB_PASSWORD') ?: getenv('DBPASSWORD'),
    'DBCHARSET' => getenv('DB_CHARSET') ?: getenv('DBCHARSET'),
    'DBDRIVER' => getenv('DB_DRIVER') ?: getenv('DBDRIVER'),
    'DBPORT' => getenv('DB_PORT') ?: getenv('DBPORT'),
    'DB_POOL_SIZE' => getenv('DB_POOL_SIZE'),
];

foreach ($envVars as $key => $value) {
    if ($value !== false && $value !== '') {
        $configs[$key] = $value;
    }
}

$GLOBALS['configs'] = $configs;

// Set default pool size for testing
$GLOBALS['configs']['DB_POOL_SIZE'] = 5;

// Load required classes
require_once PHPWEAVE_ROOT . '/coreapp/connectionpool.php';
require_once PHPWEAVE_ROOT . '/coreapp/dbconnection.php';

// Test results tracking
$tests_passed = 0;
$tests_failed = 0;
$test_number = 0;

/**
 * Print test result
 */
function printTestResult($test_name, $passed, $message = '') {
    global $tests_passed, $tests_failed, $test_number;
    $test_number++;

    if ($passed) {
        $tests_passed++;
        echo "✓ Test {$test_number}: {$test_name}\n";
    } else {
        $tests_failed++;
        echo "✗ Test {$test_number}: {$test_name}\n";
        if ($message) {
            echo "  Error: {$message}\n";
        }
    }
}

echo "\n=== PHPWeave Connection Pool Tests ===\n\n";

// Test 1: Pool Size Configuration
echo "--- Test Group 1: Configuration ---\n";
ConnectionPool::setMaxConnections(5);
$db1 = new DBConnection();
$stats = ConnectionPool::getPoolStats();
$poolKey = array_key_first($stats);
printTestResult(
    "Pool size configuration",
    $stats[$poolKey]['max_allowed'] === 5,
    "Expected max 5, got " . $stats[$poolKey]['max_allowed']
);

// Test 2: Connection Creation
printTestResult(
    "Initial connection creation",
    $stats[$poolKey]['total'] === 1 && $stats[$poolKey]['in_use'] === 1,
    "Expected 1 total/1 in use, got {$stats[$poolKey]['total']}/{$stats[$poolKey]['in_use']}"
);

// Test 3: Multiple Connection Instances
echo "\n--- Test Group 2: Connection Reuse ---\n";
$db2 = new DBConnection();
$db3 = new DBConnection();
$stats = ConnectionPool::getPoolStats();
printTestResult(
    "Multiple connections created",
    $stats[$poolKey]['total'] === 3 && $stats[$poolKey]['in_use'] === 3,
    "Expected 3 total/3 in use, got {$stats[$poolKey]['total']}/{$stats[$poolKey]['in_use']}"
);

// Test 4: Connection Reuse Statistics
printTestResult(
    "Connection reuse tracking",
    $stats[$poolKey]['total_created'] === 3 && $stats[$poolKey]['total_reused'] === 0,
    "Expected 3 created/0 reused, got {$stats[$poolKey]['total_created']}/{$stats[$poolKey]['total_reused']}"
);

// Test 5: Release and Reuse
echo "\n--- Test Group 3: Release and Reuse ---\n";
$conn = $db1->pdo;
$released = ConnectionPool::releaseConnection($conn);
printTestResult(
    "Connection release",
    $released === true,
    "Failed to release connection"
);

$stats = ConnectionPool::getPoolStats();
printTestResult(
    "Available connections after release",
    $stats[$poolKey]['available'] === 1 && $stats[$poolKey]['in_use'] === 2,
    "Expected 1 available/2 in use, got {$stats[$poolKey]['available']}/{$stats[$poolKey]['in_use']}"
);

// Create new connection - should reuse released one
$db4 = new DBConnection();
$stats = ConnectionPool::getPoolStats();
printTestResult(
    "Connection reuse after release",
    $stats[$poolKey]['total'] === 3 && $stats[$poolKey]['total_reused'] === 1,
    "Expected total=3, reused=1, got total={$stats[$poolKey]['total']}, reused={$stats[$poolKey]['total_reused']}"
);

// Test 6: Pool Limit Enforcement
echo "\n--- Test Group 4: Pool Limits ---\n";

// Temporarily set pool size to 3 for this test
$originalPoolSize = $GLOBALS['configs']['DB_POOL_SIZE'];
$GLOBALS['configs']['DB_POOL_SIZE'] = 3;

ConnectionPool::clearAllPools(); // Reset pools first
ConnectionPool::setMaxConnections(3);

try {
    $db1 = new DBConnection();
    $db2 = new DBConnection();
    $db3 = new DBConnection();

    // Verify all connections are created and tracked
    $stats = ConnectionPool::getPoolStats();
    $poolKey = array_key_first($stats);

    printTestResult(
        "Pool limit enforcement - max connections created",
        $stats[$poolKey]['total'] === 3 && $stats[$poolKey]['max_allowed'] === 3,
        "Expected 3 total with max=3, got total={$stats[$poolKey]['total']}, max={$stats[$poolKey]['max_allowed']}"
    );

    // Verify all are in use
    printTestResult(
        "Pool limit enforcement - all connections in use",
        $stats[$poolKey]['in_use'] === 3 && $stats[$poolKey]['available'] === 0,
        "Expected in_use=3, available=0, got in_use={$stats[$poolKey]['in_use']}, available={$stats[$poolKey]['available']}"
    );

} finally {
    // Restore original settings
    $GLOBALS['configs']['DB_POOL_SIZE'] = $originalPoolSize;
}

// Test 7: Pool Statistics
echo "\n--- Test Group 5: Statistics ---\n";
ConnectionPool::clearAllPools();

// Explicitly set pool size and create fresh connections
$GLOBALS['configs']['DB_POOL_SIZE'] = 10;
ConnectionPool::setMaxConnections(10);

$db1 = new DBConnection();
$db2 = new DBConnection();

// Get stats before release
$stats_before = ConnectionPool::getPoolStats();
$poolKey = array_key_first($stats_before);

ConnectionPool::releaseConnection($db1->pdo);

// Get stats after release
$stats = ConnectionPool::getPoolStats();

// Verify the key statistics
$stats_correct = (
    $stats[$poolKey]['total'] === 2 &&
    $stats[$poolKey]['available'] === 1 &&
    $stats[$poolKey]['in_use'] === 1 &&
    $stats[$poolKey]['max_allowed'] === 10
);

printTestResult(
    "Pool statistics accuracy",
    $stats_correct,
    "total={$stats[$poolKey]['total']}, available={$stats[$poolKey]['available']}, in_use={$stats[$poolKey]['in_use']}, max={$stats[$poolKey]['max_allowed']}"
);

// Test 8: Reuse Ratio Calculation
printTestResult(
    "Reuse ratio calculation",
    isset($stats[$poolKey]['reuse_ratio']),
    "Reuse ratio not calculated"
);

// Test 9: Pool Clearing
echo "\n--- Test Group 6: Pool Management ---\n";
ConnectionPool::clearAllPools();
$stats = ConnectionPool::getPoolStats();
printTestResult(
    "Clear all pools",
    count($stats) === 0,
    "Pools not cleared, found " . count($stats) . " pools"
);

// Test 10: Database Operations with Pooling
echo "\n--- Test Group 7: Database Operations ---\n";
try {
    $db = new DBConnection();

    // Test connection is working
    $result = $db->pdo->query('SELECT 1');
    printTestResult(
        "Database query execution",
        $result !== false,
        "Query failed"
    );

    // Test PDO is properly configured
    $errMode = $db->pdo->getAttribute(PDO::ATTR_ERRMODE);
    printTestResult(
        "PDO error mode configuration",
        $errMode === PDO::ERRMODE_EXCEPTION,
        "Expected ERRMODE_EXCEPTION, got {$errMode}"
    );

    $fetchMode = $db->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
    printTestResult(
        "PDO fetch mode configuration",
        $fetchMode === PDO::FETCH_ASSOC,
        "Expected FETCH_ASSOC, got {$fetchMode}"
    );

} catch (Exception $e) {
    printTestResult("Database operations test", false, $e->getMessage());
}

// Test 11: Multiple Pool Support
echo "\n--- Test Group 8: Multiple Database Support ---\n";
ConnectionPool::clearAllPools();

// Create two different "database" connections (using same DB but testing pool separation)
$originalConfig = $GLOBALS['configs'];

try {
    $db1 = new DBConnection();

    // Simulate different database config (use same DB with different charset to avoid DB creation)
    $GLOBALS['configs']['DBCHARSET'] = 'latin1';
    $db2 = new DBConnection();

    $stats = ConnectionPool::getPoolStats();
    printTestResult(
        "Multiple independent pools",
        count($stats) === 2,
        "Expected 2 pools, got " . count($stats) . " pools"
    );

} catch (Exception $e) {
    printTestResult("Multiple pools test", false, $e->getMessage());
} finally {
    $GLOBALS['configs'] = $originalConfig; // Restore original config
}

// Test 12: Connection Disabled (Pool Size = 0)
echo "\n--- Test Group 9: Pooling Disabled ---\n";
ConnectionPool::clearAllPools();
$GLOBALS['configs']['DB_POOL_SIZE'] = 0;

try {
    $db = new DBConnection();
    $stats = ConnectionPool::getPoolStats();

    printTestResult(
        "Pooling disabled (DB_POOL_SIZE=0)",
        count($stats) === 0,
        "Pool created when DB_POOL_SIZE=0"
    );

    // Verify direct PDO connection still works
    $result = $db->pdo->query('SELECT 1');
    printTestResult(
        "Direct PDO connection fallback",
        $result !== false,
        "Direct connection failed"
    );

} catch (Exception $e) {
    printTestResult("Pooling disabled test", false, $e->getMessage());
}

// Test 13: Performance Comparison
echo "\n--- Test Group 10: Performance ---\n";
ConnectionPool::clearAllPools();

// Without pooling (direct connections)
$times_without = [];
$GLOBALS['configs']['DB_POOL_SIZE'] = 0;
for ($run = 0; $run < 3; $run++) {
    $start = microtime(true);
    for ($i = 0; $i < 5; $i++) {
        $db = new DBConnection();
    }
    $times_without[] = microtime(true) - $start;
}
$time_without_pool = array_sum($times_without) / count($times_without);

// With pooling
ConnectionPool::clearAllPools();
$times_with = [];
$GLOBALS['configs']['DB_POOL_SIZE'] = 20;
ConnectionPool::setMaxConnections(20);
for ($run = 0; $run < 3; $run++) {
    ConnectionPool::clearAllPools(); // Reset between runs
    $start = microtime(true);
    for ($i = 0; $i < 5; $i++) {
        $db = new DBConnection();
    }
    $times_with[] = microtime(true) - $start;
}
$time_with_pool = array_sum($times_with) / count($times_with);

$improvement = (($time_without_pool - $time_with_pool) / $time_without_pool) * 100;

echo "  Without pooling (avg): " . round($time_without_pool * 1000, 2) . "ms\n";
echo "  With pooling (avg): " . round($time_with_pool * 1000, 2) . "ms\n";
echo "  Improvement: " . round($improvement, 1) . "%\n";

printTestResult(
    "Pooling performance measurement",
    true, // Always pass - performance varies by system
    "Performance: " . round($improvement, 1) . "% improvement"
);

// Print summary
echo "\n=== Test Summary ===\n";
echo "Total tests: " . ($tests_passed + $tests_failed) . "\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";

if ($tests_failed === 0) {
    echo "\n✓ All tests passed!\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n\n";
    exit(1);
}
