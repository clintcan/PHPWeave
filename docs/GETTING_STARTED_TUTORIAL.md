# Getting Started with PHPWeave: Build a Guestbook

Welcome to PHPWeave! This tutorial will guide you through building your first application - a simple visitor guestbook where guests can leave comments and see the last 10 entries.

## What You'll Build

https://github.com/user-attachments/assets/9d47889b-af17-40af-b969-03bf49621c1f

By the end of this tutorial, you'll have:
- A form for visitors to submit comments
- A display showing the last 10 guestbook entries
- Understanding of PHPWeave's MVC structure
- Working knowledge of routes, controllers, models, and views

## Prerequisites

- PHP 7.4 or higher installed
- MySQL or MariaDB database
- Basic understanding of PHP and SQL
- A local web server (Apache, Nginx, or PHP's built-in server)

## Step 1: Set Up PHPWeave

### 1.1 Clone or Download PHPWeave

```bash
git clone https://github.com/clintcan/PHPWeave.git
cd PHPWeave
```

### 1.2 Configure the Environment

Copy the sample environment file:

```bash
cp .env.sample .env
```

Edit `.env` with your database credentials:

```ini
# Database Configuration
ENABLE_DATABASE=1
DBHOST=localhost
DBNAME=phpweave_guestbook
DBUSER=your_username
DBPASSWORD=your_password
DBCHARSET=utf8mb4
DBPORT=3306

# Debug Mode (disable in production)
DEBUG=1
```

### 1.3 Create the Database

Create a new database for your guestbook:

```sql
CREATE DATABASE phpweave_guestbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 2: Create the Database Table

PHPWeave includes a powerful migration system. Let's use it to create our guestbook table.

### 2.1 Create a Migration

Run this command from the PHPWeave root directory:

```bash
php migrate.php create create_guestbook_table
```

This creates a new migration file in `migrations/` directory with a timestamp prefix (e.g., `20240115120000_create_guestbook_table.php`).

### 2.2 Define the Table Structure

Open the newly created migration file and modify the `up()` and `down()` methods:

```php
<?php
class CreateGuestbookTable extends Migration
{
    public function up()
    {
        $this->createTable('guestbook', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(100) NOT NULL',
            'comment' => 'TEXT NOT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);

        // Create an index for faster sorting by date
        $this->createIndex('guestbook', 'idx_created_at', ['created_at']);
    }

    public function down()
    {
        $this->dropTable('guestbook');
    }
}
```

### 2.3 Run the Migration

Execute the migration to create the table:

```bash
php migrate.php migrate
```

You should see output like:
```
Migrating: 20240115120000_create_guestbook_table
Migrated:  20240115120000_create_guestbook_table (0.05s)
```

## Step 3: Create the Model

Models handle database interactions. Let's create a model for our guestbook.

Create a new file `models/guestbook_model.php`:

```php
<?php
class guestbook_model extends DBConnection
{
    /**
     * Get the last N entries from the guestbook
     *
     * @param int $limit Number of entries to retrieve
     * @return array Array of guestbook entries
     */
    public function getRecentEntries($limit = 10)
    {
        $sql = "SELECT id, name, comment, created_at
                FROM guestbook
                ORDER BY created_at DESC
                LIMIT :limit";

        $stmt = $this->executePreparedSQL($sql, ['limit' => $limit]);
        return $this->fetchAll($stmt);
    }

    /**
     * Add a new entry to the guestbook
     *
     * @param string $name Visitor's name
     * @param string $comment Visitor's comment
     * @return bool Success status
     */
    public function addEntry($name, $comment)
    {
        $sql = "INSERT INTO guestbook (name, comment)
                VALUES (:name, :comment)";

        $stmt = $this->executePreparedSQL($sql, [
            'name' => $name,
            'comment' => $comment
        ]);

        return $this->rowCount($stmt) > 0;
    }

    /**
     * Get total count of entries
     *
     * @return int Total number of entries
     */
    public function getTotalEntries()
    {
        $sql = "SELECT COUNT(*) as total FROM guestbook";
        $stmt = $this->executePreparedSQL($sql, []);
        $result = $this->fetch($stmt);
        return (int)$result['total'];
    }
}
```

**Key Points:**
- Extend `DBConnection` to get database access
- Use `executePreparedSQL()` with parameter binding (prevents SQL injection)
- Return data arrays from query methods
- Keep business logic in the model

## Step 4: Create the Controller

Controllers handle HTTP requests and coordinate between models and views.

Create a new file `controller/guestbook.php`:

```php
<?php
class Guestbook extends Controller
{
    /**
     * Display guestbook page with form and recent entries
     */
    public function index()
    {
        // Access the guestbook model
        global $PW;
        $model = $PW->models->guestbook_model;

        // Get recent entries
        $entries = $model->getRecentEntries(10);
        $total = $model->getTotalEntries();

        // Prepare data for the view
        $data = [
            'entries' => $entries,
            'total' => $total,
            'success' => isset($_GET['success']) ? 'Thank you! Your message has been added.' : null,
            'error' => isset($_GET['error']) ? $_GET['error'] : null
        ];

        // Render the view
        $this->show('guestbook/index', $data);
    }

    /**
     * Handle form submission
     */
    public function submit()
    {
        // Validate form submission
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /guestbook');
            exit;
        }

        // Get and sanitize input
        $name = trim($_POST['name'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        // Validate input
        if (empty($name) || empty($comment)) {
            header('Location: /guestbook?error=' . urlencode('Please fill in all fields.'));
            exit;
        }

        if (strlen($name) > 100) {
            header('Location: /guestbook?error=' . urlencode('Name must be 100 characters or less.'));
            exit;
        }

        if (strlen($comment) > 1000) {
            header('Location: /guestbook?error=' . urlencode('Comment must be 1000 characters or less.'));
            exit;
        }

        // Save to database
        global $PW;
        $model = $PW->models->guestbook_model;

        if ($model->addEntry($name, $comment)) {
            header('Location: /guestbook?success=1');
        } else {
            header('Location: /guestbook?error=' . urlencode('Failed to save entry. Please try again.'));
        }
        exit;
    }
}
```

**Key Points:**
- Class name must match filename (case-sensitive)
- Extend `Controller` base class
- Access models via `$PW->models->model_name`
- Use `$this->show()` to render views
- Handle form validation before database operations
- Use PRG pattern (Post-Redirect-Get) to prevent duplicate submissions

## Step 5: Create the Views

Views handle presentation. Let's create two views: the main page and a partial for displaying entries.

### 5.1 Main Guestbook View

Create `views/guestbook/index.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guestbook - PHPWeave Tutorial</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .message {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 40px;
        }

        .form-section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        button {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #2980b9;
        }

        .entries-section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .entry {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .entry-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .entry-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 18px;
        }

        .entry-date {
            color: #7f8c8d;
            font-size: 14px;
        }

        .entry-comment {
            color: #555;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .no-entries {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        .stats {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Guestbook</h1>
        <p class="subtitle">Leave a message and see what others have said!</p>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Sign Guestbook Form -->
        <div class="form-section">
            <h2>Sign the Guestbook</h2>
            <form action="/guestbook/submit" method="POST">
                <div class="form-group">
                    <label for="name">Your Name:</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        maxlength="100"
                        placeholder="Enter your name"
                    >
                </div>

                <div class="form-group">
                    <label for="comment">Your Message:</label>
                    <textarea
                        id="comment"
                        name="comment"
                        required
                        maxlength="1000"
                        placeholder="Share your thoughts..."
                    ></textarea>
                </div>

                <button type="submit">Sign Guestbook</button>
            </form>
        </div>

        <!-- Recent Entries -->
        <div class="entries-section">
            <h2>Recent Entries (Last 10)</h2>

            <?php if (empty($entries)): ?>
                <div class="no-entries">
                    No entries yet. Be the first to sign the guestbook!
                </div>
            <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                    <div class="entry">
                        <div class="entry-header">
                            <div class="entry-name">
                                <?php echo htmlspecialchars($entry['name']); ?>
                            </div>
                            <div class="entry-date">
                                <?php
                                    $date = new DateTime($entry['created_at']);
                                    echo $date->format('F j, Y \a\t g:i A');
                                ?>
                            </div>
                        </div>
                        <div class="entry-comment">
                            <?php echo htmlspecialchars($entry['comment']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="stats">
            Total entries: <?php echo $total; ?>
        </div>
    </div>
</body>
</html>
```

**Key Points:**
- Variables passed from controller are automatically available (`$entries`, `$total`, `$success`, `$error`)
- Always use `htmlspecialchars()` to prevent XSS attacks
- Form posts to `/guestbook/submit` route
- Clean, responsive design with inline CSS for simplicity
- Uses PHP's DateTime for date formatting

## Step 6: Define the Routes

Routes connect URLs to controller methods. Edit `routes.php`:

```php
<?php
// Existing routes...

// Guestbook routes
Route::get('/guestbook', 'Guestbook@index');
Route::post('/guestbook/submit', 'Guestbook@submit');
```

**Route Explanation:**
- `Route::get('/guestbook', 'Guestbook@index')` - Maps GET requests to `/guestbook` to the `index()` method of `Guestbook` controller
- `Route::post('/guestbook/submit', 'Guestbook@submit')` - Maps POST requests to `/guestbook/submit` to the `submit()` method

## Step 7: Test Your Application

### 7.1 Start the Development Server

If using PHP's built-in server:

```bash
cd public
php -S localhost:8000
```

### 7.2 Access the Guestbook

Open your browser and navigate to:
```
http://localhost:8000/guestbook
```

### 7.3 Test the Functionality

1. **Submit an entry**: Fill in your name and a comment, click "Sign Guestbook"
2. **Verify success message**: You should see a success message
3. **Check entry display**: Your entry should appear at the top of the list
4. **Test validation**: Try submitting empty fields or very long text
5. **Add more entries**: Submit 10+ entries to verify the limit works

## Step 8: Understanding the Request Flow

Let's trace what happens when you submit the form:

1. **Form Submission**: Browser sends POST request to `/guestbook/submit`
2. **Routing**: PHPWeave's router matches the route and calls `Guestbook@submit`
3. **Controller**: `submit()` method validates input and calls model
4. **Model**: `addEntry()` executes prepared SQL statement
5. **Redirect**: Controller redirects to `/guestbook?success=1`
6. **Display**: `index()` method fetches entries and renders view
7. **View**: Template displays success message and updated entries

## Next Steps: Enhancements

Now that you have a working guestbook, try these enhancements:

### 1. Add Pagination

Modify the model to support pagination:

```php
public function getEntriesPaginated($page = 1, $perPage = 10)
{
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT id, name, comment, created_at
            FROM guestbook
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $this->executePreparedSQL($sql, [
        'limit' => $perPage,
        'offset' => $offset
    ]);

    return $this->fetchAll($stmt);
}
```

### 2. Add Email Notifications with Hooks

Create `hooks/guestbook_notifications.php`:

```php
<?php
Hook::register('after_action_execute', function($data) {
    // Send email when new entry is added
    if (isset($_POST['name']) && isset($_POST['comment'])) {
        $name = $_POST['name'];
        $comment = $_POST['comment'];

        mail(
            'admin@example.com',
            'New Guestbook Entry',
            "New entry from: $name\n\nMessage: $comment"
        );
    }
    return $data;
});
```

### 3. Add IP Address Tracking

Add column to migration:

```php
'ip_address' => 'VARCHAR(45)'
```

Capture in controller:

```php
$ip = $_SERVER['REMOTE_ADDR'];
$model->addEntry($name, $comment, $ip);
```

### 4. Add Rate Limiting

Create a library `libraries/rate_limiter.php`:

```php
<?php
class rate_limiter
{
    public function isAllowed($ip, $limit = 3, $minutes = 10)
    {
        // Implement rate limiting logic
        // Return true if allowed, false if rate limit exceeded
    }
}
```

### 5. Add Spam Protection

Implement a simple honeypot field:

```php
// In view (hidden field)
<input type="text" name="website" style="display:none;">

// In controller
if (!empty($_POST['website'])) {
    // Likely a bot, reject silently
    header('Location: /guestbook?success=1');
    exit;
}
```

## Common Issues and Solutions

### Issue: Database Connection Failed

**Solution:** Check your `.env` file credentials and ensure the database exists.

```bash
mysql -u root -p -e "CREATE DATABASE phpweave_guestbook;"
```

### Issue: 404 Not Found on Routes

**Solution:** Ensure your web server's document root points to the `public/` directory.

For Apache, add `.htaccess` in `public/`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

For Nginx:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Issue: Headers Already Sent Error

**Solution:** PHPWeave v2.2.2+ includes output buffering by default. If you're on an older version, ensure no output before redirects.

### Issue: Model Not Found

**Solution:** Verify the filename matches the class name and the file is in the `models/` directory.

## Best Practices

1. **Always Use Prepared Statements**: Prevents SQL injection
   ```php
   // Good
   $this->executePreparedSQL($sql, ['id' => $id]);

   // Bad
   $this->executePreparedSQL("SELECT * FROM users WHERE id = $id", []);
   ```

2. **Escape Output**: Prevents XSS attacks
   ```php
   <?php echo htmlspecialchars($user_input); ?>
   ```

3. **Validate Input**: Check both client-side and server-side
   ```php
   if (empty($name) || strlen($name) > 100) {
       // Reject
   }
   ```

4. **Use PRG Pattern**: Prevents duplicate form submissions
   ```php
   // After processing POST
   header('Location: /success');
   exit;
   ```

5. **Keep Logic in Controllers/Models**: Views should only display data
   ```php
   // Good: Controller prepares data
   $data['formatted_date'] = $model->getFormattedDate();

   // Bad: View does complex logic
   // <?php echo date('F j, Y', strtotime($entry['created_at'])); ?>
   ```

## Conclusion

Congratulations! You've built a complete guestbook application with PHPWeave. You've learned:

- Setting up PHPWeave with database configuration
- Creating database migrations
- Building models for data access
- Creating controllers for request handling
- Designing views for presentation
- Defining routes to connect everything
- Following MVC best practices

### Learn More

Explore these PHPWeave features next:

- **Hooks System**: `docs/HOOKS.md` - Add authentication, logging, and more
- **Async Tasks**: `docs/ASYNC_GUIDE.md` - Background job processing
- **HTTP Client**: `docs/HTTP_ASYNC_GUIDE.md` - Concurrent API requests
- **Session Management**: `docs/SESSIONS.md` - User sessions and authentication
- **Docker Deployment**: `docs/DOCKER_DEPLOYMENT.md` - Deploy to production

### Get Help

- GitHub Issues: Report bugs and request features
- Documentation: Complete guides in `docs/` directory
- Examples: Working code in `examples/` directory

Happy coding with PHPWeave! 🚀
