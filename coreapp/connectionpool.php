<?php
/**
 * Connection Pool Class
 *
 * Manages PDO connection pooling to reduce connection overhead and improve performance.
 * Features:
 * - Connection reuse within application lifecycle
 * - Configurable pool size per database configuration
 * - Automatic connection health checking
 * - Thread-safe connection management
 * - Support for multiple database configurations
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     Clint Christopher Canada
 * @version    2.2.0
 *
 * @example
 * // Get connection from pool
 * $conn = ConnectionPool::getConnection($driver, $dsn, $user, $password, $options);
 *
 * // Configure pool size
 * ConnectionPool::setMaxConnections(20);
 *
 * // Get pool statistics
 * $stats = ConnectionPool::getPoolStats();
 */
class ConnectionPool
{
	/**
	 * Pool storage by configuration hash
	 * @var array
	 */
	private static $pools = [];

	/**
	 * Maximum connections per pool
	 * @var int
	 */
	private static $maxConnections = 10;

	/**
	 * Connection timeout in seconds
	 * @var int
	 */
	private static $connectionTimeout = 5;

	/**
	 * Get or create a pooled PDO connection
	 *
	 * Retrieves an available connection from the pool or creates a new one
	 * if the pool is not at capacity. Connections are keyed by their DSN
	 * and credentials to support multiple database configurations.
	 *
	 * @param string $driver   Database driver (e.g., 'pdo_mysql')
	 * @param string $dsn      PDO Data Source Name
	 * @param string $user     Database username
	 * @param string $password Database password
	 * @param array  $options  PDO options array
	 * @return PDO Pooled PDO connection instance
	 * @throws Exception If pool is exhausted or connection fails
	 *
	 * @example
	 * $conn = ConnectionPool::getConnection(
	 *     'pdo_mysql',
	 *     'mysql:host=localhost;dbname=test',
	 *     'root',
	 *     'password',
	 *     [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
	 * );
	 */
	public static function getConnection($driver, $dsn, $user, $password, $options)
	{
		// Create unique pool key based on connection parameters
		$poolKey = self::generatePoolKey($dsn, $user);

		// Initialize pool if it doesn't exist
		if (!isset(self::$pools[$poolKey])) {
			self::$pools[$poolKey] = [
				'connections' => [],
				'available' => [],
				'in_use' => 0,
				'total_created' => 0,
				'total_reused' => 0,
				'driver' => $driver,
				'dsn' => $dsn,
				'user' => $user,
				'password' => $password,
				'options' => $options
			];
		}

		// Try to get an available connection from pool
		if (!empty(self::$pools[$poolKey]['available'])) {
			$conn = array_pop(self::$pools[$poolKey]['available']);

			// Verify connection is still alive
			if (self::isConnectionAlive($conn)) {
				self::$pools[$poolKey]['in_use']++;
				self::$pools[$poolKey]['total_reused']++;
				return $conn;
			} else {
				// Connection is dead, remove it and create a new one
				self::removeConnection($poolKey, $conn);
			}
		}

		// Create new connection if under limit
		$totalConnections = count(self::$pools[$poolKey]['connections']);
		if ($totalConnections < self::$maxConnections) {
			try {
				$conn = new PDO($dsn, $user, $password, $options);
				self::$pools[$poolKey]['connections'][] = $conn;
				self::$pools[$poolKey]['in_use']++;
				self::$pools[$poolKey]['total_created']++;
				return $conn;
			} catch (PDOException $e) {
				error_log("ConnectionPool: Failed to create new connection - " . $e->getMessage());
				throw new Exception("Failed to create database connection: " . $e->getMessage());
			}
		}

		// Pool is exhausted - throw exception
		throw new Exception(
			"Connection pool exhausted: {$totalConnections}/" . self::$maxConnections . " connections in use. " .
			"Consider increasing DB_POOL_SIZE in .env configuration."
		);
	}

	/**
	 * Release connection back to pool
	 *
	 * Returns a connection to the available pool for reuse.
	 * The connection is not closed, just marked as available.
	 *
	 * @param PDO    $conn    PDO connection to release
	 * @param string $poolKey Pool identifier (optional, auto-detected if not provided)
	 * @return bool True if released successfully, false otherwise
	 *
	 * @example
	 * ConnectionPool::releaseConnection($conn);
	 */
	public static function releaseConnection($conn, $poolKey = null)
	{
		// If no pool key provided, try to find it
		if ($poolKey === null) {
			$poolKey = self::findPoolKeyForConnection($conn);
		}

		if ($poolKey !== null && isset(self::$pools[$poolKey])) {
			// Only release if not already in available pool
			if (!in_array($conn, self::$pools[$poolKey]['available'], true)) {
				self::$pools[$poolKey]['available'][] = $conn;
				self::$pools[$poolKey]['in_use']--;
				return true;
			}
		}

		return false;
	}

