# Type Coverage Improvement Plan

**Current Status:** 86.36% type inference
**Target:** 90-95% type inference
**Estimated Improvement:** +3-8%

---

## 📊 Current Analysis

### Psalm Report Summary
- **Total issues:** 119 INFO messages + 1 error
- **Auto-fixable:** 13 issues (ClassMustBeFinal)
- **Type inference:** 86.36%
- **Memory usage:** 45-52MB

---

## 🎯 Categories of Issues

### 1. Missing Type Hints (13 issues) - **High Impact**
- `MissingParamType` - 7 instances
- `MissingReturnType` - Not found in scan
- `MissingPropertyType` - 2 instances

**Impact on Type Inference:** +2-3%

#### Locations:
- `coreapp/libraries.php:165` - `offsetExists($libraryName)`
- `coreapp/libraries.php:172` - `offsetGet($libraryName)`
- `coreapp/libraries.php:184` - Property `$libraries`
- `coreapp/models.php:165` - `offsetExists($modelName)`
- `coreapp/models.php:172` - `offsetGet($modelName)`
- `coreapp/models.php:184` - Property `$models`
- `public/index.php:93` - `__get($name)`

### 2. Possibly False Arguments (30+ issues) - **Medium Impact**
Functions that can return `false`:
- `json_encode()` → `false|string`
- `json_decode()` → `mixed|false`
- `file_get_contents()` → `string|false`
- `glob()` → `array<string>|false`
- `popen()` → `resource|false`
- `getenv()` → `string|false`

**Impact on Type Inference:** +1-2%

### 3. Risky Truthy/Falsy Comparisons (30+ issues) - **Low-Medium Impact**
Using `?:` or `||` with values that can be falsy:
- `getenv('VAR') ?: 'default'`
- `!empty($array)`
- `if ($files)`

**Impact on Type Inference:** +0.5-1%

### 4. Class Must Be Final (13 issues) - **No Impact**
Auto-fixable with `--alter --issues=ClassMustBeFinal`

**Impact on Type Inference:** 0% (code quality only)

### 5. Redundant Casts (1 issue) - **No Impact**
`(int)$max` when already documented as int

**Impact on Type Inference:** 0%

---

## 🚀 Recommended Improvements

### Priority 1: Add Type Hints (High Impact - Easy Fix)

#### Fix 1: models.php and libraries.php ArrayAccess methods

```php
// coreapp/models.php (lines 165-184)
// OLD:
public function offsetExists($modelName) { ... }
public function offsetGet($modelName) { ... }
private $models;

// NEW:
/**
 * @param string $modelName
 * @return bool
 */
public function offsetExists($modelName): bool { ... }

/**
 * @param string $modelName
 * @return object
 */
public function offsetGet($modelName): object { ... }

/**
 * @var LazyModelLoader
 */
private LazyModelLoader $models;
```

**Estimated Improvement:** +1-1.5%

#### Fix 2: public/index.php __get() method

```php
// public/index.php (line 93)
// OLD:
public function __get($name) {

// NEW:
/**
 * @param string $name
 * @return mixed
 */
public function __get(string $name) {
```

**Estimated Improvement:** +0.5%

---

### Priority 2: Add Assertions for False-Returning Functions (Medium Impact - Medium Effort)

#### Strategy: Use `assert()` or explicit null coalescing

```php
// BEFORE:
$content = file_get_contents($file);
$data = json_decode($content, true);

// AFTER (Option 1 - Assertions):
$content = file_get_contents($file);
assert($content !== false, 'Failed to read file');
$data = json_decode($content, true);
assert($data !== false, 'Failed to decode JSON');

// AFTER (Option 2 - Null coalescing):
$content = @file_get_contents($file) ?: '';
$data = json_decode($content, true) ?? [];

// AFTER (Option 3 - Explicit checks):
$content = file_get_contents($file);
if ($content === false) {
    throw new Exception('Failed to read file');
}
$data = json_decode($content, true);
if ($data === false) {
    throw new Exception('Failed to decode JSON');
}
```

**Locations to Fix (~30 instances):**
- `coreapp/async.php` - 15 instances
- `coreapp/router.php` - 5 instances
- `coreapp/controller.php` - 3 instances
- `coreapp/hooks.php` - 2 instances
- Others

**Estimated Improvement:** +1-2%

---

### Priority 3: Replace Truthy/Falsy with Strict Comparisons (Low-Medium Impact - Medium Effort)

#### Strategy: Use `!== false` instead of truthy checks

```php
// BEFORE:
$value = getenv('VAR') ?: 'default';
if (!$files) { ... }

// AFTER:
$value = getenv('VAR');
if ($value === false) {
    $value = 'default';
}

// OR use null coalescing with strict check:
$value = (getenv('VAR') !== false ? getenv('VAR') : 'default');

// For arrays:
$files = glob('*.php');
if ($files === false || count($files) === 0) { ... }
```

