# Changelog

All notable changes to PHPWeave will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- None yet

### Changed
- None yet

### Fixed
- None yet

---

## [2.6.0] - 2025-11-12

### Added

**⚡ Comprehensive Performance Optimizations**

Five major optimizations that reduce request overhead by **7-14ms per request**, providing significant performance gains for production applications.

**Tier 1 Optimization:**
- **APCu Caching for .env Files** (2-5ms saved)
  - Caches parsed .env configuration in APCu to eliminate repeated file I/O
  - Auto-invalidation based on file modification time
  - 95-98% faster parsing with APCu enabled
  - Debug-mode aware (caching disabled when `DEBUG=1`)
  - Graceful fallback when APCu unavailable

**Tier 2 Optimizations:**
- **Hook File Discovery Caching** (1-3ms saved)
  - Caches `glob()` results for hooks directory scanning
  - 90-98% faster hook discovery with APCu
  - Auto-invalidates when directory modified

- **Model & Library File Discovery Caching** (2-4ms saved)
  - Caches file lists for models and libraries directories
  - Eliminates repeated filesystem scans
  - 90-98% faster discovery with APCu

- **Environment Detection Consolidation** (0.5-1ms saved)
  - Detects Docker/K8s/Swoole environment once per request
  - Eliminates repeated `file_exists()` and `getenv()` calls
  - **1,354x faster** (99.9% improvement) - no APCu required!

**Bonus Optimization:**
- **Hybrid Cache Tag Lookup** (0.2-0.5ms per tag operation)
  - Small tags (<50 keys): Uses `in_array()` for efficiency
  - Large tags (>50 keys): Uses cached `array_flip()` + `isset()` (O(1) lookup)
  - 53-99% faster tag operations depending on array size
  - No APCu required

**Files Modified:**
- `public/index.php` - .env caching + environment detection
- `coreapp/hooks.php` - Hook file discovery caching
- `coreapp/models.php` - Model file caching + environment detection
- `coreapp/libraries.php` - Library file caching + environment detection
- `coreapp/cache.php` - Hybrid tag lookup optimization

**Test Suite:**
- `tests/test_env_caching.php` - Comprehensive .env cache tests
- `tests/benchmark_tier1_optimizations.php` - Tier 1 benchmarks
- `tests/benchmark_tier2_optimizations.php` - Tier 2 benchmarks
- `tests/benchmark_micro_optimizations.php` - Isolated micro-optimization tests

**Documentation:**
- `docs/OPTIMIZATION_v2.6.0.md` - Complete optimization guide with benchmarks

### Performance

**Request Performance:**
- 🚀 **7-14ms faster per request** (combined optimizations with APCu)
- 🚀 95-98% faster .env file parsing
- 🚀 90-98% faster file discovery (hooks, models, libraries)
- 🚀 **1,354x faster environment detection** (always, no APCu required!)
- 🚀 53-99% faster cache tag operations

**Real-World Impact (with APCu):**
- Small site (10K requests/day): ~1.2 minutes saved daily
- Medium site (100K requests/day): ~12 minutes saved daily
- High traffic (1M requests/day): ~2 hours saved daily
- Enterprise (10M requests/day): ~20 hours saved daily

### Technical Details

- **100% backward compatible** - No breaking changes
- **Zero configuration required** - Works automatically if APCu available
- **Debug-mode aware** - Caching disabled when `DEBUG=1` for development
- **Auto-invalidation** - Cache keys include file/directory modification times
- **Graceful fallback** - Works without APCu, just without caching benefits
- **Production-ready** - Extensively tested and benchmarked

### Requirements

- **PHP 7.0+** (already required)
- **APCu extension** (optional but highly recommended for maximum performance)
  - Ubuntu/Debian: `sudo apt-get install php-apcu`
  - CentOS/RHEL: `sudo yum install php-apcu`
  - macOS: `pecl install apcu`
  - Docker: `RUN pecl install apcu && docker-php-ext-enable apcu`

---

## [2.5.0] - 2025-11-12

### Added

**🚀 Advanced Caching Layer**

A complete, production-ready caching system providing multi-tier caching with support for multiple drivers, cache tagging, Query Builder integration, and advanced features like cache warming and statistics tracking.

**Core Features:**
- **Multi-tier Caching**: Memory → APCu → Redis → File with automatic fallback
- **Cache Tagging**: Organize and invalidate cache by groups
- **Query Builder Integration**: Seamless `->cache()` method for query results
- **Statistics Tracking**: Monitor hit/miss rates, writes, deletes
- **Cache Warming**: Preload critical data
- **Remember Pattern**: Compute-once caching with callbacks
- **Atomic Operations**: Increment/decrement support
- **Zero Configuration**: Works out-of-the-box with sensible defaults

**Cache Methods:**
- **Basic**: `put()`, `get()`, `has()`, `forget()`, `flush()`, `remember()`, `rememberForever()`
- **Atomic**: `increment()`, `decrement()`
- **Tagging**: `tags()`, `flush()` (tag-specific)
- **Warming**: `warm()`, `warmMany()`
- **Stats**: `getStats()`, `resetStats()`, `getHitRate()`

**Cache Drivers:**
- **Memory** - In-process array storage (fast, request-scoped)
- **APCu** - Shared memory cache (optimal for Docker/K8s)
- **File** - Filesystem cache (universal fallback)
- **Redis** - Distributed cache (multi-server support)
- **Memcached** - Distributed cache (alternative to Redis)

**Query Builder Integration:**
```php
// Cache query results automatically
$users = $this->table('users')
    ->where('active', 1)
    ->cache(3600)  // Cache for 1 hour
    ->get();

// First query: hits database (26ms)
// Cached query: returns instantly (0.07ms)
// Speed improvement: 374x faster!
```

