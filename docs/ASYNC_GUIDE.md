# PHPWeave Async Guide

## Overview

PHPWeave's async system provides simple, elegant background task processing
without external dependencies. Perfect for tasks that shouldn't block your
HTTP response:

- 📧 Sending emails
- 🖼️ Processing images
- 📊 Generating reports
- 🔄 API calls to external services
- 📝 Logging and analytics

## Quick Start

### Fire and Forget

For quick one-off tasks, `Async::run()` supports multiple callable types:

```php
// Option 1: Static method (recommended - no external library needed)
class EmailHelper {
    public static function sendWelcome($email) {
        mail($email, 'Welcome', 'Thanks for signing up!');
    }
}
Async::run(['EmailHelper', 'sendWelcome']);

// Option 2: Global function (no external library needed)
function send_welcome_email() {
    mail('user@example.com', 'Welcome', 'Thanks for signing up!');
}
Async::run('send_welcome_email');

// Option 3: Closure (requires: composer require opis/closure)
Async::run(function() {
    mail('user@example.com', 'Welcome', 'Thanks for signing up!');
});

// Response returns immediately, task runs in background
```

### Queue Jobs

For reusable, structured tasks:

```php
// Queue a job
Async::queue('SendEmailJob', [
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'message' => 'Thanks for signing up!'
]);

// Job processes in background via worker
```

### Defer Execution

Run tasks after response is sent to the user:

```php
Async::defer(function() {
    // Log analytics after user sees the page
    logPageView($_SESSION['user_id']);
});
```

## Three Ways to Use Async

### 1. Fire and Forget with `Async::run()`

**Best for:** One-off tasks, quick operations

**Supported Callable Types:**

1. **Static Methods** (recommended - works without external libraries):
   ```php
   class EmailTasks {
       public static function sendWelcome($email) {
           mail($email, 'Welcome', 'Thanks for signing up!');
       }
   }
   Async::run(['EmailTasks', 'sendWelcome']);
   ```

2. **Global Functions** (works without external libraries):
   ```php
   function send_notification() {
       mail('admin@example.com', 'Alert', 'New signup');
   }
   Async::run('send_notification');
   ```

3. **Closures** (requires `composer require opis/closure`):
   ```php
   Async::run(function() use ($userId) {
       $user = $GLOBALS['models']['user_model']->getUser($userId);
       mail($user['email'], 'Welcome!', 'Thanks for joining!');
   });
   ```

**Example:**

```php
class User extends Controller
{
    function register()
    {
        // Save user to database
        global $models;
        $userId = $models['user_model']->create($_POST);

        // Send welcome email in background using static method
        Async::run(['EmailTasks', 'sendWelcome']);

        // Return immediately
        $this->show("register_success");
    }
}
```

**Pros:**

- No setup required
- Works without external libraries (for static methods/functions)
- Perfect for simple tasks
- Uses secure JSON serialization (for static methods/functions)

**Cons:**

- Not reusable across projects
- Limited error handling
- Can't be retried
- Instance methods not supported (use static methods instead)

### 2. Job Queue with `Async::queue()`

**Best for:** Reusable tasks, complex operations, retry logic

```php
class User extends Controller
{
    function register()
    {
        // Save user to database
        global $models;
        $userId = $models['user_model']->create($_POST);

        // Queue welcome email
        Async::queue('SendEmailJob', [
            'to' => $_POST['email'],
            'subject' => 'Welcome to Our App',
            'message' => $this->getWelcomeMessage()
        ]);

        // Queue profile image processing
        if (isset($_FILES['avatar'])) {
            Async::queue('ProcessImageJob', [
                'source' => $uploadPath,
                'operations' => ['resize' => [200, 200], 'thumbnail' => true]
            ], 5); // High priority (lower number = higher priority)
        }

        $this->show("register_success");
    }
}
```

**Pros:**

- Reusable job classes
- Error handling and retries
- Priority support
- Queue monitoring

**Cons:**

- Requires worker process
- More setup

### 3. Deferred Execution with `Async::defer()`

**Best for:** Logging, cleanup, non-critical tasks

```php
class Blog extends Controller
{
    function show($id)
    {
        global $models;
        $post = $models['blog_model']->getPost($id);

        // Track view after response sent
        Async::defer(function() use ($id) {
            $GLOBALS['models']['analytics_model']->logView($id);
        });

        $this->show("blog/show", $post);
    }
}
```

**Pros:**

- Runs after response sent
- No external worker needed
- Zero setup

**Cons:**

- Limited execution time
- Blocks PHP shutdown
- Not for heavy tasks

## Creating Job Classes

All jobs extend the `Job` base class:

