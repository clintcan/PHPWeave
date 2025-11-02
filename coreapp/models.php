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
$files = glob("../models/*.php");
$GLOBALS['_model_files'] = [];

foreach ($files as $file) {
    require_once $file;
    $modelName = basename($file, ".php");
    $GLOBALS['_model_files'][$modelName] = $modelName;
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

    // Detect environment and locking requirements once
    if ($needsLocking === null) {
        $needsLocking = (
            file_exists('/.dockerenv') ||                    // Docker container
            (bool) getenv('KUBERNETES_SERVICE_HOST') ||      // Kubernetes pod
            (bool) getenv('DOCKER_ENV') ||                   // Docker environment variable
            extension_loaded('swoole') ||                    // Swoole server
            extension_loaded('pthreads') ||                  // pthreads extension
            defined('ROADRUNNER_VERSION') ||                 // RoadRunner server
            defined('FRANKENPHP_VERSION')                    // FrankenPHP server
        );
        
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
