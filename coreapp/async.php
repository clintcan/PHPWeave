<?php
/**
 * Async Task System
 *
 * Simple and elegant asynchronous task execution for PHPWeave.
 * Allows running tasks in the background without blocking the main request.
 *
 * Features:
 * - Fire-and-forget background tasks
 * - Simple job queue system
 * - Task status tracking
 * - Cross-platform support (Windows & Unix)
 * - Zero dependencies (pure PHP)
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Async
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * // Fire and forget
 * Async::run(function() {
 *     // Send email
 *     mail($to, $subject, $message);
 * });
 *
 * @example
 * // Queue a job
 * Async::queue('SendEmailJob', ['to' => $email, 'subject' => 'Hello']);
 */
class Async
{
    /**
     * Jobs directory path
     * @var string
     */
    private static $jobsDir = '../jobs';

    /**
     * Queue directory path
     * @var string
     */
    private static $queueDir = '../storage/queue';

    /**
     * Run a task asynchronously
     *
     * Supports multiple callable types:
     * - Static methods: ['ClassName', 'methodName']
     * - Instance methods: [$object, 'methodName']
     * - Global functions: 'functionName'
     * - Closures: function() {} (requires opis/closure library)
     *
     * For production use, prefer Async::queue() with Job classes.
     *
     * @param callable $task The task to run asynchronously
     * @return bool True if task was dispatched successfully
     * @throws Exception If task type is not serializable
     *
     * @example
     * // Recommended: Use Job classes
     * Async::queue('SendEmailJob', ['to' => 'user@example.com']);
     *
     * @example
     * // Static method (works without any library)
     * Async::run(['EmailHelper', 'sendWelcome']);
     *
     * @example
     * // Global function (works without any library)
     * Async::run('send_notification');
     *
     * @example
     * // Closure (requires opis/closure)
     * Async::run(function() { mail('user@example.com', 'Hi', 'Hello'); });
     */
    public static function run(callable $task)
    {
        // Determine task type and serialize appropriately
        if (is_array($task)) {
            // Callable array: ['Class', 'method'] or [$object, 'method']
            if (is_object($task[0])) {
                throw new Exception(
                    'Async::run() cannot serialize instance methods. ' .
                    'Use static methods, global functions, or Async::queue() with Job classes.'
                );
            }
            // Static method - can be serialized safely
            $serialized = base64_encode(json_encode([
                'type' => 'static',
                'class' => $task[0],
                'method' => $task[1]
            ]));
            $code = self::generateStaticMethodCode($serialized);

        } elseif (is_string($task)) {
            // Global function - can be serialized safely
            $serialized = base64_encode(json_encode([
                'type' => 'function',
                'name' => $task
            ]));
            $code = self::generateFunctionCode($serialized);

        } elseif ($task instanceof \Closure) {
            // Closure - requires opis/closure library
            if (!class_exists('Opis\Closure\SerializableClosure')) {
                throw new Exception(
                    'Async::run() with closures requires opis/closure library. ' .
                    'Install with: composer require opis/closure ' .
                    'OR use static methods/functions instead: Async::run([\'ClassName\', \'methodName\'])'
                );
            }
            $wrapper = new \Opis\Closure\SerializableClosure($task);
            $serialized = base64_encode(serialize($wrapper));
            $code = self::generateClosureCode($serialized);

        } else {
            throw new Exception('Async::run() received unsupported callable type');
        }

        // Create temporary task file
        $taskFile = sys_get_temp_dir() . '/phpweave_task_' . uniqid() . '.php';
        file_put_contents($taskFile, $code);

        // Execute in background
        self::executeBackground($taskFile);

        return true;
    }

