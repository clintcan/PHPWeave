<?php
/**
 * Database Factory Base Class
 *
 * Provides base functionality for generating test data using the factory pattern.
 * Factories can optionally integrate with Faker for realistic fake data generation.
 *
 * Features:
 * - Generate single or multiple records
 * - State modifiers for variants
 * - Faker integration (optional)
 * - Relationship support
 * - Sequence generation
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     PHPWeave Development Team
 * @version    2.4.0
 *
 * @example
 * class UserFactory extends Factory {
 *     protected $table = 'users';
 *
 *     public function definition() {
 *         return [
 *             'name' => $this->faker->name(),
 *             'email' => $this->faker->email(),
 *             'password' => password_hash('password', PASSWORD_DEFAULT)
 *         ];
 *     }
 * }
 */
abstract class Factory
{
    /**
     * Database table name
     * @var string
     */
    protected $table;

    /**
     * Database connection instance
     * @var DBConnection
     */
    protected $db;

    /**
     * Query Builder instance (if available)
     * @var mixed
     */
    protected $query;

    /**
     * Faker instance (if available)
     * @var mixed
     */
    protected $faker;

    /**
     * State modifiers
     * @var array
     */
    protected $states = [];

    /**
     * Active states for this instance
     * @var array
     */
    protected $activeStates = [];

    /**
     * Sequence counter
     * @var int
     */
    protected static $sequence = 0;

    /**
     * After create callbacks
     * @var array
     */
    protected $afterCreate = [];

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

