# PHPWeave Routing Guide

## Overview

PHPWeave now supports modern, explicit route definitions with dynamic parameters and HTTP method support.

## Quick Start

### Basic Routes

Define routes in `routes.php`:

```php
// Simple GET route
Route::get('/home', 'Home@index');

// POST route for form submissions
Route::post('/contact', 'Contact@submit');

// Route with dynamic parameter
Route::get('/blog/:id:', 'Blog@show');

// Multiple parameters
Route::get('/user/:user_id:/post/:post_id:', 'User@viewPost');
```

### Controller Methods

Controller methods receive parameters in the order they appear in the route:

```php
class Blog extends Controller
{
    // Matches: Route::get('/blog/:id:', 'Blog@show');
    function show($id) {
        // $id contains the value from the URL
        $this->show("blog", "Post ID: $id");
    }

    // Matches: Route::get('/blog/:id:/comment/:comment_id:', 'Blog@showComment');
    function showComment($id, $comment_id) {
        // Parameters passed in order
        $this->show("blog", "Post: $id, Comment: $comment_id");
    }
}
```

## HTTP Methods

### GET Requests
```php
Route::get('/products', 'Product@index');
Route::get('/product/:id:', 'Product@show');
```

### POST Requests
```php
Route::post('/product', 'Product@store');
Route::post('/login', 'Auth@login');
```

### PUT Requests
```php
Route::put('/product/:id:', 'Product@update');
```

For PUT requests from HTML forms, use method override:
```html
<form method="POST" action="/product/123">
    <input type="hidden" name="_method" value="PUT">
    <!-- form fields -->
</form>
```

### DELETE Requests
```php
Route::delete('/product/:id:', 'Product@destroy');
```

Method override for DELETE:
```html
<form method="POST" action="/product/123">
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Delete</button>
</form>
```

### PATCH Requests
```php
Route::patch('/product/:id:', 'Product@partialUpdate');
```

### ANY Method
```php
// Accepts any HTTP method
Route::any('/webhook', 'Webhook@handle');
```

## Route Parameters

### Parameter Syntax
- Parameters are wrapped in `:param_name:`
- Parameter names must start with a letter or underscore
- Can contain letters, numbers, and underscores
- Examples: `:id:`, `:user_id:`, `:post_slug:`

### Parameter Matching
- Parameters match any non-slash characters
- Pattern: `/user/:id:` matches `/user/123` and `/user/abc`
- Pattern: `/user/:id:/posts` matches `/user/123/posts`

### Accessing Parameters
Parameters are passed to controller methods as function arguments:

```php
// Route definition
Route::get('/category/:category:/product/:id:', 'Product@showInCategory');

// Controller method
class Product extends Controller
{
    function showInCategory($category, $id) {
        // $category = first parameter from URL
        // $id = second parameter from URL
        $this->show("product", "Category: $category, Product: $id");
    }
}
```

## RESTful Routes Example

```php
// List all posts
Route::get('/posts', 'Post@index');

// Show form to create new post
Route::get('/posts/create', 'Post@create');

// Store new post
Route::post('/posts', 'Post@store');

// Show specific post
Route::get('/posts/:id:', 'Post@show');

// Show form to edit post
Route::get('/posts/:id:/edit', 'Post@edit');

// Update post
Route::put('/posts/:id:', 'Post@update');

// Delete post
Route::delete('/posts/:id:', 'Post@destroy');
```

## Controller Implementation

```php
<?php
class Post extends Controller
{
    function index() {
        // List all posts
        global $models;
        $posts = $models['post_model']->getAll();
        $this->show("posts/index", $posts);
    }

    function show($id) {
        // Show single post
        global $models;
        $post = $models['post_model']->getById($id);
        $this->show("posts/show", $post);
    }

    function store() {
        // Create new post (POST request)
        // Access POST data via $_POST
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        global $models;
        $models['post_model']->create($title, $content);

        // Redirect or show success
        header("Location: /posts");
    }

    function update($id) {
        // Update existing post (PUT request)
        // Access data via $_POST (using method override)
        global $models;
        $models['post_model']->update($id, $_POST);

        header("Location: /posts/$id");
    }

    function destroy($id) {
        // Delete post
        global $models;
        $models['post_model']->delete($id);

        header("Location: /posts");
    }
}
```

## Advanced Usage

### Route Order Matters
Routes are matched in the order they're defined. Put more specific routes before general ones:

```php
// Correct order
Route::get('/posts/recent', 'Post@recent');      // Specific
Route::get('/posts/:id:', 'Post@show');          // General

// Wrong order - '/posts/recent' would match as id="recent"
Route::get('/posts/:id:', 'Post@show');          // Too general
Route::get('/posts/recent', 'Post@recent');      // Never reached
```

### API Routes
```php
// API versioning
Route::get('/api/v1/users', 'ApiV1@users');
Route::post('/api/v1/auth/login', 'ApiV1@login');

// JSON responses in controller
class ApiV1 extends Controller
{
    function users() {
        global $models;
        $users = $models['user_model']->getAll();

        header('Content-Type: application/json');
        echo json_encode($users);
    }
}
```

### Debugging Routes
To see all registered routes:

```php
// Add to any controller method temporarily
print_r(Router::getRoutes());
```

## Migration from Legacy Routing

### Old Way (Automatic)
```
URL: /blog/show/123
Automatically calls: Blog::show(123)
```

### New Way (Explicit)
```php
// Define in routes.php
Route::get('/blog/%id%', 'Blog@show');

// Clearer, more control, RESTful
```

### Enabling Legacy Support
Add catch-all routes at the end of `routes.php`:

```php
Route::any('/:controller:', 'LegacyRouter@dispatch');
Route::any('/:controller:/:action:', 'LegacyRouter@dispatch');
```

## Error Handling

### 404 Not Found
If no route matches, the Router automatically returns a 404 response.

### 500 Internal Server Error
If a controller or method doesn't exist, Router returns a 500 error.

### Custom Error Pages
Modify error handlers in `coreapp/router.php`:
- `handle404()` - Customize 404 page (line 291-296)
- `handle500()` - Customize 500 page (line 298-310)

## Best Practices

1. **Group Related Routes**: Keep routes for the same resource together
2. **Use RESTful Conventions**: Follow standard HTTP verb usage
3. **Descriptive Parameter Names**: Use `:user_id:` not `:id:` when context matters
4. **Controller Organization**: One controller per resource type
5. **Method Naming**: Use standard REST names (index, show, store, update, destroy)

## Examples

### Blog System
```php
Route::get('/blog', 'Blog@index');
Route::get('/blog/create', 'Blog@create');
Route::post('/blog', 'Blog@store');
Route::get('/blog/:slug:', 'Blog@show');
Route::get('/blog/:id:/edit', 'Blog@edit');
Route::put('/blog/:id:', 'Blog@update');
Route::delete('/blog/:id:', 'Blog@destroy');
```

### User Profile
```php
Route::get('/user/:username:', 'User@profile');
Route::get('/user/:username:/posts', 'User@posts');
Route::get('/user/:username:/followers', 'User@followers');
```

### E-commerce
```php
Route::get('/shop', 'Shop@index');
Route::get('/category/:category:', 'Shop@category');
Route::get('/product/:id:', 'Product@show');
Route::post('/cart/add/:product_id:', 'Cart@add');
Route::get('/checkout', 'Checkout@show');
Route::post('/checkout', 'Checkout@process');
```
