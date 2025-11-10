<?php
/**
 * Cache Dashboard Controller
 *
 * Provides web interface and API endpoints for monitoring cache statistics.
 *
 * Features:
 * - Real-time cache statistics display
 * - Auto-refresh with configurable intervals
 * - Reset statistics functionality
 * - Optional authentication
 * - Enable/disable via configuration
 *
 * @package    PHPWeave
 * @subpackage Controllers
 * @category   Monitoring
 * @author     PHPWeave Development Team
 * @version    2.5.0
 *
 * Security:
 * - Dashboard can be enabled/disabled via CACHE_DASHBOARD_ENABLED in .env
 * - Optional authentication via CACHE_DASHBOARD_AUTH in .env
 * - IP whitelist support via CACHE_DASHBOARD_IPS in .env
 */
class CacheDashboard extends Controller
{
    /**
     * Check if dashboard is enabled
     *
     * @return bool
     */
    private function isDashboardEnabled()
    {
        $enabled = getenv('CACHE_DASHBOARD_ENABLED');

        // If not set, check DEBUG mode (enabled in DEBUG mode by default)
        if ($enabled === false) {
            return getenv('DEBUG') == '1';
        }

        return $enabled == '1' || $enabled === 'true';
    }

    /**
     * Check if authentication is required
     *
     * @return bool
     */
    private function isAuthRequired()
    {
        $auth = getenv('CACHE_DASHBOARD_AUTH');
        return $auth == '1' || $auth === 'true';
    }

    /**
     * Get allowed IPs for dashboard access
     *
     * @return array
     */
    private function getAllowedIPs()
    {
        $ips = getenv('CACHE_DASHBOARD_IPS');

        if (!$ips) {
            return [];
        }

        return array_map('trim', explode(',', $ips));
    }

    /**
     * Check IP whitelist
     *
     * @return bool
     */
    private function isIPAllowed()
    {
        $allowedIPs = $this->getAllowedIPs();

        // No whitelist = allow all
        if (empty($allowedIPs)) {
            return true;
        }

        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

        return in_array($clientIP, $allowedIPs);
    }

    /**
     * Verify authentication
     *
     * @return bool
     */
    private function verifyAuth()
    {
        if (!$this->isAuthRequired()) {
            return true;
        }

        // Check if credentials are provided
        $username = getenv('CACHE_DASHBOARD_USER');
        $password = getenv('CACHE_DASHBOARD_PASS');

        if (!$username || !$password) {
            // Auth required but not configured = deny access
            return false;
        }

        // Check HTTP Basic Auth
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            $this->requireAuth();
            return false;
        }

        if ($_SERVER['PHP_AUTH_USER'] !== $username || $_SERVER['PHP_AUTH_PW'] !== $password) {
            $this->requireAuth();
            return false;
        }

        return true;
    }

    /**
     * Send HTTP Basic Auth challenge
     */
    private function requireAuth()
    {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Cache Dashboard"');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    /**
     * Check all access requirements
     *
     * @return bool
     */
    private function checkAccess()
    {
        // Check if dashboard is enabled
        if (!$this->isDashboardEnabled()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Cache dashboard is disabled. Enable it by setting CACHE_DASHBOARD_ENABLED=1 in .env'
            ]);
            exit;
        }

        // Check IP whitelist
        if (!$this->isIPAllowed()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied: IP not whitelisted'
            ]);
            exit;
        }

        // Check authentication
        if (!$this->verifyAuth()) {
            return false;
        }

        return true;
    }

    /**
     * Display dashboard UI
     *
     * @return void
     */
    public function index()
    {
        if (!$this->checkAccess()) {
            return;
        }

        // Load cache class
        require_once __DIR__ . '/../coreapp/cache.php';
        require_once __DIR__ . '/../coreapp/cachedriver.php';

        // Render dashboard view
        $this->show('cache_dashboard');
    }

    /**
     * Get cache statistics (JSON API)
     *
     * @return void
     */
    public function stats()
    {
        if (!$this->checkAccess()) {
            return;
        }

        header('Content-Type: application/json');

        try {
            // Load cache class
            require_once __DIR__ . '/../coreapp/cache.php';
            require_once __DIR__ . '/../coreapp/cachedriver.php';

            // Get statistics
            $stats = Cache::stats();

            echo json_encode($stats);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to retrieve cache statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reset cache statistics (POST)
     *
     * @return void
     */
    public function reset()
    {
        if (!$this->checkAccess()) {
            return;
        }

        header('Content-Type: application/json');

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'error' => 'Method not allowed. Use POST to reset statistics.'
            ]);
            return;
        }

        try {
            // Load cache class
            require_once __DIR__ . '/../coreapp/cache.php';
            require_once __DIR__ . '/../coreapp/cachedriver.php';

            // Reset statistics
            Cache::resetStats();

            echo json_encode([
                'success' => true,
                'message' => 'Cache statistics have been reset successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to reset cache statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Flush all cache (POST)
     *
     * @return void
     */
    public function flush()
    {
        if (!$this->checkAccess()) {
            return;
        }

        header('Content-Type: application/json');

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'error' => 'Method not allowed. Use POST to flush cache.'
            ]);
            return;
        }

        try {
            // Load cache class
            require_once __DIR__ . '/../coreapp/cache.php';
            require_once __DIR__ . '/../coreapp/cachedriver.php';

            // Flush cache
            $success = Cache::flush();

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'All cache has been flushed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to flush cache'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to flush cache: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache driver information (JSON API)
     *
     * @return void
     */
    public function driver()
    {
        if (!$this->checkAccess()) {
            return;
        }

        header('Content-Type: application/json');

        try {
            // Load cache class
            require_once __DIR__ . '/../coreapp/cache.php';
            require_once __DIR__ . '/../coreapp/cachedriver.php';

            $stats = Cache::stats();

            echo json_encode([
                'driver' => $stats['driver'],
                'available_drivers' => $this->getAvailableDrivers()
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to retrieve driver information: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of available cache drivers
     *
     * @return array
     */
    private function getAvailableDrivers()
    {
        $drivers = [];

        // Memory driver - always available
        $drivers['memory'] = [
            'name' => 'Memory Cache',
            'available' => true,
            'description' => 'In-memory cache (request-scoped)'
        ];

        // APCu
        $drivers['apcu'] = [
            'name' => 'APCu Cache',
            'available' => function_exists('apcu_fetch') && ini_get('apc.enabled'),
            'description' => 'PHP APCu extension cache'
        ];

        // File
        $drivers['file'] = [
            'name' => 'File Cache',
            'available' => is_writable(__DIR__ . '/../cache'),
            'description' => 'File-based cache storage'
        ];

        // Redis
        $drivers['redis'] = [
            'name' => 'Redis Cache',
            'available' => class_exists('Redis'),
            'description' => 'Redis server cache'
        ];

        // Memcached
        $drivers['memcached'] = [
            'name' => 'Memcached Cache',
            'available' => class_exists('Memcached'),
            'description' => 'Memcached server cache'
        ];

        return $drivers;
    }
}
