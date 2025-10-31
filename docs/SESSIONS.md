# Session Management in PHPWeave

**Version:** 2.2.1+
**Last Updated:** 2025

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [File-Based Sessions](#file-based-sessions)
- [Database-Based Sessions](#database-based-sessions)
- [Session API](#session-api)
- [Security Best Practices](#security-best-practices)
- [Advanced Usage](#advanced-usage)
- [Troubleshooting](#troubleshooting)

---

## Overview

PHPWeave includes a complete session management system that supports both traditional file-based sessions and modern database-backed sessions. The system is designed to work seamlessly in any environment, from single-server deployments to distributed, load-balanced systems.

### Key Features

- **Dual Storage Modes**: File-based (default) and database-backed sessions
- **Zero Configuration**: Works out-of-the-box with sensible defaults
- **Auto-Fallback**: Automatically switches to file sessions if database unavailable
- **Database-Free Compatible**: Works perfectly in database-free mode
- **Security Built-in**: IP tracking, user agent logging, secure prepared statements
- **Simple API**: Intuitive helper methods for common operations
- **Automatic Garbage Collection**: Expired sessions cleaned up automatically

---

## Features

### File-Based Sessions (Default)

**Perfect for:**
- Single-server deployments
- Development environments
- Simple applications
- Quick prototyping

**Advantages:**
- No setup required
- Fast performance
- No database dependency
- Standard PHP behavior

### Database-Based Sessions

**Perfect for:**
- Multi-server environments
- Load-balanced setups
- Docker/Kubernetes deployments
- Distributed systems
- Session analytics

**Advantages:**
- Centralized session storage
- Persistent across server restarts
- Share sessions across multiple servers
- Query active sessions
- Better monitoring

---

## Quick Start

### Using File Sessions (Default)

File sessions work automatically with no configuration:

```php
// Start using sessions immediately
$_SESSION['user_id'] = 123;
$_SESSION['username'] = 'john_doe';

// Read session data
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'guest';

// Check if logged in
if (isset($_SESSION['user_id'])) {
    // User is authenticated
}

// Logout
session_destroy();
```

### Switching to Database Sessions

**Step 1:** Configure `.env`
```ini
SESSION_DRIVER=database
SESSION_LIFETIME=1800  # 30 minutes (optional)
```

**Step 2:** Run migration
```bash
php migrate.php migrate
```

**Step 3:** Use sessions normally
```php
$_SESSION['user_id'] = 123; // Now stored in database
```

---

## Configuration

### Environment Variables

Configure sessions in your `.env` file:

```ini
# Session Driver
SESSION_DRIVER=file              # Options: file, database (default: file)

# Session Lifetime
SESSION_LIFETIME=1800            # Lifetime in seconds (default: 1800 = 30 minutes)

# Database Configuration (required if SESSION_DRIVER=database)
ENABLE_DATABASE=1
DBHOST=localhost
DBNAME=your_database
DBUSER=your_username
DBPASSWORD=your_password
```

### Configuration Options

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `SESSION_DRIVER` | `file`, `database` | `file` | Storage backend for sessions |
| `SESSION_LIFETIME` | Integer (seconds) | `1800` | How long sessions remain active (30 min) |

---

## File-Based Sessions

### How It Works

File-based sessions use PHP's native session handling. Session data is stored in the server's temporary directory (typically `/tmp` on Linux or `C:\Windows\Temp` on Windows).

### Configuration

No configuration required! Sessions work automatically with PHP defaults.

### Storage Location

Sessions are stored in:
- Linux/Mac: `/tmp` or `/var/lib/php/sessions`
- Windows: `C:\Windows\Temp` or configured `session.save_path`

To customize the storage location, use PHP's `session_save_path()`:

```php
// In public/index.php or bootstrap file
session_save_path('/path/to/custom/sessions');
session_start();
```

### Advantages

- ✅ No database required
- ✅ Fast performance
- ✅ Simple setup
- ✅ Standard PHP behavior
- ✅ Works in database-free mode

### Limitations

- ❌ Not suitable for load-balanced environments
- ❌ Lost on server restart (unless persisted)
- ❌ Difficult to share across multiple servers
- ❌ Limited session analytics

---

## Database-Based Sessions

### How It Works

Database sessions store all session data in a MySQL/PostgreSQL/SQLite database table. This enables session sharing across multiple servers and persistence across restarts.

### Setup

#### 1. Configure .env

```ini
SESSION_DRIVER=database
SESSION_LIFETIME=1800
ENABLE_DATABASE=1
DBHOST=localhost
DBNAME=phpweave_db
DBUSER=phpweave_user
DBPASSWORD=your_password
```

#### 2. Run Migration

The sessions table migration is included in the framework:

```bash
php migrate.php migrate
```

This creates the `sessions` table with the following schema:

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 3. Use Sessions Normally

```php
// Session data now stored in database
$_SESSION['user_id'] = 123;
$_SESSION['cart_items'] = [1, 2, 3];
```

### Database Table Structure

| Column | Type | Description |
|--------|------|-------------|
| `id` | VARCHAR(255) | Session ID (primary key) |
| `user_id` | INT | Optional user ID for tracking authenticated sessions |
| `ip_address` | VARCHAR(45) | Client IP address for security auditing |
| `user_agent` | TEXT | Browser/device information |
| `payload` | LONGTEXT | Serialized session data |
| `last_activity` | INT | Unix timestamp of last session activity |
| `created_at` | TIMESTAMP | When session was created |
| `updated_at` | TIMESTAMP | When session was last updated |

### Advantages

- ✅ Share sessions across multiple servers
- ✅ Persistent across server restarts
- ✅ Perfect for Docker/Kubernetes
- ✅ Centralized session management
- ✅ Query active sessions
- ✅ Security auditing (IP, user agent)
- ✅ Better monitoring

### Querying Active Sessions

```php
// Get all active sessions
$sql = "SELECT * FROM sessions WHERE last_activity >= :expiry";
$stmt = $db->executePreparedSQL($sql, [
    'expiry' => time() - 1800 // 30 minutes
]);
$activeSessions = $db->fetchAll($stmt);

// Get sessions by user ID
$sql = "SELECT * FROM sessions WHERE user_id = :user_id";
$stmt = $db->executePreparedSQL($sql, ['user_id' => 123]);
$userSessions = $db->fetchAll($stmt);

// Count active sessions
$sql = "SELECT COUNT(*) as count FROM sessions WHERE last_activity >= :expiry";
$stmt = $db->executePreparedSQL($sql, ['expiry' => time() - 1800]);
$count = $db->fetch($stmt)['count'];
```

### Garbage Collection

Expired sessions are automatically cleaned up by PHP's garbage collector. The `_gc()` method removes sessions older than the configured lifetime:

```php
// Runs automatically based on PHP's gc_probability
// Deletes sessions where last_activity < (current_time - SESSION_LIFETIME)
```

---

## Session API

### Using the Session Class

PHPWeave provides a `Session` class with convenient helper methods:

```php
$session = new Session();
```

### Helper Methods

#### set($key, $value)

Set a session variable:

```php
$session->set('user_id', 123);
$session->set('username', 'john_doe');
$session->set('cart', ['item1', 'item2']);
```

#### get($key, $default = null)

Get a session variable with optional default:

```php
$userId = $session->get('user_id');
$username = $session->get('username', 'guest'); // Returns 'guest' if not set
$theme = $session->get('theme', 'light');
```

#### has($key)

Check if a session variable exists:

```php
if ($session->has('user_id')) {
    // User is logged in
} else {
    // User is guest
}
```

#### delete($key)

Remove a session variable:

```php
$session->delete('temp_data');
$session->delete('flash_message');
```

#### flush()

Clear all session data:

```php
$session->flush(); // Empties $_SESSION array
```

#### regenerate($deleteOldSession = true)

Regenerate session ID (security best practice):

```php
// After successful login
$session->regenerate(); // Creates new session ID, deletes old one

// Keep old session data
$session->regenerate(false); // Creates new ID, keeps old session
```

#### getDriver()

Get the current session driver:

```php
$driver = $session->getDriver(); // Returns 'file' or 'database'

if ($driver === 'database') {
    echo "Using database sessions";
}
```

### Complete Example

```php
<?php
// Initialize session
$session = new Session();

// Login process
if ($_POST['username'] && $_POST['password']) {
    // Authenticate user...
    $user = authenticateUser($_POST['username'], $_POST['password']);

    if ($user) {
        // Regenerate session ID for security
        $session->regenerate();

        // Store user data
        $session->set('user_id', $user->id);
        $session->set('username', $user->username);
        $session->set('role', $user->role);

        redirect('/dashboard');
    }
}

// Check if logged in
if (!$session->has('user_id')) {
    redirect('/login');
}

// Get user info
$userId = $session->get('user_id');
$username = $session->get('username');

// Logout
if ($_GET['action'] === 'logout') {
    $session->flush();
    $session->regenerate();
    redirect('/login');
}
```

---

## Security Best Practices

### 1. Regenerate Session ID After Login

Always regenerate the session ID after authentication to prevent session fixation attacks:

```php
// After successful login
$session->regenerate();
$session->set('user_id', $user->id);
```

### 2. Use HTTPS

Always use HTTPS in production to prevent session hijacking:

```ini
# php.ini
session.cookie_secure = 1
```

### 3. HttpOnly Cookies

Prevent JavaScript access to session cookies:

```ini
# php.ini
session.cookie_httponly = 1
```

### 4. SameSite Cookie Attribute

Protect against CSRF attacks:

```ini
# php.ini (PHP 7.3+)
session.cookie_samesite = Strict
```

### 5. Session Timeout

Configure appropriate session lifetime:

```ini
# .env
SESSION_LIFETIME=1800  # 30 minutes
```

### 6. IP Address Validation (Optional)

Track session IP addresses to detect hijacking:

```php
// Store IP on login
$session->set('ip_address', $_SERVER['REMOTE_ADDR']);

// Validate on each request
if ($session->get('ip_address') !== $_SERVER['REMOTE_ADDR']) {
    // Potential session hijacking
    $session->flush();
    redirect('/login');
}
```

### 7. User Agent Validation (Optional)

Similar to IP validation:

```php
$session->set('user_agent', $_SERVER['HTTP_USER_AGENT']);

// Validate later
if ($session->get('user_agent') !== $_SERVER['HTTP_USER_AGENT']) {
    // Potential session hijacking
}
```

---

## Advanced Usage

### Custom Session Handler

If you need custom session storage (Redis, Memcached, etc.), you can extend the `Session` class:

```php
class RedisSession extends Session {
    private $redis;

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);

        session_set_save_handler(
            array($this, "_open"),
            array($this, "_close"),
            array($this, "_read"),
            array($this, "_write"),
            array($this, "_destroy"),
            array($this, "_gc")
        );

        session_start();
    }

    public function _read($id) {
        return $this->redis->get($id) ?: '';
    }

    public function _write($id, $data) {
        return $this->redis->setex($id, $this->lifetime, $data);
    }

    // Implement other methods...
}
```

### Session Middleware with Hooks

Use PHPWeave hooks to implement session-based middleware:

```php
// hooks/session_auth.php
Hook::register('before_action_execute', function($data) {
    $session = new Session();

    // Check if user is logged in
    if (!$session->has('user_id')) {
        header('Location: /login');
        Hook::halt();
        exit;
    }

    // Add user data to request
    $data['current_user'] = [
        'id' => $session->get('user_id'),
        'username' => $session->get('username'),
        'role' => $session->get('role')
    ];

    return $data;
}, 5);
```

### Flash Messages

Implement one-time flash messages:

```php
class Flash {
    public static function set($key, $message) {
        $session = new Session();
        $session->set("flash_$key", $message);
    }

    public static function get($key) {
        $session = new Session();
        $message = $session->get("flash_$key");
        $session->delete("flash_$key");
        return $message;
    }

    public static function has($key) {
        $session = new Session();
        return $session->has("flash_$key");
    }
}

// Usage
Flash::set('success', 'User created successfully!');
redirect('/users');

// On next page
if (Flash::has('success')) {
    echo Flash::get('success'); // Displays once, then removed
}
```

---

## Troubleshooting

### Sessions Not Persisting

**Problem:** Session data doesn't persist between requests.

**Solutions:**
1. Check if `session_start()` is called
2. Verify session cookies are being sent (check browser dev tools)
3. Check PHP's `session.save_path` is writable
4. Verify database connection (if using database sessions)

### "Headers Already Sent" Error

**Problem:** `Warning: Cannot modify header information - headers already sent`

**Solution:** Ensure `session_start()` is called before any output:

```php
<?php
// No whitespace or output before this
session_start();

// Your code here
```

### Database Sessions Not Working

**Problem:** Sessions fall back to file-based storage.

**Solutions:**
1. Check `.env` configuration:
   ```ini
   SESSION_DRIVER=database
   ENABLE_DATABASE=1
   ```
2. Verify database connection credentials
3. Run migrations: `php migrate.php migrate`
4. Check `coreapp/error.log` for error messages

### Session Timeout Too Short/Long

**Problem:** Sessions expire too quickly or last too long.

**Solution:** Configure `SESSION_LIFETIME` in `.env`:

```ini
SESSION_LIFETIME=3600  # 1 hour
SESSION_LIFETIME=7200  # 2 hours
SESSION_LIFETIME=86400 # 24 hours
```

### Can't Share Sessions Across Subdomains

**Problem:** Sessions don't work across `app.example.com` and `api.example.com`.

**Solution:** Configure session cookie domain in `php.ini`:

```ini
session.cookie_domain = .example.com
```

Or in code:

```php
ini_set('session.cookie_domain', '.example.com');
session_start();
```

---

## Database-Free Mode Compatibility

PHPWeave's Session class is fully compatible with database-free mode:

```ini
# .env
ENABLE_DATABASE=0
SESSION_DRIVER=database  # Requested but unavailable
```

**Result:** Sessions automatically fall back to file-based storage with a warning logged to `error.log`:

```
Session: Database driver requested but database is disabled. Falling back to file sessions.
```

This ensures your application continues to work even if the database becomes unavailable.

---

## Migration Guide

### From PHP Native Sessions

No migration needed! PHPWeave sessions are 100% compatible with standard PHP `$_SESSION`:

```php
// Before (native PHP)
session_start();
$_SESSION['user_id'] = 123;

// After (PHPWeave) - still works!
$_SESSION['user_id'] = 123;

// Optional: Use helper methods
$session = new Session();
$session->set('user_id', 123);
```

### From File to Database Sessions

**Step 1:** Configure database sessions in `.env`:
```ini
SESSION_DRIVER=database
```

**Step 2:** Run migration:
```bash
php migrate.php migrate
```

**Step 3:** Existing sessions will expire, new sessions use database

No code changes required!

---

## Performance Considerations

### File Sessions
- **Read Performance:** Very fast (direct file access)
- **Write Performance:** Very fast (direct file write)
- **Scalability:** Limited to single server
- **Best For:** Single-server deployments

### Database Sessions
- **Read Performance:** Fast (indexed queries)
- **Write Performance:** Good (prepared statements)
- **Scalability:** Excellent (multi-server)
- **Best For:** Distributed systems, load-balanced environments

### Optimization Tips

1. **Use Indexes:** The migration creates indexes on `last_activity` and `user_id`
2. **Set Appropriate Lifetime:** Shorter lifetimes mean less data to store
3. **Monitor Table Size:** Regularly check sessions table size
4. **Tune Garbage Collection:** Adjust PHP's `session.gc_probability`

```ini
# php.ini
session.gc_probability = 1
session.gc_divisor = 100      # 1% chance of GC on each request
session.gc_maxlifetime = 1800  # 30 minutes
```

---

## API Reference

### Session Class Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `set()` | `$key, $value` | `void` | Set session variable |
| `get()` | `$key, $default = null` | `mixed` | Get session variable |
| `has()` | `$key` | `bool` | Check if variable exists |
| `delete()` | `$key` | `void` | Remove session variable |
| `flush()` | - | `void` | Clear all session data |
| `regenerate()` | `$deleteOld = true` | `bool` | Regenerate session ID |
| `getDriver()` | - | `string` | Get current driver (file/database) |

### Session Handler Methods (Internal)

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `_open()` | `$savePath, $sessionName` | `bool` | Open session handler |
| `_close()` | - | `bool` | Close session handler |
| `_read()` | `$id` | `string` | Read session data |
| `_write()` | `$id, $data` | `bool` | Write session data |
| `_destroy()` | `$id` | `bool` | Destroy session |
| `_gc()` | `$maxlifetime` | `bool` | Garbage collection |

---

## Further Reading

- [PHP Session Documentation](https://www.php.net/manual/en/book.session.php)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [Database Migrations Guide](MIGRATIONS.md)
- [Hooks System Documentation](HOOKS.md)

---

**Need Help?** Check the troubleshooting section or file an issue on GitHub.
