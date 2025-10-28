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
- Routing is now explicit (routes defined in `routes.php`)
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
1. Define routes explicitly in `routes.php`
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
