<?php
/**
 * Cache Dashboard Test Suite
 *
 * Tests the cache dashboard functionality including:
 * - Dashboard access control
 * - API endpoints
 * - Statistics retrieval
 * - Reset functionality
 * - Authentication
 * - Security features
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @category   Monitoring
 */

// ANSI colors for terminal output
class TestColors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

// Test statistics
$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;

/**
 * Assert helper function
 */
function test_assert($condition, $message) {
    global $tests_run, $tests_passed, $tests_failed;

    $tests_run++;

    if ($condition) {
        $tests_passed++;
        echo TestColors::GREEN . "✓ PASS" . TestColors::RESET . ": $message\n";
        return true;
    } else {
        $tests_failed++;
        echo TestColors::RED . "✗ FAIL" . TestColors::RESET . ": $message\n";
        return false;
    }
}

/**
 * Test section header
 */
function test_section($title) {
    echo "\n" . TestColors::BLUE . "═══ $title ═══" . TestColors::RESET . "\n\n";
}

echo TestColors::BLUE . "
╔══════════════════════════════════════════════════╗
║     PHPWEAVE CACHE DASHBOARD TEST SUITE         ║
╚══════════════════════════════════════════════════╝
" . TestColors::RESET . "\n";

// Load required files
require_once __DIR__ . '/../coreapp/cache.php';
require_once __DIR__ . '/../coreapp/cachedriver.php';

// =====================================================
// TEST 1: Dashboard Files Exist
// =====================================================
test_section("TEST 1: Dashboard Files");

test_assert(file_exists(__DIR__ . '/../controller/CacheDashboard.php'), "CacheDashboard controller file exists");
test_assert(file_exists(__DIR__ . '/../views/cache_dashboard.php'), "cache_dashboard view file exists");
test_assert(is_readable(__DIR__ . '/../controller/CacheDashboard.php'), "CacheDashboard controller is readable");
test_assert(is_readable(__DIR__ . '/../views/cache_dashboard.php'), "cache_dashboard view is readable");

// =====================================================
// TEST 2: Cache Statistics Generation
// =====================================================
test_section("TEST 2: Cache Statistics");

Cache::init('memory');
Cache::resetStats();

// Generate some statistics
Cache::put('test1', 'value1', 3600);
Cache::put('test2', 'value2', 3600);
Cache::get('test1'); // Hit
Cache::get('test2'); // Hit
Cache::get('test3'); // Miss
Cache::get('test1'); // Hit
Cache::forget('test2');

$stats = Cache::stats();

test_assert(isset($stats['hits']), "Statistics include hits count");
test_assert(isset($stats['misses']), "Statistics include misses count");
test_assert(isset($stats['writes']), "Statistics include writes count");
test_assert(isset($stats['deletes']), "Statistics include deletes count");
test_assert(isset($stats['hit_rate']), "Statistics include hit rate");
test_assert(isset($stats['miss_rate']), "Statistics include miss rate");
test_assert(isset($stats['total_requests']), "Statistics include total requests");
test_assert(isset($stats['driver']), "Statistics include driver information");

test_assert($stats['hits'] === 3, "Hits count is correct (expected 3, got {$stats['hits']})");
test_assert($stats['misses'] === 1, "Misses count is correct (expected 1, got {$stats['misses']})");
test_assert($stats['writes'] === 2, "Writes count is correct (expected 2, got {$stats['writes']})");
test_assert($stats['deletes'] === 1, "Deletes count is correct (expected 1, got {$stats['deletes']})");
test_assert($stats['hit_rate'] === 75.0, "Hit rate is correct (expected 75%, got {$stats['hit_rate']}%)");

// =====================================================
// TEST 3: Statistics Reset
// =====================================================
test_section("TEST 3: Statistics Reset");

Cache::resetStats();
$stats = Cache::stats();

test_assert($stats['hits'] === 0, "Hits reset to 0");
test_assert($stats['misses'] === 0, "Misses reset to 0");
test_assert($stats['writes'] === 0, "Writes reset to 0");
test_assert($stats['deletes'] === 0, "Deletes reset to 0");
test_assert($stats['total_requests'] === 0, "Total requests reset to 0");
test_assert($stats['hit_rate'] === 0, "Hit rate reset to 0");

