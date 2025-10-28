<?php
/**
 * Database Connection Class
 *
 * Provides PDO-based database connectivity with prepared statements.
 * Features:
 * - Secure prepared statement execution
 * - Automatic configuration from .env file
 * - Exception-based error handling
 * - Fetch helpers for single and multiple rows
 * - Support for any PDO-compatible database
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * class user_model extends DBConnection {
 *     function getUser($id) {
 *         $sql = "SELECT * FROM users WHERE id = :id";
 *         $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
 *         return $this->fetch($stmt);
 *     }
 * }
 */
class DBConnection
{
	/**
	 * Database host
	 * @var string
	 */
	public $host;

	/**
	 * Database name
	 * @var string
	 */
	public $db;

	/**
	 * Database username
	 * @var string|null
	 */
	public $user;

	/**
	 * Database password
	 * @var string|null
	 */
	public $password;

	/**
	 * Database charset
	 * @var string
	 */
	public $charset;

	/**
	 * PDO Data Source Name
	 * @var string
	 */
	public $dsn;

	/**
	 * PDO connection options
	 * @var array
	 */
	public $options;

	/**
	 * PDO instance
	 * @var PDO
	 */
	public $pdo;

	/**
	 * Database driver
	 * @var string
	 */
	public $driver;

	/**
	 * Database port
	 * @var int
	 */
	public $port;

	/**
	 * Constructor
	 *
	 * Initializes database connection using configuration from .env file.
	 * Automatically sets up PDO with secure defaults:
	 * - Exception mode for errors
	 * - Associative array fetch mode
	 * - Real prepared statements (no emulation)
	 *
	 * @return void
	 * @throws Exception If connection fails
	 */
	function __construct()
	{
		$this->host = $GLOBALS['configs']['DBHOST'];
		$this->db = $GLOBALS['configs']['DBNAME'];
		$this->user = $GLOBALS['configs']['DBUSER'];
		$this->password = $GLOBALS['configs']['DBPASSWORD'];
		$this->charset = $GLOBALS['configs']['DBCHARSET'];
		$this->driver = $GLOBALS['configs']['DBDRIVER'];
		$this->port = $GLOBALS['configs']['DBPORT'];
		$this->options = [
		    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		    PDO::ATTR_EMULATE_PREPARES   => false,
		];

		switch ($this->driver) {
			case 'pdo_mysql':
				$this->dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset;port=$this->port";
				break;

			case 'pdo_pgsql':
				// PostgreSQL uses 'options' for client_encoding instead of 'charset'
				$this->dsn = "pgsql:host=$this->host;dbname=$this->db;port=$this->port";
				if (!empty($this->charset)) {
					$this->dsn .= ";options='--client_encoding=$this->charset'";
				}
				break;

			case 'pdo_sqlite':
				// SQLite only needs database file path
				$this->dsn = "sqlite:$this->db";
				// SQLite doesn't use username/password
				$this->user = null;
				$this->password = null;
				break;

			case 'pdo_sqlsrv':
				// SQL Server native driver
				$this->dsn = "sqlsrv:Server=$this->host,$this->port;Database=$this->db";
				break;

			case 'pdo_dblib':
				// SQL Server via FreeTDS (dblib)
				$this->dsn = "dblib:host=$this->host:$this->port;dbname=$this->db";
				if (!empty($this->charset)) {
					$this->dsn .= ";charset=$this->charset";
				}
				break;

			case 'pdo_odbc':
				// ODBC requires custom DSN string
				if (empty($GLOBALS['configs']['DBDSN'])) {
					throw new Exception("ODBC driver requires DBDSN configuration variable");
				}
				$this->dsn = $GLOBALS['configs']['DBDSN'];
				break;

			default:
				throw new Exception("Unsupported database driver: " . $this->driver);
		}

		try {
			$this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);
		} catch (Exception $e) {
			// Log the error securely without exposing sensitive information
			error_log("Database Connection Error: " . $e->getMessage());

			// Display user-friendly message
			if (isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'] == 1) {
				die("Database connection failed. Check error log for details.");
			} else {
				die("A database error occurred. Please contact the administrator.");
			}
		}
	}

	/**
	 * Execute a prepared SQL statement
	 *
	 * Prepares and executes an SQL statement with optional parameter binding.
	 * Provides protection against SQL injection through parameter binding.
	 *
	 * @param string $sql    SQL query with :parameter placeholders
	 * @param array  $params Associative array of parameters (default: empty)
	 * @return PDOStatement Executed statement object
	 *
	 * @example
	 * // With parameters
	 * $stmt = $this->executePreparedSQL(
	 *     "SELECT * FROM users WHERE email = :email",
	 *     ['email' => $email]
	 * );
	 *
	 * @example
	 * // Without parameters
	 * $stmt = $this->executePreparedSQL("SELECT * FROM users");
	 */
	function executePreparedSQL($sql, $params = [])
	{
		$stmt = $this->pdo->prepare($sql);
		if(empty($params))
			$stmt->execute();
		else
			$stmt->execute($params);
		return $stmt;
	}

	/**
	 * Fetch single row from statement
	 *
	 * Retrieves the next row from a result set.
	 * Returns false if no more rows available.
	 *
	 * @param PDOStatement $stmt   Executed PDO statement
	 * @param int          $option Fetch mode (default: PDO::FETCH_ASSOC)
	 * @return mixed Row data as associative array, or false if no row
	 *
	 * @example
	 * $stmt = $this->executePreparedSQL($sql, $params);
	 * $user = $this->fetch($stmt);
	 */
	function fetch($stmt, $option = PDO::FETCH_ASSOC)
	{
		$result = $stmt->fetch($option);
		return $result;
	}

	/**
	 * Fetch all rows from statement
	 *
	 * Retrieves all remaining rows from a result set.
	 * Returns empty array if no rows available.
	 *
	 * @param PDOStatement $stmt   Executed PDO statement
	 * @param int          $option Fetch mode (default: PDO::FETCH_ASSOC)
	 * @return array Array of rows, each row as associative array
	 *
	 * @example
	 * $stmt = $this->executePreparedSQL($sql);
	 * $users = $this->fetchAll($stmt);
	 * foreach($users as $user) { ... }
	 */
	function fetchAll($stmt, $option = PDO::FETCH_ASSOC)
	{
		$result = $stmt->fetchAll($option);
		return $result;
	}

	/**
	 * Get number of affected rows
	 *
	 * Returns the number of rows affected by the last SQL statement.
	 * Useful for INSERT, UPDATE, DELETE operations.
	 *
	 * @param PDOStatement $stmt Executed PDO statement
	 * @return int Number of affected rows
	 *
	 * @example
	 * $stmt = $this->executePreparedSQL("DELETE FROM users WHERE id = :id", ['id' => $id]);
	 * $count = $this->rowCount($stmt);
	 * echo "Deleted $count rows";
	 */
	function rowCount($stmt)
	{
		return $stmt->rowCount();
	}

	/**
	 * Test database connection
	 *
	 * Simple method to verify database connection is working.
	 *
	 * @return string Returns "OK" if connection is valid
	 *
	 * @example
	 * $result = $this->test();
	 * if($result === "OK") { echo "Database connected"; }
	 */
	function test() {
		return "OK";
	}
}