# Advanced Caching Layer

**Version:** 2.5.0
**Status:** Production Ready

The Advanced Caching Layer provides a powerful, flexible caching system with support for multiple drivers, cache tagging, Query Builder integration, and advanced features like cache warming and statistics tracking.

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Configuration](#configuration)
4. [Cache Drivers](#cache-drivers)
5. [Basic Usage](#basic-usage)
6. [Cache Tagging](#cache-tagging)
7. [Query Builder Integration](#query-builder-integration)
8. [Advanced Features](#advanced-features)
9. [Cache Dashboard](#cache-dashboard)
10. [Best Practices](#best-practices)
11. [Performance](#performance)
12. [API Reference](#api-reference)

---

## Overview

The Advanced Caching Layer provides:

- **Multi-tier caching** - Memory, APCu, File, Redis, Memcached
- **Cache tagging** - Organize and invalidate cache by groups
- **Query Builder integration** - Seamless ORM result caching
- **Statistics tracking** - Monitor cache performance
- **Cache warming** - Preload critical data
- **Zero dependencies** - Works out-of-the-box
- **100% backwards compatible** - No breaking changes

### Key Features

✅ Multiple cache drivers with automatic fallback
✅ Cache tagging for organized invalidation
✅ Query Builder integration with `->cache()`
✅ Statistics tracking (hit/miss rates)
✅ Cache warming for critical data
✅ Remember pattern (compute-once)
✅ Increment/Decrement atomic operations
✅ Zero configuration required

---

## Quick Start

### Basic Caching

```php
// Store a value
Cache::put('user.123', $userData, 3600); // 1 hour TTL

// Retrieve a value
$user = Cache::get('user.123');

// With default value
$user = Cache::get('user.999', ['name' => 'Guest']);

// Check if exists
if (Cache::has('user.123')) {
    // Key exists
}

// Remove a value
Cache::forget('user.123');

// Clear all cache
Cache::flush();
```

### Remember Pattern

```php
// Get from cache or compute once
$users = Cache::remember('users.all', 3600, function() {
    return DB::table('users')->get();
});

// On first call: executes callback, caches result
// On subsequent calls: returns cached value
```

### Query Builder Caching

```php
// Cache query results
$users = $this->table('users')
    ->where('active', 1)
    ->cache(3600)  // Cache for 1 hour
    ->get();

// First query: hits database (26ms)
// Cached query: returns instantly (0.07ms)
// Speed improvement: 374x faster!
```

---

## Configuration

### Environment Variables

Add to your `.env` file:

```ini
# Cache Driver (memory|apcu|redis|memcached|file)
CACHE_DRIVER=apcu

# Default TTL in seconds
CACHE_DEFAULT_TTL=3600

# Cache key prefix (for multi-tenant)
CACHE_PREFIX=phpweave_

# Enable statistics tracking
CACHE_STATS=1

# Redis Configuration (if using Redis driver)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Memcached Configuration (if using Memcached driver)
MEMCACHED_HOST=localhost
MEMCACHED_PORT=11211
```

### Manual Initialization

```php
// Initialize with specific driver
require_once __DIR__ . '/../coreapp/cache.php';
require_once __DIR__ . '/../coreapp/cachedriver.php';

Cache::init('apcu', [
    'prefix' => 'myapp_',
    'ttl' => 7200,
    'stats' => true
]);
```

### Auto-Initialization

The cache system auto-initializes on first use with sensible defaults:
- Uses `CACHE_DRIVER` from `.env` or auto-detects best driver
- Falls back to Memory driver if no persistent cache available

---

## Cache Drivers

### Available Drivers

| Driver | Persistent | Multi-Server | Speed | Requirements |
|--------|-----------|--------------|-------|--------------|
| **Memory** | ❌ No (per-request) | ❌ No | ⚡ Fastest | None (always available) |
| **APCu** | ✅ Yes | ❌ No | ⚡ Very Fast | APCu extension |
| **File** | ✅ Yes | ⚠️ Shared FS | 🐢 Slower | Writable directory |
| **Redis** | ✅ Yes | ✅ Yes | ⚡ Fast | Redis server + extension |
| **Memcached** | ✅ Yes | ✅ Yes | ⚡ Fast | Memcached server + extension |

### Driver Selection

```php
// Automatic selection (recommended)
Cache::init(); // Auto-selects best available

// Specific driver
Cache::init('apcu');    // APCu
Cache::init('redis');   // Redis
Cache::init('file');    // File
Cache::init('memory');  // Memory (per-request)
```

### Driver Details

#### Memory Driver
- **Use case**: Per-request caching, temporary data
- **Lifetime**: Single request only
- **Pros**: Fastest, always available
- **Cons**: Not persistent across requests

```php
Cache::init('memory');
```

#### APCu Driver
- **Use case**: Production, single-server setups
- **Lifetime**: Until TTL expires or server restart
- **Pros**: Very fast, persistent, shared across requests
- **Cons**: Not shared across servers

```php
Cache::init('apcu');
```

#### File Driver
- **Use case**: Shared hosting, fallback
- **Lifetime**: Until deleted or TTL expires
- **Pros**: Always available, persistent
- **Cons**: Slower than memory caches

```php
Cache::init('file', ['path' => __DIR__ . '/../cache/data']);
```

#### Redis Driver
- **Use case**: Multi-server, distributed systems
- **Lifetime**: Until TTL expires or Redis flush
- **Pros**: Shared across servers, advanced features
- **Cons**: Requires Redis server

```php
Cache::init('redis', [
    'host' => 'localhost',
    'port' => 6379,
    'password' => null,
    'database' => 0
]);
```

#### Memcached Driver
- **Use case**: Multi-server, alternative to Redis
- **Lifetime**: Until TTL expires or Memcached flush
- **Pros**: Shared across servers, battle-tested
- **Cons**: Requires Memcached server

```php
Cache::init('memcached', [
    'host' => 'localhost',
    'port' => 11211
]);
```

---

## Basic Usage

### Storing Values

```php
// Store with TTL
Cache::put('key', 'value', 3600); // 1 hour

// Store with default TTL
Cache::put('key', 'value'); // Uses CACHE_DEFAULT_TTL

// Store arrays and objects
Cache::put('user.123', [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com'
], 3600);
```

### Retrieving Values

```php
// Get value
$value = Cache::get('key');

// Get with default
$value = Cache::get('key', 'default_value');

// Check existence
if (Cache::has('key')) {
    $value = Cache::get('key');
}
```

### Deleting Values

```php
// Delete single key
Cache::forget('key');

// Clear all cache
Cache::flush();
```

### Remember Pattern

The remember pattern retrieves from cache or computes once:

```php
$users = Cache::remember('users.active', 3600, function() {
    // This expensive operation only runs on cache miss
    return DB::table('users')
        ->where('active', 1)
        ->get();
});
```

**Benefits:**
- Simplifies cache logic
- Prevents cache stampede
- Cleaner code

---

## Cache Tagging

Cache tagging allows you to organize related cache items and invalidate them together.

### Basic Tagging

```php
// Store with tags
Cache::tags(['users'])->put('user.123', $userData, 3600);
Cache::tags(['users'])->put('user.456', $userData2, 3600);
Cache::tags(['posts'])->put('post.1', $postData, 3600);

// Retrieve with tags
$user = Cache::tags(['users'])->get('user.123');

// Flush by tag (clears all items with that tag)
Cache::tags(['users'])->flush();
// user.123 and user.456 are removed
// post.1 remains in cache
```

### Multiple Tags

```php
// Store with multiple tags
Cache::tags(['users', 'active'])->put('users.active.list', $users, 3600);
Cache::tags(['users', 'inactive'])->put('users.inactive.list', $users, 3600);

// Flush all items with 'users' tag
Cache::tags(['users'])->flush();
// Both active and inactive lists are removed
```

### Tag Use Cases

**User Data:**
```php
// Tag all user-related data
Cache::tags(['users', 'user:' . $userId])->put('user.profile.' . $userId, $profile, 3600);
Cache::tags(['users', 'user:' . $userId])->put('user.settings.' . $userId, $settings, 3600);

// Invalidate all data for specific user
Cache::tags(['user:' . $userId])->flush();
```

**Content Management:**
```php
// Tag content by type and status
Cache::tags(['posts', 'published'])->put('posts.published', $posts, 3600);
Cache::tags(['posts', 'draft'])->put('posts.draft', $drafts, 3600);

// Clear all post caches
Cache::tags(['posts'])->flush();
```

---

## Query Builder Integration

The Query Builder seamlessly integrates with the caching layer.

### Basic Query Caching

```php
class user_model extends DBConnection {
    use QueryBuilder;

    public function getActiveUsers() {
        return $this->table('users')
            ->where('active', 1)
            ->cache(3600)  // Cache for 1 hour
            ->get();
    }
}
```

### Performance Impact

```php
// First call: Hits database (26ms)
$users = $this->table('users')->where('active', 1)->cache(3600)->get();

// Second call: Returns from cache (0.07ms)
$users = $this->table('users')->where('active', 1)->cache(3600)->get();

// Speed improvement: 374x faster!
```

### Query Caching with Tags

```php
public function getPublishedPosts() {
    return $this->table('posts')
        ->where('published', 1)
        ->cacheTags(['posts', 'published'])
        ->cache(3600)
        ->get();
}

public function clearPostCache() {
    Cache::tags(['posts'])->flush();
}
```

### Cache Invalidation on Updates

```php
class blog_model extends DBConnection {
    use QueryBuilder;

    public function getPublishedPosts() {
        return $this->table('posts')
            ->where('published', 1)
            ->cacheTags(['posts'])
            ->cache(3600)
            ->get();
    }

    public function createPost($data) {
        $id = $this->table('posts')->insert($data);

        // Invalidate post cache
        Cache::tags(['posts'])->flush();

        return $id;
    }

    public function updatePost($id, $data) {
        $result = $this->table('posts')->where('id', $id)->update($data);

        // Invalidate post cache
        Cache::tags(['posts'])->flush();

        return $result;
    }
}
```

### Automatic Cache Keys

Cache keys are automatically generated based on:
- Table name
- WHERE conditions
- ORDER BY clauses
- LIMIT/OFFSET values
- JOIN clauses

The same query always generates the same cache key.

### Caching with first() and find()

```php
// Cache single record
$user = $this->table('users')
    ->where('id', 123)
    ->cache(3600)
    ->first();

// Cache by primary key
$user = $this->table('users')
    ->cache(3600)
    ->find(123);

// Cache with value()
$email = $this->table('users')
    ->where('id', 123)
    ->cache(3600)
    ->value('email');
```

---

## Advanced Features

### Cache Warming

Preload critical data into cache:

```php
// Warm cache on application boot
Cache::warm('critical.settings', function() {
    return DB::table('settings')->get();
}, 7200);

// In a scheduled task
public function warmCaches() {
    Cache::warm('homepage.posts', function() {
        return $this->table('posts')
            ->where('featured', 1)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
    }, 3600);
}
```

### Increment/Decrement

Atomic counter operations:

```php
// Page view counter
Cache::put('page.views', 0, 86400);
Cache::increment('page.views', 1);

// Stock management
Cache::put('product.stock.123', 100, 3600);
Cache::decrement('product.stock.123', 5); // Sold 5 items

// Get current value
$stock = Cache::get('product.stock.123'); // 95
```

### Cache Statistics

Monitor cache performance:

```php
// Get statistics
$stats = Cache::stats();

echo "Hit rate: " . $stats['hit_rate'] . "%\n";
echo "Miss rate: " . $stats['miss_rate'] . "%\n";
echo "Total requests: " . $stats['total_requests'] . "\n";
echo "Hits: " . $stats['hits'] . "\n";
echo "Misses: " . $stats['misses'] . "\n";
echo "Writes: " . $stats['writes'] . "\n";
echo "Driver: " . $stats['driver'] . "\n";

// Reset statistics
Cache::resetStats();
```

**Example Output:**
```
Hit rate: 75.5%
Miss rate: 24.5%
Total requests: 1000
Hits: 755
Misses: 245
Writes: 245
Driver: APCuCacheDriver
```

---

## Cache Dashboard

The Advanced Caching Layer includes a modern, real-time monitoring dashboard for tracking cache performance and statistics.

### Features

- **Real-time Statistics**: Live cache metrics with auto-refresh
- **Visual Performance Metrics**: Hit/miss rates, efficiency scores
- **Interactive Controls**: Reset stats, flush cache, configure refresh intervals
- **Security**: Configurable authentication and IP whitelist
- **Responsive Design**: Works on desktop, tablet, and mobile
- **JSON API**: RESTful endpoints for custom integrations

### Quick Start

Enable the dashboard in your `.env` file:

```bash
# Enable dashboard
CACHE_DASHBOARD_ENABLED=1

# Configure authentication
CACHE_DASHBOARD_AUTH=1
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=secure_password_here
```

Access the dashboard at:

```
http://yourapp.com/cache/dashboard
```

### Dashboard Statistics

The dashboard displays:

- **Cache Hits**: Number of successful cache retrievals (green)
- **Cache Misses**: Number of cache misses (red)
- **Cache Writes**: Number of items stored (blue)
- **Cache Deletes**: Number of items removed (orange)
- **Hit Rate**: Percentage of requests served from cache
- **Efficiency Score**: ⭐-rated performance indicator
- **Driver Information**: Active cache backend

### API Endpoints

The dashboard provides JSON API endpoints:

```bash
# Get statistics
curl http://yourapp.com/cache/stats

# Reset statistics
curl -X POST http://yourapp.com/cache/reset

# Flush all cache
curl -X POST http://yourapp.com/cache/flush

# Get driver information
curl http://yourapp.com/cache/driver
```

### Security Configuration

```bash
# Enable/disable dashboard
CACHE_DASHBOARD_ENABLED=1  # 1=enabled, 0=disabled

# Authentication (recommended for production)
CACHE_DASHBOARD_AUTH=1
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=your_secure_password

# IP whitelist (comma-separated)
CACHE_DASHBOARD_IPS=127.0.0.1,192.168.1.100
```

**Note:** Dashboard is automatically enabled in DEBUG mode and disabled in production by default.

For complete dashboard documentation, see [CACHE_DASHBOARD.md](CACHE_DASHBOARD.md).

---

## Best Practices

### 1. Use Appropriate TTLs

```php
// Short TTL for frequently changing data
Cache::put('user.online', $users, 60); // 1 minute

// Medium TTL for semi-static data
Cache::put('blog.posts', $posts, 3600); // 1 hour

// Long TTL for rarely changing data
Cache::put('site.settings', $settings, 86400); // 24 hours
```

### 2. Use Tags for Organization

```php
// Group related data with tags
Cache::tags(['users', 'profiles'])->put('user.profile.123', $profile, 3600);
Cache::tags(['users', 'settings'])->put('user.settings.123', $settings, 3600);

// Easy invalidation
Cache::tags(['users'])->flush(); // Clears all user data
```

### 3. Cache Expensive Operations

```php
// DO cache: Database queries
$users = Cache::remember('users.all', 3600, fn() =>
    $this->table('users')->get()
);

// DO cache: API calls
$weather = Cache::remember('weather.newyork', 1800, fn() =>
    $this->fetchWeatherAPI('New York')
);

// DON'T cache: Simple calculations
$total = $a + $b; // Too fast to benefit from caching
```

### 4. Invalidate on Updates

```php
public function updateUser($id, $data) {
    // Update database
    $this->table('users')->where('id', $id)->update($data);

    // Invalidate related caches
    Cache::forget('user.' . $id);
    Cache::tags(['users'])->flush();
}
```

### 5. Use Remember Pattern

```php
// Good: Remember pattern
$users = Cache::remember('users.active', 3600, function() {
    return $this->table('users')->where('active', 1)->get();
});

// Less ideal: Manual cache check
if (!Cache::has('users.active')) {
    $users = $this->table('users')->where('active', 1)->get();
    Cache::put('users.active', $users, 3600);
} else {
    $users = Cache::get('users.active');
}
```

### 6. Monitor Cache Performance

```php
// Periodically check cache statistics
$stats = Cache::stats();

if ($stats['hit_rate'] < 50) {
    // Low hit rate - consider increasing TTLs or warming cache
    error_log("Cache hit rate low: " . $stats['hit_rate'] . "%");
}
```

---

## Performance

### Benchmark Results

**Test Environment:**
- PHP 8.4
- MySQL 8.0
- Memory Cache Driver

**Query Builder Caching:**
```
First Query (Database):     26.18ms
Cached Query (Memory):       0.07ms
Speed Improvement:          374x faster
```

**Cache Driver Performance:**
```
Memory Driver:  < 0.1ms per operation
APCu Driver:    < 1ms per operation
File Driver:    1-3ms per operation
Database Query: 5-50ms per operation
```

### Expected Improvements

- **Query performance**: 10-500x faster for repeated queries
- **Database load**: 20-80% reduction
- **Response times**: 50-200ms improvement per request
- **Scalability**: Handle 5-10x more traffic

### Optimization Tips

1. **Use persistent drivers in production** (APCu, Redis)
2. **Cache at the right level** (queries, not raw data)
3. **Set appropriate TTLs** (balance freshness vs performance)
4. **Use cache warming** for critical data
5. **Monitor hit rates** (aim for > 70%)

---

## API Reference

### Cache Class

#### Core Methods

```php
// Initialize cache
Cache::init(string $driver = null, array $config = []): void

// Store value
Cache::put(string $key, mixed $value, int $ttl = null): bool

// Retrieve value
Cache::get(string $key, mixed $default = null): mixed

// Remember pattern
Cache::remember(string $key, int $ttl, callable $callback): mixed

// Check existence
Cache::has(string $key): bool

// Delete value
Cache::forget(string $key): bool

// Clear all
Cache::flush(): bool
```

#### Tagging Methods

```php
// Set tags for next operation
Cache::tags(string|array $tags): Cache

// Example usage
Cache::tags(['users'])->put('user.123', $data, 3600);
Cache::tags(['users'])->flush();
```

#### Counter Methods

```php
// Increment value
Cache::increment(string $key, int $value = 1): int|bool

// Decrement value
Cache::decrement(string $key, int $value = 1): int|bool
```

#### Utility Methods

```php
// Warm cache
Cache::warm(string $key, callable $callback, int $ttl = null): mixed

// Get statistics
Cache::stats(): array

// Reset statistics
Cache::resetStats(): void
```

### Query Builder Methods

```php
// Enable caching
->cache(int $ttl = 3600): self

// Set cache tags
->cacheTags(string|array $tags): self

// Example usage
$users = $this->table('users')
    ->where('active', 1)
    ->cacheTags(['users', 'active'])
    ->cache(3600)
    ->get();
```

---

## Troubleshooting

### Cache Not Working

**Check driver availability:**
```php
$driver = new APCuCacheDriver();
if (!$driver->isAvailable()) {
    echo "APCu is not available";
}
```

**Verify configuration:**
```php
Cache::init('apcu');
$stats = Cache::stats();
echo "Driver: " . $stats['driver'];
```

### Low Hit Rate

1. **Check TTL values** - May be too short
2. **Verify cache keys** - Ensure consistent key generation
3. **Monitor invalidation** - May be flushing too frequently

### Memory Issues

1. **Use shorter TTLs** - Expire data sooner
2. **Selective caching** - Don't cache everything
3. **Use file/Redis** - Move to disk-based cache

---

## Migration Guide

### From No Caching

**Before:**
```php
public function getUsers() {
    return $this->table('users')->get();
}
```

**After:**
```php
public function getUsers() {
    return $this->table('users')
        ->cache(3600)
        ->get();
}
```

### From Manual Caching

**Before:**
```php
$cacheKey = 'users_active';
if (!isset($_SESSION[$cacheKey])) {
    $_SESSION[$cacheKey] = $this->table('users')
        ->where('active', 1)
        ->get();
}
return $_SESSION[$cacheKey];
```

**After:**
```php
return $this->table('users')
    ->where('active', 1)
    ->cache(3600)
    ->get();
```

---

## Examples

### Example 1: Blog Application

```php
class blog_model extends DBConnection {
    use QueryBuilder;

    public function getPublishedPosts($page = 1, $perPage = 10) {
        return $this->table('posts')
            ->where('published', 1)
            ->orderBy('created_at', 'DESC')
            ->cacheTags(['posts', 'published'])
            ->cache(3600)
            ->paginate($perPage, $page);
    }

    public function getPost($slug) {
        return $this->table('posts')
            ->where('slug', $slug)
            ->cacheTags(['posts'])
            ->cache(3600)
            ->first();
    }

    public function createPost($data) {
        $id = $this->table('posts')->insert($data);
        Cache::tags(['posts'])->flush();
        return $id;
    }
}
```

### Example 2: User Dashboard

```php
class Dashboard extends Controller {
    public function index() {
        global $PW;

        // Cache user stats for 5 minutes
        $stats = Cache::remember('user.stats.' . $_SESSION['user_id'], 300, function() use ($PW) {
            return [
                'posts' => $PW->models->blog_model->getUserPostCount($_SESSION['user_id']),
                'comments' => $PW->models->comment_model->getUserCommentCount($_SESSION['user_id']),
                'views' => $PW->models->analytics_model->getUserViews($_SESSION['user_id'])
            ];
        });

        $this->show('dashboard', ['stats' => $stats]);
    }
}
```

### Example 3: API with Rate Limiting

```php
class Api extends Controller {
    public function users() {
        $cacheKey = 'api.users.' . md5(serialize($_GET));

        $users = Cache::remember($cacheKey, 600, function() {
            global $PW;
            return $PW->models->user_model
                ->table('users')
                ->where('active', 1)
                ->get();
        });

        header('Content-Type: application/json');
        echo json_encode(['data' => $users]);
    }
}
```

---

## Further Reading

- **Query Builder Guide**: `docs/QUERY_BUILDER.md`
- **Performance Guide**: `docs/OPTIMIZATIONS_APPLIED.md`
- **Docker Caching**: `docs/DOCKER_CACHING_GUIDE.md`
- **Connection Pooling**: `docs/CONNECTION_POOLING.md`

---

## Support

For issues or questions:
- **GitHub Issues**: https://github.com/clintcan/PHPWeave/issues
- **Documentation**: `docs/README.md`
- **Test Suite**: `tests/test_advanced_caching.php`

---

**PHPWeave Advanced Caching Layer v2.5.0** - Built for performance, designed for simplicity.