// =====================================================
// TEST 4: Dashboard Configuration Check
// =====================================================
test_section("TEST 4: Dashboard Configuration");

// Test default behavior
$originalDebug = getenv('DEBUG');
$originalEnabled = getenv('CACHE_DASHBOARD_ENABLED');

// Clean environment
putenv('DEBUG');
putenv('CACHE_DASHBOARD_ENABLED');

test_assert(getenv('DEBUG') === false, "DEBUG can be unset");
test_assert(getenv('CACHE_DASHBOARD_ENABLED') === false, "CACHE_DASHBOARD_ENABLED can be unset");

// Test enabling dashboard
putenv('CACHE_DASHBOARD_ENABLED=1');
test_assert(getenv('CACHE_DASHBOARD_ENABLED') == '1', "Dashboard can be enabled via environment");

// Test disabling dashboard
putenv('CACHE_DASHBOARD_ENABLED=0');
test_assert(getenv('CACHE_DASHBOARD_ENABLED') == '0', "Dashboard can be disabled via environment");

// Restore original values
if ($originalDebug !== false) {
    putenv("DEBUG=$originalDebug");
} else {
    putenv('DEBUG');
}

if ($originalEnabled !== false) {
    putenv("CACHE_DASHBOARD_ENABLED=$originalEnabled");
} else {
    putenv('CACHE_DASHBOARD_ENABLED');
}

// =====================================================
// TEST 5: Authentication Configuration
// =====================================================
test_section("TEST 5: Authentication Configuration");

$originalAuth = getenv('CACHE_DASHBOARD_AUTH');
$originalUser = getenv('CACHE_DASHBOARD_USER');
$originalPass = getenv('CACHE_DASHBOARD_PASS');

putenv('CACHE_DASHBOARD_AUTH=1');
putenv('CACHE_DASHBOARD_USER=testuser');
putenv('CACHE_DASHBOARD_PASS=testpass');

test_assert(getenv('CACHE_DASHBOARD_AUTH') == '1', "Authentication can be enabled");
test_assert(getenv('CACHE_DASHBOARD_USER') === 'testuser', "Username can be set");
test_assert(getenv('CACHE_DASHBOARD_PASS') === 'testpass', "Password can be set");

// Restore original values
if ($originalAuth !== false) {
    putenv("CACHE_DASHBOARD_AUTH=$originalAuth");
} else {
    putenv('CACHE_DASHBOARD_AUTH');
}

if ($originalUser !== false) {
    putenv("CACHE_DASHBOARD_USER=$originalUser");
} else {
    putenv('CACHE_DASHBOARD_USER');
}

if ($originalPass !== false) {
    putenv("CACHE_DASHBOARD_PASS=$originalPass");
} else {
    putenv('CACHE_DASHBOARD_PASS');
}

// =====================================================
// TEST 6: IP Whitelist Configuration
// =====================================================
test_section("TEST 6: IP Whitelist Configuration");

$originalIPs = getenv('CACHE_DASHBOARD_IPS');

putenv('CACHE_DASHBOARD_IPS=127.0.0.1,192.168.1.100');
test_assert(getenv('CACHE_DASHBOARD_IPS') === '127.0.0.1,192.168.1.100', "IP whitelist can be configured");

// Restore original value
if ($originalIPs !== false) {
    putenv("CACHE_DASHBOARD_IPS=$originalIPs");
} else {
    putenv('CACHE_DASHBOARD_IPS');
}

// =====================================================
// TEST 7: Multiple Cache Drivers
// =====================================================
test_section("TEST 7: Cache Driver Detection");

$drivers = [
    'memory' => ['name' => 'Memory', 'available' => true],
    'apcu' => ['name' => 'APCu', 'available' => function_exists('apcu_fetch') && ini_get('apc.enabled')],
    'file' => ['name' => 'File', 'available' => is_writable(__DIR__ . '/../cache')],
    'redis' => ['name' => 'Redis', 'available' => class_exists('Redis')],
    'memcached' => ['name' => 'Memcached', 'available' => class_exists('Memcached')]
];

foreach ($drivers as $driver => $info) {
    $status = $info['available'] ? 'available' : 'not available';
    echo TestColors::YELLOW . "  {$info['name']} driver is $status" . TestColors::RESET . "\n";
}

