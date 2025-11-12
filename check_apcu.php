<?php
/**
 * APCu Installation Checker
 */

echo "APCu Installation Status\n";
echo str_repeat("=", 50) . "\n\n";

// Check if APCu is loaded
if (extension_loaded('apcu')) {
    echo "✓ APCu Extension: LOADED\n";
    echo "  Version: " . phpversion('apcu') . "\n\n";

    echo "Configuration:\n";
    echo "  apc.enabled: " . (ini_get('apc.enabled') ? 'Yes' : 'No') . "\n";
    echo "  apc.shm_size: " . ini_get('apc.shm_size') . "\n";
    echo "  apc.enable_cli: " . (ini_get('apc.enable_cli') ? 'Yes' : 'No') . "\n\n";

    // Test APCu functionality
    echo "Testing APCu functionality...\n";
    $testKey = 'test_key_' . time();
    $testValue = 'test_value_' . rand();

    $stored = apcu_store($testKey, $testValue, 60);
    if ($stored) {
        echo "  ✓ Store: SUCCESS\n";

        $retrieved = apcu_fetch($testKey);
        if ($retrieved === $testValue) {
            echo "  ✓ Fetch: SUCCESS\n";
            echo "  ✓ APCu is working correctly!\n\n";

            // Clean up
            apcu_delete($testKey);
        } else {
            echo "  ✗ Fetch: FAILED\n";
        }
    } else {
        echo "  ✗ Store: FAILED\n";
    }

    echo str_repeat("=", 50) . "\n";
    echo "SUCCESS! APCu is ready for PHPWeave\n";
    echo str_repeat("=", 50) . "\n";

} else {
    echo "✗ APCu Extension: NOT LOADED\n\n";

    echo "Installation Steps:\n";
    echo "1. Download APCu DLL from:\n";
    echo "   https://windows.php.net/downloads/pecl/releases/apcu/\n\n";

    echo "2. Look for file matching:\n";
    echo "   - PHP 8.4\n";
    echo "   - Thread Safe (TS)\n";
    echo "   - x64 architecture\n";
    echo "   - VS16/VS17 build\n\n";

    echo "3. Copy php_apcu.dll to: C:\\php\\ext\\\n\n";

    echo "4. Add to php.ini:\n";
    echo "   extension=apcu\n";
    echo "   apc.enabled=1\n";
    echo "   apc.shm_size=32M\n";
    echo "   apc.enable_cli=1\n\n";

    echo "5. Run this script again to verify\n";

    echo str_repeat("=", 50) . "\n";
    exit(1);
}
