<?php
/**
 * Basic Cache Layer Test (No Database Required)
 *
 * Tests core caching functionality without database dependencies.
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @category   Caching
 */

// Load framework dependencies
require_once __DIR__ . '/../coreapp/cache.php';
require_once __DIR__ . '/../coreapp/cachedriver.php';

// ANSI colors
class TestColors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;

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

function test_section($title) {
    echo "\n" . TestColors::BLUE . "═══ $title ═══" . TestColors::RESET . "\n\n";
}

echo TestColors::BLUE . "
╔══════════════════════════════════════════════════╗
║   PHPWEAVE CACHE LAYER - BASIC TESTS            ║
╚══════════════════════════════════════════════════╝
" . TestColors::RESET . "\n";

// ====================================================
// TEST 1: Memory Cache Driver
// ====================================================
test_section("TEST 1: Memory Cache Driver");

$memoryDriver = new MemoryCacheDriver();

test_assert($memoryDriver->isAvailable(), "Memory driver is available");
test_assert($memoryDriver->put('test1', 'value1', 3600), "Can store value");
test_assert($memoryDriver->get('test1') === 'value1', "Can retrieve value");
test_assert($memoryDriver->forget('test1'), "Can delete value");
test_assert($memoryDriver->get('test1') === null, "Deleted value returns null");

$memoryDriver->put('counter', 10, 3600);
test_assert($memoryDriver->increment('counter', 5) === 15, "Can increment");
test_assert($memoryDriver->decrement('counter', 3) === 12, "Can decrement");

$memoryDriver->put('test2', 'value2', 3600);
test_assert($memoryDriver->flush(), "Can flush all");
test_assert($memoryDriver->get('counter') === null, "Flush removes all values");

// ====================================================
// TEST 2: APCu Driver Detection
// ====================================================
test_section("TEST 2: APCu Driver Detection");

$apcuDriver = new APCuCacheDriver();
$available = $apcuDriver->isAvailable();

if ($available) {
    echo TestColors::GREEN . "✓ APCu is available and enabled" . TestColors::RESET . "\n";
    test_assert($apcuDriver->put('apcu_test', 'apcu_val', 3600), "APCu can store");
    test_assert($apcuDriver->get('apcu_test') === 'apcu_val', "APCu can retrieve");
    $apcuDriver->forget('apcu_test');
} else {
    echo TestColors::YELLOW . "⚠ APCu is not available (extension not loaded or disabled)" . TestColors::RESET . "\n";
}

// ====================================================
// TEST 3: File Cache Driver
// ====================================================
test_section("TEST 3: File Cache Driver");

$cachePath = __DIR__ . '/../cache/test_cache_data';
$fileDriver = new FileCacheDriver(['path' => $cachePath]);

test_assert($fileDriver->isAvailable(), "File driver is available (directory writable)");
test_assert($fileDriver->put('file_test', ['data' => 'test'], 3600), "Can store array data");

$retrieved = $fileDriver->get('file_test');
test_assert(isset($retrieved['data']) && $retrieved['data'] === 'test', "Can retrieve array data");

test_assert($fileDriver->forget('file_test'), "Can delete file cache");
test_assert($fileDriver->get('file_test') === null, "Deleted file cache returns null");

// Test increment/decrement
$fileDriver->put('file_counter', 100, 3600);
test_assert($fileDriver->increment('file_counter', 25) === 125, "File driver can increment");
test_assert($fileDriver->decrement('file_counter', 15) === 110, "File driver can decrement");

$fileDriver->flush();

// ====================================================
// TEST 4: Redis Driver Detection
// ====================================================
test_section("TEST 4: Redis Driver Detection");

$redisDriver = new RedisCacheDriver();

if ($redisDriver->isAvailable()) {
    echo TestColors::GREEN . "✓ Redis is available and connected" . TestColors::RESET . "\n";
    test_assert($redisDriver->put('redis_test', 'redis_val', 3600), "Redis can store");
    test_assert($redisDriver->get('redis_test') === 'redis_val', "Redis can retrieve");
    $redisDriver->forget('redis_test');
} else {
    echo TestColors::YELLOW . "⚠ Redis is not available (extension not loaded or server not running)" . TestColors::RESET . "\n";
}

// ====================================================
// TEST 5: Memcached Driver Detection
// ====================================================
test_section("TEST 5: Memcached Driver Detection");

$memcachedDriver = new MemcachedCacheDriver();

if ($memcachedDriver->isAvailable()) {
    echo TestColors::GREEN . "✓ Memcached is available and connected" . TestColors::RESET . "\n";
    test_assert($memcachedDriver->put('memc_test', 'memc_val', 3600), "Memcached can store");
    test_assert($memcachedDriver->get('memc_test') === 'memc_val', "Memcached can retrieve");
    $memcachedDriver->forget('memc_test');
} else {
    echo TestColors::YELLOW . "⚠ Memcached is not available (extension not loaded or server not running)" . TestColors::RESET . "\n";
}

// ====================================================
// TEST 6: High-Level Cache API
// ====================================================
test_section("TEST 6: High-Level Cache API");

