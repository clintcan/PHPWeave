# Database Migrations Guide

**PHPWeave v2.2.0+**

Complete guide to using PHPWeave's built-in migration system for version-controlled database schema changes.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [CLI Commands](#cli-commands)
- [Writing Migrations](#writing-migrations)
- [Migration Methods](#migration-methods)
- [Examples](#examples)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

The migration system provides:
- ✅ **Version control** for database schema
- ✅ **Rollback capability** to reverse changes
- ✅ **Batch tracking** for organized migrations
- ✅ **Multi-database support** (MySQL, PostgreSQL, SQLite, SQL Server)
- ✅ **Transaction support** for safe execution
- ✅ **Automatic tracking** of applied migrations

---

## Quick Start

### 1. Create a Migration

```bash
php migrate.php create create_users_table
```

This creates: `migrations/2025_10_29_123456_create_users_table.php`

### 2. Edit the Migration

```php
<?php
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('users', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'email' => 'VARCHAR(255) NOT NULL UNIQUE',
            'password' => 'VARCHAR(255) NOT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
```

### 3. Run the Migration

```bash
php migrate.php migrate
```

### 4. Check Status

```bash
php migrate.php status
```

---

## CLI Commands

### Create Migration

```bash
php migrate.php create <migration_name>
```

**Example:**
```bash
php migrate.php create add_email_to_users
php migrate.php create create_posts_table
```

**Naming Convention:** Use snake_case. The tool auto-generates timestamp prefix.

---

### Run Migrations

```bash
php migrate.php migrate
```

Runs all pending migrations in order.

**Output:**
```
Running migrations...
Running: 2025_10_29_123456_create_users_table... ✓
Running: 2025_10_29_123500_create_posts_table... ✓
✓ Executed 2 migration(s).
```

---

### Rollback Migrations

```bash
# Rollback last batch
php migrate.php rollback

# Rollback multiple batches
php migrate.php rollback 3
```

**Example:**
```bash
$ php migrate.php rollback
Rolling back 1 batch(es)...
Rolling back: 2025_10_29_123500_create_posts_table... ✓
Rolling back: 2025_10_29_123456_create_users_table... ✓
✓ Rolled back 2 migration(s).
```

---

### Reset All Migrations

```bash
php migrate.php reset
```

Rolls back all migrations and re-runs them. Useful for development.

---

### Check Status

```bash
php migrate.php status
```

**Output:**
```
Migration Status:
--------------------------------------------------------------------------------
Migration                                          Status     Batch
--------------------------------------------------------------------------------
2025_10_29_123456_create_users_table               ✓ Executed 1
2025_10_29_123500_create_posts_table               ✓ Executed 1
2025_10_29_123600_add_posts_status                 ✗ Pending  -
--------------------------------------------------------------------------------

Total: 3 migrations
Executed: 2
Pending: 1
```

---

## Writing Migrations

### Basic Structure

```php
<?php
class MigrationName extends Migration
{
    public function up()
    {
        // Apply changes (create tables, add columns, etc.)
    }

    public function down()
    {
        // Reverse changes (drop tables, remove columns, etc.)
    }
}
```

**Important:** Every `up()` must have a corresponding `down()` for rollback.

---

## Migration Methods

### Table Operations

#### Create Table

```php
$this->createTable('users', [
    'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
    'email' => 'VARCHAR(255) NOT NULL',
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
], [
    'engine' => 'InnoDB',           // MySQL only
    'charset' => 'utf8mb4',          // MySQL only
    'collate' => 'utf8mb4_unicode_ci' // MySQL only
]);
```

#### Drop Table

```php
$this->dropTable('users');
```

#### Rename Table

```php
$this->renameTable('old_name', 'new_name');
```

#### Check if Table Exists

```php
if ($this->tableExists('users')) {
    // Table exists
}
```

---

### Column Operations

#### Add Column

```php
$this->addColumn('users', 'phone', 'VARCHAR(20)');
$this->addColumn('users', 'is_active', 'BOOLEAN DEFAULT TRUE');
```

#### Drop Column

```php
$this->dropColumn('users', 'phone');
```

**Note:** SQLite doesn't support DROP COLUMN. You'll need to recreate the table.

---

### Index Operations

#### Create Index

```php
// Regular index
$this->createIndex('users', 'idx_users_email', ['email']);

// Unique index
$this->createIndex('users', 'idx_users_email', ['email'], true);

// Composite index
$this->createIndex('posts', 'idx_posts_user_status', ['user_id', 'status']);
```

#### Drop Index

```php
$this->dropIndex('users', 'idx_users_email');
```

---

### Raw SQL

#### Execute SQL

```php
$this->execute("CREATE TABLE custom (
    id INT PRIMARY KEY,
    data JSON
)");

$this->execute("ALTER TABLE users ADD INDEX (email)");
```

---

### Data Operations

#### Insert Data

```php
$this->insert('users', [
    'email' => 'admin@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'name' => 'Admin User'
]);
```

---

### Transaction Control

```php
public function up()
{
    $this->beginTransaction();

    try {
        $this->createTable('users', [...]);
        $this->createTable('posts', [...]);
        $this->commit();
    } catch (Exception $e) {
        $this->rollback();
        throw $e;
    }
}
```

**Note:** Migrations automatically use transactions. Manual control is rarely needed.

---

## Examples

### Example 1: Create Users Table

```php
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('users', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'email' => 'VARCHAR(255) NOT NULL UNIQUE',
            'password' => 'VARCHAR(255) NOT NULL',
            'name' => 'VARCHAR(255)',
            'is_active' => 'BOOLEAN DEFAULT TRUE',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);

        $this->createIndex('users', 'idx_users_email', ['email'], true);
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
```

---

### Example 2: Add Column to Existing Table

```php
class AddPhoneToUsers extends Migration
{
    public function up()
    {
        $this->addColumn('users', 'phone', 'VARCHAR(20)');
        $this->createIndex('users', 'idx_users_phone', ['phone']);
    }

    public function down()
    {
        $this->dropIndex('users', 'idx_users_phone');
        $this->dropColumn('users', 'phone');
    }
}
```

---

### Example 3: Create Related Tables with Foreign Keys

```php
class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->createTable('posts', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'INT NOT NULL',
            'title' => 'VARCHAR(255) NOT NULL',
            'content' => 'TEXT',
            'status' => "ENUM('draft', 'published') DEFAULT 'draft'",
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);

        // Add foreign key
        $this->execute("ALTER TABLE posts
            ADD CONSTRAINT fk_posts_user_id
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE");

        // Add indexes
        $this->createIndex('posts', 'idx_posts_user_id', ['user_id']);
        $this->createIndex('posts', 'idx_posts_status', ['status']);
    }

    public function down()
    {
        $this->dropTable('posts');
    }
}
```

---

### Example 4: Seed Data

```php
class SeedAdminUser extends Migration
{
    public function up()
    {
        $this->insert('users', [
            'email' => 'admin@example.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'name' => 'Administrator',
            'is_active' => 1
        ]);
    }

    public function down()
    {
        $this->execute("DELETE FROM users WHERE email = 'admin@example.com'");
    }
}
```

---

## Best Practices

### 1. Descriptive Names

✅ **Good:**
```bash
php migrate.php create create_users_table
php migrate.php create add_email_index_to_users
php migrate.php create remove_deprecated_columns
```

❌ **Bad:**
```bash
php migrate.php create migration1
php migrate.php create update_db
php migrate.php create fix
```

---

### 2. Atomic Migrations

Each migration should do ONE thing:

✅ **Good:**
- `create_users_table.php`
- `add_phone_to_users.php`
- `create_posts_table.php`

❌ **Bad:**
- `create_all_tables.php` (does too much)

---

### 3. Always Write down()

Never leave `down()` empty. Every change must be reversible:

✅ **Good:**
```php
public function up()
{
    $this->createTable('users', [...]);
}

public function down()
{
    $this->dropTable('users');
}
```

❌ **Bad:**
```php
public function down()
{
    // TODO: implement later
}
```

---

### 4. Test Before Committing

```bash
# Run migration
php migrate.php migrate

# Test rollback
php migrate.php rollback

# Re-run migration
php migrate.php migrate
```

---

### 5. Never Modify Executed Migrations

Once a migration runs in production, create a NEW migration for changes:

✅ **Good:**
```bash
php migrate.php create add_username_to_users
```

❌ **Bad:**
Editing `create_users_table.php` after it's deployed

---

### 6. Database-Specific Considerations

#### MySQL

```php
$this->createTable('users', [...], [
    'engine' => 'InnoDB',
    'charset' => 'utf8mb4'
]);
```

#### PostgreSQL

```php
// Use SERIAL instead of AUTO_INCREMENT
'id' => 'SERIAL PRIMARY KEY'
```

#### SQLite

```php
// Use INTEGER PRIMARY KEY for auto-increment
'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT'
```

---

## Troubleshooting

### Migration Failed

**Symptom:**
```
✗ Migration failed: SQLSTATE[42000]: Syntax error
```

**Solution:**
1. Check SQL syntax in migration file
2. Verify table/column names don't conflict
3. Check database driver compatibility (MySQL vs PostgreSQL)

---

### Rollback Failed

**Symptom:**
```
✗ Rollback failed: Table 'users' doesn't exist
```

**Solution:**
- Ensure `down()` method matches what `up()` created
- Check if table was manually deleted
- Use `php migrate.php reset` to clear and re-run

---

### Migration File Not Found

**Symptom:**
```
Migration file not found: migrations/2025_10_29_123456_create_users.php
```

**Solution:**
- Ensure migration file exists in `migrations/` directory
- Check filename matches exactly (case-sensitive)
- Don't manually rename migration files

---

### Foreign Key Constraint Fails

**Symptom:**
```
Cannot add foreign key constraint
```

**Solution:**
1. Ensure referenced table exists first
2. Run migrations in correct order
3. Check column types match (both INT, both VARCHAR, etc.)

---

## Advanced Usage

### Conditional Logic

```php
public function up()
{
    if (!$this->tableExists('users')) {
        $this->createTable('users', [...]);
    }

    // Check database driver
    if ($this->driver === 'pdo_mysql') {
        $this->execute("ALTER TABLE users ENGINE=InnoDB");
    }
}
```

---

### Multi-Database Compatibility

```php
public function up()
{
    if ($this->driver === 'pdo_mysql') {
        $this->execute("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ...
        ) ENGINE=InnoDB");
    } elseif ($this->driver === 'pdo_pgsql') {
        $this->execute("CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            ...
        )");
    } elseif ($this->driver === 'pdo_sqlite') {
        $this->execute("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ...
        )");
    }
}
```

---

## Integration with PHPWeave

### In Models

```php
// After running migrations, models work automatically
class user_model extends DBConnection {
    function getUser($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
        return $this->fetch($stmt);
    }
}
```

---

### In Controllers

```php
class Admin extends Controller {
    function migrate() {
        // Example: Trigger migration from controller
        require_once PHPWEAVE_ROOT . '/coreapp/migrationrunner.php';
        $runner = new MigrationRunner();
        $runner->migrate(false); // false = no verbose output

        echo "Migrations complete";
    }
}
```

---

## Migration Workflow

### Development

```bash
# 1. Create migration
php migrate.php create add_feature

# 2. Edit migration file
nano migrations/2025_10_29_123456_add_feature.php

# 3. Run migration
php migrate.php migrate

# 4. Test feature
# ... test your code ...

# 5. Rollback if needed
php migrate.php rollback

# 6. Fix and re-run
php migrate.php migrate
```

---

### Production Deployment

```bash
# 1. Pull latest code
git pull origin main

# 2. Check migration status
php migrate.php status

# 3. Run pending migrations
php migrate.php migrate

# 4. Verify
php migrate.php status
```

---

## FAQ

**Q: Can I run migrations automatically on deployment?**
A: Yes, add `php migrate.php migrate` to your deployment script.

**Q: How do I share migrations with my team?**
A: Commit migration files to Git. Team members run `php migrate.php migrate`.

**Q: What if a migration fails halfway through?**
A: Migrations use transactions - changes are automatically rolled back on error.

**Q: Can I use migrations with existing databases?**
A: Yes! Migrations only apply changes you define. Existing tables are untouched.

**Q: How do I handle environment-specific data?**
A: Use separate seed migrations or check environment variables in `up()`.

---

## Additional Resources

- [PHPWeave Documentation](../README.md)
- [Connection Pooling](CONNECTION_POOLING.md)
- [Multi-Database Support](DOCKER_DATABASE_SUPPORT.md)
- [Docker Deployment](DOCKER_DEPLOYMENT.md)

---

**Last Updated:** October 2025
**Version:** 2.2.0
**Author:** Clint Christopher Canada
