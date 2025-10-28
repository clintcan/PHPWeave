<?php
/**
 * PHPWeave Migration CLI Tool
 *
 * Command-line tool for managing database migrations.
 *
 * Usage:
 *   php migrate.php create <migration_name>  - Create a new migration
 *   php migrate.php migrate                  - Run all pending migrations
 *   php migrate.php rollback [steps]         - Rollback last migration(s)
 *   php migrate.php reset                    - Rollback all and re-run
 *   php migrate.php status                   - Show migration status
 *   php migrate.php help                     - Show this help
 *
 * Examples:
 *   php migrate.php create create_users_table
 *   php migrate.php migrate
 *   php migrate.php rollback 2
 *   php migrate.php status
 *
 * @package    PHPWeave
 * @subpackage Tools
 * @author     Clint Christopher Canada
 * @version    2.2.0
 */

// Define root directory
define('PHPWEAVE_ROOT', __DIR__);

// Load configuration
if (file_exists(__DIR__ . '/.env')) {
    $configs = parse_ini_file(__DIR__ . '/.env');
} else {
    echo "Error: .env file not found. Please copy .env.sample to .env and configure.\n";
    exit(1);
}

// Merge with environment variables
$envVars = [
    'DBHOST' => getenv('DB_HOST') ?: getenv('DBHOST'),
    'DBNAME' => getenv('DB_NAME') ?: getenv('DBNAME'),
    'DBUSER' => getenv('DB_USER') ?: getenv('DBUSER'),
    'DBPASSWORD' => getenv('DB_PASSWORD') ?: getenv('DBPASSWORD'),
    'DBCHARSET' => getenv('DB_CHARSET') ?: getenv('DBCHARSET'),
    'DBDRIVER' => getenv('DB_DRIVER') ?: getenv('DBDRIVER'),
    'DBPORT' => getenv('DB_PORT') ?: getenv('DBPORT'),
    'DB_POOL_SIZE' => getenv('DB_POOL_SIZE'),
];

foreach ($envVars as $key => $value) {
    if ($value !== false && $value !== '') {
        $configs[$key] = $value;
    }
}

$GLOBALS['configs'] = $configs;

// Load migration classes
require_once __DIR__ . '/coreapp/migrationrunner.php';

// Parse command line arguments
$command = $argv[1] ?? 'help';
$arg1 = $argv[2] ?? null;

// Initialize migration runner
try {
    $runner = new MigrationRunner();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Execute command
try {
    switch ($command) {
        case 'create':
            if (!$arg1) {
                echo "Error: Migration name required.\n";
                echo "Usage: php migrate.php create <migration_name>\n";
                exit(1);
            }

            $filePath = $runner->create($arg1);
            echo "✓ Created migration: $filePath\n";
            break;

        case 'migrate':
        case 'up':
            echo "Running migrations...\n";
            $executed = $runner->migrate(true);

            if (empty($executed)) {
                echo "✓ Database is up to date.\n";
            } else {
                echo "✓ Executed " . count($executed) . " migration(s).\n";
            }
            break;

        case 'rollback':
        case 'down':
            $steps = $arg1 ? (int)$arg1 : 1;

            echo "Rolling back $steps batch(es)...\n";
            $rolledBack = $runner->rollback(true, $steps);

            if (empty($rolledBack)) {
                echo "✓ No migrations to rollback.\n";
            } else {
                echo "✓ Rolled back " . count($rolledBack) . " migration(s).\n";
            }
            break;

        case 'reset':
            echo "Resetting all migrations...\n";
            $result = $runner->reset(true);
            echo "✓ Database reset complete.\n";
            break;

        case 'status':
            $status = $runner->status();

            if (empty($status)) {
                echo "No migrations found.\n";
                break;
            }

            echo "\nMigration Status:\n";
            echo str_repeat('-', 80) . "\n";
            printf("%-50s %-10s %s\n", "Migration", "Status", "Batch");
            echo str_repeat('-', 80) . "\n";

            foreach ($status as $migration) {
                $statusText = $migration['executed'] ? '✓ Executed' : '✗ Pending';
                $batch = $migration['batch'] ?? '-';

                printf("%-50s %-10s %s\n",
                    substr($migration['migration'], 0, 47) . (strlen($migration['migration']) > 47 ? '...' : ''),
                    $statusText,
                    $batch
                );
            }

            echo str_repeat('-', 80) . "\n";

            $executed = array_filter($status, fn($m) => $m['executed']);
            $pending = array_filter($status, fn($m) => !$m['executed']);

            echo "\nTotal: " . count($status) . " migrations\n";
            echo "Executed: " . count($executed) . "\n";
            echo "Pending: " . count($pending) . "\n\n";
            break;

        case 'help':
        case '--help':
        case '-h':
        default:
            echo "\nPHPWeave Migration Tool v2.2.0\n";
            echo str_repeat('=', 50) . "\n\n";
            echo "Usage:\n";
            echo "  php migrate.php <command> [arguments]\n\n";
            echo "Available Commands:\n";
            echo "  create <name>     Create a new migration file\n";
            echo "  migrate           Run all pending migrations\n";
            echo "  rollback [steps]  Rollback last migration(s) (default: 1)\n";
            echo "  reset             Rollback all and re-run migrations\n";
            echo "  status            Show migration status\n";
            echo "  help              Show this help message\n\n";
            echo "Examples:\n";
            echo "  php migrate.php create create_users_table\n";
            echo "  php migrate.php migrate\n";
            echo "  php migrate.php rollback\n";
            echo "  php migrate.php rollback 3\n";
            echo "  php migrate.php status\n";
            echo "  php migrate.php reset\n\n";
            echo "Migration File Naming:\n";
            echo "  Use snake_case: create_users_table, add_email_to_users, etc.\n";
            echo "  Files are auto-prefixed with timestamp: YYYY_MM_DD_HHMMSS_name.php\n\n";
            echo "Migration Storage:\n";
            echo "  Migrations are stored in: migrations/\n";
            echo "  Migration history tracked in: migrations table\n\n";
            break;
    }

    exit(0);
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
