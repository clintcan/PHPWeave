<?php
/**
 * Request Logging Hook Class
 *
 * Middleware-style logging hook that logs request details.
 * Useful for debugging, analytics, and audit trails.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 * @category   Logging
 *
 * @example
 * // Register the hook class
 * Hook::registerClass('log', LogHook::class);
 *
 * // Attach to specific routes
 * Route::get('/api/users', 'Api@users')->hook('log');
 *
 * @example
 * // Or use in route groups to log all API calls
 * Route::group(['prefix' => '/api', 'hooks' => ['log']], function() {
 *     Route::get('/users', 'Api@users');
 *     Route::post('/users', 'Api@createUser');
 *     Route::get('/posts', 'Api@posts');
 * });
 */
class LogHook
{
    /**
     * Handle the hook execution
     *
     * Logs request details including timestamp, user, route, and method.
     *
     * @param array $data Route data containing controller, method, instance, params
     * @return array Unmodified data (logging doesn't modify request)
     */
    public function handle($data)
    {
        // Gather request information
        $timestamp = date('Y-m-d H:i:s');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $uri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

        // Get user info if authenticated
        $userId = 'guest';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            if (is_array($user) && isset($user['id'])) {
                $userId = $user['id'];
            } elseif (is_object($user) && isset($user->id)) {
                $userId = $user->id;
            }
        }

        // Build log message
        $logMessage = sprintf(
            "[%s] %s %s | Controller: %s@%s | User: %s | IP: %s",
            $timestamp,
            $method,
            $uri,
            $data['controller'] ?? 'UNKNOWN',
            $data['method'] ?? 'UNKNOWN',
            $userId,
            $ip
        );

        // Add params if DEBUG mode is enabled
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'] && !empty($data['params'])) {
            $logMessage .= " | Params: " . json_encode($data['params']);
        }

        // Log to error log
        error_log($logMessage);

        // Also log to custom file if configured
        if (isset($GLOBALS['configs']['REQUEST_LOG_FILE'])) {
            $logFile = $GLOBALS['configs']['REQUEST_LOG_FILE'];
            @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        }

        // Return unmodified data
        return $data;
    }

    /**
     * Get the real client IP address
     *
     * Handles proxy headers and load balancers.
     *
     * @return string Client IP address
     */
    private function getClientIp()
    {
        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy header
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direct connection
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'UNKNOWN';
    }
}
