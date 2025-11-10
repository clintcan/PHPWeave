<?php
/**
 * Database Seeder
 *
 * Main seeder class that calls all other seeders.
 * This is the default entry point for database seeding.
 *
 * @package    PHPWeave
 * @subpackage Seeders
 * @category   Database
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds
     *
     * @return void
     */
    public function run()
    {
        $this->output("Seeding database...");

        // Call other seeders here
        // $this->call(UserSeeder::class);
        // $this->call(PostSeeder::class);

        $this->output("Database seeding completed!");
    }
}
