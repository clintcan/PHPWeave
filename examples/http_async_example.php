<?php
/**
 * HTTP Async Library - Usage Examples
 *
 * Demonstrates various ways to use the http_async library
 * for concurrent, non-blocking HTTP requests.
 *
 * @package    PHPWeave
 * @subpackage Examples
 * @category   HTTP
 * @author     Clint Christopher Canada
 * @version    2.2.2
 */

// This would typically be in a controller
// For standalone testing:
require_once __DIR__ . '/../coreapp/libraries.php';
require_once __DIR__ . '/../libraries/http_async.php';

echo "=== HTTP Async Library Examples ===\n\n";

// Example 1: Simple GET request
echo "Example 1: Simple GET Request\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();
$http->get('https://jsonplaceholder.typicode.com/posts/1', 'post');
$results = $http->executeJson();

echo "Post Title: " . $results['post']['json']['title'] . "\n";
echo "Status: " . $results['post']['status'] . "\n";
echo "Time: " . $results['post']['execution_time'] . "s\n\n";


// Example 2: Multiple concurrent requests
echo "Example 2: Multiple Concurrent Requests (3 APIs at once)\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();

$startTime = microtime(true);

// Queue 3 requests (they don't execute yet)
$http->get('https://jsonplaceholder.typicode.com/posts/1', 'post1')
     ->get('https://jsonplaceholder.typicode.com/posts/2', 'post2')
     ->get('https://jsonplaceholder.typicode.com/posts/3', 'post3');

// Execute ALL requests concurrently
$results = $http->executeJson();
$totalTime = microtime(true) - $startTime;

echo "Post 1: " . $results['post1']['json']['title'] . "\n";
echo "Post 2: " . $results['post2']['json']['title'] . "\n";
echo "Post 3: " . $results['post3']['json']['title'] . "\n";
echo "Total time for 3 concurrent requests: " . round($totalTime, 4) . "s\n\n";


// Example 3: POST request
echo "Example 3: POST Request (Create Resource)\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();
$http->post('https://jsonplaceholder.typicode.com/posts', [
    'title' => 'My Test Post',
    'body' => 'This is a test post created via http_async',
    'userId' => 1
], 'create_post');

$results = $http->executeJson();

echo "Created Post ID: " . $results['create_post']['json']['id'] . "\n";
echo "Title: " . $results['create_post']['json']['title'] . "\n";
echo "Status: " . $results['create_post']['status'] . " (201 = Created)\n\n";


// Example 4: Error handling
echo "Example 4: Error Handling\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();
$http->get('https://jsonplaceholder.typicode.com/posts/999999', 'not_found')
     ->get('https://this-domain-does-not-exist-12345.com', 'invalid');

$results = $http->execute();

// Check not_found
if ($results['not_found']['status'] === 404) {
    echo "✓ Correctly handled 404 Not Found\n";
}

// Check invalid domain
if (!empty($results['invalid']['error']) || $results['invalid']['status'] === 0) {
    echo "✓ Correctly captured connection error\n";
}
echo "\n";


// Example 5: Custom headers (API authentication)
echo "Example 5: Custom Headers\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();
$http->get('https://httpbin.org/headers', 'headers', [
    'X-Custom-Header: TestValue',
    'User-Agent: PHPWeave/2.2.2'
]);

$results = $http->executeJson();

echo "Headers sent successfully\n";
echo "Custom header value: " . $results['headers']['json']['headers']['X-Custom-Header'] . "\n\n";


// Example 6: All HTTP methods
echo "Example 6: All HTTP Methods\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();
$http->get('https://httpbin.org/get', 'get')
     ->post('https://httpbin.org/post', ['test' => 'data'], 'post')
     ->put('https://httpbin.org/put', ['test' => 'data'], 'put')
     ->patch('https://httpbin.org/patch', ['test' => 'data'], 'patch')
     ->delete('https://httpbin.org/delete', 'delete');

$results = $http->execute();

$methods = ['get', 'post', 'put', 'patch', 'delete'];
foreach ($methods as $method) {
    if ($results[$method]['status'] === 200) {
        echo "✓ " . strtoupper($method) . " request successful\n";
    }
}
echo "\n";


// Example 7: Performance comparison
echo "Example 7: Performance Comparison (Sequential vs Concurrent)\n";
echo str_repeat("-", 40) . "\n";

$urls = [
    'https://jsonplaceholder.typicode.com/posts/1',
    'https://jsonplaceholder.typicode.com/posts/2',
    'https://jsonplaceholder.typicode.com/posts/3'
];

// Sequential
$seqStart = microtime(true);
foreach ($urls as $url) {
    file_get_contents($url);
}
$seqTime = microtime(true) - $seqStart;

// Concurrent
$http = new http_async();
$concStart = microtime(true);
foreach ($urls as $i => $url) {
    $http->get($url, "req_$i");
}
$http->execute();
$concTime = microtime(true) - $concStart;

echo "Sequential time: " . round($seqTime, 4) . "s\n";
echo "Concurrent time: " . round($concTime, 4) . "s\n";
echo "Speedup: " . round($seqTime / $concTime, 2) . "x faster\n\n";


// Example 8: Real-world use case - Dashboard aggregation
echo "Example 8: Real-World Use Case - Dashboard Data Aggregation\n";
echo str_repeat("-", 40) . "\n";

$http = new http_async();

// Simulate fetching data from multiple sources for a dashboard
$http->get('https://jsonplaceholder.typicode.com/users/1', 'user')
     ->get('https://jsonplaceholder.typicode.com/posts?userId=1', 'posts')
     ->get('https://jsonplaceholder.typicode.com/albums?userId=1', 'albums')
     ->get('https://jsonplaceholder.typicode.com/todos?userId=1', 'todos');

$dashboardStart = microtime(true);
$results = $http->executeJson();
$dashboardTime = microtime(true) - $dashboardStart;

echo "Dashboard data aggregated from 4 APIs in " . round($dashboardTime, 4) . "s\n";
echo "User: " . $results['user']['json']['name'] . "\n";
echo "Posts: " . count($results['posts']['json']) . " posts\n";
echo "Albums: " . count($results['albums']['json']) . " albums\n";
echo "Todos: " . count($results['todos']['json']) . " tasks\n";
echo "\n";


echo "=== All Examples Completed Successfully! ===\n";
