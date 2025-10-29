<?php
/**
 * Controller Base Class
 *
 * Base controller class that all application controllers extend.
 * Provides core functionality for:
 * - Automatic method invocation
 * - View rendering with data passing
 * - Parameter handling
 * - Integration with the routing system
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Controllers
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * class Blog extends Controller {
 *     function index() {
 *         $this->show("blog/index", $data);
 *     }
 * }
 */
include_once "../coreapp/error.php";

class Controller
{
	/**
	 * Current action being executed
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Parameters passed to the action
	 *
	 * @var mixed
	 */
	public $actionParameters;

	/**
	 * Constructor
	 *
	 * Initializes the controller and optionally calls a method.
	 * Supports both legacy automatic routing and modern Router dispatch.
	 *
	 * @param string $function Method name to call (default: 'index')
	 * @param mixed  $arr      Parameters to pass to the method
	 * @return void
	 */
	function __construct($function="index", $arr="")
	{
		// Skip auto-call when using new Router system
		if ($function !== '__skip_auto_call__') {
			$this->callfunc($function,$arr);
		}
	}

	/**
	 * Call a controller method
	 *
	 * Invokes a method on the current controller with optional parameters.
	 * Handles both single parameters and parameter arrays.
	 *
	 * @param string $function Method name to invoke (default: 'index')
	 * @param mixed  $params   Parameters to pass (string, array, or empty)
	 * @return void
	 *
	 * @example $this->callfunc('show', ['123']);
	 * @example $this->callfunc('index');
	 */
	function callfunc($function="index", $params=""){
		if(!is_array($params)){
			call_user_func(array($this,$function));
		} else {
			call_user_func_array(array($this,$function), $params);
		}
	}

	/**
	 * Render a view template
	 *
	 * Loads and displays a view template from the views/ directory.
	 * Data passed to this method is available in the view:
	 * - As individual variables if data is an associative array (via extract())
	 * - You can now pass 'data', 'dir', or 'template' as keys without collision
	 *
	 * Security features:
	 * - Strips http://, https://, and // to prevent remote includes
	 * - Blocks path traversal attempts (..)
	 * - Removes null bytes
	 * - Automatically appends .php extension
	 * - Returns 404 if template not found
	 * - Uses EXTR_SKIP to prevent overwriting existing variables
	 * - System variables use __ prefix to avoid collision with user data
	 *
	 * @param string $template Template name (without .php extension)
	 * @param mixed  $__data   Data to pass to the view (default: empty string)
	 * @return void
	 *
	 * @example
	 * // Pass array - access as $title, $content, $author in view
	 * $this->show("blog/index", [
	 *     'title' => 'Hello',
	 *     'content' => 'World',
	 *     'author' => 'John',
	 *     'data' => 'Now this works!',  // No collision
	 *     'template' => 'metadata',      // No collision
	 *     'dir' => 'uploads'             // No collision
	 * ]);
	 *
	 * @example
	 * // Pass string or object
	 * $this->show("home", "Welcome!");
	 */
	function show($template, $__data=""){
		// Security: Sanitize template path to prevent path traversal and remote includes
		// Use __ prefix for system variables to avoid collision with user data
		$__dir = PHPWEAVE_ROOT;

		// Remove remote URL patterns
		$__template = strtr($template, [
			'https://' => '',
			'http://' => '',
			'//' => '/',
			'.php' => ''
		]);

		// Block path traversal attempts
		$__template = str_replace('..', '', $__template);

		// Remove null bytes (rare but possible attack)
		$__template = str_replace("\0", '', $__template);

		// Normalize path separators to forward slash
		$__template = str_replace('\\', '/', $__template);

		// Remove leading/trailing slashes
		$__template = trim($__template, '/');

		if(file_exists("$__dir/views/$__template.php")){
			// Trigger before view render hook
			$hookData = Hook::trigger('before_view_render', [
				'template' => $__template,
				'data' => $__data,
				'path' => "$__dir/views/$__template.php"
			]);

			// Allow hooks to modify data
			if (isset($hookData['data'])) {
				$__data = $hookData['data'];
			}

			// Store template path before extraction to prevent variable collision
			$__template_path = "$__dir/views/$__template.php";

			// Extract array data into individual variables for easier view access
			// System variables use __ prefix to avoid collision with user data
			if (is_array($__data)) {
				extract($__data, EXTR_SKIP);
			}

			include_once $__template_path;

			// Trigger after view render hook
			Hook::trigger('after_view_render', [
				'template' => $__template,
				'data' => $__data
			]);
		} else {
			header("HTTP/1.0 404 Not Found");
			echo "Oops. Not found";
			die();
		}
	}

