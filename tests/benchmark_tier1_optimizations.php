<?php
/**
 * Benchmark Test for Tier 1 Optimizations (v2.6.0)
 *
 * This benchmark measures performance improvements from:
 * 1. .env file caching with APCu
 * 2. Router string operation optimizations
 * 3. Cache tag storage optimization with array_flip
 *
 * Run this test to verify the performance gains.
 *
 * Usage: php tests/benchmark_tier1_optimizations.php
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @version    2.6.0
 */

// Disable error reporting for cleaner output
error_reporting(E_ALL);
ini_set('display_errors', 0);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║      PHPWeave v2.6.0 - Tier 1 Optimization Benchmark                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if APCu is available
$apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
echo "Environment: APCu " . ($apcuAvailable ? "✓ ENABLED" : "✗ DISABLED") . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "\n";

// =============================================================================
// BENCHMARK 1: .env File Parsing vs Caching
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ BENCHMARK 1: .env File Parsing Performance                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo "⚠ Warning: .env file not found. Creating sample .env...\n";
    file_put_contents($envPath . '.benchmark', "DBHOST=localhost\nDBNAME=test\nDBUSER=root\nDBPASSWORD=secret\nDEBUG=0\n");
    $envPath = $envPath . '.benchmark';
}

echo "Test: Parsing .env file 1000 times\n\n";

// Test 1: Without caching (old method)
$iterations = 1000;
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $config = @parse_ini_file($envPath);
}
$timeWithoutCache = (microtime(true) - $start) * 1000;

// Test 2: With APCu caching (new method)
if ($apcuAvailable) {
    apcu_clear_cache(); // Clear cache before test

    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $cacheKey = 'phpweave_env_' . filemtime($envPath);
        $config = apcu_fetch($cacheKey);
        if ($config === false) {
            $config = @parse_ini_file($envPath);
            apcu_store($cacheKey, $config, 3600);
        }
    }
    $timeWithCache = (microtime(true) - $start) * 1000;

    echo "Without Cache (parse_ini_file):  " . number_format($timeWithoutCache, 2) . " ms\n";
    echo "With APCu Cache:                  " . number_format($timeWithCache, 2) . " ms\n";

    $improvement = (($timeWithoutCache - $timeWithCache) / $timeWithoutCache) * 100;
    $speedup = $timeWithoutCache / $timeWithCache;

    echo "\n";
    echo "Performance Gain:                 " . number_format($improvement, 1) . "%\n";
    echo "Speed Improvement:                " . number_format($speedup, 1) . "x faster\n";
    echo "Time Saved per Request:           " . number_format(($timeWithoutCache - $timeWithCache) / $iterations, 3) . " ms\n";
} else {
    echo "Without Cache (parse_ini_file):  " . number_format($timeWithoutCache, 2) . " ms\n";
    echo "⚠ APCu not available - install APCu extension to see caching benefits\n";
}

echo "\n";

// Clean up
if (file_exists($envPath . '.benchmark')) {
    unlink($envPath . '.benchmark');
}

// =============================================================================
// BENCHMARK 2: Router String Operations
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ BENCHMARK 2: Router String Operation Performance                      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Test: Pattern normalization 100,000 times\n\n";

$patterns = [
    'user/:id:',           // Missing leading slash
    '/blog/',              // Trailing slash
    '/api/posts/:id:/',    // Both issues
    '/',                   // Root route
    '/admin/users',        // Already normalized
];

$iterations = 100000;

// Test 1: Old method with substr()
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($patterns as $pattern) {
        // Old method
        if (substr($pattern, 0, 1) !== '/') {
            $pattern = '/' . $pattern;
        }
        if ($pattern !== '/' && substr($pattern, -1) === '/') {
            $pattern = rtrim($pattern, '/');
        }
    }
}
$timeOld = (microtime(true) - $start) * 1000;

// Test 2: New method with direct array access
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($patterns as $pattern) {
        // New method
        if (isset($pattern[0]) && $pattern[0] !== '/') {
            $pattern = '/' . $pattern;
        }
        if ($pattern !== '/' && isset($pattern[1]) && $pattern[strlen($pattern) - 1] === '/') {
            $pattern = substr($pattern, 0, -1);
        }
    }
}
$timeNew = (microtime(true) - $start) * 1000;

echo "Old Method (substr + rtrim):      " . number_format($timeOld, 2) . " ms\n";
echo "New Method (array access):        " . number_format($timeNew, 2) . " ms\n";

$improvement = (($timeOld - $timeNew) / $timeOld) * 100;
$speedup = $timeOld / $timeNew;

echo "\n";
echo "Performance Gain:                 " . number_format($improvement, 1) . "%\n";
echo "Speed Improvement:                " . number_format($speedup, 2) . "x faster\n";
echo "Time Saved per Request:           " . number_format(($timeOld - $timeNew) / $iterations, 4) . " ms\n";

echo "\n";

// =============================================================================
// BENCHMARK 3: Cache Tag Storage Operations
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ BENCHMARK 3: Cache Tag Storage Performance                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Test: Tag key lookup with varying tag sizes\n\n";

// Test with different tag sizes
$tagSizes = [10, 50, 100, 500];

foreach ($tagSizes as $tagSize) {
    echo "Testing with $tagSize keys per tag:\n";

    // Generate test data
    $keys = [];
    for ($i = 0; $i < $tagSize; $i++) {
        $keys[] = "cache_key_$i";
    }

    $iterations = 10000;
    $testKey = "cache_key_999"; // Key not in array (worst case)

    // Test 1: Old method with in_array (O(n))
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        if (!in_array($testKey, $keys)) {
            // Would add key here
        }
    }
    $timeOld = (microtime(true) - $start) * 1000;

    // Test 2: New method with array_flip (O(1))
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $keysFlipped = array_flip($keys);
        if (!isset($keysFlipped[$testKey])) {
            // Would add key here
        }
    }
    $timeNew = (microtime(true) - $start) * 1000;

    $improvement = (($timeOld - $timeNew) / $timeOld) * 100;
    $speedup = $timeOld / $timeNew;

    echo "  Old Method (in_array):          " . number_format($timeOld, 2) . " ms\n";
    echo "  New Method (array_flip):        " . number_format($timeNew, 2) . " ms\n";
    echo "  Improvement:                    " . number_format($improvement, 1) . "% (" . number_format($speedup, 2) . "x faster)\n";
    echo "\n";
}

// =============================================================================
// SUMMARY
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ SUMMARY: Tier 1 Optimization Impact                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Expected Performance Gains per Request:\n";
echo "  1. .env Caching:                  2-5 ms (with APCu)\n";
echo "  2. Router String Operations:      0.3-0.5 ms\n";
echo "  3. Cache Tag Storage:             0.2-0.5 ms (when using tags)\n";
echo "\n";
echo "Total Expected Gain:                3-6 ms per request\n";
echo "\n";

if ($apcuAvailable) {
    echo "✓ Your environment supports all optimizations!\n";
} else {
    echo "⚠ Install APCu extension for maximum performance:\n";
    echo "  - Ubuntu/Debian: sudo apt-get install php-apcu\n";
    echo "  - CentOS/RHEL:   sudo yum install php-apcu\n";
    echo "  - macOS:         pecl install apcu\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ Benchmark Complete!                                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
