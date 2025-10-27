# PHPWeave Security Best Practices

**Version:** 2.0+
**Last Updated:** October 28, 2025
**Security Rating:** A (Excellent)

This guide provides security best practices for developers building applications with PHPWeave.

---

## Table of Contents

1. [Input Validation & Sanitization](#input-validation--sanitization)
2. [Output Escaping (XSS Prevention)](#output-escaping-xss-prevention)
3. [SQL Injection Prevention](#sql-injection-prevention)
4. [Path Traversal Protection](#path-traversal-protection)
5. [Authentication & Authorization](#authentication--authorization)
6. [Session Security](#session-security)
7. [CSRF Protection](#csrf-protection)
8. [File Upload Security](#file-upload-security)
9. [Configuration Security](#configuration-security)
10. [Async Task Security](#async-task-security)

---

## Input Validation & Sanitization

### Always Validate User Input

```php
// ❌ BAD - No validation
$email = $_POST['email'];
$user_model->create(['email' => $email]);

// ✅ GOOD - Validate before use
$email = $_POST['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email address');
}
$user_model->create(['email' => $email]);
```

### Sanitize String Input

```php
// ❌ BAD - Raw input
$username = $_POST['username'];

// ✅ GOOD - Sanitized
$username = trim(strip_tags($_POST['username'] ?? ''));
if (strlen($username) < 3 || strlen($username) > 50) {
    throw new Exception('Username must be 3-50 characters');
}
```

### Whitelist > Blacklist

```php
// ❌ BAD - Blacklist approach
$role = $_POST['role'];
if ($role === 'admin') {
    throw new Exception('Cannot set admin role');
}

// ✅ GOOD - Whitelist approach
$allowed_roles = ['user', 'moderator', 'editor'];
$role = $_POST['role'] ?? '';
if (!in_array($role, $allowed_roles)) {
    throw new Exception('Invalid role');
}
```

---

## Output Escaping (XSS Prevention)

### Always Escape Output in Views

```php
<!-- ❌ BAD - Raw output -->
<h1><?php echo $title; ?></h1>
<p><?php echo $user_input; ?></p>

<!-- ✅ GOOD - Escaped output -->
<h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
<p><?php echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8'); ?></p>

<!-- ✅ GOOD - Using helper method -->
<h1><?php echo $this->escape($title); ?></h1>
<p><?php echo $this->escape($user_input); ?></p>
```

### Escape in JavaScript Context

```php
<!-- ❌ BAD - XSS vulnerability -->
<script>
var name = "<?php echo $user_name; ?>";
</script>

<!-- ✅ GOOD - JSON encoded -->
<script>
var name = <?php echo json_encode($user_name); ?>;
</script>
```

### Escape in HTML Attributes

```php
<!-- ❌ BAD - Attribute injection -->
<input value="<?php echo $value; ?>">

<!-- ✅ GOOD - Escaped -->
<input value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
```

---

## SQL Injection Prevention

PHPWeave uses PDO prepared statements by default, which provides protection against SQL injection.

### Always Use Prepared Statements

```php
// ❌ BAD - String concatenation (SQL INJECTION!)
$sql = "SELECT * FROM users WHERE email = '$email'";
$stmt = $this->pdo->query($sql);

// ✅ GOOD - Prepared statement
$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $this->executePreparedSQL($sql, ['email' => $email]);
```

### Multiple Parameters

```php
// ✅ GOOD - Multiple parameters
$sql = "SELECT * FROM posts WHERE author_id = :author_id AND status = :status";
$stmt = $this->executePreparedSQL($sql, [
    'author_id' => $author_id,
    'status' => 'published'
]);
```

### IN Clauses

```php
// ✅ GOOD - IN clause with placeholders
$ids = [1, 2, 3, 4, 5];
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT * FROM posts WHERE id IN ($placeholders)";
$stmt = $this->pdo->prepare($sql);
$stmt->execute($ids);
```

---

## Path Traversal Protection

PHPWeave's `Controller::show()` method automatically sanitizes template paths, but **never pass user input directly to it**.

### Correct Usage

```php
// ✅ GOOD - Fixed template names
class Blog extends Controller {
    function show($id) {
        $post = $this->models->blog_model->getPost($id);
        $this->show('blog/post', ['post' => $post]);  // Safe
    }
}
```

### Dangerous Usage

```php
// ❌ BAD - User-controlled template (DON'T DO THIS!)
class Page extends Controller {
    function view() {
        $template = $_GET['template'];  // User input!
        $this->show($template);  // DANGEROUS!
    }
}

// ✅ GOOD - Whitelist approach
class Page extends Controller {
    function view() {
        $template = $_GET['template'] ?? '';
        $allowed = ['home', 'about', 'contact'];

        if (!in_array($template, $allowed)) {
            $template = 'home';
        }

        $this->show($template);  // Safe
    }
}
```

### File Upload Paths

```php
// ❌ BAD - User-controlled filename
$filename = $_FILES['upload']['name'];
move_uploaded_file($_FILES['upload']['tmp_name'], "uploads/$filename");

// ✅ GOOD - Generate safe filename
$ext = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
$allowed_ext = ['jpg', 'png', 'gif', 'pdf'];

if (!in_array(strtolower($ext), $allowed_ext)) {
    throw new Exception('Invalid file type');
}

$filename = uniqid() . '.' . $ext;
$upload_dir = realpath('../uploads');
move_uploaded_file($_FILES['upload']['tmp_name'], "$upload_dir/$filename");
```

---

## Authentication & Authorization

PHPWeave doesn't include built-in authentication. Implement it using the hooks system.

### Authentication Hook

```php
// hooks/authentication.php
Hook::register('before_action_execute', function($data) {
    $public_controllers = ['Login', 'Register', 'ForgotPassword'];

    if (!isset($_SESSION['user_id']) && !in_array($data['controller'], $public_controllers)) {
        header('Location: /login');
        Hook::halt();
        exit;
    }

    return $data;
}, 5);
```

### Authorization Hook

```php
// hooks/authorization.php
Hook::register('before_action_execute', function($data) {
    // Only admins can access Admin controller
    if ($data['controller'] === 'Admin') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied');
        }
    }

    return $data;
}, 10);
```

### Password Hashing

```php
// ✅ GOOD - Use PHP's password functions
class User extends Controller {
    function register() {
        $password = $_POST['password'];

        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Store hash in database
        global $PW;
        $PW->models->user_model->create([
            'email' => $_POST['email'],
            'password' => $hash
        ]);
    }

    function login() {
        global $PW;
        $user = $PW->models->user_model->getByEmail($_POST['email']);

        // Verify password
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: /dashboard');
        } else {
            $this->show('login', ['error' => 'Invalid credentials']);
        }
    }
}
```

---

## Session Security

### Secure Session Configuration

```php
// public/index.php - Add at the top
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access
ini_set('session.cookie_secure', 1);    // HTTPS only (production)
ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs

session_start();

// Regenerate session ID on login
if (isset($_POST['login'])) {
    session_regenerate_id(true);
}
```

### Session Timeout

```php
// hooks/session_timeout.php
Hook::register('framework_start', function($data) {
    if (isset($_SESSION['last_activity'])) {
        $timeout = 30 * 60; // 30 minutes

        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_unset();
            session_destroy();
            header('Location: /login?timeout=1');
            exit;
        }
    }

    $_SESSION['last_activity'] = time();
    return $data;
});
```

---

## CSRF Protection

### Generate CSRF Token

```php
// In Controller base class or helper
protected function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

protected function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

### In Forms

```php
<!-- In view -->
<form method="POST" action="/user/update">
    <input type="hidden" name="csrf_token" value="<?php echo $this->generateCSRFToken(); ?>">
    <!-- Other fields -->
    <button type="submit">Submit</button>
</form>
```

### Verify in Controller

```php
class User extends Controller {
    function update() {
        $token = $_POST['csrf_token'] ?? '';

        if (!$this->verifyCSRFToken($token)) {
            die('CSRF token validation failed');
        }

        // Process update...
    }
}
```

---

## File Upload Security

### Validate File Uploads

```php
class Upload extends Controller {
    function process() {
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed');
        }

        $file = $_FILES['file'];

        // 1. Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large');
        }

        // 2. Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception('Invalid file type');
        }

        // 3. Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($ext, $allowed_ext)) {
            throw new Exception('Invalid file extension');
        }

        // 4. Generate safe filename
        $new_filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        // 5. Use absolute path
        $upload_dir = realpath('../uploads');
        if (!$upload_dir || !is_writable($upload_dir)) {
            throw new Exception('Upload directory not writable');
        }

        // 6. Move file
        $destination = $upload_dir . '/' . $new_filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }

        // 7. Store reference in database
        global $PW;
        $PW->models->file_model->create([
            'filename' => $new_filename,
            'original_name' => basename($file['name']),
            'mime_type' => $mime,
            'size' => $file['size'],
            'user_id' => $_SESSION['user_id']
        ]);
    }
}
```

---

## Configuration Security

### Environment Variables

```php
// ✅ GOOD - Use .env file (excluded from git)
DBHOST=localhost
DBNAME=myapp
DBUSER=dbuser
DBPASSWORD=secure_password_here
DEBUG=0
```

### Never Hardcode Credentials

```php
// ❌ BAD - Hardcoded
$db = new PDO('mysql:host=localhost', 'root', 'password123');

// ✅ GOOD - From config
$db = new PDO(
    "mysql:host={$GLOBALS['configs']['DBHOST']}",
    $GLOBALS['configs']['DBUSER'],
    $GLOBALS['configs']['DBPASSWORD']
);
```

### Debug Mode

```php
// Always set DEBUG=0 in production
if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'] == 1) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
```

---

## Async Task Security

### Use Static Methods or Job Classes

```php
// ✅ GOOD - Static method (secure, no library needed)
class EmailTasks {
    public static function sendWelcome($user_id) {
        global $PW;
        $user = $PW->models->user_model->find($user_id);
        mail($user['email'], 'Welcome', 'Thanks for signing up!');
    }
}
Async::run(['EmailTasks', 'sendWelcome']);

// ✅ BEST - Job class (recommended for production)
Async::queue('SendWelcomeEmailJob', ['user_id' => $user_id]);
```

### Never Pass Sensitive Data

```php
// ❌ BAD - Passing sensitive data
Async::queue('SendEmailJob', [
    'password' => $password,  // DON'T DO THIS!
    'api_key' => $api_key     // DON'T DO THIS!
]);

// ✅ GOOD - Pass only IDs, fetch data in job
Async::queue('SendEmailJob', [
    'user_id' => $user_id  // Fetch password from DB in job
]);
```

---

## Security Headers

Add these headers for additional protection:

```php
// public/index.php or in a hook
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// HTTPS only (in production)
if ($_SERVER['HTTPS'] ?? '' === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
```

---

## Security Checklist

Before deploying to production, verify:

- [ ] `DEBUG=0` in `.env`
- [ ] `.env` file excluded from version control
- [ ] All database queries use prepared statements
- [ ] All output is escaped in views
- [ ] HTTPS enabled in production
- [ ] Session security configured
- [ ] CSRF protection implemented for forms
- [ ] File upload validation in place
- [ ] Authentication/authorization hooks configured
- [ ] Security headers added
- [ ] Error messages don't expose sensitive information
- [ ] File permissions set correctly (755 for directories, 644 for files)

---

## Testing Security

Run security tests regularly:

```bash
# PHPStan static analysis
vendor/bin/phpstan analyse --level=5 coreapp public

# Security-specific tests
php tests/test_path_traversal.php

# Full test suite
php tests/test_hooks.php
php tests/test_models.php
php tests/test_controllers.php
```

---

## Reporting Security Issues

If you discover a security vulnerability in PHPWeave:

1. **DO NOT** open a public GitHub issue
2. Email: mosaicked_pareja@aleeas.com
3. Include details about the vulnerability
4. Allow time for a patch before public disclosure

See `SECURITY.md` for full reporting guidelines.

---

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PHPWeave Security Audit](../SECURITY_AUDIT.md)
- [PHPWeave Hooks Guide](HOOKS.md)

---

**Last Updated:** October 28, 2025
**PHPWeave Version:** 2.0+
**Security Rating:** A (Excellent)