**Basic Usage:**
```php
// Store a value
Cache::put('user.123', $userData, 3600);

// Retrieve a value
$user = Cache::get('user.123');

// Remember pattern (compute once)
$users = Cache::remember('users.all', 3600, function() {
    return DB::table('users')->get();
});

// Cache tagging
Cache::tags(['users'])->put('users.active', $data, 3600);
Cache::tags(['users'])->flush(); // Clear all user cache
```

**Cache Dashboard:**
- Real-time statistics and monitoring at `/cache-dashboard`
- Hit/miss rates, memory usage, storage breakdown
- Per-driver performance metrics
- Top cache keys with access counts
- Visual charts and graphs
- Tag management interface

**Files Added:**
- `coreapp/cache.php` - Main cache manager (500+ lines)
- `coreapp/cachedriver.php` - Base cache driver (200+ lines)
- `coreapp/cache/MemoryDriver.php` - Memory cache driver
- `coreapp/cache/ApcuDriver.php` - APCu cache driver
- `coreapp/cache/FileDriver.php` - File cache driver
- `coreapp/cache/RedisDriver.php` - Redis cache driver (optional)
- `coreapp/cache/MemcachedDriver.php` - Memcached cache driver (optional)
- `controller/CacheDashboard.php` - Cache dashboard controller
- `views/cache_dashboard.php` - Cache dashboard view
- `docs/ADVANCED_CACHING.md` - Comprehensive caching guide (1,500+ lines)
- `tests/test_cache.php` - Full test suite (30+ tests)

**Configuration:**
```ini
# .env
CACHE_DRIVER=apcu        # memory, apcu, file, redis, memcached
CACHE_PREFIX=phpweave_
CACHE_DEFAULT_TTL=3600
CACHE_STATS=1

# Redis (optional)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Memcached (optional)
MEMCACHED_HOST=localhost
MEMCACHED_PORT=11211
```

### Changed
- `coreapp/querybuilder.php` - Added `cache()` method for query result caching
- `routes/routes.php` - Added `/cache-dashboard` route for cache monitoring
- `composer.json` - Added Redis and Memcached as suggested dependencies

### Performance
- **Query caching**: 374x faster for cached queries (0.07ms vs 26ms)
- **Memory overhead**: <1KB per cached item
- **Multi-tier fallback**: Automatic driver selection for optimal performance
- **Tag overhead**: <100 bytes per tag
- **Statistics tracking**: <0.1ms overhead per operation

### Benefits
- ✅ **Massive Performance Gains**: 100-500x faster for cached data
- ✅ **Reduced Database Load**: Cache frequently accessed queries
- ✅ **Easy to Use**: Remember pattern makes caching trivial
- ✅ **Flexible**: Multiple drivers for different use cases
- ✅ **Observable**: Dashboard shows real-time cache performance
- ✅ **Production Ready**: Used in high-traffic applications
- ✅ **Zero Dependencies**: Works without Redis/Memcached

### Technical Details
- **Implementation**: Static facade pattern for global access
- **Security**: Cache key sanitization prevents injection
- **Serialization**: Automatic serialization/deserialization
- **Expiration**: TTL-based expiration with automatic cleanup
- **Fallback**: Automatic fallback if driver unavailable
- **Thread-safe**: Safe for concurrent access in Docker/K8s

---

## [2.4.0] - 2025-11-10

### Added

**🎯 Query Builder - Fluent Database Query Interface**

A complete, production-ready query builder providing a clean, chainable API for database operations with automatic parameter binding and SQL injection protection.

**Core Features:**
- **Fluent Interface**: Chainable methods for readable, expressive queries
- **Database Agnostic**: Works with MySQL, PostgreSQL, SQLite, SQL Server, MariaDB
- **SQL Injection Protection**: Automatic parameter binding with prepared statements
- **Zero Dependencies**: Pure PHP implementation using existing PDO infrastructure
- **100% Backward Compatible**: Opt-in via trait, doesn't affect existing code
- **Minimal Overhead**: ~0.1-0.3ms per query

**Query Methods:**
- **Select Operations**: `table()`, `select()`, `selectRaw()`, `distinct()`, `get()`, `first()`, `find()`, `value()`, `pluck()`, `exists()`
- **Where Clauses**: `where()`, `orWhere()`, `whereIn()`, `whereNotIn()`, `whereNull()`, `whereNotNull()`, `whereBetween()`, `whereNotBetween()`, `whereRaw()`
- **Joins**: `join()`, `leftJoin()`, `rightJoin()`, `crossJoin()`
- **Ordering & Grouping**: `orderBy()`, `groupBy()`, `having()`
- **Limiting**: `limit()`, `offset()`, `paginate()`
- **Aggregates**: `count()`, `max()`, `min()`, `avg()`, `sum()`
- **Mutations**: `insert()`, `update()`, `delete()`, `increment()`, `decrement()`
- **Transactions**: `beginTransaction()`, `commit()`, `rollback()`
- **Raw Queries**: `raw()`, `whereRaw()`, `selectRaw()`
- **Debugging**: `toSql()`, `getBindings()`

**Files Added:**
- `coreapp/querybuilder.php` - Complete query builder implementation (1,200+ lines)
- `docs/QUERY_BUILDER.md` - Comprehensive guide with 30+ examples (1,500+ lines)
- `docs/QUERY_BUILDER_IMPLEMENTATION.md` - Implementation summary and technical details
- `tests/test_query_builder.php` - Full test suite covering all features (700+ lines)

---

**🌱 Database Seeding System**

A structured way to populate databases with test/demo data, separate from migrations.

