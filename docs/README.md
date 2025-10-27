# PHPWeave Documentation

Complete documentation for the PHPWeave framework.

---

## 📚 Table of Contents

### Getting Started

- [**README.md**](../README.md) - Main project overview and quick start (in root)

### Core Features

#### Version 2.1 Features (NEW!)

- [**V2.1_FEATURES.md**](V2.1_FEATURES.md) - New PHPWeave global object and auto-extracted view variables

#### Routing System

- [**ROUTING_GUIDE.md**](ROUTING_GUIDE.md) - Complete routing documentation
- [**MIGRATION_TO_NEW_ROUTING.md**](MIGRATION_TO_NEW_ROUTING.md) - Migrating from legacy routing

#### Hooks System

- [**HOOKS.md**](HOOKS.md) - Complete hooks documentation with all 18 hook points

#### Async/Jobs System

- [**ASYNC_GUIDE.md**](ASYNC_GUIDE.md) - Complete async job processing guide
- [**ASYNC_QUICK_START.md**](ASYNC_QUICK_START.md) - Quick start for async jobs

#### Libraries System (NEW!)

- [**LIBRARIES.md**](LIBRARIES.md) - Complete libraries documentation with lazy loading

#### Security

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

- ✅ Lazy hook priority sorting (5-10ms saved)
- ✅ Lazy model loading (3-10ms saved)
- ✅ Route caching (1-3ms saved)
- ✅ Directory path caching (~0.5ms saved)
- ✅ Template sanitization optimization (~0.1ms saved)

**Total: 30-60% faster response times**

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

   - `Router` - Modern routing with dynamic parameters
   - `Controller` - Base controller with view rendering
   - `DBConnection` - PDO-based database connection
   - `Hook` - Event-driven hooks system
   - `Libraries` - Lazy-loaded utility classes (v2.1.1+)
   - `Async` - Background job processing
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
2. Read [V2.1_FEATURES.md](V2.1_FEATURES.md) - New v2.1 features
3. Read [ROUTING_GUIDE.md](ROUTING_GUIDE.md) - Define routes
4. Read [HOOKS.md](HOOKS.md) - Add custom logic
5. Read [LIBRARIES.md](LIBRARIES.md) - Create utility libraries
6. Read [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Security basics

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

**Async Jobs:**

```php
Async::queue(new SendEmailJob(), ['to' => 'user@example.com']);
```

**Models (v2.1):**

```php
global $PW;
$user = $PW->models->user_model->getUser($id);

// Or use helper function
$user = model('user_model')->getUser($id);
```

**Libraries (v2.1.1):**

```php
global $PW;
$slug = $PW->libraries->string_helper->slugify("Hello World");
$preview = $PW->libraries->string_helper->truncate($text, 200);

// Or use helper function
$slug = library('string_helper')->slugify("Hello World");
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

### Before Optimizations

- Framework bootstrap: ~15-25ms
- With 10 hooks: ~20-30ms
- With 20 models (eager): ~25-35ms
- **Total:** ~30-50ms per request

### After Optimizations

- Framework bootstrap: ~5-10ms
- With 10 hooks: ~8-12ms
- With 20 models (lazy): ~8-12ms
- **Total:** ~15-25ms per request

### Improvement: **40-60% faster!**

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
- ✅ Automated security test suite
- ✅ Comprehensive security documentation

**Documentation:**
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Developer security guide
- [SECURITY_AUDIT.md](../SECURITY_AUDIT.md) - Full security audit report

**Key Security Features:**
- PDO prepared statements (SQL injection protection)
- Path traversal protection in view rendering
- Secure JSON serialization for caching
- Output escaping helpers
- Comprehensive error logging

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
