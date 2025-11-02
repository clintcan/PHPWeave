<?php
/**
 * Authentication Hook Class
 *
 * Middleware-style authentication hook that checks if user is logged in.
 * Redirects to login page if not authenticated.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 * @category   Authentication
 *
 * @example
 * // Register the hook class
 * Hook::registerClass('auth', AuthHook::class);
 *
 * // Attach to specific routes
 * Route::get('/profile', 'User@profile')->hook('auth');
 * Route::get('/settings', 'User@settings')->hook('auth');
 *
 * // Or use in route groups
 * Route::group(['hooks' => ['auth']], function() {
 *     Route::get('/profile', 'User@profile');
 *     Route::get('/settings', 'User@settings');
 *     Route::get('/dashboard', 'User@dashboard');
 * });
 */
class AuthHook
{
    /**
     * Handle the hook execution
     *
     * Checks if user is authenticated. If not, redirects to login and halts execution.
     *
     * @param array $data Route data containing controller, method, instance, params
     * @return array Modified data (or halts execution if not authenticated)
     */
    public function handle($data)
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            // Log the failed auth attempt
            if (class_exists('ErrorClass')) {
                error_log("Authentication required for: {$data['controller']}@{$data['method']}");
            }

            // Redirect to login page
            header('Location: /login');

            // Halt further hook execution and controller dispatch
            Hook::halt();
            exit;
        }

        // User is authenticated - add user data to params for easy access
        if (is_array($data['params'])) {
            $data['params']['authenticated_user'] = $_SESSION['user'];
        }

        // Log successful authentication check
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            error_log("AuthHook: User authenticated for {$data['controller']}@{$data['method']}");
        }

        return $data;
    }
}