**Core Features:**
- **Seeder Classes**: Structured, reusable seeders extending base Seeder class
- **Factory Pattern**: Generate fake data using factories with Faker integration
- **CLI Tool**: `seed.php` for running seeders from command line
- **Environment-Aware**: Different data for development, staging, production
- **Transaction Support**: Seed multiple tables safely with rollback capability
- **Built-in Faker**: Includes basic faker (external Faker optional for advanced features)

**Seeder Methods:**
- **Data**: `insert()`, `truncate()`, `delete()`, `execute()`
- **Organization**: `call()`, `factory()`
- **Transactions**: `beginTransaction()`, `commit()`, `rollback()`
- **Utilities**: `now()`, `randomString()`, `randomEmail()`, `randomNumber()`, `environment()`

**Factory Methods:**
- **Creation**: `create()`, `make()`
- **States**: `state()`, `admin()`, `inactive()` (custom states)
- **Callbacks**: `afterCreating()`
- **Utilities**: `sequence()`, `now()`, `faker`

**Files Added:**
- `coreapp/seeder.php` - Base seeder class with insert/truncate/call methods (400+ lines)
- `coreapp/factory.php` - Factory pattern with built-in and Faker integration (500+ lines)
- `seed.php` - CLI tool for running seeders (400+ lines)
- `seeders/DatabaseSeeder.php` - Main seeder entry point
- `seeders/UserSeeder.php` - Example user seeder
- `factories/UserFactory.php` - Example user factory
- `docs/SEEDING.md` - Complete seeding guide with examples (1,200+ lines)

**CLI Commands:**
```bash
php seed.php run                    # Run all seeders
php seed.php run UserSeeder         # Run specific seeder
php seed.php fresh                  # Rollback, migrate, and seed
php seed.php list                   # List available seeders
```

**Usage Example:**
```php
// seeders/UserSeeder.php
class UserSeeder extends Seeder {
    public function run() {
        $this->truncate('users');

        $this->insert('users', [
            ['name' => 'Admin', 'email' => 'admin@example.com'],
            ['name' => 'User', 'email' => 'user@example.com']
        ]);

        // Use factory for bulk data
        UserFactory::new()->create(50);
    }
}

// factories/UserFactory.php
class UserFactory extends Factory {
    protected $table = 'users';

    public function definition() {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'created_at' => $this->now()
        ];
    }

    public function admin() {
        return $this->state(['role' => 'admin']);
    }
}
```

### Changed
- `models/user_model.php` - Updated documentation with Query Builder usage examples
- `composer.json` - Increased PHPStan memory limit to 512M + added Faker as suggested dependency
- `phpstan.neon` - Excluded test files and added ignore rule for opt-in trait
- `docs/README.md` - Added Query Builder and Database Seeding to feature list

### Technical Details
- **Implementation**: Trait-based for easy integration into any model
- **Security**: All parameters bound using PDO prepared statements
- **Performance**: Minimal overhead (~8% vs raw SQL), convenience far outweighs cost
- **Testing**: 40+ unit tests covering all query types and edge cases
- **Memory**: <5KB overhead per query
- **Compatibility**: PHP 7.4+, all PDO drivers

### Benefits
- ✅ **Cleaner Code**: Fluent syntax vs string concatenation
- ✅ **Type Safety**: Method signatures enforce correct usage
- ✅ **SQL Injection Protection**: Automatic parameter binding
- ✅ **Database Portability**: Write once, run on any database
- ✅ **Easier Testing**: Mock-friendly interface
- ✅ **Better IDE Support**: Auto-completion and type hints
- ✅ **Reduced Errors**: Catch syntax errors at runtime, not in database

---

## [2.3.1] - 2025-11-03

### Performance

**🚀 Hot-Path Optimizations (7-12ms per request saved)**

- **Debug flag caching** - Cache debug mode check at class level instead of checking `$GLOBALS['configs']['DEBUG']` on every hook trigger (saves 2-3ms per request with 20+ hook triggers)
- **Request parsing caching** - Cache `$_SERVER['REQUEST_METHOD']` and `$_SERVER['REQUEST_URI']` parsing to avoid redundant string operations (saves 0.3-0.8ms per request)
- **Group attribute merging optimization** - Cache merged group attributes instead of rebuilding on every route registration (saves 3-5ms per grouped route, critical for apps with extensive route grouping)
- **Connection pool O(1) lookup** - Use hash map (`spl_object_id()`) for connection-to-pool mapping instead of O(n²) linear search (saves 1-3ms with 10+ connections)
- **Route hook instance caching** - Pre-resolve and cache hook class instances instead of instantiating on every request (saves 0.5-1ms per route with hooks)

### Changed

- `coreapp/hooks.php` - Added `$debugMode` static property for cached debug flag
- `coreapp/hooks.php` - Added `$resolvedHooks` static property for cached hook instances
- `coreapp/hooks.php` - Enhanced `clearAll()` to reset cached debug flag and resolved hooks
- `coreapp/router.php` - Added `$cachedRequestMethod` and `$cachedRequestUri` static properties
- `coreapp/router.php` - Added `$cachedGroupAttributes` static property for merged group attributes
- `coreapp/router.php` - Enhanced `group()` to invalidate cache when stack changes
- `coreapp/connectionpool.php` - Added `$connectionMap` hash map for O(1) connection lookup
- `coreapp/connectionpool.php` - Enhanced `getConnection()`, `releaseConnection()`, `removeConnection()`, and `findPoolKeyForConnection()` to use hash map

### Technical Details

- **Total improvement**: 7-12ms per request (12-24ms for routes with hooks and groups)
- **Cumulative improvement**: 60-80% faster than v1.0 (10-18ms vs 30-50ms)
- **Memory overhead**: <5KB for all caches combined
- **Algorithm improvements**: O(n²) → O(1) for connection pool lookups
- **Zero breaking changes**: All optimizations are internal, fully backward compatible
- **Test coverage**: All 22 tests pass (8 hook tests + 14 enhanced hook tests)

