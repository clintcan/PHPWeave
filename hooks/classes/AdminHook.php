<?php
/**
 * Admin Authorization Hook Class
 *
 * Middleware-style authorization hook that checks if user has admin privileges.
 * Should be used in combination with AuthHook.
 *
 * @package    PHPWeave
 * @subpackage Hooks
 * @category   Authorization
 *
 * @example
 * // Register the hook class
 * Hook::registerClass('admin', AdminHook::class);
 *
 * // Use with AuthHook in a group
 * Route::group(['hooks' => ['auth', 'admin']], function() {
 *     Route::get('/admin/users', 'Admin@users');
 *     Route::get('/admin/settings', 'Admin@settings');
 *     Route::post('/admin/users/:id:/delete', 'Admin@deleteUser');
 * });
 *
 * @example
 * // Or attach to specific routes
 * Route::get('/admin/dashboard', 'Admin@dashboard')->hook(['auth', 'admin']);
 */
class AdminHook
{
    /**
     * Handle the hook execution
     *
     * Checks if authenticated user has admin role. If not, returns 403 Forbidden.
     *
     * @param array $data Route data containing controller, method, instance, params
     * @return array Modified data (or halts execution if not authorized)
     */
    public function handle($data)
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user exists (should be set by AuthHook)
        if (!isset($_SESSION['user'])) {
            $this->sendForbidden("User not authenticated");
            return $data;
        }

        // Check if user has admin role
        // This assumes user data has a 'role' or 'is_admin' field
        $user = $_SESSION['user'];
        $isAdmin = false;

        // Check for various admin indicators
        if (isset($user['role']) && $user['role'] === 'admin') {
            $isAdmin = true;
        } elseif (isset($user['is_admin']) && $user['is_admin'] === true) {
            $isAdmin = true;
        } elseif (isset($user['roles']) && is_array($user['roles']) && in_array('admin', $user['roles'])) {
            $isAdmin = true;
        }

        // Not an admin - deny access
        if (!$isAdmin) {
            $this->sendForbidden("Admin access required");
            return $data;
        }

        // User is admin - log and continue
        if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG']) {
            error_log("AdminHook: Admin access granted for {$data['controller']}@{$data['method']}");
        }

        return $data;
    }

    /**
     * Send 403 Forbidden response and halt execution
     *
     * @param string $message Reason for denial
     * @return void
     */
    private function sendForbidden($message)
    {
        // Log the authorization failure
        if (class_exists('ErrorClass')) {
            error_log("AdminHook: Access denied - {$message}");
        }

        // Send 403 response
        header('HTTP/1.0 403 Forbidden');
        echo "403 - Forbidden: Admin access required";

        // Halt execution
        Hook::halt();
        exit;
    }
}
