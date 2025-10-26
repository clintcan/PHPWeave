<?php
/**
 * Example Logging Hooks
 *
 * This file demonstrates how to use hooks for logging throughout
 * the request lifecycle. Uncomment any hooks you want to use.
 *
 * @package PHPWeave
 * @category Hooks
 */

// Log every matched route
Hook::register('after_route_match', function($data) {
    error_log("Route matched: {$data['method']} {$data['uri']} -> {$data['handler']}");
    return $data;
}, 10);

// Log controller execution
Hook::register('before_action_execute', function($data) {
    error_log("Executing: {$data['controller']}@{$data['method']}");
    return $data;
}, 10);

// Log 404 errors
Hook::register('on_404', function($data) {
    error_log("404 Not Found: {$data['method']} {$data['uri']}");
    return $data;
}, 10);

// Log all errors
Hook::register('on_error', function($data) {
    error_log("Error occurred: {$data['message']} in {$data['file']}:{$data['line']}");
    return $data;
}, 10);

// Uncomment to log framework start
// Hook::register('framework_start', function($data) {
//     error_log("PHPWeave framework started");
//     return $data;
// }, 10);

// Uncomment to log all view renders
// Hook::register('before_view_render', function($data) {
//     error_log("Rendering view: {$data['template']}");
//     return $data;
// }, 10);