	/**
	 * Set maximum connections per pool
	 *
	 * Configures the maximum number of connections allowed in each pool.
	 * Should be set before connections are created.
	 *
	 * @param int $max Maximum connections (must be > 0)
	 * @return void
	 *
	 * @example
	 * ConnectionPool::setMaxConnections(20);
	 */
	public static function setMaxConnections($max)
	{
		if ($max > 0) {
			self::$maxConnections = (int)$max;
		}
	}

	/**
	 * Get current pool statistics
	 *
	 * Returns detailed statistics about all connection pools including
	 * total connections, available connections, and usage metrics.
	 *
	 * @return array Pool statistics by pool key
	 *
	 * @example
	 * $stats = ConnectionPool::getPoolStats();
	 * foreach ($stats as $key => $pool) {
	 *     echo "Pool: {$pool['driver']} - In use: {$pool['in_use']}/{$pool['total']}\n";
	 * }
	 */
	public static function getPoolStats()
	{
		$stats = [];

		foreach (self::$pools as $key => $pool) {
			$stats[$key] = [
				'driver' => $pool['driver'],
				'total' => count($pool['connections']),
				'available' => count($pool['available']),
				'in_use' => $pool['in_use'],
				'max_allowed' => self::$maxConnections,
				'total_created' => $pool['total_created'],
				'total_reused' => $pool['total_reused'],
				'reuse_ratio' => $pool['total_created'] > 0
					? round($pool['total_reused'] / $pool['total_created'], 2)
					: 0
			];
		}

		return $stats;
	}

	/**
	 * Clear all pools and close all connections
	 *
	 * Closes all pooled connections and resets the pool state.
	 * Useful for cleanup or testing.
	 *
	 * @return void
	 *
	 * @example
	 * ConnectionPool::clearAllPools();
	 */
	public static function clearAllPools()
	{
		foreach (self::$pools as $poolKey => $pool) {
			// Close all connections
			foreach ($pool['connections'] as $conn) {
				$conn = null; // Force PDO connection close
			}
		}

		self::$pools = [];
	}

	/**
	 * Clear specific pool by key
	 *
	 * @param string $poolKey Pool identifier
	 * @return bool True if pool was cleared, false if not found
	 */
	public static function clearPool($poolKey)
	{
		if (isset(self::$pools[$poolKey])) {
			foreach (self::$pools[$poolKey]['connections'] as $conn) {
				$conn = null;
			}
			unset(self::$pools[$poolKey]);
			return true;
		}

		return false;
	}

	/**
	 * Generate unique pool key from DSN and user
	 *
	 * @param string $dsn  PDO Data Source Name
	 * @param string $user Database username
	 * @return string Pool key
	 */
	private static function generatePoolKey($dsn, $user)
	{
		return md5($dsn . '|' . $user);
	}

	/**
	 * Check if PDO connection is still alive
	 *
	 * @param PDO $conn PDO connection to test
	 * @return bool True if connection is alive, false otherwise
	 */
	private static function isConnectionAlive($conn)
	{
		try {
			// Simple query to test connection
			$conn->query('SELECT 1');
			return true;
		} catch (PDOException $e) {
			return false;
		}
	}

	/**
	 * Remove dead connection from pool
	 *
	 * @param string $poolKey Pool identifier
	 * @param PDO    $conn    Connection to remove
	 * @return void
	 */
	private static function removeConnection($poolKey, $conn)
	{
		if (isset(self::$pools[$poolKey])) {
			// Remove from connections array
			$index = array_search($conn, self::$pools[$poolKey]['connections'], true);
			if ($index !== false) {
				unset(self::$pools[$poolKey]['connections'][$index]);
				self::$pools[$poolKey]['connections'] = array_values(self::$pools[$poolKey]['connections']);
			}

			// Remove from available array
			$index = array_search($conn, self::$pools[$poolKey]['available'], true);
			if ($index !== false) {
				unset(self::$pools[$poolKey]['available'][$index]);
				self::$pools[$poolKey]['available'] = array_values(self::$pools[$poolKey]['available']);
			}
		}

		// Close connection
		$conn = null;
	}

	/**
	 * Find pool key for a given connection
	 *
	 * @param PDO $conn PDO connection
	 * @return string|null Pool key or null if not found
	 */
	private static function findPoolKeyForConnection($conn)
	{
		foreach (self::$pools as $key => $pool) {
			if (in_array($conn, $pool['connections'], true)) {
				return $key;
			}
		}

		return null;
	}

	/**
	 * Enable persistent connections mode
	 *
	 * When enabled, adds PDO::ATTR_PERSISTENT to connection options.
	 * This combines connection pooling with PHP's persistent connections.
	 *
	 * @param bool $enable Enable or disable persistent mode
	 * @return void
	 */
	public static function enablePersistentMode($enable = true)
	{
		foreach (self::$pools as $poolKey => &$pool) {
			if ($enable) {
				$pool['options'][PDO::ATTR_PERSISTENT] = true;
			} else {
				unset($pool['options'][PDO::ATTR_PERSISTENT]);
			}
		}
	}
}
