# Models Guide

Complete documentation for the PHPWeave models system.

---

## Table of Contents

- [Overview](#overview)
- [Lazy Loading](#lazy-loading)
- [Accessing Models](#accessing-models)
- [Creating Models](#creating-models)
- [Database Operations](#database-operations)
- [Best Practices](#best-practices)
- [Performance Optimization](#performance-optimization)
- [Thread Safety](#thread-safety)
- [Examples](#examples)

---

## Overview

PHPWeave's model system provides a simple, efficient way to interact with your database. Models are **lazy-loaded** for optimal performance, instantiated only when first accessed.

### Key Features

- **Lazy Loading** - Models loaded on-demand (3-10ms performance gain)
- **Thread-Safe** - File locking for Docker/Kubernetes/Swoole environments (v2.1.1+)
- **Multiple Access Methods** - PHPWeave object, helper function, or legacy array
- **PDO-based** - Built on secure prepared statements
- **Multi-Database Support** - MySQL, PostgreSQL, SQLite, SQL Server, ODBC (v2.2.0+)
- **Connection Pooling** - Automatic connection reuse for performance (v2.2.0+)
- **Auto-Discovery** - Models automatically discovered from `models/` directory

---

## Lazy Loading

Models are **not** loaded all at once. Instead, they're instantiated only when you first access them.

### Performance Benefits

**Before (Eager Loading - v1.0):**
```php
// All models loaded upfront
foreach ($modelFiles as $file) {
    include $file;
    $models[$name] = new $className();  // All instantiated immediately
}
// Cost: 3-10ms even if models never used
```

**After (Lazy Loading - v2.1+):**
```php
// Models loaded on first access only
$user = $PW->models->user_model;  // Only user_model instantiated here
// Cost: ~0.1ms per model, only when needed
```

### How It Works

The `Models` class implements `ArrayAccess` and magic `__get()`:

1. When you access `$PW->models->user_model`, the model file is included
2. The class is instantiated
3. The instance is cached for subsequent accesses
4. File locking ensures thread safety in concurrent environments

**File:** `coreapp/models.php`

```php
class Models implements ArrayAccess {
    private $models = [];
    private $modelsDir;

    public function __get($name) {
        return $this->loadModel($name);
    }

    private function loadModel($name) {
        if (isset($this->models[$name])) {
            return $this->models[$name];  // Return cached instance
        }

        $modelFile = $this->modelsDir . '/' . $name . '.php';

        if (!file_exists($modelFile)) {
            throw new Exception("Model not found: {$name}");
        }

        // Thread-safe loading with file lock
        $lockFile = $modelFile . '.lock';
        $fp = fopen($lockFile, 'w');
        flock($fp, LOCK_EX);

        try {
            include_once $modelFile;
            $className = $this->getClassName($modelFile);
            $this->models[$name] = new $className();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink($lockFile);
        }

        return $this->models[$name];
    }
}
```

---

## Accessing Models

There are **three ways** to access models in PHPWeave:

### 1. PHPWeave Global Object (Recommended - v2.1+)

The modern, object-oriented approach:

```php
global $PW;

// Access model
$user = $PW->models->user_model->getUser($id);
$posts = $PW->models->blog_model->getRecentPosts(10);

// Chain methods
$username = $PW->models->user_model->getUser($id)->name;
```

**Advantages:**
- Clean, modern syntax
- IDE autocomplete friendly
- PSR-12 compliant
- Single global object

### 2. Helper Function (v2.1+)

A convenient shorthand:

```php
// Access model
$user = model('user_model')->getUser($id);
$posts = model('blog_model')->getRecentPosts(10);

// Chain methods
$username = model('user_model')->getUser($id)->name;
```

**Helper function definition** (`coreapp/models.php`):

```php
function model($name) {
    global $PW;
    return $PW->models->{$name};
}
```

**Advantages:**
- No global variable needed
- Short syntax
- Function-based (familiar)

### 3. Legacy Array Access (Still Supported)

The original v1.0 syntax:

```php
global $models;

// Access model
$user = $models['user_model']->getUser($id);
$posts = $models['blog_model']->getRecentPosts(10);
```

**Advantages:**
- Backward compatibility
- Familiar to v1.0 users

**Note:** This uses `ArrayAccess` interface behind the scenes, so it's equally performant.

---

## Creating Models

### Basic Model Structure

1. Create a file in `models/` directory
2. Name the file with `_model.php` suffix (e.g., `user_model.php`)
3. Class name should match filename (e.g., `class user_model`)
4. Extend `DBConnection` for database access

**Example:** `models/user_model.php`

```php
<?php

class user_model extends DBConnection {

    /**
     * Get user by ID
     */
    public function getUser($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->executePreparedSQL($sql, [$id]);
        return $this->fetch($stmt);
    }

    /**
     * Get all users
     */
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $this->executePreparedSQL($sql);
        return $this->fetchAll($stmt);
    }

    /**
     * Create new user
     */
    public function createUser($data) {
        $sql = "INSERT INTO users (email, password, name) VALUES (?, ?, ?)";
        $params = [$data['email'], $data['password'], $data['name']];
        $stmt = $this->executePreparedSQL($sql, $params);
        return $this->rowCount($stmt) > 0;
    }

    /**
     * Update user
     */
    public function updateUser($id, $data) {
        $sql = "UPDATE users SET email = ?, name = ? WHERE id = ?";
        $params = [$data['email'], $data['name'], $id];
        $stmt = $this->executePreparedSQL($sql, $params);
        return $this->rowCount($stmt) > 0;
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->executePreparedSQL($sql, [$id]);
        return $this->rowCount($stmt) > 0;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->executePreparedSQL($sql, [$email]);
        return $this->fetch($stmt);
    }

    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }
}
```

### Naming Conventions

**File naming:**
- Use lowercase with underscores
- Always use `_model.php` suffix
- Examples: `user_model.php`, `blog_model.php`, `product_model.php`

**Class naming:**
- Match the filename exactly (without `.php`)
- Examples: `class user_model`, `class blog_model`, `class product_model`

**Method naming:**
- Use camelCase for methods
- Be descriptive and clear
- Examples: `getUser()`, `createPost()`, `updateProduct()`

---

## Database Operations

Models extend `DBConnection`, which provides PDO-based database access.

### Available Methods

The `DBConnection` class provides these methods:

#### 1. `executePreparedSQL($sql, $params = [])`

Execute a prepared SQL statement.

```php
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $this->executePreparedSQL($sql, [$id]);
```

**Parameters:**
- `$sql` - SQL query with `?` placeholders
- `$params` - Array of parameter values

**Returns:** PDOStatement object

#### 2. `fetch($stmt)`

Fetch single row as associative array.

```php
$stmt = $this->executePreparedSQL($sql, [$id]);
$row = $this->fetch($stmt);

// Access columns
echo $row['email'];
echo $row['name'];
```

**Returns:** Associative array or `false` if no row

#### 3. `fetchAll($stmt)`

Fetch all rows as array of associative arrays.

```php
$stmt = $this->executePreparedSQL($sql);
$rows = $this->fetchAll($stmt);

// Loop through results
foreach ($rows as $row) {
    echo $row['email'];
}
```

**Returns:** Array of associative arrays

#### 4. `rowCount($stmt)`

Get number of affected rows.

```php
$stmt = $this->executePreparedSQL($sql, $params);
$affected = $this->rowCount($stmt);

if ($affected > 0) {
    echo "Success!";
}
```

**Returns:** Integer count

### Example Operations

#### SELECT Query

```php
public function getActiveUsers() {
    $sql = "SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC";
    $stmt = $this->executePreparedSQL($sql);
    return $this->fetchAll($stmt);
}
```

#### INSERT Query

```php
public function createUser($email, $password, $name) {
    $sql = "INSERT INTO users (email, password, name, created_at)
            VALUES (?, ?, ?, NOW())";
    $params = [$email, password_hash($password, PASSWORD_DEFAULT), $name];
    $stmt = $this->executePreparedSQL($sql, $params);
    return $this->rowCount($stmt) > 0;
}
```

#### UPDATE Query

```php
public function updateEmail($userId, $newEmail) {
    $sql = "UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $this->executePreparedSQL($sql, [$newEmail, $userId]);
    return $this->rowCount($stmt) > 0;
}
```

#### DELETE Query

```php
public function deleteInactiveUsers() {
    $sql = "DELETE FROM users WHERE active = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $stmt = $this->executePreparedSQL($sql);
    return $this->rowCount($stmt);
}
```

#### Complex Queries with JOINs

```php
public function getUserWithPosts($userId) {
    $sql = "SELECT u.*, COUNT(p.id) as post_count
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.id = ?
            GROUP BY u.id";
    $stmt = $this->executePreparedSQL($sql, [$userId]);
    return $this->fetch($stmt);
}
```

#### Transactions

```php
public function transferCredits($fromUserId, $toUserId, $amount) {
    try {
        $this->pdo->beginTransaction();

        // Deduct from sender
        $sql = "UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?";
        $stmt = $this->executePreparedSQL($sql, [$amount, $fromUserId, $amount]);

        if ($this->rowCount($stmt) === 0) {
            throw new Exception("Insufficient credits");
        }

        // Add to receiver
        $sql = "UPDATE users SET credits = credits + ? WHERE id = ?";
        $this->executePreparedSQL($sql, [$amount, $toUserId]);

        $this->pdo->commit();
        return true;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}
```

---

## Best Practices

### 1. Always Use Prepared Statements

**GOOD:**
```php
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $this->executePreparedSQL($sql, [$email]);
```

**BAD (SQL Injection Risk):**
```php
$sql = "SELECT * FROM users WHERE email = '$email'";
$stmt = $this->executePreparedSQL($sql);
```

### 2. Validate Input Data

```php
public function createUser($data) {
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    // Validate password strength
    if (strlen($data['password']) < 8) {
        throw new Exception("Password must be at least 8 characters");
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users (email, password, name) VALUES (?, ?, ?)";
    $params = [$data['email'], $hashedPassword, $data['name']];
    $stmt = $this->executePreparedSQL($sql, $params);

    return $this->rowCount($stmt) > 0;
}
```

### 3. Use Descriptive Method Names

```php
// GOOD
public function getActiveUsersByRole($role) { }
public function softDeleteUser($id) { }
public function countPostsByAuthor($authorId) { }

// BAD
public function get($id) { }
public function delete($id) { }
public function count() { }
```

### 4. Return Consistent Data Types

```php
// GOOD - Always returns array or empty array
public function getUsers() {
    $sql = "SELECT * FROM users";
    $stmt = $this->executePreparedSQL($sql);
    $result = $this->fetchAll($stmt);
    return $result ?: [];  // Never return false
}

// GOOD - Returns object or null
public function getUser($id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $this->executePreparedSQL($sql, [$id]);
    $result = $this->fetch($stmt);
    return $result ?: null;
}
```

### 5. Handle Errors Gracefully

```php
public function createPost($data) {
    try {
        $sql = "INSERT INTO posts (title, content, user_id) VALUES (?, ?, ?)";
        $params = [$data['title'], $data['content'], $data['user_id']];
        $stmt = $this->executePreparedSQL($sql, $params);
        return $this->rowCount($stmt) > 0;

    } catch (PDOException $e) {
        error_log("Failed to create post: " . $e->getMessage());
        return false;
    }
}
```

### 6. Use Hooks for Cross-Cutting Concerns

```php
// Don't put logging in every model method
// Instead, use hooks (hooks/model_logging.php)
Hook::register('before_db_query', function($data) {
    error_log("Query: " . $data['sql']);
    return $data;
});

Hook::register('after_db_query', function($data) {
    error_log("Query executed in: " . $data['time'] . "ms");
    return $data;
});
```

---

## Performance Optimization

### 1. Lazy Loading (Built-in)

Models are only loaded when accessed:

```php
// No models loaded yet
global $PW;

// Only user_model loaded (not blog_model, product_model, etc.)
$user = $PW->models->user_model->getUser($id);
```

**Performance gain:** 3-10ms per request

### 2. Connection Pooling (v2.2.0+)

Enable connection pooling for 6-30% performance improvement:

```ini
# .env
DB_POOL_SIZE=10  # Enable pooling with 10 max connections
```

```php
// Get statistics
$stats = ConnectionPool::getStatistics();
echo "Reuse rate: " . $stats['reuse_rate'] . "%";
```

See [CONNECTION_POOLING.md](CONNECTION_POOLING.md) for details.

### 3. Use Indexes

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_created_at ON posts(created_at);
```

### 4. Limit Result Sets

```php
// GOOD - Use LIMIT
public function getRecentPosts($limit = 10) {
    $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ?";
    $stmt = $this->executePreparedSQL($sql, [$limit]);
    return $this->fetchAll($stmt);
}

// BAD - Fetch all rows
public function getAllPosts() {
    $sql = "SELECT * FROM posts";  // Could be millions of rows!
    $stmt = $this->executePreparedSQL($sql);
    return $this->fetchAll($stmt);
}
```

### 5. Use Pagination

```php
public function getPaginatedPosts($page = 1, $perPage = 20) {
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $this->executePreparedSQL($sql, [$perPage, $offset]);

    return [
        'posts' => $this->fetchAll($stmt),
        'page' => $page,
        'per_page' => $perPage,
        'total' => $this->countPosts()
    ];
}

public function countPosts() {
    $sql = "SELECT COUNT(*) as total FROM posts";
    $stmt = $this->executePreparedSQL($sql);
    $result = $this->fetch($stmt);
    return $result['total'];
}
```

### 6. Cache Expensive Queries

```php
public function getPopularPosts($limit = 10) {
    // Check cache first
    if (apcu_exists('popular_posts')) {
        return apcu_fetch('popular_posts');
    }

    // Expensive query with JOINs and aggregations
    $sql = "SELECT p.*, COUNT(l.id) as like_count, COUNT(c.id) as comment_count
            FROM posts p
            LEFT JOIN likes l ON p.id = l.post_id
            LEFT JOIN comments c ON p.id = c.post_id
            GROUP BY p.id
            ORDER BY like_count DESC, comment_count DESC
            LIMIT ?";

    $stmt = $this->executePreparedSQL($sql, [$limit]);
    $posts = $this->fetchAll($stmt);

    // Cache for 5 minutes
    apcu_store('popular_posts', $posts, 300);

    return $posts;
}
```

---

## Thread Safety

**Version 2.1.1+** includes thread-safe model loading for concurrent environments.

### When Thread Safety Matters

- **Docker/Kubernetes** - Multiple containers accessing shared filesystem
- **Swoole/RoadRunner/FrankenPHP** - Long-running PHP processes with concurrent requests
- **Shared hosting** - Multiple PHP-FPM workers

### How It Works

File locking prevents race conditions during model instantiation:

```php
private function loadModel($name) {
    if (isset($this->models[$name])) {
        return $this->models[$name];  // Already loaded
    }

    $modelFile = $this->modelsDir . '/' . $name . '.php';

    // Create lock file
    $lockFile = $modelFile . '.lock';
    $fp = fopen($lockFile, 'w');
    flock($fp, LOCK_EX);  // Exclusive lock

    try {
        include_once $modelFile;
        $className = $this->getClassName($modelFile);
        $this->models[$name] = new $className();
    } finally {
        flock($fp, LOCK_UN);  // Release lock
        fclose($fp);
        @unlink($lockFile);  // Clean up
    }

    return $this->models[$name];
}
```

### Performance Impact

- **First access:** ~0.1-0.2ms overhead (file locking)
- **Subsequent accesses:** 0ms (cached instance)
- **Minimal impact** in most applications

---

## Examples

### Example 1: Blog System

**models/blog_model.php**

```php
<?php

class blog_model extends DBConnection {

    public function getRecentPosts($limit = 10) {
        $sql = "SELECT p.*, u.name as author_name
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.published = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        $stmt = $this->executePreparedSQL($sql, [$limit]);
        return $this->fetchAll($stmt);
    }

    public function getPost($id) {
        $sql = "SELECT p.*, u.name as author_name, u.email as author_email
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?";
        $stmt = $this->executePreparedSQL($sql, [$id]);
        return $this->fetch($stmt);
    }

    public function createPost($userId, $title, $content) {
        $sql = "INSERT INTO posts (user_id, title, content, slug, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $slug = $this->generateSlug($title);
        $params = [$userId, $title, $content, $slug];
        $stmt = $this->executePreparedSQL($sql, $params);
        return $this->rowCount($stmt) > 0;
    }

    public function updatePost($id, $title, $content) {
        $sql = "UPDATE posts SET title = ?, content = ?, slug = ?, updated_at = NOW()
                WHERE id = ?";
        $slug = $this->generateSlug($title);
        $params = [$title, $content, $slug, $id];
        $stmt = $this->executePreparedSQL($sql, $params);
        return $this->rowCount($stmt) > 0;
    }

    public function deletePost($id) {
        $sql = "DELETE FROM posts WHERE id = ?";
        $stmt = $this->executePreparedSQL($sql, [$id]);
        return $this->rowCount($stmt) > 0;
    }

    public function getPostsByAuthor($userId, $limit = 10) {
        $sql = "SELECT * FROM posts WHERE user_id = ?
                ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->executePreparedSQL($sql, [$userId, $limit]);
        return $this->fetchAll($stmt);
    }

    public function searchPosts($query, $limit = 20) {
        $searchTerm = "%{$query}%";
        $sql = "SELECT p.*, u.name as author_name
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE (p.title LIKE ? OR p.content LIKE ?) AND p.published = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        $stmt = $this->executePreparedSQL($sql, [$searchTerm, $searchTerm, $limit]);
        return $this->fetchAll($stmt);
    }

    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug;
    }
}
```

**Usage in controller:**

```php
class Blog extends Controller {

    public function index() {
        global $PW;

        $posts = $PW->models->blog_model->getRecentPosts(20);

        $this->show('blog/index', [
            'posts' => $posts,
            'title' => 'Recent Posts'
        ]);
    }

    public function show($id) {
        global $PW;

        $post = $PW->models->blog_model->getPost($id);

        if (!$post) {
            header('HTTP/1.0 404 Not Found');
            $this->show('errors/404');
            return;
        }

        $this->show('blog/show', [
            'post' => $post,
            'title' => $post['title']
        ]);
    }

    public function create() {
        global $PW;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $title = $_POST['title'];
            $content = $_POST['content'];

            if ($PW->models->blog_model->createPost($userId, $title, $content)) {
                header('Location: /blog');
                exit;
            }
        }

        $this->show('blog/create');
    }
}
```

### Example 2: E-commerce Product Model

**models/product_model.php**

```php
<?php

class product_model extends DBConnection {

    public function getFeaturedProducts($limit = 6) {
        $sql = "SELECT * FROM products
                WHERE featured = 1 AND stock > 0
                ORDER BY RAND()
                LIMIT ?";
        $stmt = $this->executePreparedSQL($sql, [$limit]);
        return $this->fetchAll($stmt);
    }

    public function getProductsByCategory($categoryId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM products
                WHERE category_id = ? AND stock > 0
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->executePreparedSQL($sql, [$categoryId, $perPage, $offset]);
        return $this->fetchAll($stmt);
    }

    public function searchProducts($query, $minPrice = 0, $maxPrice = 999999) {
        $searchTerm = "%{$query}%";
        $sql = "SELECT * FROM products
                WHERE (name LIKE ? OR description LIKE ?)
                AND price BETWEEN ? AND ?
                AND stock > 0
                ORDER BY name ASC";
        $stmt = $this->executePreparedSQL($sql, [
            $searchTerm, $searchTerm, $minPrice, $maxPrice
        ]);
        return $this->fetchAll($stmt);
    }

    public function updateStock($productId, $quantity) {
        $sql = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt = $this->executePreparedSQL($sql, [$quantity, $productId]);
        return $this->rowCount($stmt) > 0;
    }

    public function checkStock($productId, $quantity) {
        $sql = "SELECT stock FROM products WHERE id = ?";
        $stmt = $this->executePreparedSQL($sql, [$productId]);
        $product = $this->fetch($stmt);
        return $product && $product['stock'] >= $quantity;
    }
}
```

### Example 3: User Authentication Model

**models/auth_model.php**

```php
<?php

class auth_model extends DBConnection {

    public function register($email, $password, $name) {
        // Check if email already exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users (email, password, name, created_at)
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->executePreparedSQL($sql, [$email, $hashedPassword, $name]);

        if ($this->rowCount($stmt) > 0) {
            return ['success' => true, 'user_id' => $this->pdo->lastInsertId()];
        }

        return ['success' => false, 'error' => 'Registration failed'];
    }

    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ? AND active = 1";
        $stmt = $this->executePreparedSQL($sql, [$email]);
        $user = $this->fetch($stmt);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Update last login
        $this->updateLastLogin($user['id']);

        return ['success' => true, 'user' => $user];
    }

    public function emailExists($email) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = $this->executePreparedSQL($sql, [$email]);
        $result = $this->fetch($stmt);
        return $result['count'] > 0;
    }

    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->executePreparedSQL($sql, [$userId]);
    }

    public function resetPassword($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users
                SET reset_token = ?, reset_expires = ?
                WHERE email = ?";
        $stmt = $this->executePreparedSQL($sql, [$token, $expires, $email]);

        if ($this->rowCount($stmt) > 0) {
            return ['success' => true, 'token' => $token];
        }

        return ['success' => false, 'error' => 'Email not found'];
    }
}
```

---

## Related Documentation

- [ROUTING_GUIDE.md](ROUTING_GUIDE.md) - Using models in routes
- [HOOKS.md](HOOKS.md) - Model lifecycle hooks
- [CONNECTION_POOLING.md](CONNECTION_POOLING.md) - Database connection pooling
- [MIGRATIONS.md](MIGRATIONS.md) - Database migrations
- [DOCKER_DATABASE_SUPPORT.md](DOCKER_DATABASE_SUPPORT.md) - Multi-database support
- [SECURITY_BEST_PRACTICES.md](SECURITY_BEST_PRACTICES.md) - Secure model development

---

## Summary

**Key Takeaways:**

1. Models are **lazy-loaded** for optimal performance
2. Use **`$PW->models->model_name`** for modern syntax (recommended)
3. Always use **prepared statements** to prevent SQL injection
4. Extend **`DBConnection`** for database access
5. Follow **naming conventions** (`user_model.php`, `class user_model`)
6. **Thread-safe** in Docker/Kubernetes/Swoole (v2.1.1+)
7. Enable **connection pooling** for 6-30% performance boost (v2.2.0+)

---

**Happy modeling with PHPWeave!** 🚀
