<?php
/**
 * Example CORS (Cross-Origin Resource Sharing) Hooks
 *
 * This file demonstrates how to handle CORS requests using hooks.
 * Useful for API endpoints that need to be accessed from different domains.
 *
 * @package PHPWeave
 * @category Hooks
 */

// Example: Enable CORS for all requests
/*
Hook::register('framework_start', function($data) {
    // Allow requests from any origin (change * to specific domain in production)
    header('Access-Control-Allow-Origin: *');

    // Allow specific methods
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');

    // Allow specific headers
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Allow credentials
    header('Access-Control-Allow-Credentials: true');

    // Cache preflight requests for 1 hour
    header('Access-Control-Max-Age: 3600');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    return $data;
}, 5);
*/

// Example: Enable CORS only for API routes
/*
Hook::register('after_route_match', function($data) {
    // Check if this is an API route
    if (strpos($data['uri'], '/api/') === 0) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            Hook::halt();
            exit;
        }
    }

    return $data;
}, 5);
*/
