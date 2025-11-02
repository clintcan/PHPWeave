<?php
/**
 * Library Auto-loader with Lazy Loading
 *
 * Automatically discovers library files but only instantiates them when first accessed.
 * This significantly improves performance by avoiding unnecessary library instantiation.
 *
 * Features:
 * - Automatic discovery of all .php files in libraries/ directory
 * - Lazy instantiation (only when needed)
 * - Caching of instantiated libraries
 * - Global accessibility via $libraries array or library() function
 * - Backward compatible with existing code
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Libraries
 * @author     Clint Christopher Canada
 * @version    2.1.1
 *
 * @example
 * // Recommended way (PHPWeave global object):
 * global $PW;
 * $user = $PW->libraries->helper_library->getSum($num1, $num2);
 *
 * @example
 * // Alternative (function):
 * $user = library('helper_library')->getSum($num1, $num2);
 *
 * @example
 * // Legacy way (still works, now lazy loads):
 * global $libraries;
 * $user = $libraries['helper_library']->getSum($num1, $num2);
 */

// Discover all library files but don't instantiate yet
$files = glob("../libraries/*.php");
$GLOBALS['_library_files'] = [];

foreach ($files as $file) {
    require_once $file;
    $libraryName = basename($file, ".php");
    $GLOBALS['_library_files'][$libraryName] = $libraryName;
}

/**
 * Lazy Library Loader Function (Environment-Aware Thread Safety)
 *
 * Returns a library instance, creating it only on first access.
 * Subsequent calls return the cached instance.
 * Uses file-based locking only in Docker/cloud/threaded environments.
 *
 * @param string $libraryName Name of the library (e.g., 'helper_library')
 * @return object Library instance
 * @throws Exception If library not found
 *
 * @example $sum = library('helper_library')->getSum($num1, $num2);
 */
function library($libraryName) {
    static $instances = [];
    static $needsLocking = null;
    static $lockFile = null;

    // Return cached instance if exists (fast path, no locking needed)
    if (isset($instances[$libraryName])) {
        return $instances[$libraryName];
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
            $lockFile = sys_get_temp_dir() . '/phpweave_libraries.lock';
        }
    }

    // Check if library class exists
    if (!isset($GLOBALS['_library_files'][$libraryName])) {
        throw new Exception("Library '{$libraryName}' not found");
    }

    $className = $GLOBALS['_library_files'][$libraryName];

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
                if (!isset($instances[$libraryName])) {
                    $instances[$libraryName] = new $className();
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
        $instances[$libraryName] = new $className();
    }

    return $instances[$libraryName];
}

/**
 * Lazy Library Loader Class (Thread-Safe)
 *
 * Provides array-like access to libraries with lazy loading.
 * Used for backward compatibility with $libraries['library_name'] syntax.
 * Thread-safe implementation using the library() function.
 */
class LazyLibraryLoader implements ArrayAccess {
    // Removed static $instances - now uses thread-safe library() function

    /**
     * Check if library exists (ArrayAccess)
     */
    public function offsetExists($offset): bool {
        return isset($GLOBALS['_library_files'][$offset]);
    }

    /**
     * Get library instance (ArrayAccess) - lazy loads
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return library($offset);
    }

    /**
     * Set is not allowed (ArrayAccess)
     */
    public function offsetSet($offset, $value): void {
        trigger_error("Cannot set libraries directly. Libraries are auto-loaded.", E_USER_WARNING);
    }

    /**
     * Unset is not allowed (ArrayAccess)
     */
    public function offsetUnset($offset): void {
        trigger_error("Cannot unset libraries directly. Libraries are auto-loaded.", E_USER_WARNING);
    }

    /**
     * Magic get for object property access
     *
     * @param string $libraryName
     * @return object
     */
    public function __get(string $libraryName): object {
        return library($libraryName);
    }

    /**
     * Magic isset for object property access
     *
     * @param string $libraryName
     * @return bool
     */
    public function __isset(string $libraryName): bool {
        return isset($GLOBALS['_library_files'][$libraryName]);
    }
}

/**
 * PHPWeaveLibraries Framework Container
 *
 * Global framework object providing access to all framework components.
 * Supports new syntax: $PW->libraries->library->test()
 */
class PHPWeaveLibraries {
    /**
     * @var LazyLibraryLoader
     */
    public LazyLibraryLoader $libraries;

    public function __construct() {
        $this->libraries = new LazyLibraryLoader();
    }
}

// Create lazy loader for backward compatibility
$libraries = new LazyLibraryLoader();
$GLOBALS['libraries'] = $libraries;

// Create new PHPWeave global object
$PW = new PHPWeaveLibraries();
$GLOBALS['PW'] = $PW;
