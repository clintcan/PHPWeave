<?php
/**
 * Session Class
 * Handles session operations with support for file-based and database-based storage
 *
 * Features:
 * - File-based sessions (default)
 * - Database-based sessions (optional)
 * - Automatic garbage collection
 * - Database-free mode compatible
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Session
 * @author     Clint Christopher Canada
 * @version    2.2.1
 */
class Session {
    /**
     * Database connection instance
     * @var DBConnection|null
     */
    private $db = null;

    /**
     * Session driver (file or database)
     * @var string
     */
    private $driver = 'file';

    /**
     * Session table name
     * @var string
     */
    private $table = 'sessions';

    /**
     * Session lifetime in seconds
     * @var int
     */
    private $lifetime = 1800; // 30 minutes default

    /**
     * Constructor
     * Initializes session handler based on configuration
     */
    public function __construct() {
        // Get session driver from config (file or database)
        $this->driver = $GLOBALS['configs']['SESSION_DRIVER'] ?? 'file';

        // Set session lifetime from config or default
        $this->lifetime = $GLOBALS['configs']['SESSION_LIFETIME'] ?? 1800;

        // Only initialize database handler if driver is database AND database is enabled
        if ($this->driver === 'database') {
            // Check if database is enabled
            $databaseEnabled = true;
            if (isset($GLOBALS['configs']['ENABLE_DATABASE']) && $GLOBALS['configs']['ENABLE_DATABASE'] == 0) {
                $databaseEnabled = false;
            } elseif (empty($GLOBALS['configs']['DBNAME'])) {
                $databaseEnabled = false;
            }

            if (!$databaseEnabled) {
                // Database disabled, fallback to file sessions
                error_log("Session: Database driver requested but database is disabled. Falling back to file sessions.");
                $this->driver = 'file';
            } else {
                // Initialize database connection for sessions
                try {
                    $this->db = new DBConnection();
                } catch (Exception $e) {
                    // Database connection failed, fallback to file sessions
                    error_log("Session: Database connection failed. Falling back to file sessions. Error: " . $e->getMessage());
                    $this->driver = 'file';
                    $this->db = null;
                }
            }
        }

        // Register custom session handler only if using database
        if ($this->driver === 'database' && $this->db !== null) {
            session_set_save_handler(
                array($this, "_open"),
                array($this, "_close"),
                array($this, "_read"),
                array($this, "_write"),
                array($this, "_destroy"),
                // @phpstan-ignore-next-line argument.type (PHPStan expects string but PHP passes int to gc handler)
                array($this, "_gc")
            );
        }
        // Otherwise use default PHP file-based sessions

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Open session
     * @param string $savePath Session save path
     * @param string $sessionName Session name
     * @return bool Success status
     */
    public function _open($savePath, $sessionName) {
        // For database sessions, verify connection is available
        if ($this->driver === 'database' && $this->db !== null) {
            return true;
        }
        return true;
    }

    /**
     * Close session
     * @return bool Success status
     */
    public function _close() {
        return true;
    }

    /**
     * Read session data
     * @param string $id Session ID
     * @return string Session data
     */
    public function _read($id) {
        if ($this->driver === 'database' && $this->db !== null) {
            try {
                $sql = "SELECT payload FROM {$this->table} WHERE id = :id AND last_activity >= :expiry";
                $stmt = $this->db->executePreparedSQL($sql, [
                    'id' => $id,
                    'expiry' => time() - $this->lifetime
                ]);

                $result = $this->db->fetch($stmt);

                if ($result && isset($result['payload'])) {
                    return $result['payload'];
                }
            } catch (Exception $e) {
                error_log("Session read error: " . $e->getMessage());
            }
        }

        return '';
    }

    /**
     * Write session data
     * @param string $id Session ID
     * @param string $data Session data
     * @return bool Success status
     */
    public function _write($id, $data) {
        if ($this->driver === 'database' && $this->db !== null) {
            try {
                // Get client information
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

                // Use REPLACE or INSERT ... ON DUPLICATE KEY UPDATE
                $sql = "REPLACE INTO {$this->table} (id, payload, last_activity, ip_address, user_agent, updated_at)
                        VALUES (:id, :payload, :last_activity, :ip_address, :user_agent, CURRENT_TIMESTAMP)";

                $this->db->executePreparedSQL($sql, [
                    'id' => $id,
                    'payload' => $data,
                    'last_activity' => time(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ]);

                return true;
            } catch (Exception $e) {
                error_log("Session write error: " . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Destroy session
     * @param string $id Session ID
     * @return bool Success status
     */
    public function _destroy($id) {
        if ($this->driver === 'database' && $this->db !== null) {
            try {
                $sql = "DELETE FROM {$this->table} WHERE id = :id";
                $this->db->executePreparedSQL($sql, ['id' => $id]);
                return true;
            } catch (Exception $e) {
                error_log("Session destroy error: " . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Garbage collection - clean up expired sessions
     * @param int $maxlifetime Maximum session lifetime in seconds
     * @return bool Success status
     */
    public function _gc($maxlifetime) {
        if ($this->driver === 'database' && $this->db !== null) {
            try {
                $sql = "DELETE FROM {$this->table} WHERE last_activity < :expiry";
                $this->db->executePreparedSQL($sql, [
                    'expiry' => time() - $maxlifetime
                ]);
                return true;
            } catch (Exception $e) {
                error_log("Session garbage collection error: " . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Get current session driver
     * @return string Driver name (file or database)
     */
    public function getDriver() {
        return $this->driver;
    }

    /**
     * Set a session variable
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session variable exists
     * @param string $key Session key
     * @return bool True if exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable
     * @param string $key Session key
     */
    public function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session data
     */
    public function flush() {
        $_SESSION = [];
    }

    /**
     * Regenerate session ID for security
     * @param bool $deleteOldSession Whether to delete old session
     * @return bool Success status
     */
    public function regenerate($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }
}
