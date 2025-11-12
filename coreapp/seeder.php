<?php
/**
 * Database Seeder Base Class
 *
 * Provides base functionality for database seeding.
 * Seeders are used to populate databases with test/demo data separate from migrations.
 *
 * Features:
 * - Insert data into tables
 * - Truncate tables before seeding
 * - Call other seeders
 * - Transaction support
 * - Environment-aware seeding
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     PHPWeave Development Team
 * @version    2.4.0
 *
 * @example
 * class UserSeeder extends Seeder {
 *     public function run() {
 *         $this->truncate('users');
 *         $this->insert('users', [
 *             ['name' => 'Admin', 'email' => 'admin@example.com'],
 *             ['name' => 'User', 'email' => 'user@example.com']
 *         ]);
 *     }
 * }
 */
class Seeder
{
    /**
     * Database connection instance
     * @var DBConnection
     */
    protected $db;

    /**
     * Query Builder trait (if available)
     * @var mixed
     */
    protected $query;

    /**
     * Executed seeders (to prevent circular dependencies)
     * @var array
     */
    protected static $executedSeeders = [];

    /**
     * Output messages flag
     * @var bool
     */
    protected $silent = false;

    /**
     * Constructor
     *
     * @param DBConnection|null $db Database connection (optional)
     */
    public function __construct($db = null)
    {
        if ($db === null) {
            require_once __DIR__ . '/dbconnection.php';
            $this->db = new DBConnection();
        } else {
            $this->db = $db;
        }

        // Check if Query Builder is available
        if (trait_exists('QueryBuilder')) {
            require_once __DIR__ . '/querybuilder.php';
            $this->query = new class extends DBConnection {
                use QueryBuilder;
            };
        }
    }

    /**
     * Run the seeder
     *
     * This method should be overridden in child classes.
     *
     * @return void
     */
    public function run()
    {
        // Override this method in child classes
    }

    /**
     * Insert data into a table
     *
     * @param string $table Table name
     * @param array $data Array of records to insert
     * @return void
     *
     * @example
     * $this->insert('users', [
     *     ['name' => 'John', 'email' => 'john@example.com'],
     *     ['name' => 'Jane', 'email' => 'jane@example.com']
     * ]);
     */
    protected function insert($table, array $data)
    {
        if (empty($data)) {
            return;
        }

        // Use Query Builder if available
        if ($this->query && method_exists($this->query, 'table')) {
            foreach ($data as $record) {
                $this->query->table($table)->insert($record);
            }
            $this->output("✓ Inserted " . count($data) . " records into {$table}");
            return;
        }

        // Fall back to raw SQL
        foreach ($data as $record) {
            $columns = array_keys($record);
            $values = array_values($record);

            $columnList = implode(', ', $columns);
            $placeholders = ':' . implode(', :', $columns);

            $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";

            $bindings = [];
            foreach ($record as $key => $value) {
                $bindings[':' . $key] = $value;
            }

            $this->db->executePreparedSQL($sql, $bindings);
        }

        $this->output("✓ Inserted " . count($data) . " records into {$table}");
    }

