<?php
/**
 * Migration: CreateUsersTable
 *
 * Auto-generated migration file.
 * Modify the up() and down() methods to define your schema changes.
 */
class CreateUsersTable extends Migration
{
	/**
	 * Run the migration
	 *
	 * @return void
	 */
	public function up()
	{
		$this->createTable('users', [
			'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
			'email' => 'VARCHAR(255) NOT NULL UNIQUE',
			'password' => 'VARCHAR(255) NOT NULL',
			'name' => 'VARCHAR(255)',
			'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
			'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		]);

		// Create index on email
		$this->createIndex('users', 'idx_users_email', ['email'], true);
	}

	/**
	 * Reverse the migration
	 *
	 * @return void
	 */
	public function down()
	{
		$this->dropTable('users');
	}
}