Cache::init('memory');
Cache::flush();

// Basic operations
test_assert(Cache::put('api_test', 'api_value', 3600), "Cache::put() works");
test_assert(Cache::get('api_test') === 'api_value', "Cache::get() works");
test_assert(Cache::has('api_test'), "Cache::has() returns true for existing keys");
test_assert(!Cache::has('nonexistent'), "Cache::has() returns false for missing keys");

$default = Cache::get('missing_key', 'default_val');
test_assert($default === 'default_val', "Cache::get() returns default for missing keys");

test_assert(Cache::forget('api_test'), "Cache::forget() works");
test_assert(!Cache::has('api_test'), "Forgotten keys are removed");

// ====================================================
// TEST 7: Cache Remember Pattern
// ====================================================
test_section("TEST 7: Cache Remember Pattern");

Cache::flush();
$callCount = 0;

$result1 = Cache::remember('remember_test', 3600, function() use (&$callCount) {
    $callCount++;
    return 'computed_' . $callCount;
});

test_assert($result1 === 'computed_1', "Remember returns computed value on miss");
test_assert($callCount === 1, "Callback executed on cache miss");

$result2 = Cache::remember('remember_test', 3600, function() use (&$callCount) {
    $callCount++;
    return 'computed_' . $callCount;
});

test_assert($result2 === 'computed_1', "Remember returns cached value on hit");
test_assert($callCount === 1, "Callback NOT executed on cache hit");

// ====================================================
// TEST 8: Cache Tagging
// ====================================================
test_section("TEST 8: Cache Tagging");

Cache::flush();

Cache::tags(['users'])->put('users.1', ['name' => 'John'], 3600);
Cache::tags(['users'])->put('users.2', ['name' => 'Jane'], 3600);
Cache::tags(['posts'])->put('posts.1', ['title' => 'Post 1'], 3600);

test_assert(Cache::tags(['users'])->get('users.1') !== null, "Tagged cache stores correctly");
test_assert(Cache::tags(['posts'])->get('posts.1') !== null, "Different tags are independent");

Cache::tags(['users'])->flush();

test_assert(Cache::tags(['users'])->get('users.1') === null, "Flushing tag removes tagged items");
test_assert(Cache::tags(['posts'])->get('posts.1') !== null, "Flushing tag doesn't affect other tags");

// ====================================================
// TEST 9: Cache Statistics
// ====================================================
test_section("TEST 9: Cache Statistics");

Cache::resetStats();

Cache::put('stat1', 'val1', 3600);
Cache::put('stat2', 'val2', 3600);

Cache::get('stat1'); // Hit
Cache::get('stat1'); // Hit
Cache::get('stat3'); // Miss
Cache::get('stat2'); // Hit

$stats = Cache::stats();

test_assert($stats['hits'] === 3, "Hit count is correct: " . $stats['hits']);
test_assert($stats['misses'] === 1, "Miss count is correct: " . $stats['misses']);
test_assert($stats['writes'] === 2, "Write count is correct: " . $stats['writes']);
test_assert($stats['hit_rate'] === 75.0, "Hit rate calculated correctly: " . $stats['hit_rate'] . "%");

// ====================================================
// TEST 10: Cache Warming
// ====================================================
test_section("TEST 10: Cache Warming");

Cache::flush();

$warmCount = 0;
$result = Cache::warm('warm_test', function() use (&$warmCount) {
    $warmCount++;
    return 'warmed_data';
}, 3600);

test_assert($result === 'warmed_data', "Cache::warm() returns computed value");
test_assert($warmCount === 1, "Warm callback executed once");
test_assert(Cache::get('warm_test') === 'warmed_data', "Warmed data is cached");

// ====================================================
// TEST 11: Increment/Decrement
// ====================================================
test_section("TEST 11: Increment/Decrement");

Cache::flush();
Cache::put('views', 100, 3600);

$new = Cache::increment('views', 10);
test_assert($new === 110, "Increment increases value");
test_assert(Cache::get('views') === 110, "Incremented value persists");

$new = Cache::decrement('views', 5);
test_assert($new === 105, "Decrement decreases value");
test_assert(Cache::get('views') === 105, "Decremented value persists");

// ====================================================
// SUMMARY
// ====================================================

echo "\n" . TestColors::BLUE . "═══════════════════════════════════════════════════" . TestColors::RESET . "\n";
echo TestColors::BLUE . "                  TEST SUMMARY                     " . TestColors::RESET . "\n";
echo TestColors::BLUE . "═══════════════════════════════════════════════════" . TestColors::RESET . "\n\n";

echo "Total tests: " . $tests_run . "\n";
echo TestColors::GREEN . "Passed: " . $tests_passed . TestColors::RESET . "\n";

if ($tests_failed > 0) {
    echo TestColors::RED . "Failed: " . $tests_failed . TestColors::RESET . "\n";
    exit(1);
} else {
    echo TestColors::GREEN . "\n✓ ALL TESTS PASSED!" . TestColors::RESET . "\n\n";
    exit(0);
}
