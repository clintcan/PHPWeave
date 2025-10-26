<?php
/**
 * Blog Controller
 *
 * Handles blog-related operations including listing, viewing,
 * creating, and managing blog posts.
 *
 * @package    PHPWeave
 * @subpackage Controllers
 * @category   Controllers
 * @author     Clint Christopher Canada
 * @version    2.0.0
 */
class Blog extends Controller
{
	/**
	 * Index action
	 *
	 * Displays a list of all blog posts.
	 * Typically shows recent posts with pagination.
	 *
	 * @return void
	 */
	function index(){
        $this->show("blog", "Blog Index - All Posts");
	}

	/**
	 * Show action
	 *
	 * Displays a single blog post by ID.
	 * Matches route: Route::get('/blog/:id:', 'Blog@showPost')
	 *
	 * @param string $id Blog post ID from URL parameter
	 * @return void
	 */
    function showPost($id = ""){
        parent::show("blog", "Showing blog post ID: $id");
    }

	/**
	 * Store action
	 *
	 * Handles POST request to create a new blog post.
	 * Processes form data and saves to database.
	 * Matches route: Route::post('/blog', 'Blog@store')
	 *
	 * @return void
	 */
    function store(){
        // Handle POST request to create new blog post
        $this->show("blog", "Creating new blog post");
    }

	/**
	 * Sample demonstration action
	 *
	 * Example method showing multiple parameter handling.
	 * Used for testing and demonstration purposes.
	 *
	 * @param string $param1 First parameter
	 * @param string $param2 Second parameter
	 * @return void
	 */
    function sd($param1="", $param2=""){
        $this->show("blog", "Param1: $param1 Param2: $param2");
    }

	/**
	 * Test action
	 *
	 * Tests model connectivity and database functionality.
	 * Example of accessing models in controllers.
	 *
	 * @return void
	 */
    function test() {
		/* Old way (global $models)
        global $models;
        $test = $models['user_model']->test();
		*/
		/* New way (function)
		$test = model('user_model')->test();
		*/
		/* New way (PHPWeave global object) */
		global $PW;
		$test = $PW->models->user_model->test();

		// Pass data as array - accessible as $test, $title, $message in view
		$this->show("blog", [
			'test' => $test,
			'title' => 'Model Test Results',
			'message' => 'Testing PHPWeave model connectivity'
		]);
    }
}