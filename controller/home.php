<?php
/**
 * Home Controller
 *
 * Handles requests for the home/landing page of the application.
 *
 * @package    PHPWeave
 * @subpackage Controllers
 * @category   Controllers
 * @author     Clint Christopher Canada
 * @version    2.0.0
 */
class Home extends Controller
{
	/**
	 * Index action
	 *
	 * Displays the home page of the application.
	 * Default action when accessing the root URL.
	 *
	 * @return void
	 */
	function index(){
		$this->show("home");
	}
}