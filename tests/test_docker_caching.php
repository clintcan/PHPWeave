<?php
/**
 * Docker Caching Test Script
 *
 * Tests APCu and Docker-aware caching functionality
 * Usage: php tests/test_docker_caching.php
 */

echo "PHPWeave Docker Caching Test\n";
echo str_repeat("=", 70) . "\n\n";

require_once __DIR__ . '/../coreapp/router.php';

// Test 1: APCu Availability
echo "Test 1: APCu Extension Check\n";
echo str_repeat("-", 70) . "\n";

$apcuAvailable = function_exists('apcu_fetch') && function_exists('apcu_store');
$apcuEnabled = $apcuAvailable && ini_get('apc.enabled');

echo "  APCu functions available: " . ($apcuAvailable ? "YES" : "NO") . "\n";
echo "  APCu enabled (php.ini): " . ($apcuEnabled ? "YES" : "NO") . "\n";

if ($apcuEnabled) {
    echo "  ✓ APCu is fully functional\n";
} else {
    echo "  ⚠ APCu not available - will use file cache fallback\n";
}
echo "\n";

// Test 2: Enable APCu Cache
echo "Test 2: Enable APCu Caching\n";
echo str_repeat("-", 70) . "\n";

$result = Router::enableAPCuCache(60); // 1 minute TTL for testing

if ($result) {
    echo "  ✓ APCu caching enabled successfully\n";
} else {
    echo "  ⚠ APCu caching not available (expected if APCu not installed)\n";
}
echo "\n";

// Test 3: Register Test Routes
echo "Test 3: Register Test Routes\n";
echo str_repeat("-", 70) . "\n";

Route::get('/test/:id:', 'Test@show');
Route::post('/test', 'Test@store');
Route::get('/another/:name:', 'Another@method');

$routeCount = count(Router::getRoutes());
echo "  Registered routes: $routeCount\n";
echo "  ✓ Routes registered successfully\n\n";

// Test 4: Save Routes to Cache
echo "Test 4: Save Routes to Cache\n";
echo str_repeat("-", 70) . "\n";

$saveResult = Router::saveToCache();

if ($saveResult) {
    echo "  ✓ Routes saved to cache successfully\n";
    if ($apcuEnabled) {
        echo "    Cache type: APCu (in-memory)\n";
    } else {
        echo "    Cache type: File-based (fallback)\n";
    }
} else {
    echo "  ⚠ Cache save failed (may be read-only filesystem)\n";
}
echo "\n";

// Test 5: Load Routes from Cache
echo "Test 5: Load Routes from Cache\n";
echo str_repeat("-", 70) . "\n";

// Clear routes to simulate fresh load
$reflection = new ReflectionClass('Router');
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$property->setValue([]);

$loadResult = Router::loadFromCache();

if ($loadResult) {
    $cachedCount = count(Router::getRoutes());
    echo "  ✓ Routes loaded from cache successfully\n";
    echo "    Loaded routes: $cachedCount\n";
    echo "    Match: " . ($cachedCount === $routeCount ? "YES ✓" : "NO ✗") . "\n";
} else {
    echo "  ✗ Failed to load routes from cache\n";
}
echo "\n";

// Test 6: Clear Cache
echo "Test 6: Clear Cache\n";
echo str_repeat("-", 70) . "\n";

$clearResult = Router::clearCache();

if ($clearResult) {
    echo "  ✓ Cache cleared successfully\n";
} else {
    echo "  ⚠ Cache clear failed (might not exist)\n";
}
echo "\n";

// Test 7: Docker Environment Detection
echo "Test 7: Docker Environment Detection\n";
echo str_repeat("-", 70) . "\n";

$isDockerEnv = file_exists('/.dockerenv');
$dockerEnvVar = getenv('DOCKER_ENV');
$k8sEnvVar = getenv('KUBERNETES_SERVICE_HOST');

echo "  /.dockerenv file exists: " . ($isDockerEnv ? "YES" : "NO") . "\n";
echo "  DOCKER_ENV variable: " . ($dockerEnvVar ?: "not set") . "\n";
echo "  KUBERNETES_SERVICE_HOST: " . ($k8sEnvVar ?: "not set") . "\n";

$detectedAsDocker = $isDockerEnv || $dockerEnvVar || $k8sEnvVar;
echo "  Detected as Docker: " . ($detectedAsDocker ? "YES" : "NO") . "\n";
echo "\n";

// Test 8: File Cache Writable Check
echo "Test 8: File Cache Directory Check\n";
echo str_repeat("-", 70) . "\n";

$cacheDir = __DIR__ . '/cache';
$dirExists = is_dir($cacheDir);
$isWritable = $dirExists && is_writable($cacheDir);

echo "  Cache directory exists: " . ($dirExists ? "YES" : "NO") . "\n";
echo "  Cache directory writable: " . ($isWritable ? "YES" : "NO") . "\n";

if (!$dirExists) {
    echo "  ⚠ Cache directory missing - create with: mkdir cache\n";
} elseif (!$isWritable) {
    echo "  ⚠ Cache directory not writable - fix with: chmod 755 cache\n";
} else {
    echo "  ✓ File cache is available as fallback\n";
}
echo "\n";

// Summary
echo str_repeat("=", 70) . "\n";
echo "SUMMARY - Docker Caching Configuration\n";
echo str_repeat("=", 70) . "\n";

if ($apcuEnabled) {
    echo "  ✅ OPTIMAL: APCu enabled - using in-memory caching\n";
    echo "     → Best for Docker/container environments\n";
    echo "     → No filesystem dependencies\n";
    echo "     → Fast and scalable\n";
} elseif ($isWritable) {
    echo "  ✅ GOOD: File cache available\n";
    echo "     → Works but requires writable filesystem\n";
    echo "     → May have issues in multi-container setups\n";
    echo "     → Consider installing APCu for better performance\n";
} else {
    echo "  ⚠️  LIMITED: No caching available\n";
    echo "     → Routes compiled on every request (slower)\n";
    echo "     → Install APCu or make /cache writable\n";
}

if ($detectedAsDocker) {
    echo "\n  🐳 Running in Docker environment\n";
    echo "     → Automatic Docker detection working\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

if (!$apcuEnabled) {
    echo "\n";
    echo "💡 To enable APCu for optimal performance:\n";
    echo "   1. Install: pecl install apcu\n";
    echo "   2. Enable: echo 'extension=apcu.so' > /etc/php/conf.d/apcu.ini\n";
    echo "   3. Configure: echo 'apc.enabled=1' >> /etc/php/conf.d/apcu.ini\n";
    echo "   4. Restart PHP-FPM or Apache\n";
    echo "\n   Or use the provided Dockerfile which includes APCu!\n";
}

echo "\n✓ Docker caching test complete!\n";
