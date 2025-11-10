<?php
/**
 * Database Seeder CLI Tool
 *
 * Command-line interface for running database seeders.
 * Provides commands for seeding databases with test/demo data.
 *
 * @package    PHPWeave
 * @subpackage CLI
 * @category   Database
 * @author     PHPWeave Development Team
 * @version    2.4.0
 *
 * Usage:
 *   php seed.php run                    # Run all seeders
 *   php seed.php run UserSeeder         # Run specific seeder
 *   php seed.php run --class=UserSeeder # Run specific seeder (alternative)
 *   php seed.php fresh                  # Rollback, migrate, and seed
 *   php seed.php list                   # List available seeders
 *
 * @example
 * php seed.php run
 * php seed.php run UserSeeder
 * php seed.php fresh
 */

// Prevent direct access from web
if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

// Define PHPWeave root path
define('PHPWEAVE_ROOT', __DIR__);

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $GLOBALS['configs'][trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Load core files
require_once __DIR__ . '/coreapp/dbconnection.php';
require_once __DIR__ . '/coreapp/seeder.php';
require_once __DIR__ . '/coreapp/factory.php';

// Load Query Builder if available
if (file_exists(__DIR__ . '/coreapp/querybuilder.php')) {
    require_once __DIR__ . '/coreapp/querybuilder.php';
}

/**
 * Seeder CLI Class
 */
class SeederCLI
{
    /**
     * Seeders directory path
     * @var string
     */
    private $seedersPath;

    /**
     * Factories directory path
     * @var string
     */
    private $factoriesPath;

    /**
     * Available seeders
     * @var array
     */
    private $availableSeeders = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->seedersPath = __DIR__ . '/seeders';
        $this->factoriesPath = __DIR__ . '/factories';

        // Create directories if they don't exist
        if (!is_dir($this->seedersPath)) {
            mkdir($this->seedersPath, 0755, true);
        }

        if (!is_dir($this->factoriesPath)) {
            mkdir($this->factoriesPath, 0755, true);
        }

        // Load seeders and factories
        $this->loadSeeders();
        $this->loadFactories();
    }

    /**
     * Load all seeder files
     *
     * @return void
     */
    private function loadSeeders()
    {
        if (!is_dir($this->seedersPath)) {
            return;
        }

        $files = glob($this->seedersPath . '/*.php');

        foreach ($files as $file) {
            require_once $file;
            $className = basename($file, '.php');

            if (class_exists($className)) {
                $this->availableSeeders[] = $className;
            }
        }
    }

    /**
     * Load all factory files
     *
     * @return void
     */
    private function loadFactories()
    {
        if (!is_dir($this->factoriesPath)) {
            return;
        }

        $files = glob($this->factoriesPath . '/*.php');

        foreach ($files as $file) {
            require_once $file;
        }
    }

    /**
     * Run the CLI
     *
     * @param array $argv Command-line arguments
     * @return void
     */
    public function run($argv)
    {
        $command = $argv[1] ?? 'help';

        switch ($command) {
            case 'run':
                $this->runCommand($argv);
                break;

            case 'fresh':
                $this->freshCommand();
                break;

            case 'list':
                $this->listCommand();
                break;

            case 'help':
            case '--help':
            case '-h':
                $this->showHelp();
                break;

            default:
                echo "Unknown command: {$command}\n";
                echo "Run 'php seed.php help' for usage information.\n";
                exit(1);
        }
    }

    /**
     * Run seeder(s)
     *
     * @param array $argv Command-line arguments
     * @return void
     */
    private function runCommand($argv)
    {
        $seederClass = null;

        // Check for specific seeder
        if (isset($argv[2])) {
            $seederClass = $argv[2];
        }

        // Check for --class option
        foreach ($argv as $arg) {
            if (strpos($arg, '--class=') === 0) {
                $seederClass = substr($arg, 8);
                break;
            }
        }

        if ($seederClass) {
            $this->runSeeder($seederClass);
        } else {
            $this->runAllSeeders();
        }
    }

    /**
     * Run a specific seeder
     *
     * @param string $seederClass Seeder class name
     * @return void
     */
    private function runSeeder($seederClass)
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                   PHPWeave Seeder                        ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if (!class_exists($seederClass)) {
            echo "✗ Error: Seeder class '{$seederClass}' not found.\n";
            echo "  Available seeders:\n";
            foreach ($this->availableSeeders as $seeder) {
                echo "    - {$seeder}\n";
            }
            exit(1);
        }

        echo "Running seeder: {$seederClass}\n";
        echo str_repeat('─', 60) . "\n";

        try {
            $seeder = new $seederClass();
            $start = microtime(true);

            $seeder->run();

            $duration = round((microtime(true) - $start) * 1000, 2);

            echo str_repeat('─', 60) . "\n";
            echo "✓ Seeder '{$seederClass}' completed successfully\n";
            echo "  Duration: {$duration}ms\n";
            echo "\n";

            // Reset executed seeders
            Seeder::reset();

        } catch (Exception $e) {
            echo str_repeat('─', 60) . "\n";
            echo "✗ Error running seeder: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . "\n";
            echo "  Line: " . $e->getLine() . "\n";
            echo "\n";
            exit(1);
        }
    }

    /**
     * Run all seeders
     *
     * @return void
     */
    private function runAllSeeders()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                   PHPWeave Seeder                        ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if (empty($this->availableSeeders)) {
            echo "⚠ No seeders found in seeders/ directory.\n";
            echo "  Create a seeder class extending Seeder to get started.\n";
            echo "\n";
            return;
        }

        echo "Running all seeders...\n";
        echo str_repeat('─', 60) . "\n";

        $totalStart = microtime(true);
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->availableSeeders as $seederClass) {
            try {
                echo "\n→ {$seederClass}\n";

                $seeder = new $seederClass();
                $start = microtime(true);

                $seeder->run();

                $duration = round((microtime(true) - $start) * 1000, 2);
                echo "  ✓ Completed in {$duration}ms\n";

                $successCount++;

            } catch (Exception $e) {
                echo "  ✗ Failed: " . $e->getMessage() . "\n";
                $failureCount++;
            }
        }

        $totalDuration = round((microtime(true) - $totalStart) * 1000, 2);

        echo "\n";
        echo str_repeat('─', 60) . "\n";
        echo "Summary:\n";
        echo "  ✓ Successful: {$successCount}\n";

        if ($failureCount > 0) {
            echo "  ✗ Failed: {$failureCount}\n";
        }

        echo "  Total duration: {$totalDuration}ms\n";
        echo "\n";

        // Reset executed seeders
        Seeder::reset();

        if ($failureCount > 0) {
            exit(1);
        }
    }

    /**
     * Fresh command - rollback, migrate, and seed
     *
     * @return void
     */
    private function freshCommand()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║              PHPWeave Fresh Migration                    ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Check if migrate.php exists
        if (!file_exists(__DIR__ . '/migrate.php')) {
            echo "✗ Error: migrate.php not found.\n";
            echo "  Fresh command requires migrations to be available.\n";
            echo "\n";
            exit(1);
        }

        echo "Step 1: Rolling back all migrations...\n";
        echo str_repeat('─', 60) . "\n";
        passthru('php ' . __DIR__ . '/migrate.php reset');

        echo "\nStep 2: Running migrations...\n";
        echo str_repeat('─', 60) . "\n";
        passthru('php ' . __DIR__ . '/migrate.php migrate');

        echo "\nStep 3: Running seeders...\n";
        echo str_repeat('─', 60) . "\n";
        $this->runAllSeeders();

        echo "✓ Fresh migration and seeding completed!\n";
        echo "\n";
    }

    /**
     * List available seeders
     *
     * @return void
     */
    private function listCommand()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                 Available Seeders                        ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if (empty($this->availableSeeders)) {
            echo "No seeders found in seeders/ directory.\n";
            echo "\n";
            return;
        }

        foreach ($this->availableSeeders as $seeder) {
            echo "  • {$seeder}\n";
        }

        echo "\n";
        echo "Total: " . count($this->availableSeeders) . " seeder(s)\n";
        echo "\n";
    }

    /**
     * Show help information
     *
     * @return void
     */
    private function showHelp()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                   PHPWeave Seeder                        ║\n";
        echo "║                Database Seeding Tool                     ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php seed.php <command> [options]\n";
        echo "\n";
        echo "Available Commands:\n";
        echo "  run [seeder]         Run all seeders or a specific seeder\n";
        echo "  fresh                Rollback, migrate, and seed database\n";
        echo "  list                 List all available seeders\n";
        echo "  help                 Show this help message\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php seed.php run                    # Run all seeders\n";
        echo "  php seed.php run UserSeeder         # Run UserSeeder\n";
        echo "  php seed.php run --class=UserSeeder # Run UserSeeder (alternative)\n";
        echo "  php seed.php fresh                  # Fresh migration + seed\n";
        echo "  php seed.php list                   # List available seeders\n";
        echo "\n";
        echo "Seeder Location:\n";
        echo "  Place seeder files in: seeders/\n";
        echo "  Place factory files in: factories/\n";
        echo "\n";
        echo "Documentation:\n";
        echo "  See docs/SEEDING.md for complete guide\n";
        echo "\n";
    }
}

// Run the CLI
$cli = new SeederCLI();
$cli->run($argv);
