<?php
/**
 * Example Authentication Hooks
 *
 * This file demonstrates how to implement authentication checks using hooks.
 * Uncomment and modify according to your authentication needs.
 *
 * @package PHPWeave
 * @category Hooks
 */

// Example: Require authentication for all routes except login/register
/*
Hook::register('before_action_execute', function($data) {
    // List of routes that don't require authentication
    $publicRoutes = ['Auth@login', 'Auth@register', 'Home@index'];

    $handler = $data['controller'] . '@' . $data['method'];

    // If route requires auth and user is not logged in
    if (!in_array($handler, $publicRoutes) && !isset($_SESSION['user'])) {
        header('Location: /login');
        Hook::halt(); // Stop other hooks and controller execution
        exit;
    }

    return $data;
}, 5); // Priority 5 (runs early)
*/

// Example: Check user permissions before specific actions
/*
Hook::register('before_action_execute', function($data) {
    // Check if this is an admin-only action
    $adminActions = ['User@delete', 'User@ban', 'Admin@index'];

    $handler = $data['controller'] . '@' . $data['method'];

    if (in_array($handler, $adminActions)) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('HTTP/1.0 403 Forbidden');
            echo "403 - Forbidden: Admin access required";
            Hook::halt();
            exit;
        }
    }

    return $data;
}, 5);
*/

// Example: Add user data to all views
/*
Hook::register('before_view_render', function($data) {
    // Add current user to view data
    if (isset($_SESSION['user'])) {
        if (!is_array($data['data'])) {
            $data['data'] = ['_content' => $data['data']];
        }
        $data['data']['current_user'] = $_SESSION['user'];
    }

    return $data;
}, 10);
*/
