# PHPWeave Documentation

Complete documentation for the PHPWeave framework.

---

## 📚 Table of Contents

### Getting Started

- [**README.md**](../README.md) - Main project overview and quick start (in root)

### Core Features

#### Version 2.1.1 Features (LATEST!)

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

#### Async/Jobs System

- [**ASYNC_GUIDE.md**](ASYNC_GUIDE.md) - Complete async job processing guide (Updated v2.1.1)
- [**ASYNC_QUICK_START.md**](ASYNC_QUICK_START.md) - Quick start for async jobs

#### Security (NEW in v2.1.1!)

- **Security Rating: A (Excellent)**
- **OWASP Top 10 (2021) Compliant**

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

---

## 📖 Documentation by Topic

### 🎯 Quick Reference

**I want to...**

| Task                        | Documentation                                              |
| --------------------------- | ---------------------------------------------------------- |
| Get started quickly         | [README.md](../README.md)                                  |
| Learn v2.1 features         | [V2.1_FEATURES.md](V2.1_FEATURES.md)                       |
| Define routes               | [ROUTING_GUIDE.md](ROUTING_GUIDE.md)                       |
| Add hooks                   | [HOOKS.md](HOOKS.md)                                       |
| Create utility libraries    | [LIBRARIES.md](LIBRARIES.md)                               |
| Process background jobs     | [ASYNC_QUICK_START.md](ASYNC_QUICK_START.md)               |
| Deploy to Docker            | [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md)               |
| Optimize performance        | [OPTIMIZATIONS_APPLIED.md](OPTIMIZATIONS_APPLIED.md)       |
| Migrate from legacy routing | [MIGRATION_TO_NEW_ROUTING.md](MIGRATION_TO_NEW_ROUTING.md) |
| Secure my application       | [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md)   |
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
   - `DBConnection` - PDO-based database connection
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
- [LIBRARIES.md](LIBRARIES.md) - Creating reusable utility libraries
- [ASYNC_GUIDE.md](ASYNC_GUIDE.md) - Job queues and workers
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Security guidelines

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
│   ├── V2.1_FEATURES.md               # v2.1 features (NEW!)
│   ├── ROUTING_GUIDE.md              # Routing system
│   ├── MIGRATION_TO_NEW_ROUTING.md   # Migration guide
│   ├── HOOKS.md                       # Hooks system (18 points)
│   ├── LIBRARIES.md                   # Libraries system (NEW!)
│   ├── ASYNC_GUIDE.md                 # Async jobs (detailed)
│   ├── ASYNC_QUICK_START.md           # Async jobs (quick)
│   │
│   ├── # Security
│   ├── SECURITY_BEST_PRACTICES.md     # Security guidelines (NEW!)
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
3. Read [LIBRARIES.md](LIBRARIES.md) - v2.1.1 lazy-loaded libraries
4. Read [ROUTING_GUIDE.md](ROUTING_GUIDE.md) - Define routes
5. Read [HOOKS.md](HOOKS.md) - Add custom logic
6. Read [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Security basics (v2.1.1)

### Intermediate

7. Read [ASYNC_QUICK_START.md](ASYNC_QUICK_START.md) - Background jobs
8. Read [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) - Docker deployment
9. Read [OPTIMIZATIONS_APPLIED.md](OPTIMIZATIONS_APPLIED.md) - Performance

### Advanced

10. Read [PERFORMANCE_ANALYSIS.md](PERFORMANCE_ANALYSIS.md) - Optimization
11. Read [DOCKER_CACHING_GUIDE.md](DOCKER_CACHING_GUIDE.md) - Caching strategies
12. Read [ASYNC_GUIDE.md](ASYNC_GUIDE.md) - Advanced async patterns
13. Read [SECURITY_AUDIT.md](../SECURITY_AUDIT.md) - Security audit report

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

## 🔒 Security (NEW in v2.1.1!)

PHPWeave maintains an **A (Excellent)** security rating:

- ✅ OWASP Top 10 (2021) compliant
- ✅ All vulnerabilities fixed (3 medium issues resolved)
- ✅ Automated security test suite (14 tests)
- ✅ Comprehensive security documentation (500+ lines)

**Documentation:**
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Developer security guide (NEW!)
- [SECURITY_AUDIT.md](../SECURITY_AUDIT.md) - Full security audit report (NEW!)

**Key Security Features (v2.1.1):**
- ✅ PDO prepared statements (SQL injection protection)
- ✅ Path traversal protection in view rendering (FIXED)
- ✅ Secure JSON serialization for caching (FIXED)
- ✅ Restricted async callable deserialization (FIXED)
- ✅ Null byte injection protection (NEW)
- ✅ Output escaping helpers
- ✅ Comprehensive error logging

**Security Improvements in v2.1.1:**
1. Fixed path traversal vulnerability in `Controller::show()`
2. Replaced PHP serialization with JSON for route cache
3. Added multi-callable support with secure JSON serialization
4. Enhanced path sanitization (../,  null bytes, backslashes)
5. Automated security test suite created

---

## 📝 Contributing

When adding documentation:

1. Place in appropriate category in `docs/`
2. Update this README's table of contents
3. Add to quick reference if applicable
4. Cross-reference related docs
5. Include code examples
6. Test all code snippets

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
