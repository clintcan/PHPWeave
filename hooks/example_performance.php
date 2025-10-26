<?php
/**
 * Example Performance Monitoring Hooks
 *
 * This file demonstrates how to use hooks for performance monitoring
 * and profiling your application.
 *
 * @package PHPWeave
 * @category Hooks
 */

// Track execution time
$GLOBALS['_perf_start_time'] = null;
$GLOBALS['_perf_timings'] = [];

// Start timer at framework start
Hook::register('framework_start', function($data) {
    $GLOBALS['_perf_start_time'] = microtime(true);
    $GLOBALS['_perf_timings']['framework_start'] = 0;
    return $data;
}, 1);

// Track route matching time
Hook::register('after_route_match', function($data) {
    if ($GLOBALS['_perf_start_time']) {
        $elapsed = microtime(true) - $GLOBALS['_perf_start_time'];
        $GLOBALS['_perf_timings']['route_match'] = $elapsed;
    }
    return $data;
}, 99);

// Track controller execution time
$GLOBALS['_perf_action_start'] = null;

Hook::register('before_action_execute', function($data) {
    $GLOBALS['_perf_action_start'] = microtime(true);
    return $data;
}, 1);

Hook::register('after_action_execute', function($data) {
    if ($GLOBALS['_perf_action_start']) {
        $elapsed = microtime(true) - $GLOBALS['_perf_action_start'];
        $GLOBALS['_perf_timings']['action_execute'] = $elapsed;
    }
    return $data;
}, 99);

// Output performance report at shutdown
Hook::register('framework_shutdown', function($data) {
    if (!$GLOBALS['_perf_start_time']) {
        return $data;
    }

    $totalTime = microtime(true) - $GLOBALS['_perf_start_time'];

    // Only log if DEBUG is enabled
    if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
        $report = "Performance Report:\n";
        $report .= "  Total time: " . number_format($totalTime * 1000, 2) . "ms\n";

        foreach ($GLOBALS['_perf_timings'] as $stage => $time) {
            $report .= "  - $stage: " . number_format($time * 1000, 2) . "ms\n";
        }

        $report .= "  Memory peak: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n";

        error_log($report);

        // Optionally output as HTML comment
        // echo "<!-- $report -->";
    }

    return $data;
}, 99);
