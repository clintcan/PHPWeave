# Security Policy

## Supported Versions

We actively support the following versions of PHPWeave with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 2.1.x   | :white_check_mark: |
| 2.0.x   | :white_check_mark: |
| < 2.0   | :x:                |

## Reporting a Vulnerability

We take the security of PHPWeave seriously. If you believe you have found a security vulnerability, please report it to us responsibly.

### How to Report

**Please DO NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of the following methods:

1. **GitHub Security Advisories** (preferred):
   - Navigate to the [Security tab](../../security/advisories)
   - Click "Report a vulnerability"
   - Fill out the advisory form with details

2. **Email**: Send details to `mosaicked_pareja@aleeas.com`

### What to Include

Please include the following information in your report:

- **Type of vulnerability** (e.g., SQL injection, XSS, authentication bypass)
- **Affected component** (e.g., router, controller, model, hooks system)
- **Affected version(s)**
- **Step-by-step instructions** to reproduce the issue
- **Proof of concept** or exploit code (if applicable)
- **Potential impact** of the vulnerability
- **Suggested fix** (if you have one)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days with assessment and timeline
- **Fix Timeline**: Critical issues within 7-14 days, others within 30 days
- **Disclosure**: Coordinated disclosure after patch is released

### What to Expect

1. **Acknowledgment**: We'll confirm receipt of your report
2. **Assessment**: We'll validate and assess the severity
3. **Fix Development**: We'll develop and test a fix
4. **Release**: We'll release a security patch
5. **Credit**: We'll publicly credit you (unless you prefer anonymity)

## Security Best Practices

When using PHPWeave in production, follow these security guidelines:

### Environment Configuration

```php
// .env file - NEVER commit to version control
DEBUG=0                    // Disable debug mode in production
DBCHARSET=utf8mb4         // Use secure charset
```

- **Always** use `.env` for sensitive configuration
- **Never** commit `.env` to version control (use `.env.sample` as template)
- **Set** `DEBUG=0` in production environments
- **Use** strong database credentials

### Database Security

```php
// ALWAYS use prepared statements (built into DBConnection)
$stmt = $this->executePreparedSQL(
    "SELECT * FROM users WHERE id = ?",
    [$userId]
);

// NEVER concatenate user input
// BAD: $sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

- **Use** the `executePreparedSQL()` method for all queries
- **Never** concatenate user input into SQL strings
- **Validate** and sanitize all input data
- **Use** parameterized queries exclusively

### Input Validation & Sanitization

```php
// In controllers
public function store() {
    // Validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');

    if (!$email) {
        // Handle invalid input
    }
}
```

- **Validate** all user input using PHP's filter functions
- **Sanitize** output in views using `htmlspecialchars()`
- **Escape** data appropriately for context (HTML, JS, SQL, URL)
- **Use** whitelisting over blacklisting

### View Security

```php
<!-- In view templates -->
<h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
<a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">Link</a>

<!-- For JSON output -->
<?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?>
```

- **Escape** all output using `htmlspecialchars()`
- **Never** output raw user data
- **Use** Content Security Policy headers
- **Note**: The `show()` method sanitizes template paths to prevent remote includes

### Authentication & Authorization

```php
// Use hooks for authentication
// hooks/authentication.php
Hook::register('before_action_execute', function($data) {
    if (!isset($_SESSION['user']) && !in_array($data['action'], ['login', 'register'])) {
        header('Location: /login');
        Hook::halt();
        exit;
    }
    return $data;
}, 5);
```

- **Implement** authentication using the hooks system
- **Use** secure session configuration:
  ```php
  session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => true,      // HTTPS only
      'httponly' => true,    // No JavaScript access
      'samesite' => 'Strict' // CSRF protection
  ]);
  ```
- **Hash** passwords with `password_hash()` and `password_verify()`
- **Implement** CSRF protection for state-changing operations
- **Use** proper session regeneration on privilege changes

### File Upload Security

```php
// Validate file uploads
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    // Reject file
}

if ($_FILES['file']['size'] > $maxSize) {
    // Reject file
}