```php
<?php
class SendEmailJob extends Job
{
    public function handle($data)
    {
        $to = $data['to'];
        $subject = $data['subject'];
        $message = $data['message'];

        $result = mail($to, $subject, $message);

        if (!$result) {
            throw new Exception("Failed to send email to: $to");
        }
    }
}
```

Save in `jobs/` directory with the same name as the class.

### Example: Image Processing Job

```php
<?php
class ProcessImageJob extends Job
{
    public function handle($data)
    {
        $source = $data['source'];
        $width = $data['width'] ?? 800;
        $height = $data['height'] ?? 600;

        // Process image
        $image = imagecreatefromjpeg($source);
        $resized = imagescale($image, $width, $height);

        $output = pathinfo($source, PATHINFO_DIRNAME) . '/resized_' . basename($source);
        imagejpeg($resized, $output, 90);

        imagedestroy($image);
        imagedestroy($resized);
    }
}
```

### Example: API Call Job

```php
<?php
class SyncWithCRMJob extends Job
{
    public function handle($data)
    {
        $userId = $data['user_id'];
        $crmApiUrl = 'https://api.crm.com/users';

        global $models;
        $user = $models['user_model']->getUser($userId);

        $ch = curl_init($crmApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($user));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('CRM_API_KEY')
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("CRM sync failed with HTTP code: $httpCode");
        }
    }
}
```

## Running the Worker

### Manual (Testing)

```bash
php worker.php
```

### Daemon Mode (Development)

```bash
php worker.php --daemon
```

### Cron Job (Production)

Add to crontab to run every minute:

```cron
* * * * * cd /path/to/phpweave && php worker.php >> storage/logs/worker.log 2>&1
```

Or every 5 minutes with limit:

```cron
*/5 * * * * cd /path/to/phpweave && php worker.php --limit=10 \
>> storage/logs/worker.log 2>&1
```

### Supervisor (Production - Linux)

Create `/etc/supervisor/conf.d/phpweave-worker.conf`:

```ini
[program:phpweave-worker]
command=php /var/www/phpweave/worker.php --daemon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/phpweave/storage/logs/worker.log
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start phpweave-worker
```

### Windows Task Scheduler

Create a scheduled task:

- Program: `C:\php\php.exe`
- Arguments: `C:\path\to\phpweave\worker.php`
- Trigger: Every 1 minute
- Run whether user is logged on or not

## Queue Management

### Check Queue Status

```php
$status = Async::queueStatus();
echo "Pending: " . $status['pending'];
echo "Failed: " . $status['failed'];
```

### Retry Failed Jobs

```php
// Retry all failed jobs
Async::retryFailed();

// Retry first 10 failed jobs
Async::retryFailed(10);
```

### Clear Failed Jobs

```php
$cleared = Async::clearFailed();
echo "Cleared $cleared failed jobs";
```

## Real-World Examples

### E-commerce Order Processing

```php
class Order extends Controller
{
    function checkout()
    {
        global $models;

        // Process payment synchronously
        $orderId = $models['order_model']->create($_POST);

        // Queue background tasks
        Async::queue('SendOrderConfirmationJob', [
            'order_id' => $orderId,
            'email' => $_POST['email']
        ], 1); // High priority

        Async::queue('UpdateInventoryJob', [
            'order_id' => $orderId
        ], 5);

        Async::queue('NotifyWarehouseJob', [
            'order_id' => $orderId
        ], 10);

        // Immediate response
        $this->show("order_success", ['order_id' => $orderId]);
    }
}
```

### User Registration Flow

```php
class Auth extends Controller
{
    function register()
    {
        global $models;

        // Create user account
        $userId = $models['user_model']->create([
            'email' => $_POST['email'],
            'name' => $_POST['name'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);

        // Queue welcome email
        Async::queue('SendEmailJob', [
            'to' => $_POST['email'],
            'subject' => 'Welcome to ' . $_SERVER['HTTP_HOST'],
            'message' => $this->getWelcomeEmailHTML($_POST['name'])
        ]);

        // Process profile picture
        if (isset($_FILES['avatar'])) {
            $uploadPath = $this->uploadAvatar($_FILES['avatar'], $userId);

            Async::queue('ProcessImageJob', [
                'source' => $uploadPath,
                'operations' => [
                    'resize' => [400, 400],
                    'thumbnail' => true
                ]
            ]);
        }

        // Sync with marketing platform
        Async::queue('SyncWithMailchimpJob', [
            'email' => $_POST['email'],
            'name' => $_POST['name']
        ], 15); // Low priority

        // Defer analytics logging
        Async::defer(function() use ($userId) {
            logEvent('user_registered', ['user_id' => $userId]);
        });

        $this->show("register_success");
    }
}
```

### Report Generation

