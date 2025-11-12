<?php
require_once __DIR__ . '/libraries/http_async.php';

echo "Testing httpbin.org availability...\n\n";

$result = HTTPAsync::get('https://httpbin.org/get')->execute();

echo "Status: " . $result['get']['status'] . "\n";
echo "Error: " . ($result['get']['error'] ?? 'none') . "\n";
echo "Body length: " . strlen($result['get']['body'] ?? '') . "\n";
