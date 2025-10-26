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
 * Lazy Model Loader Function
 *
 * Returns a model instance, creating it only on first access.
 * Subsequent calls return the cached instance.
 *
 * @param string $modelName Name of the model (e.g., 'user_model')
 * @return object Model instance
 * @throws Exception If model not found
 *
 * @example $user = model('user_model')->getUser($id);
 */
function model($modelName) {
    static $instances = [];

    // Return cached instance if exists
    if (isset($instances[$modelName])) {
        return $instances[$modelName];
    }

    // Check if model class exists
    if (!isset($GLOBALS['_model_files'][$modelName])) {
        throw new Exception("Model '{$modelName}' not found");
    }

    // Instantiate and cache
    $className = $GLOBALS['_model_files'][$modelName];
    $instances[$modelName] = new $className();

    return $instances[$modelName];
}

/**
 * Lazy Model Loader Class
 *
 * Provides array-like access to models with lazy loading.
 * Used for backward compatibility with $models['model_name'] syntax.
 */
class LazyModelLoader implements ArrayAccess {
    private static $instances = [];

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
     */
    public function __get($modelName) {
        return model($modelName);
    }

    /**
     * Magic isset for object property access
     */
    public function __isset($modelName) {
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
    public $models;

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
