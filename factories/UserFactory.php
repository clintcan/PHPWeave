<?php
/**
 * User Factory
 *
 * Generates fake user data for testing and development.
 * Works with or without Faker library.
 *
 * @package    PHPWeave
 * @subpackage Factories
 * @category   Database
 *
 * @example
 * // Create a single user
 * UserFactory::new()->create();
 *
 * // Create multiple users
 * UserFactory::new()->create(10);
 *
 * // Create admin user
 * UserFactory::new()->admin()->create();
 *
 * // Create with specific attributes
 * UserFactory::new()->create(['email' => 'specific@example.com']);
 */
class UserFactory extends Factory
{
    /**
     * Table name
     * @var string
     */
    protected $table = 'users';

    /**
     * Define the model's default state
     *
     * @return array Default user attributes
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active',
            'created_at' => $this->now()
        ];
    }

    /**
     * Create an admin user
     *
     * @return self
     */
    public function admin()
    {
        return $this->state([
            'role' => 'admin'
        ]);
    }

    /**
     * Create an inactive user
     *
     * @return self
     */
    public function inactive()
    {
        return $this->state([
            'status' => 'inactive'
        ]);
    }

    /**
     * Create a verified user
     *
     * @return self
     */
    public function verified()
    {
        return $this->state([
            'email_verified_at' => $this->now()
        ]);
    }
}
