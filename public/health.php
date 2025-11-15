<?php
header('Content-Type: application/json');

// Load .env file if it exists (simple parser that ignores comments)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

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
    // Support both old (DBHOST) and new (DB_HOST) naming conventions
    $dbhost = getenv('DB_HOST') ?: getenv('DBHOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: getenv('DBNAME') ?: 'phpweave';
    $dbuser = getenv('DB_USER') ?: getenv('DBUSER') ?: 'root';
    $dbpass = getenv('DB_PASSWORD') ?: getenv('DBPASSWORD') ?: '';
    
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