```php
class Reports extends Controller
{
    function generateSalesReport()
    {
        $jobId = Async::queue('GenerateReportJob', [
            'type' => 'sales',
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'user_id' => $_SESSION['user_id'],
            'format' => $_POST['format'] // csv, pdf, excel
        ]);

        // Show status page
        $this->show("report_generating", [
            'job_id' => $jobId,
            'message' => 'Your report is being generated. ' .
                'You will receive an email when it\'s ready.'
        ]);
    }
}
```

## Best Practices

### 1. Keep Jobs Small and Focused

```php
// Good: Single responsibility
class SendEmailJob extends Job {
    public function handle($data) {
        mail($data['to'], $data['subject'], $data['message']);
    }
}

// Bad: Too many responsibilities
class UserRegistrationJob extends Job {
    public function handle($data) {
        // Sends email, processes image, syncs CRM, logs analytics
        // Better to split into separate jobs
    }
}
```

### 2. Make Jobs Idempotent

Jobs should be safe to run multiple times:

```php
class UpdateUserStatsJob extends Job {
    public function handle($data) {
        $userId = $data['user_id'];

        // Use REPLACE or ON DUPLICATE KEY UPDATE
        $sql = "REPLACE INTO user_stats (user_id, last_login) VALUES (:user_id, NOW())";
        // Safe to run multiple times
    }
}
```

### 3. Use Priorities Wisely

```php
// User-facing tasks: High priority (1-5)
Async::queue('SendOrderConfirmationJob', $data, 1);

// System tasks: Medium priority (5-10)
Async::queue('UpdateInventoryJob', $data, 5);

// Background maintenance: Low priority (10-20)
Async::queue('CleanupTempFilesJob', $data, 15);
```

### 4. Handle Failures Gracefully

```php
class SendEmailJob extends Job {
    public function handle($data) {
        try {
            $result = mail($data['to'], $data['subject'], $data['message']);

            if (!$result) {
                throw new Exception("mail() returned false");
            }
        } catch (Exception $e) {
            // Log detailed error
            error_log("Email failed: " . $e->getMessage() .
                " | Recipient: " . $data['to']);

            // Re-throw for queue to mark as failed
            throw $e;
        }
    }
}
```

### 5. Pass Only Necessary Data

```php
// Good: Pass IDs, let job fetch data
Async::queue('ProcessOrderJob', ['order_id' => $orderId]);

// Bad: Pass entire objects (serialization issues, stale data)
Async::queue('ProcessOrderJob', ['order' => $orderObject]);
```

## Troubleshooting

### Jobs Not Processing

1. Check worker is running:

   ```bash
   ps aux | grep worker.php
   ```

2. Check queue directory permissions:

   ```bash
   chmod 755 storage/queue
   ```

3. Check PHP CLI is available:

   ```bash
   php -v
   ```

### Failed Jobs

View failed jobs:

```bash
ls -la storage/queue/failed/
cat storage/queue/failed/10_job_*.json
```

Retry failed jobs:

```php
Async::retryFailed(5); // Retry 5 jobs
```

### Memory Issues

Limit jobs processed per run:

```bash
php worker.php --limit=10
```

### Monitoring

Add to worker script:

```php
$status = Async::queueStatus();
if ($status['pending'] > 100) {
    mail('admin@example.com', 'Queue Alert',
        'Queue has ' . $status['pending'] . ' pending jobs');
}
```

## Performance Tips

1. **Batch Operations**: Process multiple items in one job when possible
2. **Use Priorities**: Ensure important jobs run first
3. **Run Multiple Workers**: Scale by running multiple worker processes
4. **Monitor Queue Depth**: Alert when queue gets too long
5. **Set Timeouts**: Use `set_time_limit()` in long-running jobs

## Comparison with Other Solutions

| Feature          | PHPWeave Async | Laravel Queue | Beanstalkd    | RabbitMQ         |
| ---------------- | -------------- | ------------- | ------------- | ---------------- |
| Setup Complexity | ⭐ Very Easy   | ⭐⭐ Easy     | ⭐⭐⭐ Medium | ⭐⭐⭐⭐ Complex |
| Dependencies     | None           | Framework     | Daemon        | Daemon           |
| Persistence      | File-based     | DB/Redis      | Memory        | Disk             |
| Scalability      | Medium         | High          | High          | Very High        |
| Learning Curve   | Minimal        | Medium        | Medium        | Steep            |

PHPWeave Async is perfect for small to medium applications where simplicity
matters more than extreme scale.

## Summary

PHPWeave's async system gives you three powerful tools:

1. **`Async::run()`** - Fire and forget for simple tasks
2. **`Async::queue()`** - Robust job queue for complex workflows
3. **`Async::defer()`** - Post-response execution for cleanup tasks

Choose the right tool for each situation, and your application will feel
faster and more responsive to users!
