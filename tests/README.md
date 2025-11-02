# PHPWeave Test Suite

This directory contains test scripts and benchmarks for PHPWeave framework.

## 🎓 New to PHPWeave?

Before diving into tests, check out our **[Getting Started Tutorial](../docs/GETTING_STARTED_TUTORIAL.md)** which walks you through building a complete guestbook application. It's the best way to learn PHPWeave's core concepts!

## Test Files

### 1. `test_hooks.php`

**Purpose:** Tests the hooks system functionality (8 tests)

**Run:**

```bash
php tests/test_hooks.php
```

**Tests:**

- ✓ Hook registration and triggering
- ✓ Priority ordering (5 → 10 → 20)
- ✓ Data modification through hooks
- ✓ Halt execution
- ✓ Utility methods (has, count, clear)
- ✓ Exception handling

**Expected:** All 8 tests should PASS

---

### 2. `test_models.php`

**Purpose:** Tests the lazy model loading system (12 tests)

**Run:**

```bash
php tests/test_models.php
```

**Tests:**

- ✓ Model file discovery
- ✓ Model loading via model() function
- ✓ Model loading via $models[] array (legacy)
- ✓ PHPWeave global object ($PW->models->)
- ✓ Model instance caching
- ✓ Error handling for non-existent models
- ✓ ArrayAccess isset() checks
- ✓ Magic \_\_isset() for object property access
- ✓ ArrayAccess set() protection
- ✓ ArrayAccess unset() protection
- ✓ Model method availability
- ✓ All access methods return same instance

**Expected:** Most tests should PASS. Some may SKIP if database connection
unavailable.

**Note:** Models extend DBConnection, so some tests require database access.

---

### 3. `test_controllers.php`

**Purpose:** Tests the controller system and base class (15 tests)

**Run:**

```bash
php tests/test_controllers.php
```

**Tests:**

- ✓ Controller base class verification
- ✓ Controller instantiation (skip auto-call)
- ✓ Safe HTML output helper (XSS prevention)
- ✓ Loading application controllers
- ✓ Controller inheritance
- ✓ Controller method detection
- ✓ Application controller instantiation
- ✓ Method invocation with callfunc()
- ✓ View rendering hook integration
- ✓ View data extraction (array to variables)
- ✓ Template path sanitization
- ✓ 404 handling for non-existent views
- ✓ Legacy routing functions
- ✓ Controller with model access
- ✓ Multiple controller instances

**Expected:** All tests should PASS

---

### 4. `test_libraries.php`

**Purpose:** Tests the lazy library loading system (20 tests) - NEW in v2.1.1!

**Run:**

```bash
php tests/test_libraries.php
```

**Tests:**

- ✓ Library file discovery
- ✓ Library loading via library() function
- ✓ Library loading via $libraries[] array (legacy)
- ✓ PHPWeave global object ($PW->libraries->)
- ✓ Library instance caching
- ✓ Error handling for non-existent libraries
- ✓ ArrayAccess isset() checks
- ✓ Magic \_\_isset() for object property access
- ✓ ArrayAccess set() protection
- ✓ ArrayAccess unset() protection
- ✓ All access methods return same instance
- ✓ string_helper->slugify() method
- ✓ string_helper->truncate() method
- ✓ string_helper->random() method
- ✓ string_helper->ordinal() method
- ✓ string_helper->titleCase() method
- ✓ string_helper->wordCount() method
- ✓ string_helper->readingTime() method
- ✓ Chaining multiple library calls
- ✓ Performance - instance caching verification

**Expected:** All tests should PASS if string_helper library exists

**Note:** Tests all 7 methods of the string_helper library plus system
functionality.

---

### 5. `test_docker_caching.php`

**Purpose:** Tests Docker-aware caching (APCu and file cache)

**Run:**

```bash
# Local
php tests/test_docker_caching.php

# In Docker container
docker exec phpweave-app php tests/test_docker_caching.php
```

**Tests:**

- ✓ APCu extension availability
- ✓ APCu cache enable/disable
- ✓ Route registration and caching
- ✓ Cache save/load functionality
- ✓ Docker environment detection
- ✓ File cache fallback

**Expected:**

- Local: "File cache available" (if no APCu)
- Docker: "OPTIMAL: APCu enabled" (if Dockerfile used)

---

### 6. `benchmark_optimizations.php`

**Purpose:** Benchmarks performance improvements from optimizations

**Run:**

```bash
php tests/benchmark_optimizations.php
```

**Benchmarks:**

- Hook priority sorting (lazy vs eager)
- Model loading performance
- Directory path calculation
- Template sanitization
- Route caching

