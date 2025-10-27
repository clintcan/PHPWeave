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

	/**
	 * Slugify action
	 *
	 * Demonstrates library usage by converting text to URL-friendly slugs.
	 * Shows three different ways to access libraries.
	 * Matches route: Route::get('/blog/slugify/:text:', 'Blog@slugify')
	 *
	 * @param string $text Text to convert to slug (from URL parameter)
	 * @return void
	 */
	function slugify($text = "Sample Blog Post Title") {
		global $PW;

		// Method 1: PHPWeave global object (recommended)
		$slug1 = $PW->libraries->string_helper->slugify($text);

		// Method 2: library() function
		$slug2 = library('string_helper')->slugify($text);

		// Method 3: Legacy array syntax
		global $libraries;
		$slug3 = $libraries['string_helper']->slugify($text);

		// Also demonstrate other string helper methods
		$truncated = $PW->libraries->string_helper->truncate($text, 20);
		$titleCased = $PW->libraries->string_helper->titleCase($text);
		$wordCount = $PW->libraries->string_helper->wordCount($text);
		$readingTime = $PW->libraries->string_helper->readingTime(str_repeat($text . ' ', 50));
		$randomToken = $PW->libraries->string_helper->random(8);

		// Pass data as array - accessible as individual variables in view
		$this->show("blog", [
			'original_text' => $text,
			'slug1' => $slug1,
			'slug2' => $slug2,
			'slug3' => $slug3,
			'truncated' => $truncated,
			'title_cased' => $titleCased,
			'word_count' => $wordCount,
			'reading_time' => $readingTime,
			'random_token' => $randomToken,
			'title' => 'String Helper Library Demo',
			'message' => 'Demonstrating lazy-loaded library functionality'
		]);
	}
}