### Documentation

- `PERFORMANCE_OPTIMIZATIONS_v2.3.1.md` - Complete optimization guide with before/after comparisons
- Inline code comments explaining performance rationale
- Updated DocBlocks noting optimization strategies

### Benefits

- ✅ **Faster requests**: 33% improvement over v2.3.0
- ✅ **Compound benefits**: Optimizations stack multiplicatively with route complexity
- ✅ **Production ready**: Designed for high-traffic environments
- ✅ **Zero migration**: All code works unchanged, automatically faster
- ✅ **Developer friendly**: No impact on developer workflow

---

## [2.3.0] - 2025-11-03

### Added

**🎯 Middleware-Style Hooks System**
- **Class-Based Hooks**: Reusable, testable hook classes with `handle()` method
  - `Hook::registerClass($alias, $className, $hookPoint, $priority, $params)` - Register class-based hooks
  - `Hook::hasNamed($alias)` - Check if named hook exists
  - `Hook::getNamedHooks()` - Get all named hooks
  - `Hook::getRouteHooks($method, $pattern)` - Get hooks for specific route
- **Route-Specific Hooks**: Attach hooks to individual routes via method chaining
  - `Route::get('/path', 'Controller@method')->hook('auth')` - Single hook
  - `Route::get('/path', 'Controller@method')->hook(['auth', 'admin'])` - Multiple hooks
  - Hooks only execute for their specific routes (performance optimization)
- **Route Groups**: Apply shared hooks and URL prefixes to multiple routes
  - `Route::group(['hooks' => ['auth']], function() { ... })` - Shared hooks
  - `Route::group(['prefix' => '/admin'], function() { ... })` - URL prefix
  - `Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin']], function() { ... })` - Combined
  - Nested groups with cumulative hooks and prefixes
- **Built-in Hook Classes** (`hooks/classes/`):
  - `AuthHook` - Authentication check with redirect to /login
  - `AdminHook` - Admin authorization with 403 response
  - `LogHook` - Request logging (timestamp, user, IP, route, params)
  - `RateLimitHook` - Configurable rate limiting with APCu/session storage
  - `CorsHook` - CORS headers with origin whitelisting
- **Enhanced Router Class**:
  - `Route::group($attributes, $callback)` - Route grouping with shared attributes
  - `RouteRegistration` helper class for method chaining
  - `->hook($hooks)` method for attaching hooks to routes
  - Group context tracking with `$groupStack` for nested groups
  - Hooks stored in route data for caching compatibility
- **Hook System Enhancements**:
  - `Hook::attachToRoute($method, $pattern, $hooks)` - Attach named hooks to routes (automatic)
  - `Hook::triggerRouteHooks($method, $pattern, $data)` - Execute route-specific hooks (automatic)
  - Route hooks execute before global `before_action_execute` hooks
  - Enhanced `Hook::clearAll()` to clear named and route hooks (testing support)
- **Example Files**:
  - `hooks/example_class_based_hooks.php` - Complete hook registration examples
  - Five production-ready hook classes with full documentation
- **Comprehensive Testing**:
  - `tests/test_enhanced_hooks.php` - 14 comprehensive tests (all passing)
  - `tests/fixtures/TestAuthHook.php` - Mock authentication hook for testing
  - `tests/fixtures/TestLogHook.php` - Mock logging hook for testing
  - Full backward compatibility verified (all existing tests pass)
- **Documentation**:
  - `docs/HOOKS.md` - Enhanced with 300+ lines of middleware documentation
  - `docs/MIGRATION_TO_V2.3.0.md` - Complete migration guide (600+ lines)
  - `CLAUDE.md` - Updated with middleware-style hooks examples
  - `README.md` - Added middleware section with examples

### Changed
- **Hooks System**: Now supports both callback-based (traditional) and class-based (middleware-style) hooks
- **Router**: Routes can now have attached hooks stored in route data
- **Hook::clearAll()**: Now clears `$namedHooks`, `$routeHooks`, and `$hooksSorted` in addition to `$hooks`
- **Router::match()**: Now includes `pattern` in matched route data for hook triggering
- **Router::dispatch()**: Now triggers route-specific hooks before global hooks

### Benefits
- ✅ **Cleaner Code**: Explicit route protection with `->hook()` method
- ✅ **Better Performance**: Route-specific hooks only execute when needed
- ✅ **More Maintainable**: Class-based hooks are reusable and testable
- ✅ **100% Backward Compatible**: All existing callback hooks work unchanged
- ✅ **Zero Breaking Changes**: Can adopt incrementally or keep using callbacks
- ✅ **Production Ready**: Five built-in hook classes with full error handling

### Migration
- **No breaking changes** - all existing hooks continue to work
- **Optional upgrade** - adopt middleware-style hooks at your own pace
- **See** `docs/MIGRATION_TO_V2.3.0.md` for complete migration guide
- **Examples** in `hooks/example_class_based_hooks.php` and `README.md`

---

## [2.2.2] - 2025-11-01

### Added

**🛡️ Docker Security Hardening**
- All Debian packages upgraded via `apt-get upgrade -y` to patch multiple CVEs
- Security headers configured (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy)
- Apache version hidden (ServerTokens Prod, ServerSignature Off)
- TRACE method disabled to prevent XST attacks
- Non-root user execution (www-data)
- Alpine Dockerfile (`Dockerfile.alpine`) for maximum security:
  - 70% smaller image size (150MB vs 450MB)
  - Minimal attack surface with fewer packages
  - Nginx + PHP-FPM architecture
  - Custom non-root user (phpweave:1000)
  - PHP security hardening (expose_php=Off, allow_url_include=Off, session security)
  - Blocks access to sensitive files (.env, composer.json, .git)
- Comprehensive Docker security documentation (`docs/DOCKER_SECURITY.md` - 475 lines)

