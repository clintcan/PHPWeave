<?php
/**
 * Example Global Data Injection Hooks
 *
 * This file demonstrates how to inject global data into all views
 * using hooks, such as site settings, navigation menus, etc.
 *
 * @package PHPWeave
 * @category Hooks
 */

// Example: Add site settings to all views
/*
Hook::register('before_view_render', function($data) {
    // Ensure data is an array
    if (!is_array($data['data'])) {
        $originalData = $data['data'];
        $data['data'] = ['_content' => $originalData];
    }

    // Add global site settings
    $data['data']['site_name'] = 'My PHPWeave Site';
    $data['data']['site_url'] = 'https://example.com';
    $data['data']['current_year'] = date('Y');

    return $data;
}, 10);
*/

// Example: Add navigation menu to all views
/*
Hook::register('before_view_render', function($data) {
    if (!is_array($data['data'])) {
        $originalData = $data['data'];
        $data['data'] = ['_content' => $originalData];
    }

    $data['data']['navigation'] = [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About', 'url' => '/about'],
        ['label' => 'Contact', 'url' => '/contact'],
    ];

    return $data;
}, 10);
*/

// Example: Add flash messages to all views
/*
Hook::register('before_view_render', function($data) {
    if (!is_array($data['data'])) {
        $originalData = $data['data'];
        $data['data'] = ['_content' => $originalData];
    }

    // Get flash messages from session
    if (isset($_SESSION['flash_messages'])) {
        $data['data']['flash_messages'] = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
    }

    return $data;
}, 10);
*/

// Example: Add CSRF token to all views
/*
Hook::register('before_view_render', function($data) {
    if (!is_array($data['data'])) {
        $originalData = $data['data'];
        $data['data'] = ['_content' => $originalData];
    }

    // Generate or retrieve CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $data['data']['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}, 10);
*/
