# PHPWeave Libraries Guide

## Overview

Libraries in PHPWeave are reusable utility classes that provide common functionality across your application. They follow the same lazy-loading pattern as models, meaning they're only instantiated when first accessed, improving performance.

**Key Features:**
- Lazy instantiation (only loaded when needed)
- Environment-aware thread safety (Docker/cloud/threaded environments)
- Three access methods (global object, function, array)
- Automatic discovery (no manual registration)
- Caching of instances (singleton pattern)
- Backward compatible with legacy syntax

**Version:** 2.1.1+
**Location:** `coreapp/libraries.php`

## How Libraries Work

### Auto-Discovery

When PHPWeave starts, it scans the `libraries/` directory for all `.php` files:

```php
// From coreapp/libraries.php
$files = glob("../libraries/*.php");
foreach ($files as $file) {
    require_once $file;
    $libraryName = basename($file, ".php");
    $GLOBALS['_library_files'][$libraryName] = $libraryName;
}
```

All library files are **required** but **not instantiated** until first access. This provides the performance benefits of lazy loading while ensuring all classes are available.

### Lazy Instantiation

Libraries are instantiated only when you first access them:

```php
// First access - triggers instantiation
$slug = $PW->libraries->string_helper->slugify("Hello World");

// Second access - returns cached instance (fast!)
$truncated = $PW->libraries->string_helper->truncate($text, 100);
```

The `library()` function and `LazyLibraryLoader` class handle the lazy instantiation and caching.

## Creating Libraries

### Basic Structure

Create a new file in the `libraries/` directory:

**File:** `libraries/string_helper.php`

```php
<?php
/**
 * String Helper Library
 *
 * Provides common string manipulation utilities.
 *
 * @package    PHPWeave
 * @subpackage Libraries
 */
class string_helper {

    /**
     * Convert string to URL-friendly slug
     *
     * @param string $text The text to slugify
     * @return string The slugified text
     */
    public function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        $text = preg_replace('~-+~', '-', $text);
        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Truncate text to specified length
     *
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append
     * @return string Truncated text
     */
    public function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        $text = substr($text, 0, $length);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }
        return $text . $suffix;
    }
}
```

### Naming Conventions

- **Filename:** lowercase with underscores (e.g., `string_helper.php`)
- **Class name:** Must match filename exactly (e.g., `class string_helper`)
- **Methods:** camelCase or snake_case (your preference)
- **No suffix:** Don't use `_library` suffix (it's implied by location)

### Library Guidelines

**Do:**
- Keep methods focused and reusable
- Document all public methods with PHPDoc comments
- Return values rather than outputting directly
- Use type hints for parameters and return types (PHP 7.4+)
- Keep libraries stateless when possible
- Use descriptive method names

**Don't:**
- Access databases directly (use models for that)
- Depend on other libraries (keep them independent)
- Store instance state unless necessary
- Use global variables (use parameters instead)
- Mix concerns (keep each library focused)

## Using Libraries

### Method 1: PHPWeave Global Object (Recommended)

**Recommended for new code (v2.1.1+)**

```php
// In controller
global $PW;
$slug = $PW->libraries->string_helper->slugify($title);
$preview = $PW->libraries->string_helper->truncate($content, 200);
$token = $PW->libraries->string_helper->random(16);
```

**Benefits:**
- Modern, object-oriented syntax
- Clear namespace (`$PW->libraries->`)
- Consistent with models (`$PW->models->`)
- IDE auto-completion friendly

### Method 2: Helper Function

**Good for utility usage**

```php
$slug = library('string_helper')->slugify($title);
$preview = library('string_helper')->truncate($content, 200);
```

**Benefits:**
- Concise syntax
- No global declaration needed
- Good for one-off library calls

### Method 3: Legacy Array Access

**Backward compatible with older code**

```php
global $libraries;
$slug = $libraries['string_helper']->slugify($title);
$preview = $libraries['string_helper']->truncate($content, 200);
```

