# PHPWeave Documentation

Complete documentation for the PHPWeave framework.

---

## 📚 Table of Contents

### Getting Started

- [**README.md**](../README.md) - Main project overview and quick start (in root)

### Core Features

#### Version 2.2.2 Features (LATEST!)

- **Optional Composer Support** - Automatic third-party package loading (zero-dependency core maintained)
- **Production-Ready HTTP Async Library** - Concurrent HTTP client with OWASP security (3-10x performance)
- **Output Buffering & Streaming** - Prevents "headers already sent" errors with streaming support ⭐ NEW!
- [**COMPOSER_USAGE.md**](COMPOSER_USAGE.md) - Complete Composer integration guide
- [**HTTP_ASYNC_GUIDE.md**](HTTP_ASYNC_GUIDE.md) - HTTP async library documentation
- [**HTTP_ASYNC_SECURITY.md**](HTTP_ASYNC_SECURITY.md) - Security best practices
- [**HTTP_ASYNC_PRODUCTION.md**](HTTP_ASYNC_PRODUCTION.md) - Production configuration guide
- [**OUTPUT_BUFFERING.md**](OUTPUT_BUFFERING.md) - Output buffering & streaming guide ⭐ NEW!

#### Version 2.2.0 Features

- **Built-in Migrations** - Database schema version control with rollback support
- **Connection Pooling** - 6-30% performance improvement with automatic connection reuse
- **Multi-Database Support** - MySQL, PostgreSQL, SQLite, SQL Server, ODBC
- [**MIGRATIONS.md**](MIGRATIONS.md) - Complete migration system documentation
- [**CONNECTION_POOLING.md**](CONNECTION_POOLING.md) - Connection pooling guide
- [**DOCKER_DATABASE_SUPPORT.md**](DOCKER_DATABASE_SUPPORT.md) - Multi-database deployment guide

#### Version 2.1.1 Features

- **Lazy-Loaded Libraries** - Reusable utility classes loaded on-demand (3-10ms performance gain)
- **Thread-Safe Model/Library Loading** - File locking for Docker/Kubernetes environments
- **Security Enhancements** - Path traversal protection, secure deserialization, OWASP Top 10 compliant
- **Multiple Async Callable Types** - Static methods, global functions, and closures support
- [**LIBRARIES.md**](LIBRARIES.md) - Complete libraries documentation

#### Version 2.1 Features

- **PHPWeave Global Object** (`$PW`) - Unified access to models and libraries
- **Auto-Extracted View Variables** - Array data automatically extracted in views
- **Enhanced Model Loading** - Lazy loading with multiple access patterns
- [**V2.1_FEATURES.md**](V2.1_FEATURES.md) - Complete v2.1 feature documentation

#### Routing System

- [**ROUTING_GUIDE.md**](ROUTING_GUIDE.md) - Complete routing documentation
- [**MIGRATION_TO_NEW_ROUTING.md**](MIGRATION_TO_NEW_ROUTING.md) - Migrating from legacy routing

#### Hooks System

- [**HOOKS.md**](HOOKS.md) - Complete hooks documentation with all 18 hook points

#### Models System

- [**MODELS.md**](MODELS.md) - Complete models documentation with database operations

#### Session Management

- [**SESSIONS.md**](SESSIONS.md) - Complete session management guide (file and database sessions) ⭐ NEW!

#### Async/Jobs System

- [**ASYNC_GUIDE.md**](ASYNC_GUIDE.md) - Complete async job processing guide (Updated v2.1.1)
- [**ASYNC_QUICK_START.md**](ASYNC_QUICK_START.md) - Quick start for async jobs

#### Security

- **Security Rating: A (Excellent)**
- **OWASP Top 10 (2021) Compliant**
- **Automated Security Analysis: PHPStan + Psalm**

- [**PSALM_CONFIGURATION_SUMMARY.md**](PSALM_CONFIGURATION_SUMMARY.md) - Complete Psalm security configuration and current status ⭐ LATEST!
- [**SECURITY_ANALYSIS.md**](SECURITY_ANALYSIS.md) - Psalm security analysis guide (SQL injection, XSS, path traversal detection)
- [**PSALM_SETUP_COMPLETE.md**](PSALM_SETUP_COMPLETE.md) - Psalm setup summary and quick reference
- [**SECURITY_BEST_PRACTICES.md**](SECURITY_BEST_PRACTICES.md) - Comprehensive security guidelines for developers
- [**SECURITY_AUDIT.md**](../SECURITY_AUDIT.md) - OWASP Top 10 security audit report (Rating: A)

