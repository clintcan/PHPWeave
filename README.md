# PHPWeave

[![CI Tests](https://github.com/clintcan/PHPWeave/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/clintcan/PHPWeave/actions/workflows/ci.yml)
[![Docker Build](https://github.com/clintcan/PHPWeave/actions/workflows/docker.yml/badge.svg?branch=main)](https://github.com/clintcan/PHPWeave/actions/workflows/docker.yml)
[![Code Quality](https://github.com/clintcan/PHPWeave/actions/workflows/code-quality.yml/badge.svg?branch=main)](https://github.com/clintcan/PHPWeave/actions/workflows/code-quality.yml)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A lightweight, homegrown PHP MVC framework born from simplicity and evolved with modern routing.

## History

PHPWeave started as a private, in-house PHP framework created for rapid web application development. Initially inspired by **CodeIgniter's** elegant simplicity and straightforward MVC pattern, PHPWeave was designed to strip away complexity while maintaining the power needed for real-world applications.

In its early days, PHPWeave embraced CodeIgniter's convention-over-configuration philosophy, using automatic URL-to-controller routing. However, as modern PHP development practices evolved and RESTful APIs became the standard, PHPWeave underwent a significant transformation.

**The Evolution:**
- **Phase 1 (Early Days)**: Simple MVC with automatic routing inspired by CodeIgniter
- **Phase 2 (Current)**: Modern explicit routing system with full HTTP verb support, while maintaining backward compatibility with the original automatic routing approach

This migration represents PHPWeave's commitment to staying relevant while honoring its roots—a framework that grows with your needs without abandoning its core philosophy of simplicity.

## Features

- **Modern Routing System**: Express-style route definitions with dynamic parameters
- **Full HTTP Verb Support**: GET, POST, PUT, DELETE, PATCH methods
- **Global Framework Object**: Clean `$PW->models->model_name` syntax (v2.1+)
- **Auto-Extracted View Variables**: Pass arrays to views, access as individual variables
- **Lazy-Loaded Libraries**: Reusable utility classes with automatic discovery (v2.1.1+)
- **Event-Driven Hooks System**: 18 lifecycle hook points for extending functionality
- **Async Task System**: Simple background job processing without external dependencies
- **Performance Optimized**: Lazy loading, route caching, 30-60% faster than v1
- **Docker Ready**: APCu in-memory caching, multi-container support, Kubernetes compatible
- **MVC Architecture**: Clean separation of concerns
- **Auto-loading Models**: Lazy model loading for optimal performance
- **PDO Database Layer**: Secure prepared statements out of the box
- **Simple Template System**: No complex templating engine, just PHP with automatic variable extraction
- **Error Handling**: Comprehensive error logging and handling
- **Backward Compatible**: Legacy routing still available for existing projects
- **Zero Dependencies**: Pure PHP, no Composer required
- **Lightweight**: Minimal footprint, maximum performance

## Quick Start

### Installation

1. Clone or download PHPWeave to your web server
2. Point your web server document root to the `public/` directory
3. Copy `.env.sample` to `.env` and configure your database:

```ini
DBHOST=localhost
DBNAME=your_database
DBUSER=your_username
DBPASSWORD=your_password
DBCHARSET=utf8
DEBUG=1
```

**Note:** If you don't create a `.env` file (e.g., for Docker/Kubernetes deployments), PHPWeave will automatically fall back to reading environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_CHARSET`.

4. Create your database and tables
5. Start building!

### Your First Route

**1. Define a route** in `routes.php`:
```php
Route::get('/hello/:name:', 'Welcome@greet');
```

**2. Create a controller** in `controller/welcome.php`:
```php
<?php
class Welcome extends Controller
{
    function greet($name) {
        $this->show("welcome", "Hello, $name!");
    }
}
```

**3. Create a view** in `views/welcome.php`:
```php
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to PHPWeave</title>
</head>
<body>
    <h1><?php echo $data; ?></h1>
</body>
</html>
```

**4. Visit** `http://localhost/hello/World` and see your page!

### Better Example with Array Data (v2.1+)

```php
// Controller
global $PW;
$user = $PW->models->user_model->getUser(1);

$this->show("welcome", [
    'greeting' => 'Hello',
    'name' => $name,
    'username' => $user->username
]);

// View (views/welcome.php)
<h1><?php echo $greeting; ?>, <?php echo $name; ?>!</h1>
<p>Your username is: <?php echo $username; ?></p>
```

## Routing

PHPWeave now uses an explicit routing system that gives you full control over your application's URLs.

### Basic Routes

```php
// Simple GET route
Route::get('/about', 'Home@about');

// POST route for form handling
Route::post('/contact', 'Contact@submit');

// Dynamic parameters
Route::get('/blog/:id:', 'Blog@show');

// Multiple parameters
Route::get('/user/:user_id:/post/:post_id:', 'User@viewPost');
```

### RESTful Routes

```php
Route::get('/posts', 'Post@index');           // List all posts
Route::get('/posts/create', 'Post@create');   // Show create form
Route::post('/posts', 'Post@store');          // Create new post
Route::get('/posts/:id:', 'Post@show');       // Show single post
Route::get('/posts/:id:/edit', 'Post@edit');  // Show edit form
Route::put('/posts/:id:', 'Post@update');     // Update post
Route::delete('/posts/:id:', 'Post@destroy'); // Delete post
```

### HTTP Methods

PHPWeave supports all standard HTTP methods:

```php
Route::get($pattern, $handler);      // GET requests
Route::post($pattern, $handler);     // POST requests
Route::put($pattern, $handler);      // PUT requests
Route::delete($pattern, $handler);   // DELETE requests
Route::patch($pattern, $handler);    // PATCH requests
Route::any($pattern, $handler);      // Any HTTP method
```

For PUT, DELETE, and PATCH requests from HTML forms, use method override:

```html
<form method="POST" action="/posts/123">
    <input type="hidden" name="_method" value="PUT">
    <!-- form fields -->
</form>
```

## MVC Structure

### Controllers

Controllers live in `controller/` and extend the `Controller` base class:

```php
<?php
class Blog extends Controller
{
    function index() {
        // List all blogs (new v2.1 syntax)
        global $PW;
        $blogs = $PW->models->blog_model->getAll();

        $this->show("blog/index", [
            'title' => 'All Blog Posts',
            'blogs' => $blogs
        ]);
    }

    function show($id) {
        // Show single blog
        global $PW;
        $blog = $PW->models->blog_model->getById($id);

        $this->show("blog/show", [
            'title' => $blog['title'],
            'content' => $blog['content'],
            'created' => $blog['created_at']
        ]);
    }

    function store() {
        // Create new blog (POST request)
        $title = $_POST['title'];
        $content = $_POST['content'];

        global $PW;
        $PW->models->blog_model->create($title, $content);

        header("Location: /blog");
    }
}
```

### Models

Models live in `models/` and extend `DBConnection` for database access:

```php
<?php
class blog_model extends DBConnection
{
    public function __construct() {
        parent::__construct();
    }

    public function getAll() {
        $sql = "SELECT * FROM blogs ORDER BY created_at DESC";
        $stmt = $this->executePreparedSQL($sql);
        return $this->fetchAll($stmt);
    }

    public function getById($id) {
        $sql = "SELECT * FROM blogs WHERE id = :id";
        $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
        return $this->fetch($stmt);
    }

    public function create($title, $content) {
        $sql = "INSERT INTO blogs (title, content) VALUES (:title, :content)";
        return $this->executePreparedSQL($sql, [
            'title' => $title,
            'content' => $content
        ]);
    }
}
```

**Model Access Methods:**
```php
// Recommended (v2.1+): PHPWeave global object
global $PW;
$user = $PW->models->user_model->getUser($id);

// Alternative: Helper function
$user = model('user_model')->getUser($id);

// Legacy: Array access (still works)
global $models;
$user = $models['user_model']->getUser($id);
```

### Libraries

**New in v2.1.1!** Libraries provide reusable utility functions across your application. They're lazy-loaded (instantiated only when needed) and automatically discovered from the `libraries/` directory.

**Creating a Library** (`libraries/string_helper.php`):
```php
<?php
class string_helper {
    public function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        return strtolower(trim($text, '-'));
    }

    public function truncate($text, $length = 100) {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }

    public function random($length = 16) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }
}
```

**Using Libraries:**
```php
// Recommended (v2.1.1+): PHPWeave global object
global $PW;
$slug = $PW->libraries->string_helper->slugify("Hello World");
$preview = $PW->libraries->string_helper->truncate($content, 200);
$token = $PW->libraries->string_helper->random(16);

// Alternative: Helper function
$slug = library('string_helper')->slugify("Hello World");

// Legacy: Array access (still works)
global $libraries;
$slug = $libraries['string_helper']->slugify("Hello World");
```

**Library Features:**
- Lazy instantiation (only loaded when accessed)
- Automatic discovery (just drop files in `libraries/`)
- Instance caching (singleton pattern)
- Three access methods for flexibility
- No manual registration required

**Built-in Libraries:**
- `string_helper` - String manipulation (slugify, truncate, random, titleCase, etc.)

**Try it:**
Visit `/blog/slugify/Hello-World-Testing-Libraries` to see the string helper library in action!

See **docs/LIBRARIES.md** for complete documentation.

### Views

Views are plain PHP templates in `views/`. **As of v2.1+, array data is automatically extracted into individual variables:**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>
    <h1><?php echo $title; ?></h1>
    <?php foreach($blogs as $post): ?>
        <article>
            <h2><?php echo $post['title']; ?></h2>
            <p><?php echo $post['content']; ?></p>
        </article>
    <?php endforeach; ?>
</body>
</html>
```

**View Data Access:**
- Pass array from controller: `$this->show('view', ['title' => 'Hello', 'content' => 'World'])`
- Access in view: `$title`, `$content` (extracted automatically)
- Or use array notation: `$data['title']`, `$data['content']` (backward compatible)

## Database

PHPWeave uses PDO with prepared statements for security:

```php
// Execute prepared statement
$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $this->executePreparedSQL($sql, ['email' => $email]);

// Fetch single row
$user = $this->fetch($stmt);

// Fetch all rows
$users = $this->fetchAll($stmt);

// Get row count
$count = $this->rowCount($stmt);
```

**Configuration Methods:**

1. **Using `.env` file** (recommended for local development):
```ini
DBHOST=localhost
DBNAME=your_database
DBUSER=your_username
DBPASSWORD=your_password
DBCHARSET=utf8
```

2. **Using environment variables** (recommended for Docker/Kubernetes):
- Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_CHARSET` as environment variables
- PHPWeave automatically uses environment variables when `.env` file doesn't exist

## Async Background Tasks

PHPWeave includes a simple, elegant async system for background processing:

```php
// Fire and forget
Async::run(function() {
    mail('user@example.com', 'Welcome', 'Thanks for signing up!');
});

// Queue a job
Async::queue('SendEmailJob', [
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'message' => 'Thanks for signing up!'
]);

// Defer until after response
Async::defer(function() {
    logAnalytics($_SESSION['user_id']);
});
```

**Run the worker:**
```bash
php worker.php --daemon
```

See **docs/ASYNC_GUIDE.md** for complete documentation.

## Hooks System

PHPWeave includes a powerful event-driven hooks system with 18 lifecycle hook points:

```php
// Register a hook with priority (lower executes first)
Hook::register('before_action_execute', function($data) {
    // Check authentication
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt(); // Stop further hooks and execution
        exit;
    }
    return $data;
}, 5);

// Modify data in hooks
Hook::register('before_view_render', function($data) {
    $data['global_setting'] = 'value';
    return $data;
});
```

**Available Hook Points:**
- `framework_start` - First hook, fires before anything else
- `before_routing` - Before route matching
- `route_matched` - After route found, before controller load
- `before_controller_load` - Before controller instantiation
- `after_controller_load` - After controller created
- `before_action_execute` - Before controller method runs
- `after_action_execute` - After controller method completes
- `before_view_render` - Before view is rendered
- `after_view_render` - After view rendered
- And 9 more specialized hooks...

**Hook Features:**
- Priority-based execution (lower numbers first)
- Data modification through hook chain
- Halt propagation with `Hook::halt()`
- Exception handling for resilient execution

See **docs/HOOKS.md** for complete documentation with examples.

## Performance

PHPWeave v2 includes significant performance optimizations:

**Before Optimizations:**
- Framework bootstrap: ~15-25ms
- With 10 hooks: ~20-30ms
- With 20 models: ~25-35ms
- **Total: ~30-50ms per request**

**After Optimizations:**
- Framework bootstrap: ~5-10ms
- With 10 hooks: ~8-12ms
- With 20 models: ~8-12ms
- **Total: ~15-25ms per request**

**Improvement: 30-60% faster!**

**Key Optimizations:**
- Lazy hook priority sorting (5-10ms saved)
- Lazy model loading (3-10ms saved)
- Route caching (1-3ms saved)
- Directory path caching (~0.5ms saved)
- Template sanitization optimization (~0.1ms saved)

See **docs/OPTIMIZATIONS_APPLIED.md** for details.

## Docker Deployment

PHPWeave is Docker-ready with built-in APCu support:

```bash
# Production deployment with .env file
docker-compose up -d

# Production with environment variables (Kubernetes-style, recommended)
docker-compose -f docker-compose.env.yml up -d

# Development with hot-reload
docker-compose -f docker-compose.dev.yml up -d

# Scaled deployment (3 containers + load balancer)
docker-compose -f docker-compose.scale.yml up -d
```

**Docker Features:**
- APCu in-memory caching (optimal for containers)
- Automatic Docker environment detection
- Multi-container support with Nginx load balancing
- Read-only filesystem compatible
- Kubernetes ready with manifests included

**Caching Strategy:**
- **In Docker**: Prefers APCu (in-memory, container-isolated)
- **Traditional hosting**: Uses both APCu + file cache
- **Graceful degradation**: Falls back if caching unavailable

See **docs/DOCKER_DEPLOYMENT.md** for complete guide.

## Directory Structure

```
PHPWeave/
├── controller/          # Application controllers
│   ├── home.php
│   └── blog.php
├── models/             # Database models (lazy-loaded)
│   ├── user_model.php
│   └── blog_model.php
├── libraries/          # Utility libraries (lazy-loaded, v2.1.1+)
│   └── string_helper.php
├── jobs/               # Background job classes
│   ├── SendEmailJob.php
│   └── ProcessImageJob.php
├── hooks/              # Hook implementations
│   ├── example_authentication.php
│   ├── example_logging.php
│   └── example_cors.php
├── views/              # View templates
│   ├── home.php
│   └── blog.php
├── public/             # Web root (point your server here)
│   └── index.php       # Front controller
├── coreapp/            # Framework core
│   ├── router.php      # Modern routing system with caching
│   ├── controller.php  # Base controller class
│   ├── models.php      # Lazy model loader
│   ├── libraries.php   # Lazy library loader (v2.1.1+)
│   ├── dbconnection.php # PDO database class
│   ├── hooks.php       # Event-driven hooks system
│   ├── async.php       # Async task system
│   └── error.php       # Error handling
├── storage/            # Storage directory
│   └── queue/          # Job queue files
├── cache/              # Route cache (auto-created)
├── docs/               # Complete documentation
│   ├── README.md       # Documentation index
│   ├── ROUTING_GUIDE.md
│   ├── HOOKS.md        # Complete hooks guide
│   ├── LIBRARIES.md    # Libraries guide (NEW!)
│   ├── ASYNC_GUIDE.md
│   ├── ASYNC_QUICK_START.md
│   ├── DOCKER_DEPLOYMENT.md
│   ├── DOCKER_CACHING_GUIDE.md
│   ├── PERFORMANCE_ANALYSIS.md
│   ├── OPTIMIZATIONS_APPLIED.md
│   └── MIGRATION_TO_NEW_ROUTING.md
├── tests/              # Test scripts
│   ├── README.md
│   ├── test_hooks.php
│   ├── test_models.php
│   ├── test_controllers.php
│   ├── test_docker_caching.php
│   └── benchmark_optimizations.php
├── routes.php          # Route definitions
├── worker.php          # Queue worker script
├── Dockerfile          # Docker image with APCu
├── docker-compose.yml  # Standard deployment (.env file)
├── docker-compose.env.yml # Kubernetes-style (env vars only)
├── docker-compose.dev.yml  # Development setup
├── docker-compose.scale.yml # Load-balanced setup
├── nginx.conf          # Load balancer config
├── .env                # Environment config (create from .env.sample)
├── .env.sample         # Sample environment config
├── CODE_OF_CONDUCT.md  # Community code of conduct
├── SECURITY.md         # Security policy and reporting
└── README.md           # This file
```

## Creating a Blog Application

Here's a complete example of building a simple blog:

**1. Create the database:**
```sql
CREATE TABLE blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**2. Define routes** in `routes.php`:
```php
Route::get('/blog', 'Blog@index');
Route::get('/blog/create', 'Blog@create');
Route::post('/blog', 'Blog@store');
Route::get('/blog/:id:', 'Blog@show');
Route::delete('/blog/:id:', 'Blog@destroy');
```

**3. Create model** `models/blog_model.php`:
```php
<?php
class blog_model extends DBConnection
{
    public function __construct() {
        parent::__construct();
    }

    public function getAll() {
        $sql = "SELECT * FROM blogs ORDER BY created_at DESC";
        $stmt = $this->executePreparedSQL($sql);
        return $this->fetchAll($stmt);
    }

    public function getById($id) {
        $sql = "SELECT * FROM blogs WHERE id = :id";
        $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
        return $this->fetch($stmt);
    }

    public function create($title, $content) {
        $sql = "INSERT INTO blogs (title, content) VALUES (:title, :content)";
        $stmt = $this->executePreparedSQL($sql, [
            'title' => $title,
            'content' => $content
        ]);
        return $this->pdo->lastInsertId();
    }

    public function delete($id) {
        $sql = "DELETE FROM blogs WHERE id = :id";
        return $this->executePreparedSQL($sql, ['id' => $id]);
    }
}
```

**4. Create controller** `controller/blog.php`:
```php
<?php
class Blog extends Controller
{
    function index() {
        global $PW;
        $blogs = $PW->models->blog_model->getAll();

        $this->show("blog/index", [
            'title' => 'All Blog Posts',
            'blogs' => $blogs
        ]);
    }

    function create() {
        $this->show("blog/create", [
            'title' => 'Create New Post'
        ]);
    }

    function store() {
        global $PW;
        $id = $PW->models->blog_model->create($_POST['title'], $_POST['content']);
        header("Location: /blog/$id");
    }

    function show($id) {
        global $PW;
        $blog = $PW->models->blog_model->getById($id);

        $this->show("blog/show", [
            'title' => $blog['title'],
            'content' => $blog['content'],
            'created' => $blog['created_at']
        ]);
    }

    function destroy($id) {
        global $PW;
        $PW->models->blog_model->delete($id);
        header("Location: /blog");
    }
}
```

**5. Create views** in `views/blog/`:

`views/blog/index.php`:
```php
<!DOCTYPE html>
<html>
<head><title><?php echo $title; ?></title></head>
<body>
    <h1><?php echo $title; ?></h1>
    <a href="/blog/create">Create New Post</a>
    <?php foreach($blogs as $post): ?>
        <article>
            <h2><a href="/blog/<?php echo $post['id']; ?>">
                <?php echo htmlspecialchars($post['title']); ?>
            </a></h2>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        </article>
    <?php endforeach; ?>
</body>
</html>
```

`views/blog/create.php`:
```php
<!DOCTYPE html>
<html>
<head><title><?php echo $title; ?></title></head>
<body>
    <h1><?php echo $title; ?></h1>
    <form method="POST" action="/blog">
        <label>Title: <input type="text" name="title" required></label><br>
        <label>Content: <textarea name="content" required></textarea></label><br>
        <button type="submit">Create Post</button>
    </form>
</body>
</html>
```

## Legacy Routing (Backward Compatibility)

PHPWeave maintains backward compatibility with its original CodeIgniter-inspired automatic routing. The old pattern `/{controller}/{action}/{params}` still works if you enable it.

To enable legacy routing, add these catch-all routes at the end of `routes.php`:

```php
Route::any('/:controller:', 'LegacyRouter@dispatch');
Route::any('/:controller:/:action:', 'LegacyRouter@dispatch');
```

**However, we recommend using the new explicit routing system for better control, security, and maintainability.**

## API Development

PHPWeave is perfect for building REST APIs:

```php
// routes.php
Route::get('/api/users', 'Api@listUsers');
Route::post('/api/users', 'Api@createUser');
Route::get('/api/users/:id:', 'Api@getUser');
Route::put('/api/users/:id:', 'Api@updateUser');
Route::delete('/api/users/:id:', 'Api@deleteUser');
```

```php
// controller/api.php
<?php
class Api extends Controller
{
    function listUsers() {
        global $PW;
        $users = $PW->models->user_model->getAll();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    }

    function getUser($id) {
        global $PW;
        $user = $PW->models->user_model->getById($id);

        header('Content-Type: application/json');
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    }
}
```

## Error Handling

PHPWeave includes comprehensive error handling:

- Errors are logged to `coreapp/error.log`
- Critical errors can be emailed to administrators
- Debug mode can be enabled in `.env` with `DEBUG=1`

Customize error pages by modifying `coreapp/router.php`:
- `handle404()` - 404 Not Found page
- `handle500()` - 500 Internal Server Error page

## Documentation

### Core Features
- **docs/ROUTING_GUIDE.md** - Comprehensive routing documentation with examples
- **docs/HOOKS.md** - Complete hooks system guide with all 18 hook points
- **docs/LIBRARIES.md** - Complete libraries guide with lazy loading (NEW in v2.1.1!)
- **docs/ASYNC_GUIDE.md** - Complete guide to background tasks and job queues
- **docs/ASYNC_QUICK_START.md** - Quick start guide for async tasks
- **docs/MIGRATION_TO_NEW_ROUTING.md** - Guide for migrating from legacy routing

### Performance & Optimization
- **docs/PERFORMANCE_ANALYSIS.md** - Detailed performance analysis and bottlenecks
- **docs/OPTIMIZATIONS_APPLIED.md** - Summary of applied optimizations (30-60% faster)
- **docs/OPTIMIZATION_PATCHES.md** - Ready-to-apply optimization patches
- **docs/TEST_RESULTS.md** - Performance test results

### Docker & Deployment
- **docs/DOCKER_DEPLOYMENT.md** - Complete Docker deployment guide
- **docs/DOCKER_CACHING_GUIDE.md** - Caching strategies for Docker (APCu vs file)
- **docs/DOCKER_CACHING_APPLIED.md** - Docker caching implementation summary
- **docs/KUBERNETES_DEPLOYMENT.md** - Kubernetes deployment with auto-scaling

### Testing
- **tests/README.md** - Testing guide
- **tests/test_hooks.php** - Hooks system tests (8 tests)
- **tests/test_models.php** - Models system tests (12 tests)
- **tests/test_controllers.php** - Controllers system tests (15 tests)
- **tests/test_docker_caching.php** - Docker caching tests
- **tests/benchmark_optimizations.php** - Performance benchmarks

### Architecture
- **docs/README.md** - Complete documentation index with learning path

## Why PHPWeave?

**Simplicity**: No complex configuration, no bloat. Just clean, understandable code.

**Flexibility**: Start simple, scale as needed. Use automatic routing for prototypes, explicit routing for production.

**Learning**: Perfect for understanding MVC architecture without framework magic hiding the details.

**Control**: You own the code. No black boxes, no vendor lock-in.

**Evolution**: Born from CodeIgniter's philosophy but evolved with modern best practices.

## Philosophy

PHPWeave believes in:

1. **Convention with Configuration**: Sensible defaults, but full control when you need it
2. **Progressive Enhancement**: Start simple, add complexity only when needed
3. **Transparency**: No magic, every piece of the framework is readable and understandable
4. **Backward Compatibility**: Respect existing code while embracing modern practices
5. **Developer Happiness**: Code should be enjoyable to write and maintain

## Server Requirements

- PHP 7.0 or higher (PHP 8.x recommended)
- PDO extension (usually enabled by default)
- mod_rewrite (Apache) or equivalent for clean URLs
- MySQL 5.6+ or MariaDB 10.0+ (or any PDO-supported database)
- APCu extension (optional, recommended for Docker/production)

**Install APCu for optimal performance:**
```bash
pecl install apcu
echo "extension=apcu.so" > /etc/php/conf.d/apcu.ini
echo "apc.enabled=1" >> /etc/php/conf.d/apcu.ini
```

Or use the provided Dockerfile which includes APCu pre-configured.

## Deployment

### Apache

Make sure `mod_rewrite` is enabled and create `.htaccess` in the `public/` directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Docker

See complete Docker deployment guide in **docs/DOCKER_DEPLOYMENT.md**.

**Quick start:**
```bash
# Clone and navigate to project
cd PHPWeave

# Standard production
docker-compose up -d

# Development with hot-reload
docker-compose -f docker-compose.dev.yml up -d

# Scaled deployment (3 containers + load balancer)
docker-compose -f docker-compose.scale.yml up -d

# Test APCu caching
docker exec phpweave-app php tests/test_docker_caching.php
```

### Kubernetes

PHPWeave includes production-ready Kubernetes manifests with auto-scaling, health checks, and MySQL StatefulSet.

**Quick deploy:**
```bash
kubectl create namespace phpweave
kubectl apply -k k8s/
```

See complete Kubernetes deployment guide in **docs/KUBERNETES_DEPLOYMENT.md**.

**Features:**
- APCu in-memory caching (container-isolated, no shared state issues)
- Nginx load balancing for multi-container deployments
- Automatic environment detection
- Read-only filesystem compatible
- Kubernetes ready

## GitHub Actions / CI/CD

PHPWeave includes comprehensive GitHub Actions workflows for automated testing and quality assurance:

### Workflows

**CI Tests** - Automated testing across PHP 7.4 - 8.3:
- Runs all test suites (hooks, models, controllers, caching, benchmarks)
- Tests with MySQL database integration
- Validates PHP syntax for all files

**Docker Build** - Container image building and testing:
- Builds and tests Docker images
- Multi-platform support (amd64, arm64)
- Publishes images to GitHub Container Registry
- Tests docker-compose deployments

**Code Quality** - Static analysis and security:
- PHP syntax validation
- PHPStan static analysis
- Security vulnerability scanning
- Markdown linting

See `.github/workflows/README.md` for detailed workflow documentation.

## Contributing

PHPWeave was born as a private framework but has evolved with contributions from its users. If you're using PHPWeave and have improvements, feel free to share them!

**Development Process:**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Ensure all tests pass (`php tests/*.php`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

All pull requests are automatically tested via GitHub Actions.

### Code of Conduct

PHPWeave is committed to providing a welcoming and inclusive environment for all contributors. By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

Key points:
- Be respectful and inclusive
- Accept constructive feedback gracefully
- Focus on what's best for the community
- Report violations to mosaicked_pareja@aleeas.com

See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for complete guidelines.

## Security

Security is a top priority for PHPWeave. If you discover a security vulnerability:

**DO NOT** report it through public GitHub issues.

Instead:
- Use [GitHub Security Advisories](../../security/advisories) (preferred)
- Email: mosaicked_pareja@aleeas.com

We will respond within 48 hours and work with you on a coordinated disclosure.

For security best practices, supported versions, and detailed reporting guidelines, see [SECURITY.md](SECURITY.md).

## License

PHPWeave is open-source software licensed under the MIT License. Use it, modify it, love it.

## Credits

- Initial inspiration: **CodeIgniter** - for proving that simplicity is powerful
- Routing evolution: Modern frameworks like Laravel and Express.js
- Philosophy: The belief that developers should understand their tools

---

**PHPWeave** - From homegrown simplicity to modern elegance, one route at a time.
