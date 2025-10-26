# Migration to New Routing System

## Summary of Changes

PHPWeave now supports a modern, explicit routing system similar to Laravel, Express.js, and other popular frameworks.

## What Changed?

### Before (Legacy Routing)
```
URL: /blog/show/123
Automatic dispatch to: Blog::show(123)
```

### After (Modern Routing)
```php
// Define in routes.php
Route::get('/blog/:id:', 'Blog@show');

// Controller receives parameter
function show($id) {
    // $id = 123
}
```

## New Files Created

1. **`coreapp/router.php`** - Router class with route matching and dispatching
2. **`routes.php`** - Central route definitions file
3. **`ROUTING_GUIDE.md`** - Comprehensive routing documentation
4. **`MIGRATION_TO_NEW_ROUTING.md`** - This file

## Modified Files

1. **`public/index.php`**
   - Added router loading
   - Added routes file loading
   - Changed to use `Router::dispatch()`

2. **`coreapp/controller.php`**
   - Modified constructor to support new routing
   - Wrapped legacy routing in `legacyRouting()` function
   - Kept backward compatibility

3. **`controller/blog.php`**
   - Added example `show($id)` method
   - Added example `store()` method for POST requests

4. **`.env.sample`**
   - Added `DEBUG=1` option

## Key Features

### 1. HTTP Method Support
```php
Route::get('/posts', 'Post@index');
Route::post('/posts', 'Post@store');
Route::put('/posts/:id:', 'Post@update');
Route::delete('/posts/:id:', 'Post@destroy');
Route::patch('/posts/:id:', 'Post@patch');
Route::any('/webhook', 'Webhook@handle');
```

### 2. Dynamic Parameters
```php
// Single parameter
Route::get('/user/:id:', 'User@show');

// Multiple parameters
Route::get('/user/:user_id:/post/:post_id:', 'User@viewPost');

// Controller receives parameters in order
function viewPost($user_id, $post_id) { }
```

### 3. Clean Syntax
```php
// Clear and explicit
Route::post('/login', 'Auth@login');
Route::get('/dashboard', 'Dashboard@index');
Route::get('/profile/:username:', 'Profile@show');
```

### 4. RESTful Support
```php
// Standard REST operations
Route::get('/api/users', 'Api@listUsers');
Route::post('/api/users', 'Api@createUser');
Route::get('/api/users/:id:', 'Api@getUser');
Route::put('/api/users/:id:', 'Api@updateUser');
Route::delete('/api/users/:id:', 'Api@deleteUser');
```

## How to Use

### Step 1: Define Routes
Edit `routes.php`:

```php
Route::get('/home', 'Home@index');
Route::get('/about', 'Home@about');
Route::get('/blog', 'Blog@index');
Route::get('/blog/:id:', 'Blog@show');
Route::post('/blog', 'Blog@store');
```

### Step 2: Create Controller
Create or update controller in `controller/` directory:

```php
<?php
class Blog extends Controller
{
    function index() {
        // Show all blogs
        $this->show("blog_list");
    }

    function show($id) {
        // Show single blog with ID from URL
        $this->show("blog_detail", "Blog ID: $id");
    }

    function store() {
        // Handle POST request to create blog
        $title = $_POST['title'];
        $content = $_POST['content'];
        // Save to database...
    }
}
```

### Step 3: Test Your Routes
Visit URLs:
- `http://localhost/home` → `Home@index`
- `http://localhost/blog` → `Blog@index`
- `http://localhost/blog/123` → `Blog@show(123)`

Submit POST to `/blog` → `Blog@store()`

## Backward Compatibility

The legacy routing system is still available if needed. To enable:

1. Add catch-all routes at the end of `routes.php`:
```php
Route::any('/:controller:', 'LegacyRouter@dispatch');
Route::any('/:controller:/:action:', 'LegacyRouter@dispatch');
```

2. Or call `legacyRouting()` function directly

However, we recommend migrating to the new system for better control and clarity.

## Benefits of New System

1. **Explicit**: Routes are clearly defined in one place
2. **RESTful**: Proper HTTP verb support (GET, POST, PUT, DELETE, PATCH)
3. **Flexible**: Dynamic parameters with custom names
4. **Maintainable**: Easy to see all application routes
5. **Secure**: Only defined routes are accessible
6. **Modern**: Follows industry standards

## Examples

### Blog Application
```php
// routes.php
Route::get('/', 'Home@index');
Route::get('/blog', 'Blog@index');
Route::get('/blog/create', 'Blog@create');
Route::post('/blog', 'Blog@store');
Route::get('/blog/:slug:', 'Blog@show');
Route::get('/blog/:id:/edit', 'Blog@edit');
Route::put('/blog/:id:', 'Blog@update');
Route::delete('/blog/:id:', 'Blog@destroy');
```

### API Endpoints
```php
// routes.php
Route::post('/api/auth/login', 'Api@login');
Route::post('/api/auth/register', 'Api@register');
Route::get('/api/users', 'Api@listUsers');
Route::get('/api/users/:id:', 'Api@getUser');
Route::post('/api/users', 'Api@createUser');
Route::put('/api/users/:id:', 'Api@updateUser');
Route::delete('/api/users/:id:', 'Api@deleteUser');
```

### E-commerce
```php
// routes.php
Route::get('/shop', 'Shop@index');
Route::get('/products', 'Product@list');
Route::get('/product/:id:', 'Product@show');
Route::post('/cart/add/:id:', 'Cart@add');
Route::get('/cart', 'Cart@show');
Route::post('/checkout', 'Checkout@process');
Route::get('/order/:id:', 'Order@show');
```

## Debugging

### View All Routes
Temporarily add to any controller:
```php
print_r(Router::getRoutes());
```

### Check Matched Route
```php
$match = Router::getMatchedRoute();
print_r($match);
```

### Enable Debug Mode
In `.env`:
```
DEBUG=1
```

This shows detailed error messages when routes fail.

## Common Patterns

### Form with Method Override
HTML forms only support GET/POST, use method override for PUT/DELETE:

```html
<!-- PUT request -->
<form method="POST" action="/blog/123">
    <input type="hidden" name="_method" value="PUT">
    <input name="title" value="Updated Title">
    <button type="submit">Update</button>
</form>

<!-- DELETE request -->
<form method="POST" action="/blog/123">
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Delete</button>
</form>
```

### JSON API Response
```php
class Api extends Controller
{
    function getUser($id) {
        global $models;
        $user = $models['user_model']->getById($id);

        header('Content-Type: application/json');
        echo json_encode($user);
    }
}
```

### Redirect After POST
```php
function store() {
    // Save data...
    global $models;
    $id = $models['blog_model']->create($_POST);

    // Redirect to show page
    header("Location: /blog/$id");
    exit();
}
```

## Support

- See `ROUTING_GUIDE.md` for comprehensive documentation
- Check `routes.php` for example route definitions

## Next Steps

1. Review existing controllers and identify routes needed
2. Define routes in `routes.php`
3. Test each route
4. Remove legacy routing catch-all if not needed
5. Enjoy cleaner, more maintainable code!