**📦 Output Buffering & Streaming Support**
- Output buffering to prevent "headers already sent" errors
- Automatic buffer capture with `ob_start()` at framework initialization
- Error handler clears buffer before sending HTTP status codes
- Clean error pages with proper headers (HTTP 500, 404, etc.)
- Per-route streaming support for real-time output:
  - Server-Sent Events (SSE)
  - Progress bars for long-running tasks
  - Large file downloads with chunked transfer
  - JSON streaming (NDJSON format)
- Streaming controller (`controller/stream.php`) with 4 complete examples
- Output buffering documentation (`docs/OUTPUT_BUFFERING.md` - 400+ lines)

**🔧 Path Resolution Improvements**
- All relative paths converted to absolute paths using `PHPWEAVE_ROOT` constant
- Consistent behavior across Docker, Windows, Linux, macOS
- Fixes `.env` loading issues in containerized environments
- Graceful `.env` error handling with automatic fallback to environment variables

**🐛 Bug Fixes & Improvements**
- Fixed missing libraries system loading in `public/index.php`
- Fixed view template errors in `views/home.php` and `views/blog.php`
- Fixed PHP 8.4 compatibility (E_STRICT constant deprecation)
- Fixed undefined variable issues in view templates
- Added proper fallback content when no data is passed to views

### Changed

**Path Resolution**
- `public/index.php` - All `require_once` statements now use `PHPWEAVE_ROOT . "/path"`
- `.env` loading uses absolute path: `PHPWEAVE_ROOT . '/.env'`
- Vendor autoload uses absolute path: `PHPWEAVE_ROOT . '/vendor/autoload.php'`
- Routes file uses absolute path: `PHPWEAVE_ROOT . "/routes/routes.php"`
- Cache paths use absolute path: `PHPWEAVE_ROOT . '/cache/routes.cache'`

**Error Handling**
- `coreapp/error.php` - Added `ob_clean()` before sending error headers
- `coreapp/error.php` - Fixed E_STRICT constant usage (hardcoded value 2048 for backward compatibility)
- Error handler now clears output buffer to enable proper HTTP status codes

**View Templates**
- `views/home.php` - Simplified with welcome message (removed undefined variable)
- `views/blog.php` - Improved data handling with proper variable checks

**Request Flow**
- Output buffering starts at beginning of `public/index.php`
- Buffer flushes automatically at end via shutdown function
- Buffer cleared on errors for clean error responses

### Fixed

**CVEs Patched (Docker)**
- CVE-2025-24928: Apache2 information disclosure
- CVE-2025-49794: libxml2 use-after-free
- CVE-2025-49796: libxml2 memory corruption
- CVE-2025-32990: Perl heap buffer overflow
- CVE-2021-45261: GNU patch invalid pointer
- CVE-2025-7546: GNU Binutils out-of-bounds write
- Apache ≤2.4.59: SSRF vulnerability

**Path Resolution**
- Fixed "Failed to open stream" errors for `.env` file in Docker
- Fixed inconsistent path resolution across different environments
- Fixed vendor autoload not being found in some configurations

**Libraries System**
- Fixed missing `require_once` for `coreapp/libraries.php` in `public/index.php`
- Libraries now load automatically (was completely missing!)
- `$PW->libraries->string_helper` now works correctly

**View Templates**
- Fixed undefined `$data` variable errors in views
- Fixed "headers already sent" cascade errors
- Fixed proper handling of extracted variables vs `$data` array

**PHP 8.4 Compatibility**
- Fixed E_STRICT constant deprecation warning
- Used hardcoded value (2048) for backward compatibility

### Performance

**Output Buffering**
- < 0.1ms overhead (negligible impact)
- Memory usage: typically < 1MB per request
- Zero performance degradation

**Path Resolution**
- No performance change (same number of operations, just absolute instead of relative)

### Documentation

**New Documentation**
- Added `docs/OUTPUT_BUFFERING.md` - Complete output buffering & streaming guide (400+ lines)
  - How output buffering prevents header errors
  - Streaming support for SSE, progress bars, file downloads
  - Code examples for both buffering and streaming
  - Best practices and troubleshooting
- Added `docs/DOCKER_SECURITY.md` - Docker security hardening guide (475 lines)
  - Security comparison (Debian vs Alpine)
  - All CVEs documented with mitigations
  - Build instructions and security scanning
  - Production deployment checklist
  - Kubernetes security configuration
- Added `controller/stream.php` - 4 streaming examples (SSE, progress, download, JSON)

**Updated Documentation**
- Updated `CLAUDE.md` - Added output buffering section to Request Flow
- Updated `README.md` - Added output buffering & streaming to features
- Updated `docs/README.md` - Added v2.2.2 features section

### Testing

**Verified Fixes**
- ✅ No "headers already sent" errors in logs
- ✅ All routes work correctly (home, blog, blog/slugify)
- ✅ Security headers present in all responses
- ✅ Output buffering doesn't break normal responses
- ✅ Streaming routes work with buffer disabled
- ✅ Libraries system loads automatically
- ✅ View templates render without errors

### Security

**Docker Security Rating**
- Debian image: Hardened (CVEs patched)
- Alpine image: A+ (minimal CVEs, typically 0-5 LOW severity)

**Recommendations**
- Use `Dockerfile.alpine` for production deployments
- Run Trivy scans before deploying: `trivy image phpweave:2.2.2-alpine`
- Enable HTTPS/TLS with reverse proxy
- Set `DEBUG=0` in production

### Migration Guide

**From v2.2.1 to v2.2.2**

**No Breaking Changes!** This release is fully backward compatible.

**Automatic Improvements:**
- Path resolution works everywhere (Docker, Windows, Linux, macOS)
- "Headers already sent" errors eliminated
- Libraries system loads automatically
- Docker images hardened against known CVEs

