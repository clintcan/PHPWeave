<?php
/**
 * Application Routes
 *
 * Define your application routes here using the Route facade.
 *
 * Syntax:
 *   Route::get('/pattern', 'Controller@method');
 *   Route::post('/pattern', 'Controller@method');
 *   Route::put('/pattern', 'Controller@method');
 *   Route::delete('/pattern', 'Controller@method');
 *   Route::patch('/pattern', 'Controller@method');
 *   Route::any('/pattern', 'Controller@method');
 *
 * Dynamic Parameters:
 *   Use :param_name: for dynamic segments
 *   Example: Route::get('/user/:id:', 'User@show');
 *   The parameter will be passed to the method: function show($id) { }
 *
 * Multiple Parameters:
 *   Route::get('/user/:user_id:/post/:post_id:', 'User@viewPost');
 *   Parameters are passed in order: function viewPost($user_id, $post_id) { }
 */

// Home routes
Route::get('/', 'Home@index');
Route::get('/home', 'Home@index');

// Blog routes
Route::get('/blog', 'Blog@index');
Route::get('/blog/:id:', 'Blog@showPost');
Route::post('/blog', 'Blog@store');

// Example: User routes (uncomment when User controller exists)
// Route::get('/user/:id:', 'User@show');
// Route::post('/user', 'User@create');
// Route::put('/user/:id:', 'User@update');
// Route::delete('/user/:id:', 'User@delete');

// Example: API routes
// Route::get('/api/users', 'Api@users');
// Route::post('/api/login', 'Api@login');

// Catch-all fallback (optional): Use legacy routing for undefined routes
// Comment the line below if you want strict route matching only
Route::any('/:controller:', 'LegacyRouter@dispatch');
Route::any('/:controller:/:action:', 'LegacyRouter@dispatch');
