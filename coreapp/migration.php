<?php
/**
 * Migration Base Class
 *
 * Base class for all database migrations. Provides structure for schema changes
 * with up (apply) and down (rollback) methods.
 *
 * Features:
 * - Version-controlled schema changes
 * - Rollback capability
 * - Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server)
 * - Database-agnostic helper methods
 * - Transaction support
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     Clint Christopher Canada
 * @version    2.2.0
 *
 * @example
 * class CreateUsersTable extends Migration {
 *     public function up() {
 *         $this->execute("CREATE TABLE users (
 *             id INT AUTO_INCREMENT PRIMARY KEY,
 *             email VARCHAR(255) NOT NULL,
 *             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *         )");
 *     }
 *
 *     public function down() {
 *         $this->execute("DROP TABLE users");
 *     }
 * }
 */
abstract class Migration extends DBConnection
{
	/**
	 * Migration name/identifier
	 * @var string
	 */
	protected $name;

	/**
	 * Migration description
	 * @var string
	 */
	protected $description = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->name = get_class($this);
	}

	/**
	 * Apply the migration (create tables, add columns, etc.)
	 *
	 * @return void
	 */
	abstract public function up();

	/**
	 * Rollback the migration (drop tables, remove columns, etc.)
	 *
	 * @return void
	 */
	abstract public function down();

	/**
	 * Execute raw SQL statement
	 *
	 * @param string $sql SQL query to execute
	 * @return PDOStatement Executed statement
	 */
	protected function execute($sql)
	{
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute();
			return $stmt;
		} catch (PDOException $e) {
			throw new Exception("Migration SQL error: " . $e->getMessage() . "\nSQL: " . $sql);
		}
	}

	/**
	 * Create a new table
	 *
	 * @param string $tableName Table name
	 * @param array  $columns   Column definitions
	 * @param array  $options   Table options (engine, charset, etc.)
	 * @return void
	 *
	 * @example
	 * $this->createTable('users', [
	 *     'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
	 *     'email' => 'VARCHAR(255) NOT NULL',
	 *     'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
	 * ]);
	 */
	protected function createTable($tableName, array $columns, array $options = [])
	{
		$columnDefinitions = [];
		foreach ($columns as $name => $definition) {
			$columnDefinitions[] = "$name $definition";
		}

		$columnSql = implode(', ', $columnDefinitions);

		// Build SQL based on driver
		$sql = $this->buildCreateTableSQL($tableName, $columnSql, $options);

		$this->execute($sql);
	}

	/**
	 * Drop a table
	 *
	 * @param string $tableName Table name
	 * @return void
	 */
	protected function dropTable($tableName)
	{
		$this->execute("DROP TABLE IF EXISTS $tableName");
	}

	/**
	 * Add a column to existing table
	 *
	 * @param string $tableName  Table name
	 * @param string $columnName Column name
	 * @param string $definition Column definition
	 * @return void
	 */
	protected function addColumn($tableName, $columnName, $definition)
	{
		$this->execute("ALTER TABLE $tableName ADD COLUMN $columnName $definition");
	}

	/**
	 * Drop a column from table
	 *
	 * @param string $tableName  Table name
	 * @param string $columnName Column name
	 * @return void
	 */
	protected function dropColumn($tableName, $columnName)
	{
		// SQLite doesn't support DROP COLUMN, need special handling
		if ($this->driver === 'pdo_sqlite') {
			throw new Exception("SQLite does not support DROP COLUMN. Please recreate the table.");
		}

		$this->execute("ALTER TABLE $tableName DROP COLUMN $columnName");
	}

	/**
	 * Rename a table
	 *
	 * @param string $oldName Old table name
	 * @param string $newName New table name
	 * @return void
	 */
	protected function renameTable($oldName, $newName)
	{
		$this->execute("ALTER TABLE $oldName RENAME TO $newName");
	}

	/**
	 * Create an index
	 *
	 * @param string $tableName  Table name
	 * @param string $indexName  Index name
	 * @param array  $columns    Columns to index
	 * @param bool   $unique     Create unique index
	 * @return void
	 */
	protected function createIndex($tableName, $indexName, array $columns, $unique = false)
	{
		$uniqueSql = $unique ? 'UNIQUE' : '';
		$columnList = implode(', ', $columns);
		$this->execute("CREATE $uniqueSql INDEX $indexName ON $tableName ($columnList)");
	}

	/**
	 * Drop an index
	 *
	 * @param string $tableName Table name
	 * @param string $indexName Index name
	 * @return void
	 */
	protected function dropIndex($tableName, $indexName)
	{
		if ($this->driver === 'pdo_mysql') {
			$this->execute("DROP INDEX $indexName ON $tableName");
		} else {
			$this->execute("DROP INDEX $indexName");
		}
	}

	/**
	 * Insert data into table
	 *
	 * @param string $tableName Table name
	 * @param array  $data      Associative array of column => value
	 * @return void
	 */
	protected function insert($tableName, array $data)
	{
		$columns = array_keys($data);
		$values = array_values($data);

		$columnList = implode(', ', $columns);
		$placeholders = implode(', ', array_fill(0, count($values), '?'));

		$sql = "INSERT INTO $tableName ($columnList) VALUES ($placeholders)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($values);
	}

	/**
	 * Check if table exists
	 *
	 * @param string $tableName Table name
	 * @return bool True if table exists
	 */
	protected function tableExists($tableName)
	{
		try {
			$result = $this->pdo->query("SELECT 1 FROM $tableName LIMIT 1");
			return $result !== false;
		} catch (PDOException $e) {
			return false;
		}
	}

	/**
	 * Begin transaction
	 *
	 * @return void
	 */
	protected function beginTransaction()
	{
		$this->pdo->beginTransaction();
	}

	/**
	 * Commit transaction
	 *
	 * @return void
	 */
	protected function commit()
	{
		$this->pdo->commit();
	}

	/**
	 * Rollback transaction
	 *
	 * @return void
	 */
	protected function rollback()
	{
		$this->pdo->rollBack();
	}

	/**
	 * Build CREATE TABLE SQL based on database driver
	 *
	 * @param string $tableName Table name
	 * @param string $columnSql Column definitions
	 * @param array  $options   Table options
	 * @return string SQL statement
	 */
	private function buildCreateTableSQL($tableName, $columnSql, array $options)
	{
		$sql = "CREATE TABLE $tableName ($columnSql)";

		// Add MySQL-specific options
		if ($this->driver === 'pdo_mysql') {
			$engine = $options['engine'] ?? 'InnoDB';
			$charset = $options['charset'] ?? 'utf8mb4';
			$collate = $options['collate'] ?? 'utf8mb4_unicode_ci';

			$sql .= " ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collate";
		}

		return $sql;
	}

	/**
	 * Get migration name
	 *
	 * @return string Migration name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get migration description
	 *
	 * @return string Migration description
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set migration description
	 *
	 * @param string $description Description
	 * @return void
	 */
	protected function setDescription($description)
	{
		$this->description = $description;
	}
}