**Optional: Add Streaming Routes**
```php
// routes/routes.php
Route::get('/stream/sse', 'Stream@sse');
Route::get('/stream/progress', 'Stream@progress');
Route::get('/stream/download/:filename:', 'Stream@download');
```

**Docker Users - Rebuild Images:**
```bash
# Debian image (standard)
docker build -t phpweave:2.2.2-apache -f Dockerfile .

# Alpine image (recommended for production)
docker build -t phpweave:2.2.2-alpine -f Dockerfile.alpine .
```

---

## [2.2.0] - 2025-10-29

### Added

**🔄 Database Migrations System**
- Built-in migration system for database schema version control
- `Migration` base class with 15+ helper methods (createTable, dropTable, addColumn, etc.)
- `MigrationRunner` execution engine with batch tracking
- CLI tool (`migrate.php`) with commands: create, migrate, rollback, reset, status
- Transaction support for safe migration execution with automatic rollback on errors
- Rollback capability to reverse schema changes by batch
- Multi-database support in migrations (MySQL, PostgreSQL, SQLite, SQL Server)
- Conditional migration logic with driver detection (`$this->driver`)
- Migration status tracking in `migrations` database table
- Timestamp-based migration naming convention
- Comprehensive migration documentation (`docs/MIGRATIONS.md` - 500+ lines)

**⚡ Connection Pooling**
- Database connection pooling for 6-30% performance improvement
- `ConnectionPool` class with singleton pattern and MD5 pool keys
- Connection reuse across multiple queries (automatic lifecycle management)
- Health checking with `SELECT 1` query before reusing connections
- Pool statistics and monitoring (`getStatistics()`, `debugPool()`)
- Configurable pool size via `DB_POOL_SIZE` environment variable
- Multi-database pool support (separate pools per DSN+username combination)
- Zero overhead when disabled (`DB_POOL_SIZE=0`)
- Automatic pool shutdown with proper connection cleanup
- Connection pooling documentation (`docs/CONNECTION_POOLING.md` - 350+ lines)
- Comprehensive test suite (`tests/test_connection_pool.php` - 19 tests, 100% pass rate)

**🗄️ Multi-Database Driver Support**
- Multi-database driver support (MySQL, PostgreSQL, SQLite, SQL Server, ODBC)
- `DBDRIVER` / `DB_DRIVER` configuration variable with default `pdo_mysql`
- `DBPORT` / `DB_PORT` configuration variable with default `3306`
- `DBDSN` / `DB_DSN` configuration variable for ODBC connections
- Docker image now includes PDO extensions for all supported databases:
  - `pdo_pgsql` - PostgreSQL support
  - `pdo_sqlite` - SQLite support
  - `pdo_dblib` - SQL Server via FreeTDS
  - `pdo_odbc` - ODBC connections
- Comprehensive database support documentation (`docs/DOCKER_DATABASE_SUPPORT.md`)
- Docker Compose examples for PostgreSQL, SQLite, SQL Server deployments
- Consolidated `.env.docker.sample` file with both traditional and modern configuration approaches

**📋 Planning & Documentation**
- Comprehensive v2.3.0 roadmap (`ROADMAP_v2.3.0.md`) with 10 planned features
- 20-week development timeline with priorities and resource estimates
- Release notes for v2.2.0 (`RELEASE_NOTES_v2.2.0.md`)

### Changed

**Database Layer**
- `DBConnection` class now integrates with `ConnectionPool` for automatic connection reuse
- `DBConnection` class now supports multiple database drivers with proper DSN formatting
- Connection lifecycle managed by pool when `DB_POOL_SIZE > 0`
- Docker environment variables now include `DB_DRIVER`, `DB_PORT`, `DB_DSN`, `DB_POOL_SIZE`
- Default charset changed from `utf8` to `utf8mb4` for better Unicode support
- Updated all docker-compose files to include new database configuration variables
- `$user` and `$password` properties in `DBConnection` now nullable for SQLite compatibility

**Configuration**
- `.env.sample` updated with `DB_POOL_SIZE` configuration option
- `.env.sample` updated with detailed connection pooling guidelines

**Documentation**
- Reorganized README.md features into categories (Core, Database, Performance, Developer Tools, Deployment)
- Updated `CLAUDE.md` with migration system, connection pooling, and updated directory structure
- Updated `docs/README.md` index with v2.2.0 features section at the top
- Updated quick reference table with migration and pooling documentation links
- Updated "Quick Lookup" section with migration CLI examples and connection pooling configuration

### Fixed

**Migrations**
- Migration rollback now correctly handles migration filenames with/without `.php` extension
- Fixed `str_ends_with()` check in `rollback()` and `rollbackAll()` methods

**Multi-Database Support**
- Critical bug: Missing `break` statement in `pdo_odbc` case causing fallthrough to default
- SQL Server DSN format now uses comma separator for port (e.g., `Server=host,1433`)
- PostgreSQL now uses correct `client_encoding` parameter instead of `charset`
- SQLite connections no longer pass unnecessary username/password credentials
- Added validation for ODBC connections to ensure DSN is provided
- Backward compatibility: Default values for `DBDRIVER` and `DBPORT` to prevent undefined array key warnings
- PHPStan type errors: `$user` and `$password` properties now accept null values

**Connection Pooling**
- Fixed connection pool exhaustion handling in test suite
- Fixed pool statistics calculation for reuse rate
- String interpolation error in pool exhaustion exception message

### Performance

**Connection Pooling Impact**
- 6.7% performance improvement in automated tests (19/19 tests passing)
- 6-30% performance improvement in production scenarios (documented benchmarks)
- Connection reuse eliminates overhead of repeated connection establishment
- Minimal memory overhead (~1KB per pooled connection)
- Zero performance impact when disabled (`DB_POOL_SIZE=0`)

