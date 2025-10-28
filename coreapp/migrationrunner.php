<?php
/**
 * Migration Runner Class
 *
 * Manages database migration execution, tracking, and rollback.
 * Automatically tracks which migrations have been applied and allows
 * version control of database schema changes.
 *
 * Features:
 * - Automatic migration discovery
 * - Migration history tracking
 * - Rollback support
 * - Batch execution
 * - Status reporting
 * - Transaction support
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     Clint Christopher Canada
 * @version    2.2.0
 */

require_once __DIR__ . '/dbconnection.php';
require_once __DIR__ . '/migration.php';

class MigrationRunner extends DBConnection
{
	/**
	 * Migration directory path
	 * @var string
	 */
	private $migrationPath;

	/**
	 * Migrations table name
	 * @var string
	 */
	private $migrationsTable = 'migrations';

	/**
	 * Constructor
	 *
	 * @param string $migrationPath Path to migration files (default: migrations/)
	 */
	public function __construct($migrationPath = null)
	{
		parent::__construct();

		if ($migrationPath === null) {
			$migrationPath = defined('PHPWEAVE_ROOT')
				? PHPWEAVE_ROOT . '/migrations'
				: dirname(__DIR__) . '/migrations';
		}

		$this->migrationPath = rtrim($migrationPath, '/');
		$this->ensureMigrationsTable();
	}