---

## 🚀 Performance & Optimization

### Performance Documentation

- [**PERFORMANCE_ANALYSIS.md**](PERFORMANCE_ANALYSIS.md) - Detailed performance analysis and bottlenecks
- [**OPTIMIZATION_PATCHES.md**](OPTIMIZATION_PATCHES.md) - Ready-to-apply optimization patches
- [**OPTIMIZATIONS_APPLIED.md**](OPTIMIZATIONS_APPLIED.md) - Summary of applied optimizations
- [**TEST_RESULTS.md**](TEST_RESULTS.md) - Performance test results

### Performance Improvements Applied

#### Version 2.1.1 (Latest)
- ✅ Lazy library loading (3-10ms saved)
- ✅ Thread-safe model/library instantiation (Docker/K8s optimized)
- ✅ Enhanced path sanitization (security + performance)

#### Version 2.1
- ✅ Lazy hook priority sorting (5-10ms saved)
- ✅ Lazy model loading (3-10ms saved)
- ✅ Route caching (1-3ms saved)
- ✅ Directory path caching (~0.5ms saved)
- ✅ Template sanitization optimization (~0.1ms saved)

**Total: 40-70% faster response times** (v2.1.1)

---

## 🐳 Docker & Deployment

### Docker Documentation

- [**DOCKER_DEPLOYMENT.md**](DOCKER_DEPLOYMENT.md) - Complete Docker deployment guide
- [**DOCKER_DATABASE_SUPPORT.md**](DOCKER_DATABASE_SUPPORT.md) - Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server) ⭐ NEW!
- [**DOCKER_CACHING_GUIDE.md**](DOCKER_CACHING_GUIDE.md) - Caching strategies for Docker
- [**DOCKER_CACHING_APPLIED.md**](DOCKER_CACHING_APPLIED.md) - Docker caching implementation summary

### Docker Features

- ✅ APCu in-memory caching (optimal for containers)
- ✅ Automatic Docker detection
- ✅ Multi-container support with load balancing
- ✅ Read-only filesystem compatible
- ✅ Kubernetes ready
- ✅ Thread-safe model/library loading (v2.1.1)
- ✅ Swoole/RoadRunner/FrankenPHP compatible (v2.1.1)
- ✅ Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server) ⭐ NEW!

---

## 📖 Documentation by Topic

### 🎯 Quick Reference

**I want to...**

| Task                        | Documentation                                              |
| --------------------------- | ---------------------------------------------------------- |
| Get started quickly         | [README.md](../README.md)                                  |
| Learn v2.1 features         | [V2.1_FEATURES.md](V2.1_FEATURES.md)                       |
| Use migrations              | [MIGRATIONS.md](MIGRATIONS.md)                             |
| Enable connection pooling   | [CONNECTION_POOLING.md](CONNECTION_POOLING.md)             |
| Define routes               | [ROUTING_GUIDE.md](ROUTING_GUIDE.md)                       |
| Add hooks                   | [HOOKS.md](HOOKS.md)                                       |
| Work with models            | [MODELS.md](MODELS.md)                                     |
| Manage sessions             | [SESSIONS.md](SESSIONS.md)                                 |
| Create utility libraries    | [LIBRARIES.md](LIBRARIES.md)                               |
| Process background jobs     | [ASYNC_QUICK_START.md](ASYNC_QUICK_START.md)               |
| Deploy to Docker            | [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md)               |
| Optimize performance        | [OPTIMIZATIONS_APPLIED.md](OPTIMIZATIONS_APPLIED.md)       |
| Migrate from legacy routing | [MIGRATION_TO_NEW_ROUTING.md](MIGRATION_TO_NEW_ROUTING.md) |
| Secure my application       | [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md)   |
| Run security analysis       | [SECURITY_ANALYSIS.md](SECURITY_ANALYSIS.md)                |
| View security audit         | [SECURITY_AUDIT.md](../SECURITY_AUDIT.md)                  |

---

### 🏗️ Architecture

**Understanding the Framework:**

1. **Request Lifecycle:**

   ```
   Request → Router → Controller → Model → View → Response
              ↓
           Hooks (18 points)
   ```