    /**
     * Generate code for static method execution
     *
     * @param string $serialized Base64-encoded JSON task data
     * @return string PHP code
     */
    private static function generateStaticMethodCode($serialized)
    {
        return '<?php
$data = json_decode(base64_decode(\'' . $serialized . '\'), true);
if ($data && $data[\'type\'] === \'static\') {
    call_user_func([$data[\'class\'], $data[\'method\']]);
}
unlink(__FILE__);
';
    }

    /**
     * Generate code for global function execution
     *
     * @param string $serialized Base64-encoded JSON task data
     * @return string PHP code
     */
    private static function generateFunctionCode($serialized)
    {
        return '<?php
$data = json_decode(base64_decode(\'' . $serialized . '\'), true);
if ($data && $data[\'type\'] === \'function\' && function_exists($data[\'name\'])) {
    call_user_func($data[\'name\']);
}
unlink(__FILE__);
';
    }

    /**
     * Generate code for closure execution (requires opis/closure)
     *
     * @param string $serialized Base64-encoded serialized closure
     * @return string PHP code
     */
    private static function generateClosureCode($serialized)
    {
        $vendorPath = str_replace("'", "\\'", dirname(__DIR__) . '/vendor/autoload.php');
        return '<?php
// Security: Only allow SerializableClosure objects
if (file_exists(\'' . $vendorPath . '\')) {
    require_once \'' . $vendorPath . '\';
}
$wrapper = unserialize(base64_decode(\'' . $serialized . '\'), [\'allowed_classes\' => [\'Opis\\\\Closure\\\\SerializableClosure\', \'Closure\']]);
if ($wrapper instanceof \\Opis\\Closure\\SerializableClosure) {
    $task = $wrapper->getClosure();
    $task();
}
unlink(__FILE__);
';
    }

    /**
     * Queue a job for background processing
     *
     * Adds a job to the queue for later processing by a worker.
     * Jobs are stored as JSON files in the queue directory.
     *
     * @param string $jobClass Name of the job class to execute
     * @param array  $data     Data to pass to the job
     * @param int    $priority Priority (lower number = higher priority)
     * @return string Job ID
     *
     * @example
     * Async::queue('SendEmailJob', [
     *     'to' => 'user@example.com',
     *     'subject' => 'Welcome',
     *     'message' => 'Thanks for signing up!'
     * ]);
     */
    public static function queue($jobClass, $data = [], $priority = 10)
    {
        self::ensureQueueDirectory();

        $jobId = uniqid('job_', true);
        $job = [
            'id' => $jobId,
            'class' => $jobClass,
            'data' => $data,
            'priority' => $priority,
            'created_at' => time(),
            'status' => 'pending'
        ];

        $filename = self::$queueDir . '/' . $priority . '_' . $jobId . '.json';
        file_put_contents($filename, json_encode($job, JSON_PRETTY_PRINT));

        return $jobId;
    }

    /**
     * Process queued jobs
     *
     * Processes all pending jobs in the queue.
     * Should be called by a worker process or cron job.
     *
     * @param int $limit Maximum number of jobs to process (0 = all)
     * @return int Number of jobs processed
     *
     * @example
     * // In a worker script or cron job
     * Async::processQueue(10); // Process 10 jobs
     */
    public static function processQueue($limit = 0)
    {
        self::ensureQueueDirectory();

        $files = glob(self::$queueDir . '/*.json');
        if (!$files) {
            return 0;
        }

        // Sort by priority (filename starts with priority)
        sort($files);

        $processed = 0;
        foreach ($files as $file) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $job = json_decode(file_get_contents($file), true);

            try {
                // Load job class
                self::loadJobClass($job['class']);

                // Execute job
                $jobInstance = new $job['class']();
                $jobInstance->handle($job['data']);

                // Remove job file on success
                unlink($file);
                $processed++;

            } catch (Exception $e) {
                // Mark as failed
                $job['status'] = 'failed';
                $job['error'] = $e->getMessage();
                $job['failed_at'] = time();

                // Move to failed directory
                $failedDir = self::$queueDir . '/failed';
                if (!is_dir($failedDir)) {
                    mkdir($failedDir, 0755, true);
                }

                $failedFile = $failedDir . '/' . basename($file);
                file_put_contents($failedFile, json_encode($job, JSON_PRETTY_PRINT));
                unlink($file);
            }
        }

        return $processed;
    }

    /**
     * Defer a task for later execution
     *
     * Schedules a task to run after the current request completes.
     * Using PHP's register_shutdown_function.
     *
     * @param callable $task The task to defer
     * @return void
     *
     * @example
     * Async::defer(function() {
     *     // Log analytics after response sent
     *     logAnalytics($_SESSION['user_id']);
     * });
     */
    public static function defer(callable $task)
    {
        register_shutdown_function($task);
    }

    /**
     * Execute command in background
     *
     * Platform-agnostic background execution.
     * Works on both Windows and Unix-like systems.
     *
     * @param string $command Command to execute
     * @return void
     */
    private static function executeBackground($command)
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows
            pclose(popen('start /B php ' . escapeshellarg($command), 'r'));
        } else {
            // Unix/Linux/Mac
            exec('php ' . escapeshellarg($command) . ' > /dev/null 2>&1 &');
        }
    }

    /**
     * Load job class file
     *
     * Loads the job class from the jobs directory.
     *
     * @param string $className Job class name
     * @return void
     * @throws Exception If job class file not found
     */
    private static function loadJobClass($className)
    {
        $dir = dirname(__FILE__, 2);
        $dir = str_replace("\\", "/", $dir);
        $jobFile = "$dir/jobs/$className.php";

        if (!file_exists($jobFile)) {
            throw new Exception("Job class file not found: $jobFile");
        }

        require_once $jobFile;

        if (!class_exists($className)) {
            throw new Exception("Job class not found: $className");
        }
    }

    /**
     * Ensure queue directory exists
     *
     * Creates queue directory structure if it doesn't exist.
     *
     * @return void
     */
    private static function ensureQueueDirectory()
    {
        $dir = dirname(__FILE__, 2);
        $dir = str_replace("\\", "/", $dir);
        $queuePath = "$dir/storage/queue";

        if (!is_dir($queuePath)) {
            mkdir($queuePath, 0755, true);
        }

        self::$queueDir = $queuePath;
    }

    /**
     * Get queue status
     *
     * Returns information about pending jobs in the queue.
     *
     * @return array Queue statistics
     *
     * @example
     * $status = Async::queueStatus();
     * echo "Pending jobs: " . $status['pending'];
     */
    public static function queueStatus()
    {
        self::ensureQueueDirectory();

        $pending = glob(self::$queueDir . '/*.json');
        $failedDir = self::$queueDir . '/failed';
        $failed = is_dir($failedDir) ? glob($failedDir . '/*.json') : [];

        return [
            'pending' => count($pending),
            'failed' => count($failed),
            'queue_path' => self::$queueDir
        ];
    }

    /**
     * Clear failed jobs
     *
     * Removes all failed jobs from the failed directory.
     *
     * @return int Number of jobs cleared
     */
    public static function clearFailed()
    {
        $failedDir = self::$queueDir . '/failed';
        if (!is_dir($failedDir)) {
            return 0;
        }

        $files = glob($failedDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            unlink($file);
            $count++;
        }

        return $count;
    }

    /**
     * Retry failed jobs
     *
     * Moves failed jobs back to the pending queue.
     *
     * @param int $limit Maximum number of jobs to retry (0 = all)
     * @return int Number of jobs retried
     */
    public static function retryFailed($limit = 0)
    {
        $failedDir = self::$queueDir . '/failed';
        if (!is_dir($failedDir)) {
            return 0;
        }

        $files = glob($failedDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            if ($limit > 0 && $count >= $limit) {
                break;
            }

            $job = json_decode(file_get_contents($file), true);
            $job['status'] = 'pending';
            unset($job['error']);
            unset($job['failed_at']);

            // Move back to queue
            $newFile = self::$queueDir . '/' . $job['priority'] . '_' . $job['id'] . '.json';
            file_put_contents($newFile, json_encode($job, JSON_PRETTY_PRINT));
            unlink($file);

            $count++;
        }

        return $count;
    }
}

/**
 * Job Base Class
 *
 * Base class for background jobs.
 * All job classes should extend this.
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Async
 *
 * @example
 * class SendEmailJob extends Job {
 *     public function handle($data) {
 *         mail($data['to'], $data['subject'], $data['message']);
 *     }
 * }
 */
abstract class Job
{
    /**
     * Handle the job
     *
     * This method must be implemented by all job classes.
     *
     * @param array $data Job data
     * @return void
     */
    abstract public function handle($data);
}
