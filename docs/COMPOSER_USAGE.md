# Using Composer Packages with PHPWeave

**Version:** 2.2.2+
**Status:** Optional Feature

---

## Overview

PHPWeave is designed as a **zero-dependency** framework that works perfectly without Composer. However, v2.2.2+ includes **automatic Composer support** for developers who want to use third-party packages.

### Key Principles

- ✅ **Framework works without Composer** - No Composer required for core functionality
- ✅ **Optional package support** - Install only what you need
- ✅ **Automatic loading** - Framework detects and loads `vendor/autoload.php` if it exists
- ✅ **No code changes** - Just install packages and use them
- ✅ **Production-friendly** - Use `composer install --no-dev` to exclude dev tools

---

## How It Works

### Automatic Loading

The framework automatically loads Composer packages during initialization:

```php
// public/index.php:91-93
// Load composer autoload if available (optional - only if using composer packages)
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}
```

**Loading Order:**
1. Core framework components (hooks, models, libraries, router, controller)
2. **Composer autoload** (if exists)
3. Route caching and dispatch

This means Composer packages are available in:
- ✅ All controllers
- ✅ All models
- ✅ All libraries
- ✅ All views
- ✅ All routes

---

## Installation

### Install Composer (if not already installed)

```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# Download from: https://getcomposer.org/download/
```

### Install Packages

```bash
# Navigate to framework root
cd /path/to/phpweave

# Install packages
composer require phpmailer/phpmailer
composer require nesbot/carbon
composer require guzzlehttp/guzzle
```

### Production Deployment

```bash
# Install without dev dependencies
composer install --no-dev --optimize-autoloader
```

---

## Usage Examples

### Example 1: Email with PHPMailer

**Install:**
```bash
composer require phpmailer/phpmailer
```

**Controller Usage:**
```php
<?php
// controller/email.php
class Email extends Controller
{
    function send()
    {
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USERNAME');
            $mail->Password = getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('noreply@example.com', 'PHPWeave App');
            $mail->addAddress('user@example.com', 'User Name');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from PHPWeave';
            $mail->Body = '<h1>Hello from PHPWeave!</h1><p>This is a test email.</p>';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
```

**Route:**
```php
Route::get('/send-email', 'Email@send');
```

---

### Example 2: Date/Time with Carbon

**Install:**
```bash
composer require nesbot/carbon
```

**Library Usage:**
```php
<?php
// libraries/date_helper.php
class date_helper
{
    public function humanize($date)
    {
        use Carbon\Carbon;
        return Carbon::parse($date)->diffForHumans();
    }

    public function format($date, $format = 'Y-m-d H:i:s')
    {
        use Carbon\Carbon;
        return Carbon::parse($date)->format($format);
    }

    public function addDays($date, $days)
    {
        use Carbon\Carbon;
        return Carbon::parse($date)->addDays($days);
    }

    public function timezone($date, $timezone)
    {
        use Carbon\Carbon;
        return Carbon::parse($date)->setTimezone($timezone);
    }
}
```

**Controller Usage:**
```php
<?php
// controller/blog.php
class Blog extends Controller
{
    function index()
    {
        global $PW;

        $posts = [
            ['title' => 'Post 1', 'created_at' => '2024-01-01'],
            ['title' => 'Post 2', 'created_at' => '2024-10-15'],
            ['title' => 'Post 3', 'created_at' => '2024-11-01'],
        ];

        // Format dates using Carbon
        foreach ($posts as &$post) {
            $post['created_at_human'] = $PW->libraries->date_helper->humanize($post['created_at']);
        }

        $this->show('blog/index', ['posts' => $posts]);
    }
}
```

**View:**
```php
<!-- views/blog/index.php -->
<h1>Blog Posts</h1>
<?php foreach ($posts as $post): ?>
    <article>
        <h2><?php echo $post['title']; ?></h2>
        <p>Published <?php echo $post['created_at_human']; ?></p>
    </article>
<?php endforeach; ?>
```

---

### Example 3: HTTP Client with Guzzle

**Install:**
```bash
composer require guzzlehttp/guzzle
```

