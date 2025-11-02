<?php
/**
 * Legacy Router Controller
 *
 * Provides backward compatibility with CodeIgniter-inspired automatic routing.
 * This controller acts as a bridge between the modern routing system and the
 * legacy URL pattern: /{controller}/{method}/{param1}/{param2}/...
 *
 * @package    PHPWeave
 * @subpackage Controllers
 * @category   Routing
 * @author     Clint Christopher Canada
 * @version    2.3.1
 *
 * @example
 * URL: /blog/show/123
 * Maps to: Blog::show('123')
 *
 * @example
 * URL: /user/profile/john/edit
 * Maps to: User::profile('john', 'edit')
 */
class LegacyRouter extends Controller {
    /**
     * Dispatch request to legacy routing system
     *
     * This method is called by the modern router when catch-all routes match.
     * It delegates to the legacyRouting() function which automatically maps
     * URLs to controller classes and methods.
     *
     * @return void
     */
    public function dispatch() {
        // Call the legacy routing function from controller.php
        legacyRouting();
    }
}
