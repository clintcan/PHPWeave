<?php
/**
 * Cache Driver Interface
 *
 * Base interface for all cache drivers in PHPWeave.
 * Drivers must implement these methods for cache operations.
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Caching
 * @version    2.5.0
 */
interface CacheDriver
{
    /**
     * Retrieve an item from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public function get($key);

    /**
     * Store an item in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success status
     */
    public function put($key, $value, $ttl);

    /**
     * Remove an item from cache
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function forget($key);

    /**
     * Remove all items from cache
     *
     * @return bool Success status
     */
    public function flush();

    /**
     * Increment a cached integer value
     *
     * @param string $key Cache key
     * @param int $value Amount to increment
     * @return int|bool New value or false on failure
     */
    public function increment($key, $value = 1);

    /**
     * Decrement a cached integer value
     *
     * @param string $key Cache key
     * @param int $value Amount to decrement
     * @return int|bool New value or false on failure
     */
    public function decrement($key, $value = 1);

    /**
     * Check if driver is available
     *
     * @return bool True if driver is available and functional
     */
    public function isAvailable();
}

/**
 * Memory Cache Driver
 *
 * In-memory cache stored in PHP array.
 * Lifetime: Single request only (fastest, but not persistent).
 *
 * Use case: Per-request caching, temporary data.
 */
class MemoryCacheDriver implements CacheDriver
{
    /**
     * Cache storage
     * @var array
     */
    protected $cache = [];