**Benefits:**
- Maintains compatibility with existing code
- Familiar to developers used to older PHPWeave versions

## Complete Example

### Creating a Validation Library

**File:** `libraries/validator.php`

```php
<?php
/**
 * Validation Library
 *
 * Provides common input validation utilities.
 *
 * @package    PHPWeave
 * @subpackage Libraries
 * @version    1.0.0
 */
class validator {

    /**
     * Validate email address
     *
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    public function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate string length
     *
     * @param string $string String to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return bool True if valid, false otherwise
     */
    public function length($string, $min = 1, $max = 255) {
        $length = strlen($string);
        return $length >= $min && $length <= $max;
    }

    /**
     * Validate required field
     *
     * @param mixed $value Value to check
     * @return bool True if not empty, false otherwise
     */
    public function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }

    /**
     * Validate numeric value
     *
     * @param mixed $value Value to validate
     * @return bool True if numeric, false otherwise
     */
    public function numeric($value) {
        return is_numeric($value);
    }

    /**
     * Validate value is in array
     *
     * @param mixed $value Value to check
     * @param array $allowed Allowed values
     * @return bool True if in array, false otherwise
     */
    public function inArray($value, $allowed) {
        return in_array($value, $allowed, true);
    }

    /**
     * Validate multiple rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Array of errors (empty if valid)
     */
    public function validate($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleSet);

            foreach ($ruleList as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $params) = explode(':', $rule, 2);
                    $params = explode(',', $params);
                } else {
                    $ruleName = $rule;
                    $params = [];
                }

                $isValid = false;
                switch ($ruleName) {
                    case 'required':
                        $isValid = $this->required($value);
                        break;
                    case 'email':
                        $isValid = $this->email($value);
                        break;
                    case 'url':
                        $isValid = $this->url($value);
                        break;
                    case 'numeric':
                        $isValid = $this->numeric($value);
                        break;
                    case 'length':
                        $min = (int)($params[0] ?? 1);
                        $max = (int)($params[1] ?? 255);
                        $isValid = $this->length($value, $min, $max);
                        break;
                }

                if (!$isValid) {
                    $errors[$field][] = "Field {$field} failed validation: {$ruleName}";
                }
            }
        }

        return $errors;
    }
}
```

### Using the Validation Library

**In a controller:**

```php
<?php
class User extends Controller {

    function register() {
        global $PW;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate form data
            $errors = $PW->libraries->validator->validate($_POST, [
                'email' => 'required|email',
                'password' => 'required|length:8,100',
                'username' => 'required|length:3,30'
            ]);

            if (empty($errors)) {
                // Validation passed - process registration
                $email = $_POST['email'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $username = $_POST['username'];

                // Save to database using model
                $userId = $PW->models->user_model->create([
                    'email' => $email,
                    'password' => $password,
                    'username' => $username
                ]);

                // Create slug for user profile
                $slug = $PW->libraries->string_helper->slugify($username);
                $PW->models->user_model->updateSlug($userId, $slug);

                // Redirect to success page
                header('Location: /user/welcome');
                exit;
            }

            // Validation failed - show errors
            $this->show('user/register', [
                'errors' => $errors,
                'old' => $_POST
            ]);
        } else {
            // Show registration form
            $this->show('user/register', []);
        }
    }
}
```

## Built-in Libraries

### String Helper Library

**File:** `libraries/string_helper.php`

**Methods:**
- `slugify($text)` - Convert to URL-friendly slug
- `truncate($text, $length, $suffix)` - Truncate with ellipsis
- `random($length, $includeSpecial)` - Generate random string
- `ordinal($number)` - Add ordinal suffix (1st, 2nd, 3rd)
- `titleCase($text)` - Convert to title case
- `wordCount($text)` - Count words
- `readingTime($text, $wpm)` - Estimate reading time