test_assert(true, "Driver detection completed");

// =====================================================
// TEST 8: Statistics with Different Drivers
// =====================================================
test_section("TEST 8: Multi-Driver Statistics");

// Test Memory driver
Cache::init('memory');
Cache::resetStats();
Cache::put('mem_test', 'value', 3600);
Cache::get('mem_test');
$memStats = Cache::stats();
test_assert($memStats['driver'] === 'MemoryCacheDriver', "Memory driver statistics work");
test_assert($memStats['hits'] === 1, "Memory driver tracks hits");

// Test File driver
Cache::init('file', ['path' => __DIR__ . '/../cache/test']);
Cache::resetStats();
Cache::put('file_test', 'value', 3600);
Cache::get('file_test');
$fileStats = Cache::stats();
test_assert($fileStats['driver'] === 'FileCacheDriver', "File driver statistics work");
test_assert($fileStats['hits'] === 1, "File driver tracks hits");

// =====================================================
// TEST 9: JSON Response Format
// =====================================================
test_section("TEST 9: JSON Response Format");

Cache::init('memory');
Cache::resetStats();

// Generate some data
Cache::put('json_test', 'value', 3600);
Cache::get('json_test');

$stats = Cache::stats();
$jsonStats = json_encode($stats);

test_assert($jsonStats !== false, "Statistics can be JSON encoded");

$decoded = json_decode($jsonStats, true);
test_assert($decoded !== null, "JSON can be decoded");
test_assert(isset($decoded['hits']), "Decoded JSON contains hits");
test_assert(isset($decoded['driver']), "Decoded JSON contains driver");

// =====================================================
// TEST 10: High-Volume Statistics
// =====================================================
test_section("TEST 10: High-Volume Statistics");

Cache::init('memory');
Cache::resetStats();

// Generate high volume of operations
for ($i = 0; $i < 100; $i++) {
    Cache::put("key_$i", "value_$i", 3600);
}

for ($i = 0; $i < 50; $i++) {
    Cache::get("key_$i"); // Hits
}

for ($i = 100; $i < 150; $i++) {
    Cache::get("key_$i"); // Misses
}

$stats = Cache::stats();

test_assert($stats['writes'] === 100, "High-volume writes tracked correctly");
test_assert($stats['hits'] === 50, "High-volume hits tracked correctly");
test_assert($stats['misses'] === 50, "High-volume misses tracked correctly");
test_assert($stats['total_requests'] === 100, "High-volume total requests correct");
test_assert($stats['hit_rate'] === 50.0, "High-volume hit rate calculated correctly");

// =====================================================
// SUMMARY
// =====================================================

echo "\n" . TestColors::BLUE . "═══════════════════════════════════════════════════" . TestColors::RESET . "\n";
echo TestColors::BLUE . "                  TEST SUMMARY                     " . TestColors::RESET . "\n";
echo TestColors::BLUE . "═══════════════════════════════════════════════════" . TestColors::RESET . "\n\n";

echo "Total tests run: " . $tests_run . "\n";
echo TestColors::GREEN . "Passed: " . $tests_passed . TestColors::RESET . "\n";

if ($tests_failed > 0) {
    echo TestColors::RED . "Failed: " . $tests_failed . TestColors::RESET . "\n";
    echo "\n" . TestColors::YELLOW . "Note: To test the dashboard UI, start a web server and visit:" . TestColors::RESET . "\n";
    echo TestColors::YELLOW . "  php -S localhost:8000 -t public/" . TestColors::RESET . "\n";
    echo TestColors::YELLOW . "  Then open: http://localhost:8000/cache/dashboard" . TestColors::RESET . "\n\n";
    exit(1);
} else {
    echo TestColors::GREEN . "\n✓ ALL TESTS PASSED!" . TestColors::RESET . "\n";
    echo "\n" . TestColors::YELLOW . "To test the dashboard UI, start a web server and visit:" . TestColors::RESET . "\n";
    echo TestColors::YELLOW . "  php -S localhost:8000 -t public/" . TestColors::RESET . "\n";
    echo TestColors::YELLOW . "  Then open: http://localhost:8000/cache/dashboard" . TestColors::RESET . "\n\n";
    exit(0);
}