    /**
     * Expiration timestamps
     * @var array
     */
    protected $expires = [];

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        // Check if expired
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            unset($this->cache[$key], $this->expires[$key]);
            return null;
        }

        return $this->cache[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value, $ttl)
    {
        $this->cache[$key] = $value;
        $this->expires[$key] = $ttl > 0 ? time() + $ttl : PHP_INT_MAX;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        unset($this->cache[$key], $this->expires[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        $this->cache = [];
        $this->expires = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $value = 1)
    {
        $current = (int) $this->get($key);
        $new = $current + $value;
        $this->cache[$key] = $new;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable()
    {
        return true; // Always available
    }
}

/**
 * APCu Cache Driver
 *
 * Persistent in-memory cache using APCu extension.
 * Lifetime: Until TTL expires or server restart.
 *
 * Use case: Production, Docker, multi-request caching.
 */
class APCuCacheDriver implements CacheDriver
{
    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $value = @apcu_fetch($key);
        return $value !== false ? $value : null;
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value, $ttl)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @apcu_store($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @apcu_delete($key);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @apcu_clear_cache();
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $result = @apcu_inc($key, $value);
        return $result !== false ? $result : false;
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $result = @apcu_dec($key, $value);
        return $result !== false ? $result : false;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable()
    {
        return function_exists('apcu_fetch')
            && function_exists('apcu_store')
            && ini_get('apc.enabled');
    }
}

/**
 * File Cache Driver
 *
 * Filesystem-based cache using JSON serialization.
 * Lifetime: Until manually deleted or TTL expires.
 *
 * Use case: Fallback, shared hosting, persistent storage.
 */
class FileCacheDriver implements CacheDriver
{
    /**
     * Cache directory path
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? __DIR__ . '/../cache/data';

        // Create directory if it doesn't exist
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    /**
     * Get cache file path for key
     *
     * @param string $key Cache key
     * @return string File path
     */
    protected function getPath($key)
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $file = $this->getPath($key);

        if (!file_exists($file)) {
            return null;
        }

        $contents = @file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $data = @json_decode($contents, true);
        if ($data === null || !isset($data['expires'], $data['value'])) {
            return null;
        }

        // Check expiration
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value, $ttl)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $file = $this->getPath($key);
        $expires = $ttl > 0 ? time() + $ttl : 0;

        $data = json_encode([
            'expires' => $expires,
            'value' => $value
        ]);

        return @file_put_contents($file, $data, LOCK_EX) !== false;
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        $file = $this->getPath($key);

        if (!file_exists($file)) {
            return true;
        }

        return @unlink($file);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $files = glob($this->path . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $value = 1)
    {
        $current = (int) $this->get($key);
        $new = $current + $value;

        // Preserve TTL
        $file = $this->getPath($key);
        if (file_exists($file)) {
            $contents = @file_get_contents($file);
            $data = @json_decode($contents, true);
            $ttl = $data['expires'] > 0 ? $data['expires'] - time() : 0;
        } else {
            $ttl = 3600; // Default 1 hour
        }

        $this->put($key, $new, $ttl);
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable()
    {
        return is_dir($this->path) && is_writable($this->path);
    }
}

/**
 * Redis Cache Driver
 *
 * Distributed cache using Redis server.
 * Lifetime: Until TTL expires or Redis flush.
 *
 * Use case: Multi-server, load-balanced, distributed applications.
 * Requires: Redis PHP extension or Predis library.
 */
class RedisCacheDriver implements CacheDriver
{
    /**
     * Redis connection
     * @var Redis|null
     */
    protected $redis;

    /**
     * Configuration
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => getenv('REDIS_HOST') ?: 'localhost',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'password' => getenv('REDIS_PASSWORD') ?: null,
            'database' => getenv('REDIS_DATABASE') ?: 0,
            'timeout' => 2.5
        ], $config);

        $this->connect();
    }

    /**
     * Connect to Redis server
     *
     * @return void
     */
    protected function connect()
    {
        if (!class_exists('Redis')) {
            return; // Redis extension not available
        }

        try {
            $this->redis = new Redis();
            $connected = @$this->redis->connect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );

            if (!$connected) {
                $this->redis = null;
                return;
            }

            // Authenticate if password is set
            if ($this->config['password']) {
                @$this->redis->auth($this->config['password']);
            }

            // Select database
            if ($this->config['database']) {
                @$this->redis->select($this->config['database']);
            }
        } catch (Exception $e) {
            $this->redis = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $value = @$this->redis->get($key);
        return $value !== false ? json_decode($value, true) : null;
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value, $ttl)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $serialized = json_encode($value);

        if ($ttl > 0) {
            return @$this->redis->setex($key, $ttl, $serialized);
        } else {
            return @$this->redis->set($key, $serialized);
        }
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->redis->del($key) > 0;
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->redis->flushDB();
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->redis->incrBy($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->redis->decrBy($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable()
    {
        return $this->redis !== null && @$this->redis->ping() === '+PONG';
    }
}

/**
 * Memcached Cache Driver
 *
 * Distributed cache using Memcached server.
 * Lifetime: Until TTL expires or Memcached flush.
 *
 * Use case: Multi-server, load-balanced applications.
 * Requires: Memcached PHP extension.
 */
class MemcachedCacheDriver implements CacheDriver
{
    /**
     * Memcached connection
     * @var Memcached|null
     */
    protected $memcached;

    /**
     * Configuration
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => getenv('MEMCACHED_HOST') ?: 'localhost',
            'port' => getenv('MEMCACHED_PORT') ?: 11211,
        ], $config);

        $this->connect();
    }

    /**
     * Connect to Memcached server
     *
     * @return void
     */
    protected function connect()
    {
        if (!class_exists('Memcached')) {
            return; // Memcached extension not available
        }

        try {
            $this->memcached = new Memcached();
            $this->memcached->addServer($this->config['host'], $this->config['port']);

            // Test connection
            if (@$this->memcached->getVersion() === false) {
                $this->memcached = null;
            }
        } catch (Exception $e) {
            $this->memcached = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $value = @$this->memcached->get($key);
        return $value !== false ? $value : null;
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value, $ttl)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->memcached->set($key, $value, $ttl > 0 ? $ttl : 0);
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->memcached->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->memcached->flush();
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->memcached->increment($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $value = 1)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return @$this->memcached->decrement($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable()
    {
        return $this->memcached !== null && @$this->memcached->getVersion() !== false;
    }
}
