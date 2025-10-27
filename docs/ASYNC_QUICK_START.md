# Async Quick Start

Get started with PHPWeave async in 3 minutes.

## 1. Send an Email in Background

```php
// In your controller
class User extends Controller
{
    function register()
    {
        // Save user
        global $models;
        $models['user_model']->create($_POST);

        // Send welcome email (doesn't block response!)
        Async::run(function() {
            mail($_POST['email'], 'Welcome!', 'Thanks for signing up!');
        });

        $this->show("success");
    }
}
```

**That's it!** The email sends in the background.

## 2. Use the Job Queue (Recommended)

**Create a job** in `jobs/SendEmailJob.php`:

```php
<?php
class SendEmailJob extends Job
{
    public function handle($data)
    {
        mail($data['to'], $data['subject'], $data['message']);
    }
}
```

**Queue it** in your controller:

```php
Async::queue('SendEmailJob', [
    'to' => 'user@example.com',
    'subject' => 'Welcome',
    'message' => 'Thanks for signing up!'
]);
```

**Run the worker**:

```bash
php worker.php --daemon
```

## 3. Common Use Cases

### After User Registration

```php
// Queue multiple tasks
Async::queue('SendEmailJob', ['to' => $email, ...]);
Async::queue('ProcessImageJob', ['source' => $avatar, ...]);
Async::queue('SyncWithCRMJob', ['user_id' => $userId, ...]);
```

### After Order Placement

```php
Async::queue('SendOrderConfirmationJob', ['order_id' => $orderId], 1); // High priority
Async::queue('UpdateInventoryJob', ['order_id' => $orderId], 5);
Async::queue('NotifyWarehouseJob', ['order_id' => $orderId], 10);
```

### Generate Reports

```php
Async::queue('GenerateReportJob', [
    'type' => 'sales',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'user_id' => $_SESSION['user_id']
]);
```

## Setup for Production

### Option 1: Cron Job (Simplest)

Add to crontab:

```cron
* * * * * cd /path/to/phpweave && php worker.php >> storage/logs/worker.log 2>&1
```

### Option 2: Supervisor (Best)

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

Restart supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start phpweave-worker
```

## Management Commands

```php
// Check queue status
$status = Async::queueStatus();
echo "Pending: {$status['pending']}, Failed: {$status['failed']}";

// Retry failed jobs
Async::retryFailed();

// Clear failed jobs
Async::clearFailed();
```

## That's It

You now have async task processing in PHPWeave.

See **ASYNC_GUIDE.md** for complete documentation.