	/**
	 * Create migrations tracking table if it doesn't exist
	 *
	 * @return void
	 */
	private function ensureMigrationsTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
			id INT AUTO_INCREMENT PRIMARY KEY,
			migration VARCHAR(255) NOT NULL UNIQUE,
			batch INT NOT NULL,
			executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)";

		// Adjust SQL for different databases
		if ($this->driver === 'pdo_sqlite') {
			$sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				migration TEXT NOT NULL UNIQUE,
				batch INTEGER NOT NULL,
				executed_at TEXT DEFAULT CURRENT_TIMESTAMP
			)";
		} elseif ($this->driver === 'pdo_pgsql') {
			$sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
				id SERIAL PRIMARY KEY,
				migration VARCHAR(255) NOT NULL UNIQUE,
				batch INT NOT NULL,
				executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
			)";
		}

		try {
			$this->pdo->exec($sql);
		} catch (PDOException $e) {
			throw new Exception("Failed to create migrations table: " . $e->getMessage());
		}
	}

	/**
	 * Run all pending migrations
	 *
	 * @param bool $verbose Show detailed output
	 * @return array Results array with executed migrations
	 */
	public function migrate($verbose = true)
	{
		$pending = $this->getPendingMigrations();

		if (empty($pending)) {
			if ($verbose) {
				echo "No pending migrations.\n";
			}
			return [];
		}

		$batch = $this->getNextBatchNumber();
		$executed = [];

		foreach ($pending as $migrationFile) {
			try {
				$this->runMigration($migrationFile, $batch, 'up', $verbose);
				$executed[] = $migrationFile;
			} catch (Exception $e) {
				if ($verbose) {
					echo "✗ Migration failed: " . $e->getMessage() . "\n";
				}
				throw $e;
			}
		}

		return $executed;
	}

	/**
	 * Rollback the last batch of migrations
	 *
	 * @param bool $verbose Show detailed output
	 * @param int  $steps   Number of batches to rollback (default: 1)
	 * @return array Results array with rolled back migrations
	 */
	public function rollback($verbose = true, $steps = 1)
	{
		$batches = $this->getExecutedMigrationsByBatch($steps);

		if (empty($batches)) {
			if ($verbose) {
				echo "No migrations to rollback.\n";
			}
			return [];
		}

		$rolledBack = [];

		foreach ($batches as $migrationName) {
			try {
				// Add .php extension if not present
				$migrationFile = str_ends_with($migrationName, '.php') ? $migrationName : $migrationName . '.php';

				$this->runMigration($migrationFile, null, 'down', $verbose);
				$this->removeMigrationRecord($migrationName);
				$rolledBack[] = $migrationName;
			} catch (Exception $e) {
				if ($verbose) {
					echo "✗ Rollback failed: " . $e->getMessage() . "\n";
				}
				throw $e;
			}
		}

		return $rolledBack;
	}

	/**
	 * Reset all migrations (rollback all and re-run)
	 *
	 * @param bool $verbose Show detailed output
	 * @return array Results array
	 */
	public function reset($verbose = true)
	{
		// Rollback all
		$this->rollbackAll($verbose);

		// Run all migrations
		return $this->migrate($verbose);
	}

	/**
	 * Rollback all migrations
	 *
	 * @param bool $verbose Show detailed output
	 * @return array Rolled back migrations
	 */
	public function rollbackAll($verbose = true)
	{
		$allMigrations = $this->getExecutedMigrations();

		if (empty($allMigrations)) {
			if ($verbose) {
				echo "No migrations to rollback.\n";
			}
			return [];
		}

		// Reverse order for rollback
		$allMigrations = array_reverse($allMigrations);
		$rolledBack = [];

		foreach ($allMigrations as $migrationName) {
			try {
				// Add .php extension if not present
				$migrationFile = str_ends_with($migrationName, '.php') ? $migrationName : $migrationName . '.php';

				$this->runMigration($migrationFile, null, 'down', $verbose);
				$this->removeMigrationRecord($migrationName);
				$rolledBack[] = $migrationName;
			} catch (Exception $e) {
				if ($verbose) {
					echo "✗ Rollback failed: " . $e->getMessage() . "\n";
				}
				throw $e;
			}
		}

		return $rolledBack;
	}

	/**
	 * Get migration status
	 *
	 * @return array Migration status information
	 */
	public function status()
	{
		$allFiles = $this->getAllMigrationFiles();
		$executed = $this->getExecutedMigrations();

		$status = [];

		foreach ($allFiles as $file) {
			$migrationName = $this->getMigrationName($file);
			$status[] = [
				'migration' => $migrationName,
				'file' => $file,
				'executed' => in_array($migrationName, $executed),
				'batch' => $this->getMigrationBatch($migrationName)
			];
		}

		return $status;
	}

	/**
	 * Run a single migration
	 *
	 * @param string $migrationFile Migration file name
	 * @param int    $batch         Batch number
	 * @param string $direction     Direction ('up' or 'down')
	 * @param bool   $verbose       Show detailed output
	 * @return void
	 */
	private function runMigration($migrationFile, $batch, $direction = 'up', $verbose = true)
	{
		$migrationName = $this->getMigrationName($migrationFile);
		$filePath = $this->migrationPath . '/' . $migrationFile;

		if (!file_exists($filePath)) {
			throw new Exception("Migration file not found: $filePath");
		}

		// Load migration file
		require_once $filePath;

		// Instantiate migration class
		$className = $this->getMigrationClassName($migrationFile);

		if (!class_exists($className)) {
			throw new Exception("Migration class '$className' not found in $migrationFile");
		}

		$migration = new $className();

		if (!($migration instanceof Migration)) {
			throw new Exception("$className must extend Migration class");
		}

		// Execute migration
		try {
			$this->pdo->beginTransaction();

			if ($direction === 'up') {
				if ($verbose) {
					echo "Running: $migrationName... ";
				}
				$migration->up();

				// Record migration
				$this->recordMigration($migrationName, $batch);

				if ($verbose) {
					echo "✓\n";
				}
			} else {
				if ($verbose) {
					echo "Rolling back: $migrationName... ";
				}
				$migration->down();

				if ($verbose) {
					echo "✓\n";
				}
			}

			$this->pdo->commit();
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}

	/**
	 * Get all migration files
	 *
	 * @return array Migration file names sorted by timestamp
	 */
	private function getAllMigrationFiles()
	{
		if (!is_dir($this->migrationPath)) {
			return [];
		}

		$files = scandir($this->migrationPath);
		$migrations = [];

		foreach ($files as $file) {
			if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.php$/', $file)) {
				$migrations[] = $file;
			}
		}

		sort($migrations);
		return $migrations;
	}

	/**
	 * Get pending migrations
	 *
	 * @return array Pending migration file names
	 */
	private function getPendingMigrations()
	{
		$allMigrations = $this->getAllMigrationFiles();
		$executed = $this->getExecutedMigrations();

		$pending = [];

		foreach ($allMigrations as $file) {
			$name = $this->getMigrationName($file);
			if (!in_array($name, $executed)) {
				$pending[] = $file;
			}
		}

		return $pending;
	}

	/**
	 * Get executed migrations
	 *
	 * @return array Executed migration names
	 */
	private function getExecutedMigrations()
	{
		$stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id ASC");
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Get executed migrations by batch
	 *
	 * @param int $steps Number of batches to retrieve
	 * @return array Migration names
	 */
	private function getExecutedMigrationsByBatch($steps = 1)
	{
		$sql = "SELECT migration FROM {$this->migrationsTable}
				WHERE batch >= (SELECT MAX(batch) - ? + 1 FROM {$this->migrationsTable})
				ORDER BY id DESC";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([$steps]);

		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Get next batch number
	 *
	 * @return int Next batch number
	 */
	private function getNextBatchNumber()
	{
		$stmt = $this->pdo->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
		$max = $stmt->fetchColumn();

		return $max ? (int)$max + 1 : 1;
	}

	/**
	 * Get migration batch number
	 *
	 * @param string $migrationName Migration name
	 * @return int|null Batch number or null if not executed
	 */
	private function getMigrationBatch($migrationName)
	{
		$stmt = $this->pdo->prepare("SELECT batch FROM {$this->migrationsTable} WHERE migration = ?");
		$stmt->execute([$migrationName]);
		$result = $stmt->fetchColumn();

		return $result ? (int)$result : null;
	}

	/**
	 * Record migration in database
	 *
	 * @param string $migrationName Migration name
	 * @param int    $batch         Batch number
	 * @return void
	 */
	private function recordMigration($migrationName, $batch)
	{
		$stmt = $this->pdo->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
		$stmt->execute([$migrationName, $batch]);
	}

	/**
	 * Remove migration record from database
	 *
	 * @param string $migrationName Migration name
	 * @return void
	 */
	private function removeMigrationRecord($migrationName)
	{
		$stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
		$stmt->execute([$migrationName]);
	}

	/**
	 * Get migration name from file name
	 *
	 * @param string $fileName File name
	 * @return string Migration name
	 */
	private function getMigrationName($fileName)
	{
		return str_replace('.php', '', $fileName);
	}

	/**
	 * Get migration class name from file name
	 *
	 * @param string $fileName File name
	 * @return string Class name
	 */
	private function getMigrationClassName($fileName)
	{
		// Extract class name from filename
		// Format: 2025_10_29_123456_create_users_table.php -> CreateUsersTable
		$parts = explode('_', $fileName);
		$nameParts = array_slice($parts, 4); // Skip timestamp parts
		$className = implode('_', $nameParts);
		$className = str_replace('.php', '', $className);

		// Convert to PascalCase
		$className = str_replace('_', ' ', $className);
		$className = ucwords($className);
		$className = str_replace(' ', '', $className);

		return $className;
	}

	/**
	 * Create a new migration file
	 *
	 * @param string $name Migration name (snake_case)
	 * @return string Created file path
	 */
	public function create($name)
	{
		// Ensure migration directory exists
		if (!is_dir($this->migrationPath)) {
			mkdir($this->migrationPath, 0755, true);
		}

		// Generate filename with timestamp
		$timestamp = date('Y_m_d_His');
		$fileName = "{$timestamp}_{$name}.php";
		$filePath = $this->migrationPath . '/' . $fileName;

		// Generate class name
		$className = str_replace('_', ' ', $name);
		$className = ucwords($className);
		$className = str_replace(' ', '', $className);

		// Create migration file from template
		$template = $this->getMigrationTemplate($className);

		file_put_contents($filePath, $template);

		return $filePath;
	}

	/**
	 * Get migration file template
	 *
	 * @param string $className Class name
	 * @return string Template content
	 */
	private function getMigrationTemplate($className)
	{
		return <<<PHP
<?php
/**
 * Migration: $className
 *
 * Auto-generated migration file.
 * Modify the up() and down() methods to define your schema changes.
 */
class $className extends Migration
{
	/**
	 * Run the migration
	 *
	 * @return void
	 */
	public function up()
	{
		// Example: Create a table
		/*
		\$this->createTable('table_name', [
			'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
			'name' => 'VARCHAR(255) NOT NULL',
			'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
		]);
		*/

		// Example: Execute raw SQL
		/*
		\$this->execute("CREATE TABLE table_name (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL
		)");
		*/
	}

	/**
	 * Reverse the migration
	 *
	 * @return void
	 */
	public function down()
	{
		// Example: Drop the table
		/*
		\$this->dropTable('table_name');
		*/
	}
}
PHP;
	}
}