**Model Usage:**
```php
<?php
// models/api_model.php
class ApiModel extends DBConnection
{
    public function fetchGitHubUser($username)
    {
        use GuzzleHttp\Client;
        use GuzzleHttp\Exception\GuzzleException;

        $client = new Client([
            'base_uri' => 'https://api.github.com',
            'timeout' => 5.0,
        ]);

        try {
            $response = $client->request('GET', "/users/{$username}", [
                'headers' => [
                    'User-Agent' => 'PHPWeave-App',
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            error_log("GitHub API Error: " . $e->getMessage());
            return null;
        }
    }
}
```

**Controller Usage:**
```php
<?php
// controller/github.php
class Github extends Controller
{
    function user($username)
    {
        global $PW;

        $userData = $PW->models->api_model->fetchGitHubUser($username);

        if ($userData) {
            $this->show('github/user', [
                'user' => $userData,
                'username' => $username
            ]);
        } else {
            $this->show('errors/404', ['message' => 'User not found']);
        }
    }
}
```

---

### Example 4: Validation with Respect\Validation

**Install:**
```bash
composer require respect/validation
```

**Model Usage:**
```php
<?php
// models/user_model.php
class UserModel extends DBConnection
{
    public function validateUserData($data)
    {
        use Respect\Validation\Validator as v;
        use Respect\Validation\Exceptions\ValidationException;

        $validator = v::key('email', v::email())
                      ->key('username', v::alnum()->length(3, 20))
                      ->key('age', v::intVal()->min(18)->max(120))
                      ->key('website', v::url(), false); // optional

        try {
            $validator->assert($data);
            return ['valid' => true];
        } catch (ValidationException $e) {
            return [
                'valid' => false,
                'errors' => $e->getMessages()
            ];
        }
    }

    public function createUser($data)
    {
        $validation = $this->validateUserData($data);

        if (!$validation['valid']) {
            return $validation;
        }

        // Create user in database
        $stmt = $this->executePreparedSQL(
            "INSERT INTO users (email, username, age, website) VALUES (?, ?, ?, ?)",
            [$data['email'], $data['username'], $data['age'], $data['website'] ?? null]
        );

        return [
            'valid' => true,
            'user_id' => $this->pdo->lastInsertId()
        ];
    }
}
```

---

### Example 5: Async with Closures (opis/closure)

**Install:**
```bash
composer require opis/closure
```

**Usage:**
```php
<?php
// Now closures work with Async::run()
Async::run(function($email, $subject, $body) {
    mail($email, $subject, $body);
}, ['user@example.com', 'Hello', 'Test message']);

// Without opis/closure, use static methods:
class EmailHelper {
    public static function send($email, $subject, $body) {
        mail($email, $subject, $body);
    }
}

Async::run(['EmailHelper', 'send'], ['user@example.com', 'Hello', 'Test']);
```

---

## Development Tools

### PHPStan (Static Analysis)

**Install:**
```bash
composer require --dev phpstan/phpstan
```

**Run:**
```bash
composer phpstan
# Or directly
./vendor/bin/phpstan analyse --no-progress
```

**Benefits:**
- Find type errors before runtime
- Catch bugs early
- Improve code quality

---

### Psalm (Security Analysis)

**Install:**
```bash
composer require --dev vimeo/psalm
```

**Run:**
```bash
composer psalm-security
# Or directly
./vendor/bin/psalm --taint-analysis --no-progress
```

**Benefits:**
- Find security vulnerabilities
- SQL injection detection
- XSS vulnerability detection
- Path traversal detection

---

## composer.json Structure

PHPWeave's `composer.json`:

```json
{
    "name": "phpweave/phpweave",
    "description": "Lightweight MVC PHP framework",
    "require": {
        "php": ">=7.4"
        // No runtime dependencies - zero-dependency core
    },
    "require-dev": {
        "phpstan/phpstan": "^2.1",
        "vimeo/psalm": "^6.0"
    },
    "suggest": {
        "opis/closure": "Required for Async::run() with closures"
    },
    "scripts": {
        "phpstan": "phpstan analyse --no-progress",
        "psalm": "psalm --no-progress",
        "psalm-security": "psalm --taint-analysis --no-progress",
        "check": ["@phpstan", "@psalm-security"]
    }
}
```

---

## Best Practices

### 1. Use Composer for Third-Party Packages Only

✅ **Good:**
```php
// Use Composer for external libraries
composer require phpmailer/phpmailer
composer require nesbot/carbon
```

