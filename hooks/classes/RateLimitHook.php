<?php
/**
 * Rate Limiting Hook Class
 *
 * Middleware-style rate limiting hook that prevents abuse by limiting
 * the number of requests per time window.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 * @category   Security
 *
 * @example
 * // Register with default parameters (100 requests per 60 seconds)
 * Hook::registerClass('rate-limit', RateLimitHook::class);
 *
 * // Register with custom parameters (10 requests per 60 seconds)
 * Hook::registerClass('rate-limit-strict', RateLimitHook::class, 'before_action_execute', 10, [
 *     'max' => 10,
 *     'window' => 60
 * ]);
 *
 * @example
 * // Protect API endpoints
 * Route::group(['prefix' => '/api', 'hooks' => ['rate-limit']], function() {
 *     Route::post('/login', 'Auth@login');
 *     Route::post('/register', 'Auth@register');
 * });
 *
 * @example
 * // Strict rate limiting for sensitive operations
 * Route::post('/reset-password', 'Auth@resetPassword')->hook('rate-limit-strict');
 */
class RateLimitHook
{
    /**
     * Default maximum requests per window
     *
     * @var int
     */
    private $maxRequests = 100;

    /**
     * Default time window in seconds
     *
     * @var int
     */
    private $window = 60;

    /**
     * Storage method (session, file, apcu)
     *
     * @var string
     */
    private $storage = 'session';

    /**
     * Handle the hook execution
     *
     * Checks rate limit and denies access if exceeded.
     *
     * @param array $data Route data containing controller, method, instance, params
     * @param int|null $max Optional max requests override
     * @param int|null $window Optional time window override
     * @return array Unmodified data or halts if rate limit exceeded
     */
    public function handle($data, $max = null, $window = null)
    {
        // Override defaults with parameters
        if ($max !== null) {
            $this->maxRequests = (int)$max;
        }
        if ($window !== null) {
            $this->window = (int)$window;
        }

        // Get client identifier
        $clientId = $this->getClientId();

        // Check rate limit
        if (!$this->checkRateLimit($clientId)) {
            $this->sendTooManyRequests();
        }

        return $data;
    }

    /**
     * Check if client is within rate limit
     *
     * @param string $clientId Client identifier
     * @return bool True if within limit, false if exceeded
     */
    private function checkRateLimit($clientId)
    {
        $now = time();
        $key = "rate_limit_{$clientId}";

        // Get request log
        $requests = $this->getRequests($key);

        // Filter out old requests outside the window
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });

        // Check if limit exceeded
        if (count($requests) >= $this->maxRequests) {
            return false;
        }

        // Add current request
        $requests[] = $now;

        // Save updated log
        $this->saveRequests($key, $requests);

        return true;
    }

    /**
     * Get client identifier based on IP and user agent
     *
     * @return string Client identifier
     */
    private function getClientId()
    {
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

        // Hash for privacy and consistent length
        return md5($ip . $userAgent);
    }

    /**
     * Get client IP address
     *
     * @return string Client IP
     */
    private function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get request log from storage
     *
     * @param string $key Storage key
     * @return array Array of timestamps
     */
    private function getRequests($key)
    {
        // Try APCu first
        if (function_exists('apcu_fetch')) {
            $data = @apcu_fetch($key);
            if ($data !== false) {
                return $data;
            }
        }

        // Fall back to session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION[$key] ?? [];
    }

    /**
     * Save request log to storage
     *
     * @param string $key Storage key
     * @param array $requests Array of timestamps
     * @return void
     */
    private function saveRequests($key, $requests)
    {
        // Try APCu first
        if (function_exists('apcu_store')) {
            @apcu_store($key, $requests, $this->window + 10);
            return;
        }

        // Fall back to session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[$key] = $requests;
    }

    /**
     * Send 429 Too Many Requests response and halt
     *
     * @return void
     */
    private function sendTooManyRequests()
    {
        // Log rate limit violation
        error_log(sprintf(
            "Rate limit exceeded for %s - %d requests in %d seconds",
            $this->getClientIp(),
            $this->maxRequests,
            $this->window
        ));

        // Set headers
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: ' . $this->window);

        // Send response
        echo json_encode([
            'error' => 'Too Many Requests',
            'message' => "Rate limit exceeded. Maximum {$this->maxRequests} requests per {$this->window} seconds.",
            'retry_after' => $this->window
        ]);

        // Halt execution
        Hook::halt();
        exit;
    }
}
