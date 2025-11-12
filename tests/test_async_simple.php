<?php
/**
 * Simple async test to debug background execution
 */

require_once __DIR__ . '/../coreapp/async.php';

class SimpleTest {
    public static function log($message) {
        $file = sys_get_temp_dir() . '/phpweave_simple_test.log';
        file_put_contents($file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
    }
}

echo "Testing async execution...\n";
echo "Temp dir: " . sys_get_temp_dir() . "\n";
echo "PHP Binary: " . PHP_BINARY . "\n\n";

// Clean up old log
$logFile = sys_get_temp_dir() . '/phpweave_simple_test.log';
if (file_exists($logFile)) {
    unlink($logFile);
}

try {
    echo "Running async task...\n";
    Async::run(['SimpleTest', 'log'], ['Test message from async']);

    echo "Waiting for background process...\n";
    sleep(3);

    if (file_exists($logFile)) {
        echo "✓ Log file created!\n";
        echo "Contents:\n";
        echo file_get_contents($logFile);
    } else {
        echo "✗ Log file NOT created\n";

        // Check for temp task files
        echo "\nChecking for task files in temp dir...\n";
        $files = glob(sys_get_temp_dir() . '/phpweave_task_*.php');
        if (!empty($files)) {
            echo "Found " . count($files) . " task files:\n";
            foreach ($files as $file) {
                echo "  - $file\n";
                echo "Contents:\n";
                echo file_get_contents($file);
                echo "\n";
            }
        } else {
            echo "No task files found\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
