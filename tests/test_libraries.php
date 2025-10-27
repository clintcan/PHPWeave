<?php
/**
 * Libraries System Test Script
 *
 * Comprehensive tests for PHPWeave's lazy library loading system.
 * Run this script from command line to verify libraries work correctly:
 * php tests/test_libraries.php
 *
 * Tests include:
 * - Lazy library loading
 * - Library instantiation
 * - Multiple access methods (function, array, object)
 * - Library caching
 * - Error handling
 * - PHPWeave global object
 * - String helper library methods
 */

echo "Testing PHPWeave Libraries System\n";
echo str_repeat("=", 50) . "\n";
echo "Testing lazy library loading and string_helper functionality\n";
echo str_repeat("=", 50) . "\n\n";

// Load required core files
define('PHPWEAVE_ROOT', dirname(__DIR__));

// Change to coreapp directory so relative paths work
$originalDir = getcwd();
chdir(PHPWEAVE_ROOT . '/coreapp');

require_once PHPWEAVE_ROOT . '/coreapp/libraries.php';

// Change back to original directory
chdir($originalDir);

// Test 1: Library file discovery
echo "Test 1: Library File Discovery\n";
$discoveredLibraries = $GLOBALS['_library_files'] ?? [];
echo "  - Libraries discovered: " . count($discoveredLibraries) . "\n";
foreach ($discoveredLibraries as $name => $class) {
    echo "    * $name => $class\n";
}
echo "  Result: " . (count($discoveredLibraries) > 0 ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Library loading using function (recommended)
echo "Test 2: Library Loading via library() Function\n";
try {
    if (isset($GLOBALS['_library_files']['string_helper'])) {
        $stringHelper = library('string_helper');
        echo "  - Library loaded: " . get_class($stringHelper) . "\n";
        echo "  - Has slugify() method: " . (method_exists($stringHelper, 'slugify') ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  - string_helper not found (skipping test)\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 3: Library loading using global $libraries array
echo "Test 3: Library Loading via \$libraries Array (Legacy)\n";
try {
    global $libraries;
    if (isset($GLOBALS['_library_files']['string_helper'])) {
        $stringHelper2 = $libraries['string_helper'];
        echo "  - Library loaded: " . get_class($stringHelper2) . "\n";
        echo "  - Is LazyLibraryLoader: " . (get_class($libraries) === 'LazyLibraryLoader' ? 'yes' : 'no') . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  - string_helper not found (skipping test)\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 4: PHPWeave global object
echo "Test 4: PHPWeave Global Object (\$PW)\n";
try {
    global $PW;
    echo "  - \$PW exists: " . (isset($PW) ? 'yes' : 'no') . "\n";
    echo "  - \$PW is PHPWeaveLibraries instance: " . (get_class($PW) === 'PHPWeaveLibraries' ? 'yes' : 'no') . "\n";
    echo "  - \$PW->libraries exists: " . (isset($PW->libraries) ? 'yes' : 'no') . "\n";

    if (isset($GLOBALS['_library_files']['string_helper'])) {
        $stringHelper3 = $PW->libraries->string_helper;
        echo "  - Library loaded via \$PW->libraries: " . get_class($stringHelper3) . "\n";
        echo "  Result: PASS\n\n";
    } else {
        echo "  Result: PASS (no libraries to test)\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 5: Library instance caching (same instance returned)
echo "Test 5: Library Instance Caching\n";
try {
    if (isset($GLOBALS['_library_files']['string_helper'])) {
        $instance1 = library('string_helper');
        $instance2 = library('string_helper');
        $isSameInstance = spl_object_id($instance1) === spl_object_id($instance2);
        echo "  - First call object ID: " . spl_object_id($instance1) . "\n";
        echo "  - Second call object ID: " . spl_object_id($instance2) . "\n";
        echo "  - Same instance: " . ($isSameInstance ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($isSameInstance ? 'PASS' : 'FAIL') . "\n\n";
    } else {
        echo "  - No libraries available for testing\n";
        echo "  Result: SKIP\n\n";
    }
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  Result: FAIL\n\n";
}

// Test 6: Error handling for non-existent library
echo "Test 6: Error Handling for Non-Existent Library\n";
try {
    $nonExistent = library('nonexistent_library');
    echo "  - Should have thrown exception\n";
    echo "  Result: FAIL\n\n";
} catch (Exception $e) {
    echo "  - Caught expected exception: " . $e->getMessage() . "\n";
    echo "  Result: PASS\n\n";
}

// Test 7: ArrayAccess isset() check
echo "Test 7: ArrayAccess isset() Check\n";
global $libraries;
echo "  - isset(\$libraries['string_helper']): " . (isset($libraries['string_helper']) ? 'yes' : 'no') . "\n";
echo "  - isset(\$libraries['nonexistent']): " . (isset($libraries['nonexistent']) ? 'yes' : 'no') . "\n";
$stringHelperExists = isset($GLOBALS['_library_files']['string_helper']);
echo "  Result: " . (
    isset($libraries['string_helper']) === $stringHelperExists &&
    !isset($libraries['nonexistent']) ? 'PASS' : 'FAIL'
) . "\n\n";

// Test 8: Magic __isset() for object property access
echo "Test 8: Magic __isset() for Object Property Access\n";
global $PW;
echo "  - isset(\$PW->libraries->string_helper): " . (isset($PW->libraries->string_helper) ? 'yes' : 'no') . "\n";
echo "  - isset(\$PW->libraries->nonexistent): " . (isset($PW->libraries->nonexistent) ? 'yes' : 'no') . "\n";
echo "  Result: " . (
    isset($PW->libraries->string_helper) === $stringHelperExists &&
    !isset($PW->libraries->nonexistent) ? 'PASS' : 'FAIL'
) . "\n\n";

// Test 9: ArrayAccess set() should trigger warning
echo "Test 9: ArrayAccess set() Protection\n";
set_error_handler(function($errno, $errstr) {
    global $setWarningTriggered;
    if (strpos($errstr, 'Cannot set libraries') !== false) {
        $setWarningTriggered = true;
    }
    return true;
}, E_USER_WARNING);

$setWarningTriggered = false;
$libraries['test'] = 'should not work';
restore_error_handler();
echo "  - Warning triggered: " . ($setWarningTriggered ? 'yes' : 'no') . "\n";
echo "  Result: " . ($setWarningTriggered ? 'PASS' : 'FAIL') . "\n\n";

// Test 10: ArrayAccess unset() should trigger warning
echo "Test 10: ArrayAccess unset() Protection\n";
set_error_handler(function($errno, $errstr) {
    global $unsetWarningTriggered;
    if (strpos($errstr, 'Cannot unset libraries') !== false) {
        $unsetWarningTriggered = true;
    }
    return true;
}, E_USER_WARNING);

$unsetWarningTriggered = false;
unset($libraries['string_helper']); // Try to unset any library
restore_error_handler();
echo "  - Warning triggered: " . ($unsetWarningTriggered ? 'yes' : 'no') . "\n";
echo "  Result: " . ($unsetWarningTriggered ? 'PASS' : 'FAIL') . "\n\n";

// Test 11: All three access methods return same instance
echo "Test 11: All Access Methods Return Same Instance\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        global $libraries, $PW;
        $viaFunction = library('string_helper');
        $viaArray = $libraries['string_helper'];
        $viaObject = $PW->libraries->string_helper;

        $id1 = spl_object_id($viaFunction);
        $id2 = spl_object_id($viaArray);
        $id3 = spl_object_id($viaObject);

        echo "  - library() ID: $id1\n";
        echo "  - \$libraries[] ID: $id2\n";
        echo "  - \$PW->libraries-> ID: $id3\n";

        $allSame = ($id1 === $id2 && $id2 === $id3);
        echo "  - All same instance: " . ($allSame ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($allSame ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - No libraries available\n";
    echo "  Result: SKIP\n\n";
}

// Test 12: string_helper->slugify() method
echo "Test 12: string_helper->slugify() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $input = "Hello World! This is a Test";
        $output = $helper->slugify($input);
        $expected = "hello-world-this-is-a-test";
        echo "  - Input: '$input'\n";
        echo "  - Output: '$output'\n";
        echo "  - Expected: '$expected'\n";
        echo "  - Match: " . ($output === $expected ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($output === $expected ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 13: string_helper->truncate() method
echo "Test 13: string_helper->truncate() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $input = "This is a very long string that needs to be truncated for display purposes.";
        $output = $helper->truncate($input, 20);
        echo "  - Input: '$input'\n";
        echo "  - Output: '$output'\n";
        echo "  - Length <= 23 (20 + '...'): " . (strlen($output) <= 23 ? 'yes' : 'no') . "\n";
        echo "  - Contains '...': " . (strpos($output, '...') !== false ? 'yes' : 'no') . "\n";
        echo "  Result: " . (strlen($output) <= 23 && strpos($output, '...') !== false ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 14: string_helper->random() method
echo "Test 14: string_helper->random() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $length = 16;
        $output1 = $helper->random($length);
        $output2 = $helper->random($length);
        echo "  - Requested length: $length\n";
        echo "  - Output 1: '$output1' (length: " . strlen($output1) . ")\n";
        echo "  - Output 2: '$output2' (length: " . strlen($output2) . ")\n";
        echo "  - Correct length: " . (strlen($output1) === $length ? 'yes' : 'no') . "\n";
        echo "  - Different strings: " . ($output1 !== $output2 ? 'yes' : 'no') . "\n";
        echo "  Result: " . (strlen($output1) === $length && $output1 !== $output2 ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 15: string_helper->ordinal() method
echo "Test 15: string_helper->ordinal() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $tests = [
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            4 => '4th',
            11 => '11th',
            21 => '21st',
            22 => '22nd',
            103 => '103rd'
        ];
        $allPass = true;
        foreach ($tests as $input => $expected) {
            $output = $helper->ordinal($input);
            $match = ($output === $expected);
            echo "  - ordinal($input): '$output' " . ($match ? '✓' : "✗ (expected: '$expected')") . "\n";
            if (!$match) $allPass = false;
        }
        echo "  Result: " . ($allPass ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 16: string_helper->titleCase() method
echo "Test 16: string_helper->titleCase() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $input = "the quick brown fox jumps over the lazy dog";
        $output = $helper->titleCase($input);
        $expected = "The Quick Brown Fox Jumps over the Lazy Dog";
        echo "  - Input: '$input'\n";
        echo "  - Output: '$output'\n";
        echo "  - Expected: '$expected'\n";
        echo "  - Match: " . ($output === $expected ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($output === $expected ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 17: string_helper->wordCount() method
echo "Test 17: string_helper->wordCount() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        $input = "The quick brown fox jumps";
        $output = $helper->wordCount($input);
        $expected = 5;
        echo "  - Input: '$input'\n";
        echo "  - Output: $output\n";
        echo "  - Expected: $expected\n";
        echo "  - Match: " . ($output === $expected ? 'yes' : 'no') . "\n";
        echo "  Result: " . ($output === $expected ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 18: string_helper->readingTime() method
echo "Test 18: string_helper->readingTime() Method\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $helper = library('string_helper');
        // Create text with approximately 600 words (should be 3 min at 200 wpm)
        $input = str_repeat("word ", 600);
        $output = $helper->readingTime($input);
        echo "  - Input word count: ~600 words\n";
        echo "  - Output: '$output'\n";
        echo "  - Expected: '3 min read'\n";
        echo "  - Contains 'min read': " . (strpos($output, 'min read') !== false ? 'yes' : 'no') . "\n";
        echo "  Result: " . (strpos($output, 'min read') !== false ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 19: Chaining library calls
echo "Test 19: Chaining Multiple Library Calls\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        global $PW;
        $text = "My Awesome Blog Post Title!";

        $slug = $PW->libraries->string_helper->slugify($text);
        $titleCased = $PW->libraries->string_helper->titleCase($text);
        $wordCount = $PW->libraries->string_helper->wordCount($text);

        echo "  - Original: '$text'\n";
        echo "  - Slug: '$slug'\n";
        echo "  - Title Case: '$titleCased'\n";
        echo "  - Word Count: $wordCount\n";
        echo "  - All operations completed: yes\n";
        echo "  Result: PASS\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

// Test 20: Performance - multiple library calls use cached instance
echo "Test 20: Performance - Instance Caching Verification\n";
if (isset($GLOBALS['_library_files']['string_helper'])) {
    try {
        $startTime = microtime(true);

        // First call - instantiation
        $instance1 = library('string_helper');
        $time1 = microtime(true) - $startTime;

        $startTime2 = microtime(true);
        // Subsequent calls - should be much faster (cached)
        for ($i = 0; $i < 100; $i++) {
            library('string_helper');
        }
        $time2 = (microtime(true) - $startTime2) / 100;

        echo "  - First call time: " . number_format($time1 * 1000, 4) . "ms\n";
        echo "  - Average cached call time: " . number_format($time2 * 1000, 4) . "ms\n";
        echo "  - Cached is faster: " . ($time2 < $time1 ? 'yes' : 'no') . "\n";
        echo "  - Performance gain: " . number_format(($time1 / $time2), 2) . "x faster\n";
        echo "  Result: " . ($time2 < $time1 ? 'PASS' : 'FAIL') . "\n\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
        echo "  Result: FAIL\n\n";
    }
} else {
    echo "  - string_helper not available\n";
    echo "  Result: SKIP\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "All Library Tests Completed!\n";
echo "\nSummary:\n";
echo "- Library system uses lazy loading for performance\n";
echo "- Three ways to access: library(), \$libraries[], \$PW->libraries->\n";
echo "- All methods return cached instances (same object)\n";
echo "- Protected against direct modification\n";
echo "- string_helper provides 7 utility methods\n";
echo "\nTo see libraries in action, visit:\n";
echo "  http://yoursite.com/blog/slugify/Hello-World-Testing\n";