**Example:**
```php
global $PW;

// Create blog post slug
$slug = $PW->libraries->string_helper->slugify("My New Blog Post!");
// Result: "my-new-blog-post"

// Create excerpt
$excerpt = $PW->libraries->string_helper->truncate($article, 200);
// Result: "This is the beginning of my article text..."

// Generate API token
$token = $PW->libraries->string_helper->random(32);
// Result: "aB3xY9mKz7Qw2Vn5Rp8Lh4Jc6Fg1Td0"

// Show reading time
$time = $PW->libraries->string_helper->readingTime($article);
// Result: "5 min read"
```

## Performance Considerations

### Lazy Loading Benefits

Libraries are only instantiated when first accessed:

```php
// If you never access a library, it's never instantiated
// Only the class definition is loaded (minimal overhead)

// First access - instantiation occurs (~0.1-0.2ms)
$slug1 = $PW->libraries->string_helper->slugify($text1);

// Subsequent accesses - uses cached instance (~0.001ms)
$slug2 = $PW->libraries->string_helper->slugify($text2);
$slug3 = $PW->libraries->string_helper->slugify($text3);
```

### Instance Caching & Thread Safety

Each library is instantiated once and cached with environment-aware thread safety:

```php
// From coreapp/libraries.php
function library($libraryName) {
    static $instances = [];
    static $needsLocking = null;
    static $lockFile = null;

    // Return cached instance if exists (very fast!)
    if (isset($instances[$libraryName])) {
        return $instances[$libraryName];
    }

    // Detect environment and locking requirements once
    if ($needsLocking === null) {
        $needsLocking = (
            file_exists('/.dockerenv') ||                    // Docker container
            getenv('KUBERNETES_SERVICE_HOST') !== false ||   // Kubernetes pod
            getenv('DOCKER_ENV') !== false ||                // Docker environment variable
            extension_loaded('swoole') ||                    // Swoole server
            extension_loaded('pthreads') ||                  // pthreads extension
            defined('ROADRUNNER_VERSION') ||                 // RoadRunner server
            defined('FRANKENPHP_VERSION')                    // FrankenPHP server
        );
    }

    // Use thread-safe instantiation in containerized/threaded environments
    if ($needsLocking) {
        // File locking for thread safety
        // [implementation details in coreapp/libraries.php]
    } else {
        // Fast path for traditional PHP deployments (no locking overhead)
        $instances[$libraryName] = new $className();
    }

    return $instances[$libraryName];
}
```

**Environment Detection:**
- **Traditional PHP** (Apache, PHP-FPM): Fast path, zero locking overhead
- **Docker/Kubernetes**: Automatic thread-safe instantiation with file locking
- **Swoole/RoadRunner/FrankenPHP**: Thread-safe instantiation for multi-threaded servers
- **Performance**: Only first access requires locking, subsequent accesses use cached instance

### Best Practices

1. **Reuse library instances** - Don't create manual instances
2. **Keep methods stateless** - Avoid storing data in properties
3. **Avoid heavy constructors** - Defer expensive operations to methods
4. **Use parameters** - Pass data via parameters, not globals
5. **Cache results** - If a method is expensive, cache the result in your code

## Common Library Patterns

### 1. Utility Library (Stateless)

```php
class date_helper {
    public function format($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }

    public function humanize($date) {
        $timestamp = strtotime($date);
        $diff = time() - $timestamp;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        return floor($diff / 86400) . ' days ago';
    }
}
```

### 2. Configuration Library (Stateful)

```php
class config {
    private $settings = [];

    public function load($file) {
        $this->settings = parse_ini_file($file, true);
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value) {
        $this->settings[$key] = $value;
    }
}
```

### 3. Helper with Dependencies

