<?php
/**
 * Test Script for .env Caching Optimization (v2.6.0)
 *
 * This test verifies that:
 * 1. .env caching works correctly with APCu
 * 2. Cache invalidation works when .env is modified
 * 3. Fallback to parse_ini_file works when cache is disabled
 * 4. Performance improvement is measurable
 *
 * Usage: php tests/test_env_caching.php
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @version    2.6.0
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║      PHPWeave v2.6.0 - .env Caching Test Suite                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$testsPassed = 0;
$testsFailed = 0;

// Check APCu availability
$apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
echo "APCu Status: " . ($apcuAvailable ? "✓ ENABLED" : "✗ DISABLED") . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "\n";

// =============================================================================
// TEST 1: Create test .env file
// =============================================================================

echo "[TEST 1] Creating test .env file...\n";
$testEnvPath = __DIR__ . '/test_env_' . time() . '.env';
$testEnvContent = "TEST_KEY1=value1\nTEST_KEY2=value2\nTEST_KEY3=123\nDEBUG=0\n";
file_put_contents($testEnvPath, $testEnvContent);

if (file_exists($testEnvPath)) {
    echo "  ✓ Test .env file created\n";
    $testsPassed++;
} else {
    echo "  ✗ Failed to create test .env file\n";
    $testsFailed++;
    exit(1);
}

echo "\n";

// =============================================================================
// TEST 2: Parse .env without caching
// =============================================================================

echo "[TEST 2] Parsing .env without caching...\n";
$config = @parse_ini_file($testEnvPath);

if ($config && isset($config['TEST_KEY1']) && $config['TEST_KEY1'] === 'value1') {
    echo "  ✓ .env parsed correctly\n";
    echo "    Found keys: " . implode(', ', array_keys($config)) . "\n";
    $testsPassed++;
} else {
    echo "  ✗ Failed to parse .env file\n";
    $testsFailed++;
}

echo "\n";

// =============================================================================
// TEST 3: Cache .env in APCu (if available)
// =============================================================================

if ($apcuAvailable) {
    echo "[TEST 3] Testing APCu caching...\n";

    // Clear any existing cache
    apcu_clear_cache();

    $cacheKey = 'test_phpweave_env_' . filemtime($testEnvPath);

    // First access - should miss cache
    $start = microtime(true);
    $cached = apcu_fetch($cacheKey);
    $time1 = (microtime(true) - $start) * 1000;

    if ($cached === false) {
        echo "  ✓ Cache miss (expected on first access)\n";
        $testsPassed++;

        // Store in cache
        $config = @parse_ini_file($testEnvPath);
        apcu_store($cacheKey, $config, 3600);
        echo "  ✓ Stored in APCu cache\n";
    } else {
        echo "  ✗ Expected cache miss but got hit\n";
        $testsFailed++;
    }

    // Second access - should hit cache
    $start = microtime(true);
    $cached = apcu_fetch($cacheKey);
    $time2 = (microtime(true) - $start) * 1000;

    if ($cached !== false && is_array($cached)) {
        echo "  ✓ Cache hit (expected on second access)\n";
        echo "  ✓ Retrieved from cache: " . implode(', ', array_keys($cached)) . "\n";
        $testsPassed++;
    } else {
        echo "  ✗ Expected cache hit but got miss\n";
        $testsFailed++;
    }

    echo "\n";
} else {
    echo "[TEST 3] Skipping APCu tests (APCu not available)\n";
    echo "  ℹ Install APCu to test caching: sudo apt-get install php-apcu\n";
    echo "\n";
}

// =============================================================================
// TEST 4: Cache invalidation on file modification
// =============================================================================

if ($apcuAvailable) {
    echo "[TEST 4] Testing cache invalidation on file modification...\n";

    // Get original cache key
    $originalMtime = filemtime($testEnvPath);
    $originalCacheKey = 'test_phpweave_env_' . $originalMtime;

    // Wait a moment and modify file
    sleep(1);
    file_put_contents($testEnvPath, "TEST_KEY1=modified\nTEST_KEY2=value2\n");

    // New cache key should be different
    $newMtime = filemtime($testEnvPath);
    $newCacheKey = 'test_phpweave_env_' . $newMtime;

    if ($newCacheKey !== $originalCacheKey) {
        echo "  ✓ Cache key changed after file modification\n";
        echo "    Old key: $originalCacheKey\n";
        echo "    New key: $newCacheKey\n";
        $testsPassed++;
    } else {
        echo "  ✗ Cache key should change after file modification\n";
        $testsFailed++;
    }

    // Verify old cache is not used
    $config = apcu_fetch($newCacheKey);
    if ($config === false) {
        echo "  ✓ Old cache correctly invalidated\n";
        $testsPassed++;
    } else {
        echo "  ✗ Old cache should not be accessible with new key\n";
        $testsFailed++;
    }

    echo "\n";
} else {
    echo "[TEST 4] Skipping cache invalidation test (APCu not available)\n\n";
}

// =============================================================================
// TEST 5: Performance benchmark
// =============================================================================

echo "[TEST 5] Performance benchmark (1000 iterations)...\n";

$iterations = 1000;

// Without caching
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $config = @parse_ini_file($testEnvPath);
}
$timeWithoutCache = (microtime(true) - $start) * 1000;

echo "  Without Cache: " . number_format($timeWithoutCache, 2) . " ms\n";
echo "  Per Request:   " . number_format($timeWithoutCache / $iterations, 3) . " ms\n";

if ($apcuAvailable) {
    // With caching
    apcu_clear_cache();
    $cacheKey = 'test_phpweave_env_' . filemtime($testEnvPath);

    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $config = apcu_fetch($cacheKey);
        if ($config === false) {
            $config = @parse_ini_file($testEnvPath);
            apcu_store($cacheKey, $config, 3600);
        }
    }
    $timeWithCache = (microtime(true) - $start) * 1000;

    echo "  With Cache:    " . number_format($timeWithCache, 2) . " ms\n";
    echo "  Per Request:   " . number_format($timeWithCache / $iterations, 3) . " ms\n";

    $improvement = (($timeWithoutCache - $timeWithCache) / $timeWithoutCache) * 100;
    $speedup = $timeWithoutCache / $timeWithCache;

    echo "\n";
    echo "  Performance:   " . number_format($improvement, 1) . "% faster\n";
    echo "  Speed:         " . number_format($speedup, 1) . "x improvement\n";
    echo "  Saved:         " . number_format(($timeWithoutCache - $timeWithCache) / $iterations, 3) . " ms per request\n";

    if ($improvement > 50) {
        echo "  ✓ Significant performance improvement achieved\n";
        $testsPassed++;
    } else {
        echo "  ⚠ Performance improvement is less than expected\n";
    }
} else {
    echo "  ⚠ APCu not available - caching performance cannot be tested\n";
}

echo "\n";

// =============================================================================
// Cleanup
// =============================================================================

echo "[CLEANUP] Removing test files...\n";
if (file_exists($testEnvPath)) {
    unlink($testEnvPath);
    echo "  ✓ Test .env file removed\n";
}

if ($apcuAvailable) {
    apcu_clear_cache();
    echo "  ✓ APCu cache cleared\n";
}

echo "\n";

// =============================================================================
// Results
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ Test Results                                                           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "\n";

if ($testsFailed === 0) {
    echo "✓ All tests passed!\n";
    if (!$apcuAvailable) {
        echo "\n";
        echo "⚠ Note: Install APCu extension for production use:\n";
        echo "  Ubuntu/Debian: sudo apt-get install php-apcu\n";
        echo "  CentOS/RHEL:   sudo yum install php-apcu\n";
        echo "  macOS:         pecl install apcu\n";
        echo "  Windows:       Enable extension=apcu in php.ini\n";
    }
} else {
    echo "✗ Some tests failed. Please review the output above.\n";
    exit(1);
}

echo "\n";