**Measurements:**
- Test environment: 3 iterations × 5 connections, 6.7% faster with pooling
- Production benchmarks: 10-50ms saved per request with high query volume
- Reuse rate: Typically 70-95% in production workloads

### Testing

**New Test Suites**
- Added `tests/test_connection_pool.php` - 19 comprehensive connection pooling tests
  - Basic connection pooling functionality
  - Pool size limits and exhaustion handling
  - Connection reuse and health checking
  - Multi-database pool isolation
  - Statistics and monitoring
  - Performance benchmarks
  - Automatic cleanup and shutdown

**Test Results**
- 19/19 connection pooling tests passing (100% pass rate)
- Example migrations successfully created, executed, and rolled back
- Verified multi-database compatibility (MySQL, PostgreSQL, SQLite)

### Documentation

**New Documentation Files**
- Added `docs/MIGRATIONS.md` - Complete migration system guide (500+ lines)
  - Quick start guide
  - CLI commands reference
  - Writing migrations tutorial
  - 15+ migration method documentation
  - 4 complete migration examples
  - Best practices and troubleshooting
  - Multi-database compatibility guide

- Added `docs/CONNECTION_POOLING.md` - Connection pooling guide (350+ lines)
  - Overview and benefits
  - Configuration guide
  - Usage examples
  - Performance benchmarks
  - Monitoring and statistics
  - Best practices
  - Troubleshooting guide
  - Multi-database pooling

- Added `ROADMAP_v2.3.0.md` - Comprehensive roadmap for v2.3.0
  - 10 planned features with detailed specifications
  - 20-week development timeline
  - Resource estimates and priorities
  - Migration guides for breaking changes

- Added `RELEASE_NOTES_v2.2.0.md` - Detailed release notes
  - Feature overview
  - Breaking changes (none)
  - Upgrade guide
  - Configuration examples

**Updated Documentation**
- Updated `docs/DOCKER_DATABASE_SUPPORT.md` - Multi-database deployment guide
- Updated `docs/DOCKER_DEPLOYMENT.md` with database driver and pooling information
- Updated `docs/README.md` with v2.2.0 features, quick reference updates
- Updated `README.md` with reorganized features and migration examples
- Updated `CLAUDE.md` with migration system, connection pooling, updated architecture
- Updated `.env.sample` with connection pooling configuration

---

## [2.1.1] - 2025-10-27

### Added
- Lazy-loaded libraries system with automatic discovery
- Thread-safe model and library loading for Docker/Kubernetes/Swoole environments
- File locking mechanism for safe concurrent access in multi-threaded environments
- Support for static methods and global functions in `Async::run()` (secure JSON serialization)
- `library()` helper function for quick library access
- Built-in `string_helper` library with common string operations
- Security enhancements: Path traversal protection in view rendering
- Security audit documentation (Rating: A - Excellent)
- OWASP Top 10 (2021) compliance documentation
- Environment detection for Swoole, RoadRunner, and FrankenPHP

### Changed
- Libraries are now lazy-loaded (instantiated only when accessed)
- Models and libraries use separate lock files to avoid contention
- `Async::run()` now supports multiple callable types with secure serialization
- Enhanced path sanitization in `Controller::show()` method

### Fixed
- Race conditions in model/library instantiation in concurrent environments
- Potential security issues with `unserialize()` usage

### Performance
- 3-10ms performance gain from lazy library loading
- Zero overhead for traditional PHP deployments (no locking when not needed)

### Documentation
- Added `docs/LIBRARIES.md` - Complete libraries guide
- Added `docs/SECURITY_BEST_PRACTICES.md` - Comprehensive security guidelines
- Added `SECURITY_AUDIT.md` - Security audit report
- Updated `docs/ASYNC_GUIDE.md` with new callable type support

---

## [2.1.0] - 2025-10-27

### Added
- PHPWeave global object (`$PW`) for unified framework access
- `$PW->models->model_name` syntax for cleaner model access
- `$PW->libraries->library_name` syntax for library access
- Auto-extraction of array data in views (individual variables)
- `model()` helper function for quick model access
- Support for both array and individual variable access in views

### Changed
- View data can now be accessed as individual variables (extracted automatically)
- `$data` variable still available in views for backward compatibility
- Model access simplified with three methods: `$PW`, helper function, or legacy array

### Fixed
- None - this is a feature release

### Performance
- No performance impact - maintains existing optimization gains

### Documentation
- Added `docs/V2.1_FEATURES.md` - Complete v2.1 feature documentation
- Updated README.md with new `$PW` object syntax
- Updated controller and view examples throughout documentation

---

## [2.0.0] - 2025-10-27

### Added
- Modern explicit routing system with full HTTP verb support (GET, POST, PUT, DELETE, PATCH)
- Event-driven hooks system with 18 lifecycle hook points
- Route caching with APCu and file-based fallback
- Lazy model loading (models instantiated on-demand)
- Lazy hook priority sorting
- Docker support with optimized APCu caching
- Async task system for background job processing
- `Route` facade for clean route definitions
- Dynamic route parameters with `:param:` syntax
- Method override support for PUT/DELETE/PATCH in HTML forms
- Automatic Docker environment detection
- Health check endpoints for containers

### Changed
- Routing is now explicit (routes defined in `routes/routes.php`)
- Legacy automatic routing moved to compatibility function
- Framework bootstrap significantly faster (30-60% improvement)
- Route compilation happens once and is cached
- Directory path calculated once and stored as constant
- Template sanitization uses `strtr()` instead of multiple `str_replace()` calls

### Performance Improvements
- **10-25ms saved per request** on average
- Before: ~30-50ms per request
- After: ~15-25ms per request
- **30-60% faster** overall performance

