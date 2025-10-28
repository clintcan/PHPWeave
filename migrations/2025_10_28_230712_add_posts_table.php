<?php
/**
 * Migration: AddPostsTable
 *
 * Auto-generated migration file.
 * Modify the up() and down() methods to define your schema changes.
 */
class AddPostsTable extends Migration
{
	/**
	 * Run the migration
	 *
	 * @return void
	 */
	public function up()
	{
		$this->createTable('posts', [
			'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
			'user_id' => 'INT NOT NULL',
			'title' => 'VARCHAR(255) NOT NULL',
			'slug' => 'VARCHAR(255) NOT NULL UNIQUE',
			'content' => 'TEXT',
			'status' => "ENUM('draft', 'published', 'archived') DEFAULT 'draft'",
			'published_at' => 'TIMESTAMP NULL',
			'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
			'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		]);

		// Create indexes
		$this->createIndex('posts', 'idx_posts_user_id', ['user_id']);
		$this->createIndex('posts', 'idx_posts_slug', ['slug'], true);
		$this->createIndex('posts', 'idx_posts_status', ['status']);

		// Add foreign key constraint
		$this->execute("ALTER TABLE posts ADD CONSTRAINT fk_posts_user_id
			FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
	}

	/**
	 * Reverse the migration
	 *
	 * @return void
	 */
	public function down()
	{
		$this->dropTable('posts');
	}
}