2. **Core Components:**

   - `Router` - Modern routing with dynamic parameters + JSON cache (v2.1.1)
   - `Controller` - Base controller with path traversal protection (v2.1.1)
   - `DBConnection` - PDO-based database connection with connection pooling (v2.2.0)
   - `ConnectionPool` - Database connection pooling for performance (v2.2.0)
   - `Migration` - Database schema version control base class (v2.2.0)
   - `MigrationRunner` - Migration execution and rollback engine (v2.2.0)
   - `Hook` - Event-driven hooks system
   - `Models` - Lazy-loaded models with thread safety (v2.1.1)
   - `Libraries` - Lazy-loaded utility classes (v2.1.1)
   - `Async` - Background job processing with multiple callable types (v2.1.1)
   - `ErrorClass` - Error handling and logging

3. **Hook Points:** 18 lifecycle hooks for extending functionality

---

### 🔧 Development

**For Developers:**

- [ROUTING_GUIDE.md](ROUTING_GUIDE.md) - Route patterns and methods
- [HOOKS.md](HOOKS.md) - All available hooks with examples
- [MODELS.md](MODELS.md) - Database models and operations
- [LIBRARIES.md](LIBRARIES.md) - Creating reusable utility libraries
- [ASYNC_GUIDE.md](ASYNC_GUIDE.md) - Job queues and workers
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Security guidelines
- [SECURITY_ANALYSIS.md](SECURITY_ANALYSIS.md) - Automated security scanning ⭐ NEW!

---

### 🚀 Deployment

**For DevOps:**

- [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) - Docker setup

  - Standard (single container)
  - Development (hot-reload)
  - Scaled (load balanced)
  - Kubernetes examples

- [DOCKER_CACHING_GUIDE.md](DOCKER_CACHING_GUIDE.md) - Caching strategies
  - APCu vs File cache
  - Multi-container considerations
  - Read-only filesystems

---

### ⚡ Performance

**For Optimization:**

- [PERFORMANCE_ANALYSIS.md](PERFORMANCE_ANALYSIS.md) - Bottleneck analysis
- [OPTIMIZATION_PATCHES.md](OPTIMIZATION_PATCHES.md) - How to apply patches
- [OPTIMIZATIONS_APPLIED.md](OPTIMIZATIONS_APPLIED.md) - What's been done
- [TEST_RESULTS.md](TEST_RESULTS.md) - Benchmark results

---

## 📂 Documentation Structure

```
PHPWeave/
├── README.md                          # Main overview
│
├── docs/                              # All documentation
│   ├── README.md                      # This file
│   │
│   ├── # Core Features
│   ├── V2.1_FEATURES.md               # v2.1 features
│   ├── MIGRATIONS.md                  # Migration system (v2.2.0)
│   ├── CONNECTION_POOLING.md          # Connection pooling (v2.2.0)
│   ├── ROUTING_GUIDE.md              # Routing system
│   ├── MIGRATION_TO_NEW_ROUTING.md   # Migration guide
│   ├── HOOKS.md                       # Hooks system (18 points)
│   ├── MODELS.md                      # Models system
│   ├── LIBRARIES.md                   # Libraries system
│   ├── ASYNC_GUIDE.md                 # Async jobs (detailed)
│   ├── ASYNC_QUICK_START.md           # Async jobs (quick)
│   │
│   ├── # Security
│   ├── SECURITY_BEST_PRACTICES.md     # Security guidelines
│   ├── SECURITY_ANALYSIS.md           # Psalm security analysis ⭐ NEW!
│   ├── PSALM_SETUP_COMPLETE.md        # Psalm setup summary ⭐ NEW!
│   │
│   ├── # Performance
│   ├── PERFORMANCE_ANALYSIS.md        # Analysis
│   ├── OPTIMIZATION_PATCHES.md        # Patches
│   ├── OPTIMIZATIONS_APPLIED.md       # Applied
│   ├── TEST_RESULTS.md                # Results
│   │
│   └── # Docker
│       ├── DOCKER_DEPLOYMENT.md       # Deployment guide
│       ├── DOCKER_CACHING_GUIDE.md    # Caching strategies
│       └── DOCKER_CACHING_APPLIED.md  # Implementation
│
├── tests/                             # Test scripts
│   ├── README.md                      # Testing guide
│   ├── test_hooks.php                 # Hooks tests
│   ├── test_path_traversal.php        # Security tests (NEW!)
│   ├── test_docker_caching.php        # Caching tests
│   └── benchmark_optimizations.php    # Benchmarks
│
└── SECURITY_AUDIT.md                  # Security audit report (NEW!)
```

