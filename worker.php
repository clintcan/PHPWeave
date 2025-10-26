<?php
/**
 * Queue Worker
 *
 * Processes background jobs from the queue.
 * Run this script via cron job or as a daemon process.
 *
 * @package    PHPWeave
 * @category   Worker
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * Usage:
 *   php worker.php           # Process all pending jobs once
 *   php worker.php --daemon  # Run continuously
 *   php worker.php --limit=5 # Process only 5 jobs
 */

// Load the framework
require_once __DIR__ . '/coreapp/dbconnection.php';
require_once __DIR__ . '/coreapp/models.php';
require_once __DIR__ . '/coreapp/async.php';

// Parse command line arguments
$options = getopt('', ['daemon', 'limit:', 'sleep:']);
$daemon = isset($options['daemon']);
$limit = isset($options['limit']) ? (int)$options['limit'] : 0;
$sleep = isset($options['sleep']) ? (int)$options['sleep'] : 5;

echo "PHPWeave Queue Worker\n";
echo "=====================\n\n";

if ($daemon) {
    echo "Running in daemon mode (Ctrl+C to stop)\n";
    echo "Sleep interval: {$sleep} seconds\n\n";

    while (true) {
        $processed = Async::processQueue($limit);

        if ($processed > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Processed $processed job(s)\n";
        }

        // Check queue status
        $status = Async::queueStatus();
        if ($status['pending'] > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Queue: {$status['pending']} pending, {$status['failed']} failed\n";
        }

        sleep($sleep);
    }
} else {
    // Single run
    echo "Processing queue...\n";

    $processed = Async::processQueue($limit);

    echo "Processed: $processed job(s)\n";

    $status = Async::queueStatus();
    echo "Queue Status:\n";
    echo "  Pending: {$status['pending']}\n";
    echo "  Failed: {$status['failed']}\n";
}
