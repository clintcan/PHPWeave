<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check PHP
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Check APCu if enabled
if (extension_loaded('apcu') && ini_get('apc.enabled')) {
    $health['checks']['apcu'] = [
        'status' => 'ok',
        'enabled' => true,
        'cache_info' => apcu_cache_info(true)
    ];
}

// Check database connection
try {
    $dbhost = getenv('DBHOST') ?: 'localhost';
    $dbname = getenv('DBNAME') ?: 'phpweave';
    $dbuser = getenv('DBUSER') ?: 'root';
    $dbpass = getenv('DBPASSWORD') ?: '';
    
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SELECT 1');
    $health['checks']['database'] = [
        'status' => 'ok',
        'connected' => true
    ];
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'connected' => false,
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Check filesystem
$storagePath = $_SERVER['DOCUMENT_ROOT'] . '/../storage';
$cachePath = $_SERVER['DOCUMENT_ROOT'] . '/../cache';

$health['checks']['filesystem'] = [
    'status' => 'ok',
    'writable' => [
        'storage' => is_dir($storagePath) && is_writable($storagePath),
        'cache' => is_dir($cachePath) && is_writable($cachePath)
    ]
];

if (!$health['checks']['filesystem']['writable']['storage'] || 
    !$health['checks']['filesystem']['writable']['cache']) {
    $health['checks']['filesystem']['status'] = 'warning';
    $health['status'] = 'degraded';
}

// Set appropriate HTTP status code
http_response_code($health['status'] === 'healthy' ? 200 : 503);

echo json_encode($health, JSON_PRETTY_PRINT);