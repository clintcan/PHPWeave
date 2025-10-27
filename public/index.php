<?php
$GLOBALS['baseurl'] = "/";

// Define framework root path constant (performance: avoid repeated dirname() calls)
define('PHPWEAVE_ROOT', str_replace("\\", "/", dirname(__FILE__, 2)));

// We will load the database connection variables here
$GLOBALS['configs'] = parse_ini_file('.env');

// Load hooks system first
require_once "../coreapp/hooks.php";

// Trigger framework start hook
Hook::trigger('framework_start');

// Load hook files from hooks directory
$hooksDir = dirname(__FILE__, 2) . '/hooks';
Hook::loadHookFiles($hooksDir);

// Before database connection
Hook::trigger('before_db_connection');

require_once "../coreapp/dbconnection.php";

// After database connection
Hook::trigger('after_db_connection');

// Before models load
Hook::trigger('before_models_load');

// We will load all the models here
require_once "../coreapp/models.php";

// After models load
Hook::trigger('after_models_load');

// Before router init
Hook::trigger('before_router_init');

// Load the router before controller
require_once "../coreapp/router.php";
// This should be the last to load for the controller class
require_once "../coreapp/controller.php";

// Smart caching configuration (Docker-aware)
if (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    // Detect Docker/container environment
    $isDocker = file_exists('/.dockerenv') || getenv('DOCKER_ENV') !== false || getenv('KUBERNETES_SERVICE_HOST') !== false;

    if ($isDocker) {
        // In Docker: prefer APCu (in-memory), fallback to file cache if writable
        if (!Router::enableAPCuCache()) {
            // APCu not available - try file cache only if directory is writable
            if (is_writable('../cache')) {
                Router::enableCache('../cache/routes.cache');
            }
            // else: no caching (read-only filesystem)
        }
    } else {
        // Traditional hosting: use file cache, APCu as bonus if available
        Router::enableAPCuCache(); // Try APCu first (might not be available)
        Router::enableCache('../cache/routes.cache'); // File cache as fallback
    }
}

// Load routes (from cache if available, otherwise from routes.php)
if (!Router::loadFromCache()) {
    require_once "../routes.php";
    Router::saveToCache(); // Fails gracefully if cache not writable
}

// After routes registered
Hook::trigger('after_routes_registered');

// Register shutdown hook
register_shutdown_function(function() {
    Hook::trigger('framework_shutdown');
});

// Dispatch the request using the new Router
Router::dispatch();