❌ **Avoid:**
```php
// Don't install packages that duplicate framework features
composer require symfony/routing  // PHPWeave has routing
composer require illuminate/database  // PHPWeave has PDO
```

---

### 2. Keep Dependencies Minimal

Only install what you actually need:

```bash
# Good - specific packages for specific needs
composer require phpmailer/phpmailer

# Avoid - installing entire frameworks
composer require laravel/framework
```

---

### 3. Use --no-dev in Production

```bash
# Development
composer install

# Production
composer install --no-dev --optimize-autoloader
```

---

### 4. Version Constraints

Use semantic versioning in `composer.json`:

```json
{
    "require": {
        "phpmailer/phpmailer": "^6.8",  // Allow 6.x updates
        "nesbot/carbon": "^2.70"        // Allow 2.x updates
    }
}
```

---

### 5. Security Updates

Regularly update dependencies:

```bash
# Check for updates
composer outdated

# Update all packages
composer update

# Update specific package
composer update phpmailer/phpmailer
```

---

## Common Use Cases

### Email Sending
- `phpmailer/phpmailer` - Full-featured email library
- `sendgrid/sendgrid` - SendGrid API
- `mailgun/mailgun-php` - Mailgun API

### Date/Time
- `nesbot/carbon` - DateTime manipulation
- `cakephp/chronos` - Immutable DateTime

### HTTP Clients
- `guzzlehttp/guzzle` - Full-featured HTTP client
- `symfony/http-client` - Symfony HTTP client

### Validation
- `respect/validation` - Comprehensive validation
- `particle/validator` - Lightweight validation

### PDF Generation
- `dompdf/dompdf` - HTML to PDF
- `tecnickcom/tcpdf` - PDF generation

### Image Manipulation
- `intervention/image` - Image processing
- `gumlet/php-image-resize` - Simple image resize

### Template Engines (if you don't like PHP templates)
- `twig/twig` - Twig templating
- `smarty/smarty` - Smarty templating

---

## Troubleshooting

### Problem: "Class not found" errors

**Cause:** Composer autoload not working

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Or with optimization
composer dump-autoload --optimize
```

---

### Problem: Packages not available in controllers

**Cause:** `vendor/autoload.php` doesn't exist or wrong location

**Solution:**
```bash
# Check if vendor directory exists
ls -la vendor/

# If not, install dependencies
composer install

# Verify autoload file exists
ls -la vendor/autoload.php
```

---

### Problem: Composer command not found

**Cause:** Composer not installed or not in PATH

**Solution:**
```bash
# Install composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Or use php composer.phar instead of composer
php composer.phar install
```

---

### Problem: Memory limit errors

**Cause:** Composer requires more memory

**Solution:**
```bash
# Increase PHP memory limit temporarily
php -d memory_limit=-1 /usr/local/bin/composer install

# Or update php.ini
memory_limit = 512M
```

---

## Migration Guide

### From No Composer to With Composer

**Before (v2.2.1):**
```php
// Had to implement everything manually
function sendEmail($to, $subject, $body) {
    mail($to, $subject, $body);
}
```

**After (v2.2.2+):**
```php
// Install package
composer require phpmailer/phpmailer

// Use in controller
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);
// ... full featured email sending
```

---

## FAQ

### Q: Do I need Composer for PHPWeave to work?
**A:** No. PHPWeave is zero-dependency and works perfectly without Composer.

### Q: Will existing code break if I install Composer?
**A:** No. Composer support is backward compatible. Existing code continues to work.

### Q: Can I use Composer in production?
**A:** Yes. Use `composer install --no-dev` to exclude development tools.

### Q: What happens if vendor/autoload.php doesn't exist?
**A:** Framework continues normally. The loading is conditional and safe.

### Q: Do I need to modify public/index.php?
**A:** No. v2.2.2+ already includes automatic Composer loading.

### Q: Can I use both Composer packages and PHPWeave libraries?
**A:** Yes. They work together seamlessly.

---

## Additional Resources

- **Composer Documentation:** https://getcomposer.org/doc/
- **Packagist (Package Repository):** https://packagist.org/
- **Composer Best Practices:** https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md

---

**Last Updated:** November 1, 2025
**PHPWeave Version:** 2.2.2+