**Locations to Fix (~30 instances):**
- `public/index.php` - 10+ instances
- `coreapp/async.php` - 8+ instances
- Others

**Estimated Improvement:** +0.5-1%

---

### Priority 4: Make Classes Final (No Impact - Easy Fix)

```bash
# Auto-fix with Psalm:
vendor/bin/psalm --alter --issues=ClassMustBeFinal
```

**Classes to finalize (13 instances):**
- `Async`
- `ConnectionPool`
- `Controller`
- `DBConnection`
- `ErrorClass`
- `Hook`
- `Job`
- `LazyLibraryLoader`
- `LazyModelLoader`
- `Migration`
- `MigrationRunner`
- `Router`
- `Session`

**Estimated Improvement:** 0% (code quality only)

---

## 📈 Estimated Cumulative Impact

| Priority | Category | Effort | Type Coverage Gain |
|----------|----------|--------|--------------------|
| 1 | Add type hints | Low | +1.5-2% |
| 2 | Assert false-returning functions | Medium | +1-2% |
| 3 | Strict comparisons | Medium | +0.5-1% |
| 4 | Final classes | Low | 0% |
| **Total** | - | **Medium** | **+3-5%** |

**Target Type Coverage:** 89-91% (from current 86.36%)

---

## 🎯 Quick Wins (30 minutes of work)

### Phase 1: Type Hints (Fastest Improvement)

1. **Fix models.php** (5 minutes)
   - Add type hints to `offsetExists()`, `offsetGet()`
   - Add property type to `$models`

2. **Fix libraries.php** (5 minutes)
   - Add type hints to `offsetExists()`, `offsetGet()`
   - Add property type to `$libraries`

3. **Fix public/index.php __get()** (2 minutes)
   - Add type hint to `$name` parameter

4. **Run Psalm auto-fix for final classes** (1 minute)
   ```bash
   vendor/bin/psalm --alter --issues=ClassMustBeFinal
   ```

**Expected Improvement:** 86.36% → 88-89%

---

## 🔧 Implementation Strategy

### Step 1: Quick Wins (Priority 1 + 4)
- Time: 15 minutes
- Gain: +1.5-2%
- Risk: Very low
- Files: 3 files modified

### Step 2: Medium Wins (Priority 2 - top 10 instances)
- Time: 30 minutes
- Gain: +0.5-1%
- Risk: Low
- Files: 3-4 files modified

### Step 3: Full Implementation (Priority 2 + 3)
- Time: 2-3 hours
- Gain: +1-2%
- Risk: Medium (requires careful testing)
- Files: 8-10 files modified

---

## ⚠️ Trade-offs

### Pros
- ✅ Better static analysis
- ✅ More IDE autocomplete
- ✅ Catches bugs earlier
- ✅ Better documentation
- ✅ Easier to refactor

### Cons
- ⚠️ PHP 7.4 type hints are limited (no union types until PHP 8.0)
- ⚠️ Adding assertions adds runtime overhead (minimal)
- ⚠️ More verbose code in some places
- ⚠️ Requires careful testing to avoid breaking changes

---

## 🎓 Best Practices

### Type Hints to Use (PHP 7.4 Compatible)

```php
// Scalars
function foo(string $name, int $age, bool $active, float $price): void

// Arrays
function bar(array $items): array

// Objects
function baz(PDO $conn, Exception $e): object

// Nullable
function qux(?string $name): ?array

// Mixed (PHP 8.0+, use @param mixed in PHP 7.4)
/**
 * @param mixed $value
 * @return mixed
 */
function quux($value)
```

### DocBlock Types (When Return Type Can't Be Declared)

```php
/**
 * @param string $name
 * @return LazyModelLoader|object
 */
public function offsetGet($modelName): object { ... }
```

---

## 📊 Success Metrics

### Before Optimization
- Type Inference: 86.36%
- INFO Issues: 119
- Errors: 1
- Memory: 45-52MB

### After Quick Wins (Phase 1)
- Type Inference: **88-89%** ✨
- INFO Issues: ~100
- Errors: 0-1
- Memory: Similar

### After Full Implementation (Phase 3)
- Type Inference: **90-92%** 🚀
- INFO Issues: ~60-70
- Errors: 0
- Memory: Similar

---

## 🚀 Next Steps

1. **Implement Quick Wins** (Phase 1) - 15 minutes
2. **Run tests** - Ensure no regressions
3. **Check new type coverage** - Run Psalm again
4. **Document improvements** - Update CHANGELOG

Would you like me to implement the quick wins now?

---

## 📚 References

- [Psalm Documentation](https://psalm.dev/docs/)
- [PHP Type Declarations](https://www.php.net/manual/en/language.types.declarations.php)
- [PHPStan Type Coverage](https://phpstan.org/blog/what-is-type-coverage)
- [Type Safety Best Practices](https://psalm.dev/articles/better-php-type-safety)

---

**Note:** The slight decrease from 87.33% to 86.36% is due to the new code added in v2.3.1 optimizations. These improvements will bring it back above the original level.
