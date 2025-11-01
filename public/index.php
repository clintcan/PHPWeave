<?php
// Start output buffering to prevent "headers already sent" errors
// This captures all output and allows headers to be sent even after content generation
// Note: Disable buffering for streaming routes by setting $_SERVER['DISABLE_OUTPUT_BUFFER'] = true
if (!isset($_SERVER['DISABLE_OUTPUT_BUFFER']) || !$_SERVER['DISABLE_OUTPUT_BUFFER']) {
    ob_start();
}

$GLOBALS['baseurl'] = "/";

// Define framework root path constant (performance: avoid repeated dirname() calls)
define('PHPWEAVE_ROOT', str_replace("\\", "/", dirname(__FILE__, 2)));

// We will load the database connection variables here
// Check if .env file exists
$envPath = PHPWEAVE_ROOT . '/.env';
if (file_exists($envPath)) {
    $GLOBALS['configs'] = @parse_ini_file($envPath);

    // Handle parse errors gracefully
    if ($GLOBALS['configs'] === false) {
        error_log("PHPWeave: Failed to parse .env file at $envPath - using environment variables");
        $GLOBALS['configs'] = []; // Will fall through to environment variable loading below
    } else {
        // Set defaults for new fields if not present in .env (backward compatibility)
        $GLOBALS['configs']['DBCHARSET'] = $GLOBALS['configs']['DBCHARSET'] ?? 'utf8mb4';
        $GLOBALS['configs']['DBDRIVER'] = $GLOBALS['configs']['DBDRIVER'] ?? 'pdo_mysql';
        $GLOBALS['configs']['DBPORT'] = $GLOBALS['configs']['DBPORT'] ?? 3306;
        $GLOBALS['configs']['DBDSN'] = $GLOBALS['configs']['DBDSN'] ?? null;
    }
}

// Load from environment if .env not found or failed to parse
if (!isset($GLOBALS['configs']) || empty($GLOBALS['configs'])) {
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
require_once PHPWEAVE_ROOT . "/coreapp/hooks.php";

// Trigger framework start hook
Hook::trigger('framework_start');

// Load hook files from hooks directory
$hooksDir = PHPWEAVE_ROOT . '/hooks';
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

	require_once PHPWEAVE_ROOT . "/coreapp/dbconnection.php";

	// After database connection
	Hook::trigger('after_db_connection');

	// Before models load
	Hook::trigger('before_models_load');

	// We will load all the models here
	require_once PHPWEAVE_ROOT . "/coreapp/models.php";

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

// Load libraries system (always loaded, even if database is disabled)
require_once PHPWEAVE_ROOT . "/coreapp/libraries.php";

// Before router init
Hook::trigger('before_router_init');

// Load the router before controller
require_once PHPWEAVE_ROOT . "/coreapp/router.php";
// This should be the last to load for the controller class
require_once PHPWEAVE_ROOT . "/coreapp/controller.php";

// Load composer autoload if available (optional - only if using composer packages)
$vendorAutoload = PHPWEAVE_ROOT . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Smart caching configuration (Docker-aware)
if (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    // Detect Docker/container environment
    $isDocker = file_exists('/.dockerenv') || (bool) getenv('DOCKER_ENV') || (bool) getenv('KUBERNETES_SERVICE_HOST');

    if ($isDocker) {
        // In Docker: prefer APCu (in-memory), fallback to file cache if writable
        if (!Router::enableAPCuCache()) {
            // APCu not available - try file cache only if directory is writable
            $cacheDir = PHPWEAVE_ROOT . '/cache';
            if (is_writable($cacheDir)) {
                Router::enableCache($cacheDir . '/routes.cache');
            }
            // else: no caching (read-only filesystem)
        }
    } else {
        // Traditional hosting: use file cache, APCu as bonus if available
        Router::enableAPCuCache(); // Try APCu first (might not be available)
        Router::enableCache(PHPWEAVE_ROOT . '/cache/routes.cache'); // File cache as fallback
    }
}

// Load routes (from cache if available, otherwise from routes.php)
if (!Router::loadFromCache()) {
    require_once PHPWEAVE_ROOT . "/routes.php";
    Router::saveToCache(); // Fails gracefully if cache not writable
}

// After routes registered
Hook::trigger('after_routes_registered');

// Register shutdown hook
register_shutdown_function(function() {
    Hook::trigger('framework_shutdown');

    // Flush output buffer at the end of execution
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
});

// Dispatch the request using the new Router
Router::dispatch();