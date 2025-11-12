<?php
/**
 * Model Auto-loader with Lazy Loading
 *
 * Automatically discovers model files but only instantiates them when first accessed.
 * This significantly improves performance by avoiding unnecessary model instantiation.
 *
 * Features:
 * - Automatic discovery of all .php files in models/ directory
 * - Lazy instantiation (only when needed)
 * - Caching of instantiated models
 * - Global accessibility via $models array or model() function
 * - Backward compatible with existing code
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Models
 * @author     Clint Christopher Canada
 * @version    2.0.1
 *
 * @example
 * // Recommended way (PHPWeave global object):
 * global $PW;
 * $user = $PW->models->user_model->getUser($id);
 *
 * @example
 * // Alternative (function):
 * $user = model('user_model')->getUser($id);
 *
 * @example
 * // Legacy way (still works, now lazy loads):
 * global $models;
 * $user = $models['user_model']->getUser($id);
 */

// Discover all model files but don't instantiate yet
// v2.6.0: Cache file list in APCu to avoid repeated glob() calls
$GLOBALS['_model_files'] = [];
$modelsDir = "../models";
$files = false;
$cacheKey = 'phpweave_model_files_' . (is_dir($modelsDir) ? filemtime($modelsDir) : 0);

// Try to load from cache (if APCu available and not in debug mode)
if (function_exists('apcu_enabled') && apcu_enabled() &&
    (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG'])) {
    $cachedData = @apcu_fetch($cacheKey);

    if ($cachedData !== false && is_array($cachedData)) {
        $files = $cachedData['files'];
        $GLOBALS['_model_files'] = $cachedData['model_names'];
    }
}

// Cache miss or APCu not available - scan directory
if ($files === false) {
    $files = glob($modelsDir . "/*.php");

    foreach ($files as $file) {
        require_once $file;
        $modelName = basename($file, ".php");
        $GLOBALS['_model_files'][$modelName] = $modelName;
    }

    // Store in cache for future requests
    if (function_exists('apcu_enabled') && apcu_enabled() &&
        (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG'])) {
        @apcu_store($cacheKey, [
            'files' => $files,
            'model_names' => $GLOBALS['_model_files']
        ], 3600);
    }
} else {
    // Load files from cache (still need to require_once them)
    foreach ($files as $file) {
        require_once $file;
    }
}

/**
 * Lazy Model Loader Function (Environment-Aware Thread Safety)
 *
 * Returns a model instance, creating it only on first access.
 * Subsequent calls return the cached instance.
 * Uses file-based locking only in Docker/cloud/threaded environments.
 *
 * @param string $modelName Name of the model (e.g., 'user_model')
 * @return object Model instance
 * @throws Exception If model not found
 *
 * @example $user = model('user_model')->getUser($id);
 */
function model($modelName) {
    static $instances = [];
    static $needsLocking = null;
    static $lockFile = null;

    // Return cached instance if exists (fast path, no locking needed)
    if (isset($instances[$modelName])) {
        return $instances[$modelName];
    }

    // v2.6.0: Use pre-detected environment from index.php (optimization)
    if ($needsLocking === null) {
        $needsLocking = $GLOBALS['_phpweave_needs_locking'] ?? false;

        if ($needsLocking && $lockFile === null) {
            $lockFile = sys_get_temp_dir() . '/phpweave_models.lock';
        }
    }

    // Check if model class exists
    if (!isset($GLOBALS['_model_files'][$modelName])) {
        throw new Exception("Model '{$modelName}' not found");
    }

    $className = $GLOBALS['_model_files'][$modelName];

    // Use locking only in containerized/threaded environments
    if ($needsLocking) {
        // Thread-safe instantiation using file locking
        $fp = fopen($lockFile, 'c+');
        if (!$fp) {
            throw new Exception("Unable to create lock file for thread safety");
        }

        if (flock($fp, LOCK_EX)) {
            try {
                // Double-check pattern: verify instance wasn't created while waiting for lock
                if (!isset($instances[$modelName])) {
                    $instances[$modelName] = new $className();
                }
            } finally {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        } else {
            fclose($fp);
            throw new Exception("Unable to acquire lock for thread safety");
        }
    } else {
        // Fast path for traditional PHP deployments (no locking overhead)
        $instances[$modelName] = new $className();
    }

    return $instances[$modelName];
}

/**
 * Lazy Model Loader Class (Thread-Safe)
 *
 * Provides array-like access to models with lazy loading.
 * Used for backward compatibility with $models['model_name'] syntax.
 * Thread-safe implementation using the model() function.
 */
class LazyModelLoader implements ArrayAccess {
    // Removed static $instances - now uses thread-safe model() function

    /**
     * Check if model exists (ArrayAccess)
     */
    public function offsetExists($offset): bool {
        return isset($GLOBALS['_model_files'][$offset]);
    }

    /**
     * Get model instance (ArrayAccess) - lazy loads
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return model($offset);
    }

    /**
     * Set is not allowed (ArrayAccess)
     */
    public function offsetSet($offset, $value): void {
        trigger_error("Cannot set models directly. Models are auto-loaded.", E_USER_WARNING);
    }

    /**
     * Unset is not allowed (ArrayAccess)
     */
    public function offsetUnset($offset): void {
        trigger_error("Cannot unset models. Models are auto-loaded.", E_USER_WARNING);
    }

    /**
     * Magic get for object property access
     *
     * @param string $modelName
     * @return object
     */
    public function __get(string $modelName): object {
        return model($modelName);
    }

    /**
     * Magic isset for object property access
     *
     * @param string $modelName
     * @return bool
     */
    public function __isset(string $modelName): bool {
        return isset($GLOBALS['_model_files'][$modelName]);
    }
}

/**
 * PHPWeave Framework Container
 *
 * Global framework object providing access to all framework components.
 * Supports new syntax: $PW->models->user_model->test()
 */
class PHPWeave {
    /**
     * @var LazyModelLoader
     */
    public LazyModelLoader $models;

    public function __construct() {
        $this->models = new LazyModelLoader();
    }
}

// Create lazy loader for backward compatibility
$models = new LazyModelLoader();
$GLOBALS['models'] = $models;

// Create new PHPWeave global object
$PW = new PHPWeave();
$GLOBALS['PW'] = $PW;