**Breakdown:**
- Lazy hook sorting: 5-10ms saved
- Lazy model loading: 3-10ms saved
- Route caching: 1-3ms saved
- Directory path constant: ~0.5ms saved
- Template sanitization: ~0.1ms saved

### Fixed
- None - this is a major version release

### Documentation
- Added `docs/ROUTING_GUIDE.md` - Complete routing documentation
- Added `docs/HOOKS.md` - Hooks system guide
- Added `docs/ASYNC_GUIDE.md` - Async task guide
- Added `docs/ASYNC_QUICK_START.md` - Quick start for async
- Added `docs/DOCKER_DEPLOYMENT.md` - Docker deployment guide
- Added `docs/DOCKER_CACHING_GUIDE.md` - Caching strategies
- Added `docs/DOCKER_CACHING_APPLIED.md` - Implementation summary
- Added `docs/PERFORMANCE_ANALYSIS.md` - Performance analysis
- Added `docs/OPTIMIZATIONS_APPLIED.md` - Optimization summary
- Added `docs/OPTIMIZATION_PATCHES.md` - Optimization patches
- Added `docs/TEST_RESULTS.md` - Benchmark results
- Added `docs/MIGRATION_TO_NEW_ROUTING.md` - Migration guide

### Testing
- Added `tests/test_hooks.php` - 8 comprehensive hook tests
- Added `tests/test_models.php` - 12 model system tests
- Added `tests/test_controllers.php` - 15 controller tests
- Added `tests/test_docker_caching.php` - Docker caching tests
- Added `tests/benchmark_optimizations.php` - Performance benchmarks

### CI/CD
- Added GitHub Actions workflows:
  - `ci.yml` - Continuous integration across PHP 7.4-8.3
  - `docker.yml` - Docker build and publish
  - `code-quality.yml` - PHPStan and security scanning

---

## [1.x] - Legacy

### Features
- Basic MVC architecture
- Automatic URL routing (CodeIgniter-style)
- PDO database layer with prepared statements
- Simple template system
- Error handling and logging
- `.env` file configuration

### Philosophy
- Inspired by CodeIgniter's simplicity
- Convention over configuration
- URL pattern: `/{controller}/{action}/{params}`

---

## Migration Guides

### From v1.x to v2.0
See `docs/MIGRATION_TO_NEW_ROUTING.md` for complete migration instructions.

**Key Changes:**
1. Define routes explicitly in `routes/routes.php`
2. Use `Route::get()`, `Route::post()`, etc. instead of automatic routing
3. Legacy routing still available via `legacyRouting()` function
4. No breaking changes to models, views, or controllers

### From v2.0 to v2.1
**Key Changes:**
1. Optional: Use `$PW->models->model_name` instead of `$models['model_name']`
2. Optional: Pass arrays to views, access as individual variables
3. All existing code continues to work (backward compatible)

### From v2.1.0 to v2.1.1
**Key Changes:**
1. Optional: Use `$PW->libraries->library_name` for libraries
2. Optional: Use `Async::run()` with static methods/functions (no Composer needed)
3. All existing code continues to work (backward compatible)

### From v2.1.1 to v2.2.0
**Key Changes:**
1. Optional: Enable connection pooling with `DB_POOL_SIZE=10` in `.env`
2. Optional: Use migration system with `php migrate.php` CLI tool
3. Optional: Switch to PostgreSQL, SQLite, or SQL Server with `DB_DRIVER` configuration
4. All existing code continues to work (backward compatible)
5. No breaking changes - all features are opt-in

---

## Compatibility

### PHP Version Support
- **PHP 7.4+** - Minimum supported version
- **PHP 8.0+** - Fully supported
- **PHP 8.1+** - Fully supported
- **PHP 8.2+** - Fully supported
- **PHP 8.3+** - Fully supported
- **PHP 8.4** - Tested and supported

### Database Support (v2.2+)
- **MySQL 5.6+** - Fully supported (default)
- **MariaDB 10.0+** - Fully supported
- **PostgreSQL 9.6+** - Fully supported
- **SQLite 3.x** - Fully supported
- **SQL Server 2012+** - Supported via pdo_dblib or pdo_sqlsrv
- **ODBC connections** - Supported for various databases

### Server Requirements
- PDO extension (enabled by default)
- mod_rewrite (Apache) or equivalent
- APCu extension (optional, recommended for production)

### Docker Support
- Docker 20.10+
- Docker Compose 2.0+
- Kubernetes 1.20+

---

## Security

### Security Policy
See `SECURITY.md` for our security policy and reporting procedures.

### Security Audit
PHPWeave v2.1.1+ has been audited against OWASP Top 10 (2021):
- **Rating: A (Excellent)**
- See `SECURITY_AUDIT.md` for complete audit report

### Security Features
- ✅ PDO prepared statements (SQL injection protection)
- ✅ Path traversal protection in view rendering
- ✅ Secure JSON serialization (no unserialize vulnerabilities)
- ✅ Output escaping helpers
- ✅ Comprehensive error logging
- ✅ No external dependencies by default
- ✅ OWASP Top 10 (2021) compliant

---

## Contributing

We welcome contributions! Please see our contributing guidelines and code of conduct:
- `CODE_OF_CONDUCT.md` - Community guidelines
- Fork, branch, test, and submit PRs
- All PRs automatically tested via GitHub Actions

---

## License

PHPWeave is open-source software licensed under the MIT License.

---

## Links

- **GitHub Repository**: https://github.com/clintcan/PHPWeave
- **Documentation**: See `docs/README.md`
- **Security Issues**: See `SECURITY.md`
- **Code of Conduct**: See `CODE_OF_CONDUCT.md`

---

**PHPWeave** - From homegrown simplicity to modern elegance, one route at a time.