---

## 🎓 Learning Path

### Beginner

1. Read [README.md](../README.md) - Overview
2. Read [V2.1_FEATURES.md](V2.1_FEATURES.md) - v2.1 features (PHPWeave global object)
3. Read [ROUTING_GUIDE.md](ROUTING_GUIDE.md) - Define routes
4. Read [MODELS.md](MODELS.md) - Work with database models
5. Read [LIBRARIES.md](LIBRARIES.md) - v2.1.1 lazy-loaded libraries
6. Read [HOOKS.md](HOOKS.md) - Add custom logic
7. Read [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Security basics (v2.1.1)

### Intermediate

8. Read [ASYNC_QUICK_START.md](ASYNC_QUICK_START.md) - Background jobs
9. Read [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) - Docker deployment
10. Read [OPTIMIZATIONS_APPLIED.md](OPTIMIZATIONS_APPLIED.md) - Performance

### Advanced

11. Read [PERFORMANCE_ANALYSIS.md](PERFORMANCE_ANALYSIS.md) - Optimization
12. Read [DOCKER_CACHING_GUIDE.md](DOCKER_CACHING_GUIDE.md) - Caching strategies
13. Read [ASYNC_GUIDE.md](ASYNC_GUIDE.md) - Advanced async patterns
14. Read [SECURITY_AUDIT.md](../SECURITY_AUDIT.md) - Security audit report

---

## 🔍 Quick Lookup

### Common Tasks

**Routing:**

```php
Route::get('/blog/:id:', 'Blog@show');        // Dynamic parameter
Route::post('/blog', 'Blog@store');           // POST request
Route::any('/webhook', 'Webhook@handle');     // Any method
```

**Hooks:**

```php
Hook::register('before_action_execute', function($data) {
    // Authentication check
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt();
        exit;
    }
    return $data;
}, 5);
```

**Async Jobs (v2.1.1 - Multiple Callable Types):**

```php
// Static method (recommended - no library needed)
Async::run(['EmailTasks', 'sendWelcome']);

// Global function (no library needed)
Async::run('send_notification');

// Closure (requires opis/closure)
Async::run(function() { /* ... */ });

// Job class (best for production)
Async::queue('SendEmailJob', ['to' => 'user@example.com']);
```

**Migrations (v2.2.0):**

```bash
# Create migration
php migrate.php create create_users_table

# Run pending migrations
php migrate.php migrate

# Rollback last batch
php migrate.php rollback

# Check status
php migrate.php status
```

```php
// In migration file
class CreateUsersTable extends Migration {
    public function up() {
        $this->createTable('users', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'email' => 'VARCHAR(255) NOT NULL UNIQUE',
            'password' => 'VARCHAR(255) NOT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);
    }

    public function down() {
        $this->dropTable('users');
    }
}
```

**Connection Pooling (v2.2.0):**

```ini
# .env configuration
DB_POOL_SIZE=10  # Enable pooling with 10 max connections

# Disable pooling
DB_POOL_SIZE=0
```

```php
// Get pool statistics
$stats = ConnectionPool::getStatistics();
echo "Reuse rate: " . $stats['reuse_rate'] . "%";
```

**Models (v2.1):**

```php
global $PW;
$user = $PW->models->user_model->getUser($id);

// Or use helper function
$user = model('user_model')->getUser($id);
```

**Libraries (v2.1.1 - Lazy Loaded):**

```php
global $PW;
$slug = $PW->libraries->string_helper->slugify("Hello World");
$preview = $PW->libraries->string_helper->truncate($text, 200);
$token = $PW->libraries->string_helper->random(16);

// Or use helper function
$slug = library('string_helper')->slugify("Hello World");

// Thread-safe in Docker/Kubernetes/Swoole
```

**Views (v2.1):**

```php
// Controller
$this->show('profile', [
    'username' => $user->name,
    'email' => $user->email
]);

// View - direct variable access
<h1><?php echo $username; ?></h1>
<p><?php echo $email; ?></p>
```

---

## 📊 Performance Metrics

### Version 2.1.1 (Latest)

**After All Optimizations (includes libraries, security fixes):**
- Framework bootstrap: ~5-8ms
- With 10 hooks: ~7-10ms
- With 20 models (lazy): ~7-10ms
- With 10 libraries (lazy): ~7-10ms
- **Total:** ~12-20ms per request

### Version 2.1

- Framework bootstrap: ~5-10ms
- With 10 hooks: ~8-12ms
- With 20 models (lazy): ~8-12ms
- **Total:** ~15-25ms per request

### Version 1.0 (Before Optimizations)

- Framework bootstrap: ~15-25ms
- With 10 hooks: ~20-30ms
- With 20 models (eager): ~25-35ms
- **Total:** ~30-50ms per request

### Improvement: **40-70% faster!** (v2.1.1 vs v1.0)

---

## 🐳 Docker Quick Start

```bash
# Standard production
docker-compose up -d

# Development with hot-reload
docker-compose -f docker-compose.dev.yml up -d

# Load balanced (3 containers)
docker-compose -f docker-compose.scale.yml up -d

# Test caching
docker exec phpweave-app php tests/test_docker_caching.php
```

---

## 🧪 Testing

All test scripts are in the `tests/` directory:

```bash
# Run all tests
php tests/test_hooks.php                # 8 hook tests
php tests/test_path_traversal.php       # 14 security tests (NEW!)
php tests/test_docker_caching.php       # Caching tests
php tests/benchmark_optimizations.php   # Performance benchmarks
```

See [tests/README.md](../tests/README.md) for detailed testing guide.

---

## 🔒 Security

PHPWeave maintains an **A (Excellent)** security rating:

- ✅ OWASP Top 10 (2021) compliant
- ✅ All vulnerabilities fixed (3 medium issues resolved)
- ✅ Automated security test suite (14 tests)
- ✅ **Automated security analysis (PHPStan + Psalm)** ⭐ NEW!
- ✅ **95% security vulnerability detection** ⭐ NEW!
- ✅ Comprehensive security documentation (500+ lines)

**Documentation:**
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Developer security guide
- [SECURITY_ANALYSIS.md](SECURITY_ANALYSIS.md) - Automated security analysis with Psalm ⭐ NEW!
- [PSALM_SETUP_COMPLETE.md](PSALM_SETUP_COMPLETE.md) - Quick setup reference ⭐ NEW!
- [SECURITY_AUDIT.md](../SECURITY_AUDIT.md) - Full security audit report

**Automated Security Analysis (NEW!):**
- ✅ **Psalm taint analysis** - Tracks user input flow to dangerous functions
- ✅ **SQL injection detection** - Simple and complex patterns
- ✅ **XSS detection** - Cross-site scripting vulnerabilities
- ✅ **Path traversal detection** - File inclusion attacks
- ✅ **Command injection detection** - Shell command vulnerabilities
- ✅ **CI/CD integration** - Automatic scanning on every commit

**Key Security Features:**
- ✅ PDO prepared statements (SQL injection protection)
- ✅ Path traversal protection in view rendering
- ✅ Secure JSON serialization for caching
- ✅ Restricted async callable deserialization
- ✅ Null byte injection protection
- ✅ Output escaping helpers
- ✅ Comprehensive error logging

**Run Security Scan:**
```bash
# Quick security scan
composer psalm-security

# Or use scripts
run-psalm-security.bat     # Windows
./run-psalm-security.sh    # Linux/Mac

# Full analysis (PHPStan + Psalm)
composer check
```

---

## 📝 Contributing

We welcome contributions to PHPWeave! Whether you're fixing bugs, adding features, or improving documentation, your help is appreciated.

**Read our [Contributing Guide](../CONTRIBUTING.md)** for:
- Development workflow and branching strategy
- Coding standards and style guide
- Testing requirements (PHPStan, Psalm, unit tests)
- Pull request process and review guidelines
- Security best practices

### Contributing to Documentation

When adding or updating documentation:

1. Place in appropriate category in `docs/`
2. Update this README's table of contents
3. Add to quick reference if applicable
4. Cross-reference related docs
5. Include code examples
6. Test all code snippets
7. Follow [markdown best practices](../CONTRIBUTING.md#documentation)

---

## 🔗 External Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [Docker Documentation](https://docs.docker.com/)
- [APCu Extension](https://www.php.net/manual/en/book.apcu.php)
- [Composer](https://getcomposer.org/)

---

## 📧 Support

For issues or questions:

- Check relevant documentation above
- Review code examples in docs
- Run test scripts in `tests/`

---

**Happy coding with PHPWeave!** 🚀
