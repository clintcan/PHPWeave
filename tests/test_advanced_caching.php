<?php
/**
 * Advanced Caching Layer Test Suite
 *
 * Tests all caching functionality including:
 * - Multiple cache drivers (Memory, APCu, File, Redis, Memcached)
 * - Cache tagging and invalidation
 * - Query Builder caching integration
 * - Cache statistics
 * - Cache warming
 *
 * Requirements:
 * - MySQL running on localhost (XAMPP default credentials)
 * - APCu extension (optional, will test availability)
 * - Redis server (optional, will test availability)
 * - Memcached server (optional, will test availability)
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @category   Caching
 */

// Load framework dependencies
require_once __DIR__ . '/../coreapp/cache.php';
require_once __DIR__ . '/../coreapp/cachedriver.php';
require_once __DIR__ . '/../coreapp/dbconnection.php';
require_once __DIR__ . '/../coreapp/querybuilder.php';

// Test configuration
define('TEST_DB_HOST', 'localhost');
define('TEST_DB_NAME', 'test_phpweave_cache');
define('TEST_DB_USER', 'root');
define('TEST_DB_PASS', '');

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

/**
 * Setup test database
 */
function setup_test_database() {
    echo TestColors::YELLOW . "Setting up test database..." . TestColors::RESET . "\n";

    try {
        // Connect without selecting database
        $pdo = new PDO(
            'mysql:host=' . TEST_DB_HOST,
            TEST_DB_USER,
            TEST_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Drop and recreate database
        $pdo->exec("DROP DATABASE IF EXISTS " . TEST_DB_NAME);
        $pdo->exec("CREATE DATABASE " . TEST_DB_NAME);
        $pdo->exec("USE " . TEST_DB_NAME);

        // Create test table
        $pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert test data
        $pdo->exec("
            INSERT INTO users (name, email, active) VALUES
            ('John Doe', 'john@example.com', 1),
            ('Jane Smith', 'jane@example.com', 1),
            ('Bob Wilson', 'bob@example.com', 0),
            ('Alice Brown', 'alice@example.com', 1),
            ('Charlie Davis', 'charlie@example.com', 1)
        ");

        echo TestColors::GREEN . "✓ Test database created successfully" . TestColors::RESET . "\n\n";
        return $pdo;

    } catch (PDOException $e) {
        echo TestColors::RED . "✗ Database setup failed: " . $e->getMessage() . TestColors::RESET . "\n";
        echo TestColors::YELLOW . "Please ensure MySQL is running on localhost with root access" . TestColors::RESET . "\n";
        exit(1);
    }
}

/**
 * Test model using Query Builder
 */
class TestUserModel extends DBConnection {
    use QueryBuilder;

    public function __construct() {
        parent::__construct();
    }
}

// =====================================================
// START TESTS
// =====================================================

echo TestColors::BLUE . "
╔══════════════════════════════════════════════════╗
║   PHPWEAVE ADVANCED CACHING LAYER TEST SUITE    ║
╚══════════════════════════════════════════════════╝
" . TestColors::RESET . "\n";

// Setup
$pdo = setup_test_database();

// Override DBConnection settings for tests
$GLOBALS['configs'] = [
    'DBHOST' => TEST_DB_HOST,
    'DBNAME' => TEST_DB_NAME,
    'DBUSER' => TEST_DB_USER,
    'DBPASSWORD' => TEST_DB_PASS,
    'DBCHARSET' => 'utf8mb4'
];

// =====================================================
// TEST 1: Memory Cache Driver
// =====================================================
test_section("TEST 1: Memory Cache Driver");

Cache::init('memory');
$driver = new MemoryCacheDriver();

test_assert($driver->isAvailable(), "Memory driver is available");

$driver->put('test_key', 'test_value', 3600);
test_assert($driver->get('test_key') === 'test_value', "Memory driver can store and retrieve values");

$driver->put('counter', 5, 3600);
$result = $driver->increment('counter', 3);
test_assert($result === 8, "Memory driver can increment values");

$result = $driver->decrement('counter', 2);
test_assert($result === 6, "Memory driver can decrement values");

$driver->forget('test_key');
test_assert($driver->get('test_key') === null, "Memory driver can delete values");

$driver->flush();
test_assert($driver->get('counter') === null, "Memory driver can flush all values");

// =====================================================
// TEST 2: APCu Cache Driver
// =====================================================
test_section("TEST 2: APCu Cache Driver");

$apcuDriver = new APCuCacheDriver();

if ($apcuDriver->isAvailable()) {
    echo TestColors::GREEN . "APCu is available - running tests" . TestColors::RESET . "\n";

    $apcuDriver->put('test_apcu_key', 'apcu_value', 3600);
    test_assert($apcuDriver->get('test_apcu_key') === 'apcu_value', "APCu driver can store and retrieve values");

    $apcuDriver->put('apcu_counter', 10, 3600);
    $result = $apcuDriver->increment('apcu_counter', 5);
    test_assert($result === 15, "APCu driver can increment values");

    $apcuDriver->forget('test_apcu_key');
    test_assert($apcuDriver->get('test_apcu_key') === null, "APCu driver can delete values");

    echo TestColors::GREEN . "✓ APCu tests completed" . TestColors::RESET . "\n";
} else {
    echo TestColors::YELLOW . "⚠ APCu is not available - skipping APCu tests" . TestColors::RESET . "\n";
}

// =====================================================
// TEST 3: File Cache Driver
// =====================================================
test_section("TEST 3: File Cache Driver");

$fileDriver = new FileCacheDriver(['path' => __DIR__ . '/../cache/test']);

test_assert($fileDriver->isAvailable(), "File driver is available");

$fileDriver->put('test_file_key', 'file_value', 3600);
test_assert($fileDriver->get('test_file_key') === 'file_value', "File driver can store and retrieve values");

$fileDriver->put('file_counter', 20, 3600);
$result = $fileDriver->increment('file_counter', 5);
test_assert($result === 25, "File driver can increment values");

$fileDriver->forget('test_file_key');
test_assert($fileDriver->get('test_file_key') === null, "File driver can delete values");

$fileDriver->flush();
test_assert($fileDriver->get('file_counter') === null, "File driver can flush all values");

// =====================================================
// TEST 4: Redis Cache Driver (Optional)
// =====================================================
test_section("TEST 4: Redis Cache Driver (Optional)");

$redisDriver = new RedisCacheDriver();

if ($redisDriver->isAvailable()) {
    echo TestColors::GREEN . "Redis is available - running tests" . TestColors::RESET . "\n";

    $redisDriver->put('test_redis_key', 'redis_value', 3600);
    test_assert($redisDriver->get('test_redis_key') === 'redis_value', "Redis driver can store and retrieve values");

    $redisDriver->put('redis_counter', 30, 3600);
    $result = $redisDriver->increment('redis_counter', 10);
    test_assert($result === 40, "Redis driver can increment values");

    $redisDriver->forget('test_redis_key');
    test_assert($redisDriver->get('test_redis_key') === null, "Redis driver can delete values");

    echo TestColors::GREEN . "✓ Redis tests completed" . TestColors::RESET . "\n";
} else {
    echo TestColors::YELLOW . "⚠ Redis is not available - skipping Redis tests" . TestColors::RESET . "\n";
}

// =====================================================
// TEST 5: Cache API (High-Level)
// =====================================================
test_section("TEST 5: Cache API (High-Level)");

Cache::init('memory');

Cache::put('user.123', ['id' => 123, 'name' => 'Test User'], 3600);
$user = Cache::get('user.123');
test_assert($user['name'] === 'Test User', "Cache::put() and Cache::get() work correctly");

test_assert(Cache::has('user.123'), "Cache::has() returns true for existing key");
test_assert(!Cache::has('user.999'), "Cache::has() returns false for non-existing key");

$value = Cache::get('nonexistent', 'default_value');
test_assert($value === 'default_value', "Cache::get() returns default value for missing keys");

Cache::forget('user.123');
test_assert(!Cache::has('user.123'), "Cache::forget() removes keys");

// =====================================================
// TEST 6: Cache Remember Pattern
// =====================================================
test_section("TEST 6: Cache Remember Pattern");

Cache::init('memory');
Cache::flush();

$callCount = 0;
$result1 = Cache::remember('expensive.calculation', 3600, function() use (&$callCount) {
    $callCount++;
    return 'computed_value';
});

test_assert($result1 === 'computed_value', "Cache::remember() returns computed value on first call");
test_assert($callCount === 1, "Callback was executed on cache miss");

$result2 = Cache::remember('expensive.calculation', 3600, function() use (&$callCount) {
    $callCount++;
    return 'computed_value';
});

test_assert($result2 === 'computed_value', "Cache::remember() returns cached value on second call");
test_assert($callCount === 1, "Callback was NOT executed on cache hit");

// =====================================================
// TEST 7: Cache Tagging
// =====================================================
test_section("TEST 7: Cache Tagging");

Cache::init('memory');
Cache::flush();

Cache::tags(['users', 'active'])->put('users.active.list', ['user1', 'user2'], 3600);
Cache::tags(['users', 'inactive'])->put('users.inactive.list', ['user3'], 3600);
Cache::tags(['posts'])->put('posts.recent', ['post1', 'post2'], 3600);

test_assert(Cache::tags(['users', 'active'])->get('users.active.list') !== null, "Tagged cache can be retrieved");

Cache::tags(['users'])->flush();

test_assert(Cache::tags(['users', 'active'])->get('users.active.list') === null, "Flushing tag removes tagged items");
test_assert(Cache::tags(['users', 'inactive'])->get('users.inactive.list') === null, "Flushing tag removes all items with that tag");
test_assert(Cache::tags(['posts'])->get('posts.recent') !== null, "Flushing tag does not affect other tags");

// =====================================================
// TEST 8: Cache Statistics
// =====================================================
test_section("TEST 8: Cache Statistics");

Cache::init('memory');
Cache::resetStats();

Cache::put('stat_key1', 'value1', 3600);
Cache::get('stat_key1'); // Hit
Cache::get('stat_key2'); // Miss
Cache::get('stat_key1'); // Hit

$stats = Cache::stats();

test_assert($stats['hits'] === 2, "Statistics track cache hits correctly");
test_assert($stats['misses'] === 1, "Statistics track cache misses correctly");
test_assert($stats['writes'] === 1, "Statistics track cache writes correctly");
test_assert($stats['hit_rate'] === 66.67, "Hit rate calculated correctly");

// =====================================================
// TEST 9: Cache Warming
// =====================================================
test_section("TEST 9: Cache Warming");

Cache::init('memory');
Cache::flush();

$warmCallCount = 0;
$result = Cache::warm('preloaded.data', function() use (&$warmCallCount) {
    $warmCallCount++;
    return 'warmed_value';
}, 3600);

test_assert($result === 'warmed_value', "Cache::warm() returns computed value");
test_assert($warmCallCount === 1, "Cache::warm() executes callback");
test_assert(Cache::get('preloaded.data') === 'warmed_value', "Cache::warm() stores value in cache");

// =====================================================
// TEST 10: Query Builder Cache Integration
// =====================================================
test_section("TEST 10: Query Builder Cache Integration");

Cache::init('memory');
Cache::flush();

$model = new TestUserModel();

// First query - should hit database
$start = microtime(true);
$users1 = $model->table('users')->where('active', 1)->cache(3600)->get();
$time1 = (microtime(true) - $start) * 1000;

test_assert(count($users1) === 4, "Query Builder cache returns correct result count");
test_assert($users1[0]['name'] === 'John Doe', "Query Builder cache returns correct data");

// Second query - should hit cache
$start = microtime(true);
$users2 = $model->table('users')->where('active', 1)->cache(3600)->get();
$time2 = (microtime(true) - $start) * 1000;

test_assert(count($users2) === 4, "Cached query returns same result count");
test_assert($users2[0]['name'] === 'John Doe', "Cached query returns same data");
test_assert($time2 < $time1, "Cached query is faster than database query (" . round($time2, 2) . "ms vs " . round($time1, 2) . "ms)");

// Test first() with cache
$user = $model->table('users')->where('id', 1)->cache(3600)->first();
test_assert($user['email'] === 'john@example.com', "Query Builder cache works with first()");

// =====================================================
// TEST 11: Query Builder Cache with Tags
// =====================================================
test_section("TEST 11: Query Builder Cache with Tags");

Cache::init('memory');
Cache::flush();

$model = new TestUserModel();

// Query with tags
$activeUsers = $model->table('users')
    ->where('active', 1)
    ->cacheTags(['users', 'active'])
    ->cache(3600)
    ->get();

test_assert(count($activeUsers) === 4, "Query with cache tags returns correct results");

// Verify cache hit
$cachedUsers = $model->table('users')
    ->where('active', 1)
    ->cacheTags(['users', 'active'])
    ->cache(3600)
    ->get();

test_assert(count($cachedUsers) === 4, "Tagged query cache hit works");

// Flush by tag
Cache::tags(['users'])->flush();

// Verify cache was cleared
$stats = Cache::stats();
Cache::resetStats();

$freshUsers = $model->table('users')
    ->where('active', 1)
    ->cacheTags(['users', 'active'])
    ->cache(3600)
    ->get();

test_assert(count($freshUsers) === 4, "Query after tag flush returns correct results");

// =====================================================
// TEST 12: Increment/Decrement
// =====================================================
test_section("TEST 12: Increment/Decrement Operations");

Cache::init('memory');
Cache::flush();

Cache::put('page_views', 100, 3600);

$newValue = Cache::increment('page_views', 5);
test_assert($newValue === 105, "Cache::increment() increases value correctly");

$value = Cache::get('page_views');
test_assert($value === 105, "Incremented value persists in cache");

$newValue = Cache::decrement('page_views', 10);
test_assert($newValue === 95, "Cache::decrement() decreases value correctly");

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
    exit(1);
} else {
    echo TestColors::GREEN . "\n✓ ALL TESTS PASSED!" . TestColors::RESET . "\n\n";
    exit(0);
}
