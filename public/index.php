<?php
$GLOBALS['baseurl'] = "/";

// Define framework root path constant (performance: avoid repeated dirname() calls)
define('PHPWEAVE_ROOT', str_replace("\\", "/", dirname(__FILE__, 2)));

// We will load the database connection variables here
// Check if .env file exists
if (file_exists('../.env')) {
    $GLOBALS['configs'] = parse_ini_file('../.env');

    // Set defaults for new fields if not present in .env (backward compatibility)
    $GLOBALS['configs']['DBCHARSET'] = $GLOBALS['configs']['DBCHARSET'] ?? 'utf8mb4';
    $GLOBALS['configs']['DBDRIVER'] = $GLOBALS['configs']['DBDRIVER'] ?? 'pdo_mysql';
    $GLOBALS['configs']['DBPORT'] = $GLOBALS['configs']['DBPORT'] ?? 3306;
    $GLOBALS['configs']['DBDSN'] = $GLOBALS['configs']['DBDSN'] ?? null;
} else {
    // We will load the database connection variables from environment
    // Support both naming conventions: DB_HOST and DBHOST for compatibility
    $GLOBALS['configs']['DBHOST'] = getenv('DB_HOST') ?: getenv('DBHOST') ?: 'localhost';
    $GLOBALS['configs']['DBNAME'] = getenv('DB_NAME') ?: getenv('DBNAME') ?: '';
    $GLOBALS['configs']['DBUSER'] = getenv('DB_USER') ?: getenv('DBUSER') ?: '';
    $GLOBALS['configs']['DBPASSWORD'] = getenv('DB_PASSWORD') ?: getenv('DBPASSWORD') ?: '';
    $GLOBALS['configs']['DBCHARSET'] = getenv('DB_CHARSET') ?: getenv('DBCHARSET') ?: 'utf8mb4';
    $GLOBALS['configs']['DBDRIVER'] = getenv('DB_DRIVER') ?: getenv('DBDRIVER') ?: 'pdo_mysql';
    $GLOBALS['configs']['DBPORT'] = getenv('DB_PORT') ?: getenv('DBPORT') ?: 3306;
    $GLOBALS['configs']['DBDSN'] = getenv('DB_DSN') ?: getenv('DBDSN') ?: null; // For custom DSN/ODBC
    $GLOBALS['configs']['DEBUG'] = getenv('DEBUG') ?: 0;
}

// Load hooks system first
require_once "../coreapp/hooks.php";

// Trigger framework start hook
Hook::trigger('framework_start');

// Load hook files from hooks directory
$hooksDir = dirname(__FILE__, 2) . '/hooks';
Hook::loadHookFiles($hooksDir);

// Check if database is enabled (ENABLE_DATABASE=1 or DBNAME is set)
$databaseEnabled = true;
if (isset($GLOBALS['configs']['ENABLE_DATABASE']) && $GLOBALS['configs']['ENABLE_DATABASE'] == 0) {
	$databaseEnabled = false;
} elseif (empty($GLOBALS['configs']['DBNAME'])) {
	// Auto-detect: if DBNAME is not configured, assume database-free mode
	$databaseEnabled = false;
}

// Only load database and models if database is enabled
if ($databaseEnabled) {
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
} else {
	// Database-free mode: Skip database and models entirely
	// Set empty models array for backward compatibility
	$GLOBALS['models'] = [];
	if (!isset($GLOBALS['PW'])) {
		$GLOBALS['PW'] = new stdClass();
	}
	$GLOBALS['PW']->models = new class {
		public function __get($name) {
			throw new Exception("Database is disabled. Cannot access model: $name");
		}
	};
}

// Before router init
Hook::trigger('before_router_init');

// Load the router before controller
require_once "../coreapp/router.php";
// This should be the last to load for the controller class
require_once "../coreapp/controller.php";

// Smart caching configuration (Docker-aware)
if (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    // Detect Docker/container environment
    $isDocker = file_exists('/.dockerenv') || (bool) getenv('DOCKER_ENV') || (bool) getenv('KUBERNETES_SERVICE_HOST');

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