    /**
     * Truncate a table
     *
     * @param string $table Table name
     * @return void
     *
     * @example
     * $this->truncate('users');
     */
    protected function truncate($table)
    {
        // Get database driver
        $driver = $this->db->driver;

        try {
            // Different drivers have different truncate syntax
            switch ($driver) {
                case 'pdo_sqlite':
                    $this->db->executePreparedSQL("DELETE FROM {$table}");
                    $this->db->executePreparedSQL("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
                    break;

                case 'pdo_pgsql':
                    $this->db->executePreparedSQL("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
                    break;

                default:
                    $this->db->executePreparedSQL("TRUNCATE TABLE {$table}");
                    break;
            }

            $this->output("✓ Truncated table {$table}");
        } catch (Exception $e) {
            $this->output("⚠ Could not truncate {$table}: " . $e->getMessage());
        }
    }

    /**
     * Delete all records from a table
     *
     * @param string $table Table name
     * @return void
     *
     * @example
     * $this->delete('users');
     */
    protected function delete($table)
    {
        if (is_object($this->query) && method_exists($this->query, 'table')) {
            $count = $this->query->table($table)->count();
            $this->query->raw("DELETE FROM {$table}");
            $this->output("✓ Deleted {$count} records from {$table}");
        } else {
            $this->db->executePreparedSQL("DELETE FROM {$table}");
            $this->output("✓ Deleted all records from {$table}");
        }
    }

    /**
     * Call another seeder
     *
     * @param string $seederClass Seeder class name
     * @return void
     *
     * @example
     * $this->call(UserSeeder::class);
     * $this->call('UserSeeder');
     */
    protected function call($seederClass)
    {
        // Prevent circular dependencies
        if (in_array($seederClass, self::$executedSeeders)) {
            $this->output("⚠ Seeder {$seederClass} already executed, skipping");
            return;
        }

        if (!class_exists($seederClass)) {
            throw new Exception("Seeder class {$seederClass} not found");
        }

        self::$executedSeeders[] = $seederClass;

        $seeder = new $seederClass($this->db);
        $seeder->setSilent($this->silent);

        $this->output("Running seeder: {$seederClass}");
        $seeder->run();
    }

    /**
     * Execute raw SQL
     *
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return PDOStatement
     *
     * @example
     * $this->execute("UPDATE users SET status = :status WHERE id = :id", [
     *     'status' => 'active',
     *     'id' => 1
     * ]);
     */
    protected function execute($sql, array $bindings = [])
    {
        return $this->db->executePreparedSQL($sql, $bindings);
    }

    /**
     * Get database connection
     *
     * @return DBConnection
     */
    protected function getConnection()
    {
        return $this->db;
    }

    /**
     * Get Query Builder instance
     *
     * @return mixed Query Builder instance or null
     */
    protected function query()
    {
        return $this->query;
    }

    /**
     * Check if running in specific environment
     *
     * @param string $environment Environment name (e.g., 'production', 'development')
     * @return bool
     *
     * @example
     * if ($this->environment('development')) {
     *     // Only seed in development
     * }
     */
    protected function environment($environment)
    {
        $currentEnv = getenv('APP_ENV') ?: ($GLOBALS['configs']['APP_ENV'] ?? 'production');
        return $currentEnv === $environment;
    }

    /**
     * Set silent mode
     *
     * @param bool $silent Silent mode flag
     * @return void
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
    }

    /**
     * Output message
     *
     * @param string $message Message to output
     * @return void
     */
    protected function output($message)
    {
        if (!$this->silent) {
            echo $message . "\n";
        }
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    protected function beginTransaction()
    {
        $this->db->connect();
        $this->db->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    protected function commit()
    {
        $this->db->pdo->commit();
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    protected function rollback()
    {
        $this->db->pdo->rollBack();
    }

    /**
     * Reset executed seeders list
     *
     * @return void
     */
    public static function reset()
    {
        self::$executedSeeders = [];
    }

    /**
     * Create a factory instance
     *
     * @param string $factoryClass Factory class name
     * @return mixed Factory instance
     *
     * @example
     * $this->factory(UserFactory::class)->create(10);
     */
    protected function factory($factoryClass)
    {
        if (!class_exists($factoryClass)) {
            throw new Exception("Factory class {$factoryClass} not found");
        }

        return new $factoryClass($this->db);
    }

    /**
     * Get current timestamp
     *
     * @param string $format Date format
     * @return string Current timestamp
     */
    protected function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }

    /**
     * Generate a random string
     *
     * @param int $length String length
     * @return string Random string
     */
    protected function randomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, $max)];
        }

        return $string;
    }

    /**
     * Generate a random email
     *
     * @param string|null $domain Email domain
     * @return string Random email
     */
    protected function randomEmail($domain = 'example.com')
    {
        return strtolower($this->randomString(8)) . '@' . $domain;
    }

    /**
     * Generate a random number
     *
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Random number
     */
    protected function randomNumber($min = 0, $max = 100)
    {
        return random_int($min, $max);
    }
}