```php
class image_helper {
    private $uploadPath;

    public function __construct() {
        $this->uploadPath = $_ENV['UPLOAD_PATH'] ?? 'uploads/';
    }

    public function resize($source, $width, $height) {
        // Resize image logic
    }

    public function upload($file, $name = null) {
        // Upload file logic
    }
}
```

## Testing Libraries

You can test libraries in isolation:

```php
// tests/test_string_helper.php
<?php
require_once '../coreapp/libraries.php';

// Test slugify
$slug = library('string_helper')->slugify("Hello World!");
assert($slug === 'hello-world', 'Slugify failed');

// Test truncate
$text = "This is a long text that needs truncation";
$truncated = library('string_helper')->truncate($text, 20);
assert(strlen($truncated) <= 23, 'Truncate failed'); // 20 + "..."

// Test random
$random = library('string_helper')->random(16);
assert(strlen($random) === 16, 'Random length failed');

echo "All tests passed!\n";
```

## Route Example

**Route definition in `routes.php`:**

```php
// Demonstrate library usage
Route::get('/blog/slugify/:text:', 'Blog@slugify');
```

**Controller method in `controller/blog.php`:**

```php
function slugify($text = "Sample Blog Post Title") {
    global $PW;

    // Demonstrate all three access methods
    $slug1 = $PW->libraries->string_helper->slugify($text);
    $slug2 = library('string_helper')->slugify($text);
    $slug3 = $libraries['string_helper']->slugify($text);

    // Use other string helper methods
    $truncated = $PW->libraries->string_helper->truncate($text, 20);
    $titleCased = $PW->libraries->string_helper->titleCase($text);
    $wordCount = $PW->libraries->string_helper->wordCount($text);

    $this->show("blog", [
        'original_text' => $text,
        'slug' => $slug1,
        'truncated' => $truncated,
        'title_cased' => $titleCased,
        'word_count' => $wordCount,
        'title' => 'String Helper Demo'
    ]);
}
```

**Access the route:**
```
http://yoursite.com/blog/slugify/Hello-World-Testing-Libraries
```

## Troubleshooting

### Library Not Found

**Error:** `Library 'library_name' not found`

**Solutions:**
1. Check filename matches class name
2. Ensure file is in `libraries/` directory
3. Check file has `.php` extension
4. Verify class is defined in the file

### Method Not Found

**Error:** `Call to undefined method library_name::method()`

**Solutions:**
1. Check method name spelling
2. Ensure method is `public`
3. Verify method exists in the class

### Wrong Instance Returned

**Issue:** Getting wrong library instance

**Solutions:**
1. Check library name spelling
2. Clear any opcode cache (OPcache, APCu)
3. Restart web server

## Migration from Models to Libraries

If you have utility methods in models that don't access the database, consider moving them to libraries:

**Before (in model):**
```php
// models/helper_model.php
class helper_model extends DBConnection {
    public function slugify($text) {
        // No database access - should be a library!
        return strtolower(str_replace(' ', '-', $text));
    }
}
```

**After (in library):**
```php
// libraries/string_helper.php
class string_helper {
    public function slugify($text) {
        return strtolower(str_replace(' ', '-', $text));
    }
}
```

**Benefits:**
- Clearer separation of concerns
- Models for database, libraries for utilities
- Better performance (no unnecessary DBConnection inheritance)
- More intuitive naming

## Summary

**Libraries provide:**
- Reusable utility functions
- Lazy instantiation for performance
- Multiple access methods for flexibility
- Automatic discovery (no registration)
- Instance caching (singleton pattern)

**Best practices:**
- Use `$PW->libraries->` syntax for new code
- Keep libraries stateless when possible
- Document all public methods
- Focus each library on one concern
- Test libraries in isolation

**Example route:** `/blog/slugify/:text:` → `Blog@slugify($text)`

For more information, see:
- `coreapp/libraries.php` - Implementation
- `libraries/string_helper.php` - Example library
- `controller/blog.php` - Usage example (slugify method)
- `routes.php` - Route definition