// Store outside web root or with random names
$filename = bin2hex(random_bytes(16)) . '.jpg';
```

- **Validate** file types using both MIME type and extension
- **Limit** file sizes
- **Store** uploads outside the web root when possible
- **Use** random filenames
- **Scan** for malware if possible

### Docker Security

```dockerfile
# Use non-root user (already implemented in Dockerfile)
RUN addgroup -g 1000 phpweave && \
    adduser -D -u 1000 -G phpweave phpweave

USER phpweave
```

- **Run** containers as non-root user (already configured)
- **Keep** base images updated
- **Scan** images for vulnerabilities
- **Use** secrets management for sensitive data
- **Limit** container capabilities

### Headers & HTTPS

```php
// Set security headers (add to public/index.php or use hooks)
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'');

// Force HTTPS (in production)
if ($_SERVER['HTTPS'] !== 'on' && $_ENV['DEBUG'] == 0) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

- **Use** HTTPS in production (Let's Encrypt provides free certificates)
- **Set** security headers via hooks or front controller
- **Enable** HSTS (HTTP Strict Transport Security)
- **Implement** Content Security Policy

### Error Handling

```php
// In production (.env)
DEBUG=0

// Errors logged to coreapp/error.log, not displayed
// Custom error pages instead of stack traces
```

- **Disable** debug mode in production (`DEBUG=0`)
- **Log** errors to files, not to users
- **Implement** custom error pages
- **Monitor** error logs regularly
- **Never** expose stack traces or system information

### Dependency Security

```bash
# Keep PHP updated
php --version

# Monitor for security advisories
# Check framework updates regularly
```

- **Keep** PHP version updated (7.4+ supported, 8.x recommended)
- **Monitor** PHPWeave releases for security patches
- **Review** third-party code before integration
- **Audit** dependencies regularly

## Known Security Considerations

### Framework-Specific Notes

1. **Remote Template Inclusion Prevention**:
   - The `show()` method in `coreapp/controller.php:65-73` sanitizes template paths
   - Automatically removes `http://`, `https://`, and `//` to prevent remote includes
   - Templates are restricted to local filesystem

2. **Route Parameter Injection**:
   - Route parameters are extracted from URL and passed to controllers
   - **Always validate** route parameters in controller methods
   - Example: Validate numeric IDs, sanitize string inputs

3. **Hook System**:
   - Hooks execute code at 18 lifecycle points
   - **Carefully review** all hook files before deployment
   - Malicious hooks can bypass security controls
   - **Restrict** write access to `hooks/` directory

4. **Async Job Processing**:
   - Jobs are serialized to `storage/queue/`
   - **Validate** job data before processing
   - **Restrict** filesystem permissions on queue directory
   - **Never** unserialize untrusted data

5. **Model Access**:
   - Models have direct database access
   - **Implement** proper access controls in models
   - **Don't** trust controller data without validation

## Security Features

PHPWeave includes these built-in security features:

- **Prepared Statements**: PDO with parameterized queries (prevents SQL injection)
- **Template Path Sanitization**: Prevents remote file inclusion attacks
- **Error Handler**: Prevents information disclosure via stack traces
- **Hook-Based Security**: Implement authentication/authorization at framework level
- **Request Method Verification**: Router validates HTTP methods
- **Container Isolation**: Docker deployment with non-root user

## Disclosure Policy

- **Coordinated Disclosure**: We prefer coordinated disclosure with 90-day embargo
- **CVE Assignment**: We'll request CVEs for confirmed vulnerabilities
- **Public Credit**: Security researchers will be credited in release notes
- **Hall of Fame**: Contributors listed below

## Security Hall of Fame

We'd like to thank the following individuals for responsibly disclosing security issues:

<!-- Names will be added here as vulnerabilities are reported and fixed -->

*No vulnerabilities reported yet. Be the first to help secure PHPWeave!*

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Docker Security Best Practices](https://docs.docker.com/develop/security-best-practices/)
- [GitHub Security Advisories](https://docs.github.com/en/code-security/security-advisories)

## License

This security policy is licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).

---

**Last Updated**: 2025-10-27
**Version**: 1.0
