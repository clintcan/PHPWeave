<?php
/**
 * CORS (Cross-Origin Resource Sharing) Hook Class
 *
 * Middleware-style hook that handles CORS headers for API endpoints.
 * Enables cross-origin requests from specified domains.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 * @category   Security
 *
 * @example
 * // Register with default parameters (allow all origins)
 * Hook::registerClass('cors', CorsHook::class);
 *
 * // Register with specific allowed origins
 * Hook::registerClass('cors-api', CorsHook::class, 'before_action_execute', 5, [
 *     'origins' => ['https://example.com', 'https://app.example.com'],
 *     'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
 *     'headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
 *     'credentials' => true,
 *     'max_age' => 3600
 * ]);
 *
 * @example
 * // Apply to all API routes
 * Route::group(['prefix' => '/api', 'hooks' => ['cors']], function() {
 *     Route::get('/users', 'Api@users');
 *     Route::post('/users', 'Api@createUser');
 * });
 */
class CorsHook
{
    /**
     * Allowed origins (default: all)
     *
     * @var array|string
     */
    private $allowedOrigins = '*';

    /**
     * Allowed HTTP methods
     *
     * @var array
     */
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];

    /**
     * Allowed headers
     *
     * @var array
     */
    private $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'];

    /**
     * Allow credentials (cookies, HTTP auth)
     *
     * @var bool
     */
    private $allowCredentials = false;

    /**
     * Max age for preflight cache (in seconds)
     *
     * @var int
     */
    private $maxAge = 3600;

    /**
     * Handle the hook execution
     *
     * Sets CORS headers and handles OPTIONS preflight requests.
     *
     * @param array $data Route data
     * @param array|null $origins Optional allowed origins override
     * @param array|null $methods Optional allowed methods override
     * @param array|null $headers Optional allowed headers override
     * @param bool|null $credentials Optional credentials override
     * @param int|null $maxAge Optional max age override
     * @return array Modified data
     */
    public function handle($data, $origins = null, $methods = null, $headers = null, $credentials = null, $maxAge = null)
    {
        // Override defaults with parameters
        if ($origins !== null) {
            $this->allowedOrigins = $origins;
        }
        if ($methods !== null) {
            $this->allowedMethods = $methods;
        }
        if ($headers !== null) {
            $this->allowedHeaders = $headers;
        }
        if ($credentials !== null) {
            $this->allowCredentials = $credentials;
        }
        if ($maxAge !== null) {
            $this->maxAge = $maxAge;
        }

        // Set CORS headers
        $this->setCorsHeaders();

        // Handle OPTIONS preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->handlePreflight();
        }

        return $data;
    }

    /**
     * Set CORS headers
     *
     * @return void
     */
    private function setCorsHeaders()
    {
        // Get request origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Set Access-Control-Allow-Origin
        if ($this->allowedOrigins === '*') {
            header('Access-Control-Allow-Origin: *');
        } elseif (is_array($this->allowedOrigins)) {
            if (in_array($origin, $this->allowedOrigins)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Vary: Origin');
            }
        } elseif ($origin === $this->allowedOrigins) {
            header("Access-Control-Allow-Origin: {$origin}");
        }

        // Set Access-Control-Allow-Methods
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));

        // Set Access-Control-Allow-Headers
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));

        // Set Access-Control-Allow-Credentials
        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        // Set Access-Control-Max-Age
        header("Access-Control-Max-Age: {$this->maxAge}");

        // Log if debug enabled
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            error_log("CorsHook: CORS headers set for origin: {$origin}");
        }
    }

    /**
     * Handle OPTIONS preflight request
     *
     * @return void
     */
    private function handlePreflight()
    {
        // Log preflight request
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            error_log("CorsHook: Handling OPTIONS preflight request");
        }

        // Send 200 OK status
        http_response_code(200);

        // Halt further processing
        Hook::halt();
        exit;
    }
}