**Results:**

- Total improvement: ~10-25ms per request
- 30-60% faster for typical applications

---

## Running All Tests

### Local Development

```bash
# Run all tests
php tests/test_hooks.php
php tests/test_models.php
php tests/test_controllers.php
php tests/test_libraries.php
php tests/test_docker_caching.php
php tests/benchmark_optimizations.php
```

### Docker Environment

```bash
# Run all tests in container
docker exec phpweave-app php tests/test_hooks.php
docker exec phpweave-app php tests/test_models.php
docker exec phpweave-app php tests/test_controllers.php
docker exec phpweave-app php tests/test_libraries.php
docker exec phpweave-app php tests/test_docker_caching.php
docker exec phpweave-app php tests/benchmark_optimizations.php

# Or enter container and run
docker exec -it phpweave-app bash
cd /var/www/html
php tests/test_hooks.php
php tests/test_libraries.php
```

---

## Continuous Integration

Tests are automatically run in CI/CD pipelines:

```yaml
# GitHub Actions example
- name: Run Tests
  run: |
    docker exec test php tests/test_hooks.php
    docker exec test php tests/test_models.php
    docker exec test php tests/test_controllers.php
    docker exec test php tests/test_libraries.php
    docker exec test php tests/test_docker_caching.php
```

---

## Expected Results

### All Tests Passing

```text
✓ test_hooks.php: 8/8 tests PASS
✓ test_models.php: 12/12 tests PASS
✓ test_controllers.php: 15/15 tests PASS
✓ test_libraries.php: 20/20 tests PASS (NEW!)
✓ test_docker_caching.php: APCu or file cache working
✓ benchmark_optimizations.php: Performance improvements verified
```

### Typical Output

**Hooks Test:**

```text
Testing PHPWeave Hooks System
==================================================
Test 1: Basic Hook Registration        PASS
Test 2: Hook Priority Order            PASS
...
All Tests Completed!
```

**Docker Caching Test (with APCu):**

```text
✅ OPTIMAL: APCu enabled - using in-memory caching
   → Best for Docker/container environments
   → No filesystem dependencies
```

**Docker Caching Test (without APCu):**

```text
✅ GOOD: File cache available
   → Works but requires writable filesystem
   → Consider installing APCu for better performance
```

---

## Troubleshooting

### Test Failures

**"APCu not available"**

- Expected if APCu not installed
- Framework falls back to file cache
- To enable APCu: see `DOCKER_DEPLOYMENT.md`

**"Cache directory not writable"**

```bash
# Fix permissions
chmod 755 cache/
chown -R www-data:www-data cache/
```

**"Hook tests failing"**

- Check PHP version >= 7.4
- Verify no syntax errors in core files

---

## Adding New Tests

To add a new test:

1. Create `tests/test_myfeature.php`
2. Include framework files: `require_once __DIR__ . '/../coreapp/...'`
3. Add test cases with clear output
4. Document in this README
5. Add to CI/CD pipeline

### Template

```php
<?php
/**
 * Test Description
 *
 * Usage: php tests/test_myfeature.php
 */

echo "Testing My Feature\n";
echo str_repeat("=", 70) . "\n\n";

require_once __DIR__ . '/../coreapp/myfeature.php';

// Test 1
echo "Test 1: Basic Functionality\n";
// ... test code ...
echo "  Result: PASS\n\n";

echo "✓ All tests completed!\n";
```

---

## Test Coverage

Current test coverage:

- ✅ Hooks system (comprehensive - 8 tests)
- ✅ Models system (comprehensive - 12 tests)
- ✅ Controllers system (comprehensive - 15 tests)
- ✅ Libraries system (comprehensive - 20 tests) - NEW in v2.1.1!
- ✅ Docker caching (APCu + file)
- ✅ Performance benchmarks
- ⚠️ Router (partial - tested via hooks)
- ❌ Database connections (manual testing)
- ❌ Async jobs (manual testing)

---

## Performance Benchmarks

Run benchmarks to verify optimizations:

```bash
php tests/benchmark_optimizations.php
```

Expected improvements:

- Hook sorting: ~10x faster registration
- Model loading: 3-10ms saved
- Path caching: 95% faster
- Template sanitization: 45% faster
- Route caching: 1-3ms saved

---

For more information, see:

- `HOOKS.md` - Hooks documentation
- `LIBRARIES.md` - Libraries documentation (NEW!)
- `DOCKER_DEPLOYMENT.md` - Docker testing
- `OPTIMIZATIONS_APPLIED.md` - Performance details
