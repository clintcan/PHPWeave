<?php
/**
 * User Seeder
 *
 * Seeds the users table with test data.
 * This is an example seeder demonstrating various seeding techniques.
 *
 * @package    PHPWeave
 * @subpackage Seeders
 * @category   Database
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds
     *
     * @return void
     */
    public function run()
    {
        $this->output("Seeding users table...");

        // Truncate table first (optional)
        // $this->truncate('users');

        // Insert specific users
        $this->insert('users', [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active',
                'created_at' => $this->now()
            ],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active',
                'created_at' => $this->now()
            ]
        ]);

        // Use factory to create random users (if UserFactory exists)
        // if (class_exists('UserFactory')) {
        //     UserFactory::new()->create(10);
        // }

        $this->output("Users seeded successfully!");
    }
}
