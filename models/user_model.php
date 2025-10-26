<?php
/**
 * User Model
 *
 * Handles user-related database operations.
 * Provides methods for user retrieval, creation, and management.
 *
 * @package    PHPWeave
 * @subpackage Models
 * @category   Models
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * // In controller:
 * global $models;
 * $user = $models['user_model']->getUser(123);
 */
class user_model extends DBConnection
{
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