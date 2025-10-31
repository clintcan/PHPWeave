<?php
/**
 * Migration: CreateSessionsTable
 *
 * Core migration for database-backed PHP sessions.
 * Creates the sessions table for storing session data with proper indexes
 * for performance and automatic cleanup of expired sessions.
 *
 * This is a core framework migration that enables database session handling
 * as an alternative to file-based sessions, which is beneficial for:
 * - Distributed/load-balanced environments
 * - Docker/Kubernetes deployments
 * - Session persistence across server restarts
 * - Centralized session management
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @version    2.2.0
 */
class CreateSessionsTable extends Migration
{
	/**
	 * Run the migration
	 *
	 * Creates the sessions table with:
	 * - Unique session ID (primary key)
	 * - Session data (text field for serialized data)
	 * - User ID (optional, for tracking authenticated sessions)
	 * - IP address (security tracking)
	 * - User agent (browser/device identification)
	 * - Last activity timestamp (for expiration)
	 * - Created/updated timestamps
	 *
	 * @return void
	 */
	public function up()
	{
		// Set migration description
		$this->setDescription('Create database-backed sessions table for distributed session management');

		// Build column definitions based on database driver
		$columns = $this->getSessionColumns();

		// Create sessions table
		$this->createTable('sessions', $columns);

		// Create indexes for performance
		// Index on last_activity for quick cleanup of expired sessions
		$this->createIndex('sessions', 'idx_sessions_last_activity', ['last_activity']);

		// Index on user_id for quick lookup of user's sessions
		$this->createIndex('sessions', 'idx_sessions_user_id', ['user_id']);
	}

	/**
	 * Reverse the migration
	 *
	 * Drops the sessions table and all associated indexes.
	 *
	 * @return void
	 */
	public function down()
	{
		$this->dropTable('sessions');
	}

	/**
	 * Get column definitions based on database driver
	 *
	 * Returns appropriate column definitions for the current database driver,
	 * handling differences between MySQL, PostgreSQL, SQLite, and SQL Server.
	 *
	 * @return array Column definitions
	 */
	private function getSessionColumns()
	{
		// Determine primary key syntax based on driver
		switch ($this->driver) {
			case 'pdo_pgsql':
				// PostgreSQL
				return [
					'id' => 'VARCHAR(255) PRIMARY KEY',
					'user_id' => 'INTEGER DEFAULT NULL',
					'ip_address' => 'VARCHAR(45) DEFAULT NULL',
					'user_agent' => 'TEXT DEFAULT NULL',
					'payload' => 'TEXT NOT NULL',
					'last_activity' => 'INTEGER NOT NULL',
					'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
					'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
				];

			case 'pdo_sqlite':
				// SQLite
				return [
					'id' => 'VARCHAR(255) PRIMARY KEY',
					'user_id' => 'INTEGER DEFAULT NULL',
					'ip_address' => 'VARCHAR(45) DEFAULT NULL',
					'user_agent' => 'TEXT DEFAULT NULL',
					'payload' => 'TEXT NOT NULL',
					'last_activity' => 'INTEGER NOT NULL',
					'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
					'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
				];

			case 'pdo_sqlsrv':
			case 'pdo_dblib':
				// SQL Server
				return [
					'id' => 'VARCHAR(255) PRIMARY KEY',
					'user_id' => 'INT DEFAULT NULL',
					'ip_address' => 'VARCHAR(45) DEFAULT NULL',
					'user_agent' => 'NVARCHAR(MAX) DEFAULT NULL',
					'payload' => 'NVARCHAR(MAX) NOT NULL',
					'last_activity' => 'INT NOT NULL',
					'created_at' => 'DATETIME DEFAULT GETDATE()',
					'updated_at' => 'DATETIME DEFAULT GETDATE()'
				];

			case 'pdo_mysql':
			default:
				// MySQL (default)
				return [
					'id' => 'VARCHAR(255) PRIMARY KEY',
					'user_id' => 'INT UNSIGNED DEFAULT NULL',
					'ip_address' => 'VARCHAR(45) DEFAULT NULL',
					'user_agent' => 'TEXT DEFAULT NULL',
					'payload' => 'LONGTEXT NOT NULL',
					'last_activity' => 'INT UNSIGNED NOT NULL',
					'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
					'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
				];
		}
	}
}