	/**
	 * Safe HTML output helper
	 *
	 * Escapes HTML special characters to prevent XSS attacks.
	 * Use this method when outputting user-generated content.
	 *
	 * @param string $string The string to escape
	 * @return string The escaped string safe for HTML output
	 *
	 * @example
	 * // In views:
	 * <?php echo $this->safe($userInput); ?>
	 */
	function safe($string) {
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}
}

/**
 * Get controller class name from URL (Legacy Routing)
 *
 * Extracts the controller name from the request URI.
 * Part of the legacy automatic routing system.
 *
 * @return string Controller class name (default: 'Home')
 * @deprecated 2.0.0 Use explicit Route definitions instead
 *
 * @example
 * URL: /blog/show/123
 * Returns: 'Blog'
 */
function getControllerClass(){
	$filename = "index.php";
	$path = $_SERVER['REQUEST_URI'];
	$baseurl = $GLOBALS['baseurl'];
	$path = preg_replace("/^\/$filename/", "", $path);
	$patharr = explode("/", $path);
	if(!isset($patharr[1]) || empty($patharr[1])){
		return "Home";
	}
	// Get the first token which is the controller name
	$token = strtok($patharr[1], "/");
	if($token !== false && !empty($token)){
		return ucwords($token);
	}
	return "Home";
}

/**
 * Get controller method name from URL (Legacy Routing)
 *
 * Extracts the method/action name from the request URI.
 * Part of the legacy automatic routing system.
 *
 * @return string Method name (default: 'index')
 * @deprecated 2.0.0 Use explicit Route definitions instead
 *
 * @example
 * URL: /blog/show/123
 * Returns: 'show'
 */
function getControllerFunction(){
	$filename = "index.php";
	$path = $_SERVER['REQUEST_URI'];
	$baseurl = $GLOBALS['baseurl'];
	// $path = str_replace($baseurl,"",$path);
	$path = preg_replace("/^\/$filename/", "", $path);
	$patharr = explode("/", $path);
	if(!isset($patharr[2])){
		return "index";
	}
	return $patharr[2];
}

/**
 * Get controller parameters from URL (Legacy Routing)
 *
 * Extracts parameters from the request URI path.
 * Part of the legacy automatic routing system.
 *
 * @return array Array of parameters from URL segments
 * @deprecated 2.0.0 Use explicit Route definitions instead
 *
 * @example
 * URL: /blog/show/123/edit
 * Returns: ['123', 'edit']
 */
function getControllerParams(){
	$filename = "index.php";
	$path = $_SERVER['REQUEST_URI'];
	$baseurl = $GLOBALS['baseurl'];
	// $path = str_replace($baseurl,"",$path);
	$path = preg_replace("/^\/$filename\//", "", $path);
	$patharr = explode("/", $path);
	if(!isset($patharr[2])){
		return array();
	} else {
		$params = array();
		for($i = 2; $i < count($patharr); $i++){
			$params[] = $patharr[$i];
		}
	}
	return $params;
}

// Initialize error handling
$statuserror = new ErrorClass();

/**
 * Legacy Routing Handler
 *
 * Provides backward compatibility with the original automatic routing system.
 * Automatically maps URLs to controller classes and methods.
 * Only used when modern Router doesn't match a route.
 *
 * URL Pattern: /{controller}/{method}/{param1}/{param2}/...
 *
 * @return void
 * @deprecated 2.0.0 Use explicit Route definitions instead
 *
 * @example
 * URL: /blog/show/123
 * Maps to: Blog::show('123')
 */
function legacyRouting() {
	$classfile = strtolower(getControllerClass()).".php";
	$class = getControllerClass();
	$function = getControllerFunction();
	$params = getControllerParams();

	$dir = PHPWEAVE_ROOT;

	if (file_exists("$dir/controller/$classfile")) {
		include "$dir/controller/$classfile";
		if(empty($params))
		{
			$controller = new $class($function, "");
		} else {
			$controller = new $class($function, $params);
		}
	} else {
		header("HTTP/1.0 404 Not Found");
		echo "404 - Controller not found";
		die();
	}
}