        // Initialize Faker if available
        $this->initializeFaker();
    }

    /**
     * Initialize Faker library if available
     *
     * @return void
     */
    protected function initializeFaker()
    {
        // Check if Faker is installed
        if (class_exists('Faker\Factory')) {
            $this->faker = \Faker\Factory::create();
        } else {
            // Create a simple mock faker with basic methods
            $this->faker = new class {
                public function name() {
                    $first = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Eve', 'Frank'];
                    $last = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
                    return $first[array_rand($first)] . ' ' . $last[array_rand($last)];
                }

                public function email() {
                    return strtolower($this->randomString(8)) . '@example.com';
                }

                public function userName() {
                    return strtolower($this->randomString(8));
                }

                public function text($maxLength = 200) {
                    $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
                    $text = '';
                    while (strlen($text) < $maxLength) {
                        $text .= $words[array_rand($words)] . ' ';
                    }
                    return trim(substr($text, 0, $maxLength));
                }

                public function sentence($words = 6) {
                    return ucfirst($this->text($words * 6)) . '.';
                }

                public function paragraph($sentences = 3) {
                    $para = '';
                    for ($i = 0; $i < $sentences; $i++) {
                        $para .= $this->sentence() . ' ';
                    }
                    return trim($para);
                }

                public function randomNumber($min = 0, $max = 100) {
                    return random_int($min, $max);
                }

                public function numberBetween($min, $max) {
                    return random_int($min, $max);
                }

                public function randomDigit() {
                    return random_int(0, 9);
                }

                public function boolean($chanceOfTrue = 50) {
                    return random_int(1, 100) <= $chanceOfTrue;
                }

                public function date($format = 'Y-m-d') {
                    $timestamp = strtotime('-' . random_int(1, 365) . ' days');
                    return date($format, $timestamp);
                }

                public function dateTime() {
                    return $this->date('Y-m-d H:i:s');
                }

                public function dateTimeBetween($startDate, $endDate) {
                    $start = strtotime($startDate);
                    $end = strtotime($endDate);
                    $timestamp = random_int($start, $end);
                    return date('Y-m-d H:i:s', $timestamp);
                }

                public function url() {
                    return 'https://example.com/' . $this->randomString(8);
                }

                public function word() {
                    $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
                    return $words[array_rand($words)];
                }

                public function words($count = 3, $asText = false) {
                    $words = [];
                    for ($i = 0; $i < $count; $i++) {
                        $words[] = $this->word();
                    }
                    return $asText ? implode(' ', $words) : $words;
                }

                public function randomElement($array) {
                    return $array[array_rand($array)];
                }

                public function randomString($length = 10) {
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $string = '';
                    $max = strlen($characters) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $string .= $characters[random_int(0, $max)];
                    }
                    return $string;
                }

                public function uuid() {
                    return sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        random_int(0, 0xffff), random_int(0, 0xffff),
                        random_int(0, 0xffff),
                        random_int(0, 0x0fff) | 0x4000,
                        random_int(0, 0x3fff) | 0x8000,
                        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
                    );
                }

                public function __call($method, $args) {
                    // Fallback for any unknown method
                    return $this->randomString(10);
                }
            };
        }
    }

    /**
     * Define the model's default state
     *
     * This method should be overridden in child classes.
     *
     * @return array Default attribute values
     */
    abstract public function definition();

    /**
     * Create one or more model instances
     *
     * @param int|array $count Number of instances or array of attributes
     * @return mixed Created instance(s)
     *
     * @example
     * UserFactory::new()->create();
     * UserFactory::new()->create(5);
     * UserFactory::new()->create(['name' => 'John']);
     */
    public function create($count = 1)
    {
        if (is_array($count)) {
            return $this->createOne($count);
        }

        if ($count === 1) {
            return $this->createOne();
        }

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->createOne();
        }

        return $results;
    }

    /**
     * Create a single model instance
     *
     * @param array $attributes Override attributes
     * @return mixed Created instance
     */
    protected function createOne(array $attributes = [])
    {
        $data = array_merge($this->definition(), $this->getStateAttributes(), $attributes);

        // Insert into database
        if ($this->query && method_exists($this->query, 'table')) {
            $id = $this->query->table($this->table)->insert($data);
            $data['id'] = $id;
        } else {
            $columns = array_keys($data);
            $values = array_values($data);

            $columnList = implode(', ', $columns);
            $placeholders = ':' . implode(', :', $columns);

            $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})";

            $bindings = [];
            foreach ($data as $key => $value) {
                $bindings[':' . $key] = $value;
            }

            $this->db->executePreparedSQL($sql, $bindings);
            $data['id'] = $this->db->pdo->lastInsertId();
        }

        // Execute after create callbacks
        foreach ($this->afterCreate as $callback) {
            $callback($data);
        }

        return $data;
    }

    /**
     * Make one or more model instances without persisting
     *
     * @param int|array $count Number of instances or array of attributes
     * @return mixed Instance(s) without saving
     *
     * @example
     * $user = UserFactory::new()->make();
     * $users = UserFactory::new()->make(5);
     */
    public function make($count = 1)
    {
        if (is_array($count)) {
            return array_merge($this->definition(), $this->getStateAttributes(), $count);
        }

        if ($count === 1) {
            return array_merge($this->definition(), $this->getStateAttributes());
        }

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = array_merge($this->definition(), $this->getStateAttributes());
        }

        return $results;
    }

    /**
     * Define a state modification
     *
     * @param array $attributes State attributes
     * @return self
     *
     * @example
     * UserFactory::new()->state(['role' => 'admin'])->create();
     */
    public function state(array $attributes)
    {
        $instance = clone $this;
        $instance->activeStates[] = $attributes;
        return $instance;
    }

    /**
     * Get merged state attributes
     *
     * @return array Merged attributes from all active states
     */
    protected function getStateAttributes()
    {
        $attributes = [];

        foreach ($this->activeStates as $state) {
            $attributes = array_merge($attributes, $state);
        }

        return $attributes;
    }

    /**
     * Set a callback to be run after creation
     *
     * @param callable $callback Callback function
     * @return self
     *
     * @example
     * UserFactory::new()
     *     ->afterCreating(function($user) {
     *         // Create related profile
     *     })
     *     ->create();
     */
    public function afterCreating(callable $callback)
    {
        $instance = clone $this;
        $instance->afterCreate[] = $callback;
        return $instance;
    }

    /**
     * Create a new factory instance
     *
     * @return static
     */
    public static function new()
    {
        return new static();
    }

    /**
     * Get next sequence number
     *
     * @return int Sequence number
     */
    protected function sequence()
    {
        return ++self::$sequence;
    }

    /**
     * Reset sequence counter
     *
     * @return void
     */
    public static function resetSequence()
    {
        self::$sequence = 0;
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
     * Get current timestamp
     *
     * @param string $format Date format
     * @return string Current timestamp
     */
    protected function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }
}
