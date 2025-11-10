<?php
/**
 * User Model
 *
 * Handles user-related database operations.
 * Provides methods for user retrieval, creation, and management.
 *
 * NEW in v2.4.0: Now supports Query Builder for cleaner, more expressive queries.
 * Uncomment the QueryBuilder trait below to enable fluent query syntax.
 *
 * @package    PHPWeave
 * @subpackage Models
 * @category   Models
 * @author     Clint Christopher Canada
 * @version    2.4.0
 *
 * @example
 * // Traditional way (still works):
 * global $models;
 * $user = $models['user_model']->getUser(123);
 *
 * @example
 * // With Query Builder (uncomment trait below):
 * global $PW;
 * $users = $PW->models->user_model->table('users')
 *     ->where('status', 'active')
 *     ->orderBy('created_at', 'DESC')
 *     ->get();
 */
class user_model extends DBConnection
{
    // Uncomment the line below to enable Query Builder
    // use QueryBuilder;
    /**
     * Constructor
     *
     * Initializes the model and database connection.
     * Calls parent DBConnection constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get user by ID
     *
     * Retrieves a single user record from the database by ID.
     * Uses prepared statements for security.
     *
     * @param int $id User ID
     * @return array|false User data as associative array, or false if not found
     *
     * @example
     * $user = $this->getUser(123);
     * if($user) {
     *     echo $user['name'];
     * }
     */
    public function getUser($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
        return $this->fetch($stmt);
    }
}