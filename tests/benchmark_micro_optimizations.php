<?php
/**
 * Isolated Benchmark for Micro-Optimizations
 *
 * Re-testing router string operations and cache tag storage
 * to verify if they were actually slower or if there was system interference.
 *
 * Usage: php tests/benchmark_micro_optimizations.php
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @version    2.6.0
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║      PHPWeave v2.6.0 - Micro-Optimization Re-Test                     ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Running isolated benchmarks with system warm-up...\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "\n";

// System warm-up
for ($i = 0; $i < 1000; $i++) {
    $dummy = strlen("test string");
}

// =============================================================================
// TEST 1: Router String Operations (Re-test)
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ TEST 1: Router String Operations (Isolated)                           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$patterns = [
    'user/:id:',
    '/blog/',
    '/api/posts/:id:/',
    '/',
    '/admin/users',
    'products/:category:/:id:',
    '/search',
    '/api/v1/users/:userId:/posts/:postId:',
];

$iterations = 100000;

echo "Test patterns: " . count($patterns) . " patterns x $iterations iterations\n";
echo "Warming up...\n";

// Warm-up run
for ($i = 0; $i < 1000; $i++) {
    foreach ($patterns as $pattern) {
        if (substr($pattern, 0, 1) !== '/') {
            $pattern = '/' . $pattern;
        }
    }
}

echo "Running benchmarks...\n\n";

// Test 1: Old method with substr()
$times = [];
for ($run = 0; $run < 5; $run++) {
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        foreach ($patterns as $pattern) {
            // Old method
            if (substr($pattern, 0, 1) !== '/') {
                $pattern = '/' . $pattern;
            }
            if ($pattern !== '/' && substr($pattern, -1) === '/') {
                $pattern = rtrim($pattern, '/');
            }
        }
    }
    $times[] = (microtime(true) - $start) * 1000;
}
$timeOld = array_sum($times) / count($times);

// Test 2: New method with array access
$times = [];
for ($run = 0; $run < 5; $run++) {
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        foreach ($patterns as $pattern) {
            // New method - check if pattern is not empty first
            $len = strlen($pattern);
            if ($len > 0 && $pattern[0] !== '/') {
                $pattern = '/' . $pattern;
                $len++;
            }
            if ($len > 1 && $pattern[$len - 1] === '/') {
                $pattern = substr($pattern, 0, -1);
            }
        }
    }
    $times[] = (microtime(true) - $start) * 1000;
}
$timeNew = array_sum($times) / count($times);

echo "Method              | Time (ms)  | Avg per pattern\n";
echo "--------------------+------------+------------------\n";
echo "substr() + rtrim()  | " . str_pad(number_format($timeOld, 2), 10, ' ', STR_PAD_LEFT) . " | " . number_format($timeOld / ($iterations * count($patterns)) * 1000, 6) . " μs\n";
echo "Array access        | " . str_pad(number_format($timeNew, 2), 10, ' ', STR_PAD_LEFT) . " | " . number_format($timeNew / ($iterations * count($patterns)) * 1000, 6) . " μs\n";
echo "\n";

if ($timeNew < $timeOld) {
    $improvement = (($timeOld - $timeNew) / $timeOld) * 100;
    echo "✓ Array access is FASTER by " . number_format($improvement, 2) . "%\n";
    echo "  Recommendation: USE the array access optimization\n";
} else {
    $degradation = (($timeNew - $timeOld) / $timeOld) * 100;
    echo "✗ Array access is SLOWER by " . number_format($degradation, 2) . "%\n";
    echo "  Recommendation: KEEP substr() + rtrim()\n";
}

echo "\n";

// =============================================================================
// TEST 2: Cache Tag Storage (Re-test)
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ TEST 2: Cache Tag Storage (Isolated)                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$tagSizes = [10, 50, 100, 200, 500, 1000];

echo "Testing various tag sizes...\n\n";

echo "Tag Size | in_array() | array_flip() | Winner      | Improvement\n";
echo "---------+------------+--------------+-------------+-------------\n";

foreach ($tagSizes as $tagSize) {
    // Generate test data
    $keys = [];
    for ($i = 0; $i < $tagSize; $i++) {
        $keys[] = "cache_key_$i";
    }

    $iterations = 10000;
    $testKey = "cache_key_not_found"; // Key not in array (worst case)

    // Test 1: in_array (O(n))
    $times = [];
    for ($run = 0; $run < 5; $run++) {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            if (!in_array($testKey, $keys)) {
                // Would add key here
            }
        }
        $times[] = (microtime(true) - $start) * 1000;
    }
    $timeOld = array_sum($times) / count($times);

    // Test 2: array_flip (O(1) lookup)
    $times = [];
    for ($run = 0; $run < 5; $run++) {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $keysFlipped = array_flip($keys);
            if (!isset($keysFlipped[$testKey])) {
                // Would add key here
            }
        }
        $times[] = (microtime(true) - $start) * 1000;
    }
    $timeNew = array_sum($times) / count($times);

    // Determine winner
    if ($timeNew < $timeOld) {
        $improvement = (($timeOld - $timeNew) / $timeOld) * 100;
        $winner = "array_flip";
        $impStr = "+" . number_format($improvement, 1) . "%";
    } else {
        $improvement = (($timeNew - $timeOld) / $timeOld) * 100;
        $winner = "in_array";
        $impStr = "-" . number_format($improvement, 1) . "%";
    }

    printf("%7d | %9.2f | %11.2f | %-11s | %s\n",
        $tagSize, $timeOld, $timeNew, $winner, $impStr);
}

echo "\n";

// =============================================================================
// TEST 3: array_flip with caching (optimization)
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ TEST 3: Optimized array_flip (Cache flipped array)                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Tag Size | in_array() | Cached flip  | Winner      | Improvement\n";
echo "---------+------------+--------------+-------------+-------------\n";

foreach ($tagSizes as $tagSize) {
    // Generate test data
    $keys = [];
    for ($i = 0; $i < $tagSize; $i++) {
        $keys[] = "cache_key_$i";
    }

    $iterations = 10000;
    $testKey = "cache_key_not_found";

    // Test 1: in_array (O(n))
    $times = [];
    for ($run = 0; $run < 5; $run++) {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            if (!in_array($testKey, $keys)) {
                // Would add key here
            }
        }
        $times[] = (microtime(true) - $start) * 1000;
    }
    $timeOld = array_sum($times) / count($times);

    // Test 2: Cached array_flip (realistic - flip once, use many times)
    $times = [];
    for ($run = 0; $run < 5; $run++) {
        $start = microtime(true);
        $keysFlipped = array_flip($keys); // Flip once outside loop
        for ($i = 0; $i < $iterations; $i++) {
            if (!isset($keysFlipped[$testKey])) {
                // Would add key here
            }
        }
        $times[] = (microtime(true) - $start) * 1000;
    }
    $timeNew = array_sum($times) / count($times);

    // Determine winner
    if ($timeNew < $timeOld) {
        $improvement = (($timeOld - $timeNew) / $timeOld) * 100;
        $winner = "cached_flip";
        $impStr = "+" . number_format($improvement, 1) . "%";
    } else {
        $improvement = (($timeNew - $timeOld) / $timeOld) * 100;
        $winner = "in_array";
        $impStr = "-" . number_format($improvement, 1) . "%";
    }

    printf("%7d | %9.2f | %11.2f | %-11s | %s\n",
        $tagSize, $timeOld, $timeNew, $winner, $impStr);
}

echo "\n";

// =============================================================================
// RECOMMENDATIONS
// =============================================================================

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ RECOMMENDATIONS                                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Based on isolated benchmarks:\n\n";

echo "1. Router String Operations:\n";
echo "   Run the benchmark above to see if array access is actually faster.\n";
echo "   If yes: Implement the optimization\n";
echo "   If no:  Keep current substr() implementation\n";
echo "\n";

echo "2. Cache Tag Storage:\n";
echo "   For small tags (<100 keys): in_array() is likely faster\n";
echo "   For large tags (>200 keys): array_flip() with caching wins\n";
echo "   Recommendation: Use hybrid approach with threshold\n";
echo "\n";

echo "3. Suggested Implementation for Tags:\n";
echo "   if (count(\$keys) > 100) {\n";
echo "       \$keysFlipped = array_flip(\$keys);\n";
echo "       if (!isset(\$keysFlipped[\$key])) { ... }\n";
echo "   } else {\n";
echo "       if (!in_array(\$key, \$keys)) { ... }\n";
echo "   }\n";
echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║ Benchmark Complete!                                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
