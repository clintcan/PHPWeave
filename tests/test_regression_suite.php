<?php
/**
 * PHPWeave Regression Test Suite
 *
 * Comprehensive regression testing to detect any functionality breakage.
 * Runs all core tests and reports detailed results.
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @category   Regression
 * @version    2.6.0
 *
 * Usage: php tests/test_regression_suite.php
 */

// Colors for terminal output
class Colors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const MAGENTA = "\033[35m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

// Test results tracker
class TestResults {
    private $suites = [];
    private $totalTests = 0;
    private $totalPassed = 0;
    private $totalFailed = 0;
    private $totalSkipped = 0;
    private $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
    }

    public function addSuite($name, $passed, $failed, $skipped = 0, $duration = 0, $output = '') {
        $this->suites[] = [
            'name' => $name,
            'passed' => $passed,
            'failed' => $failed,
            'skipped' => $skipped,
            'duration' => $duration,
            'output' => $output
        ];
        $this->totalTests += ($passed + $failed);
        $this->totalPassed += $passed;
        $this->totalFailed += $failed;
        $this->totalSkipped += $skipped;
    }

    public function getTotalTests() { return $this->totalTests; }
    public function getTotalPassed() { return $this->totalPassed; }
    public function getTotalFailed() { return $this->totalFailed; }
    public function getTotalSkipped() { return $this->totalSkipped; }
    public function getDuration() { return microtime(true) - $this->startTime; }
    public function getSuites() { return $this->suites; }
}

/**
 * Test runner class
 */
class RegressionTestRunner {
    private $results;
    private $testDir;

    public function __construct() {
        $this->results = new TestResults();
        $this->testDir = __DIR__;
    }

    /**
     * Print header
     */
    public function printHeader() {
        $width = 70;
        echo "\n";
        echo Colors::BLUE . Colors::BOLD . str_repeat("═", $width) . Colors::RESET . "\n";
        echo Colors::BLUE . Colors::BOLD . "║" . str_pad(" PHPWEAVE REGRESSION TEST SUITE v2.6.0", $width - 2) . "║" . Colors::RESET . "\n";
        echo Colors::BLUE . Colors::BOLD . str_repeat("═", $width) . Colors::RESET . "\n";
        echo Colors::CYAN . "Testing for functionality regressions...\n" . Colors::RESET;
        echo Colors::CYAN . "Date: " . date('Y-m-d H:i:s') . "\n" . Colors::RESET;
        echo Colors::BLUE . str_repeat("─", $width) . Colors::RESET . "\n\n";
    }

    /**
     * Run a test file
     */
    private function runTestFile($testFile, $testName) {
        echo Colors::CYAN . "Running: " . Colors::BOLD . $testName . Colors::RESET . "\n";

        $start = microtime(true);
        $output = [];
        $returnCode = 0;

        // Execute test file
        exec("php " . escapeshellarg($testFile) . " 2>&1", $output, $returnCode);
        $duration = microtime(true) - $start;
        $outputStr = implode("\n", $output);

        // Parse results
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        // Try to extract pass/fail counts from output
        if (preg_match('/(\d+)\s+tests?\s+passed/i', $outputStr, $matches)) {
            $passed = (int)$matches[1];
        } elseif (preg_match('/Passed:\s*(\d+)/i', $outputStr, $matches)) {
            $passed = (int)$matches[1];
        } elseif (preg_match_all('/✓\s*PASS/i', $outputStr, $matches)) {
            $passed = count($matches[0]);
        }

        if (preg_match('/(\d+)\s+tests?\s+failed/i', $outputStr, $matches)) {
            $failed = (int)$matches[1];
        } elseif (preg_match('/Failed:\s*(\d+)/i', $outputStr, $matches)) {
            $failed = (int)$matches[1];
        } elseif (preg_match_all('/✗\s*FAIL/i', $outputStr, $matches)) {
            $failed = count($matches[0]);
        }

        // Check return code
        if ($returnCode !== 0) {
            if ($failed === 0) {
                // If no failures detected but non-zero exit code, mark as failed
                $failed = 1;
            }
        }

        // Display result
        if ($returnCode === 0 && $failed === 0) {
            echo Colors::GREEN . "  ✓ PASSED" . Colors::RESET;
            echo Colors::YELLOW . " [{$passed} tests, " . round($duration * 1000, 2) . "ms]" . Colors::RESET . "\n";
        } else {
            echo Colors::RED . "  ✗ FAILED" . Colors::RESET;
            echo Colors::YELLOW . " [{$failed} failures, " . round($duration * 1000, 2) . "ms]" . Colors::RESET . "\n";
        }

        $this->results->addSuite($testName, $passed, $failed, $skipped, $duration, $outputStr);

        return $returnCode === 0;
    }

    /**
     * Run all regression tests
     */
    public function runAllTests() {
        $this->printHeader();

        // Define test suites in execution order
        $testSuites = [
            // Core functionality tests
            ['file' => 'test_hooks.php', 'name' => 'Hooks System'],
            ['file' => 'test_controllers.php', 'name' => 'Controllers'],
            ['file' => 'test_models.php', 'name' => 'Models & Database'],
            ['file' => 'test_libraries.php', 'name' => 'Libraries'],

            // Query Builder
            ['file' => 'test_query_builder.php', 'name' => 'Query Builder'],

            // Caching
            ['file' => 'test_cache_basic.php', 'name' => 'Basic Caching'],
            ['file' => 'test_advanced_caching.php', 'name' => 'Advanced Caching'],
            ['file' => 'test_docker_caching.php', 'name' => 'Docker Caching'],
            ['file' => 'test_env_caching.php', 'name' => 'Environment Caching'],

            // Sessions
            ['file' => 'test_sessions.php', 'name' => 'Session Management'],

            // Security
            ['file' => 'test_security_features.php', 'name' => 'Security Features'],
            ['file' => 'test_path_traversal.php', 'name' => 'Path Traversal Protection'],

            // Database modes
            ['file' => 'test_database_modes.php', 'name' => 'Database Modes'],
            ['file' => 'test_connection_pool.php', 'name' => 'Connection Pooling'],

            // Async & HTTP
            ['file' => 'test_http_async.php', 'name' => 'HTTP Async'],
            ['file' => 'test_async_params.php', 'name' => 'Async Parameters'],
        ];

        echo Colors::BOLD . "Core Functionality Tests:\n" . Colors::RESET;
        echo Colors::BLUE . str_repeat("─", 70) . Colors::RESET . "\n";

        foreach ($testSuites as $suite) {
            $testPath = $this->testDir . '/' . $suite['file'];

            if (!file_exists($testPath)) {
                echo Colors::YELLOW . "  ⚠ SKIPPED: " . $suite['name'] . " (file not found)" . Colors::RESET . "\n";
                $this->results->addSuite($suite['name'], 0, 0, 1);
                continue;
            }

            $this->runTestFile($testPath, $suite['name']);
            echo "\n";

            // Small delay to prevent resource issues
            usleep(100000); // 100ms
        }

        $this->printSummary();
    }

    /**
     * Print test summary
     */
    private function printSummary() {
        $width = 70;
        echo "\n";
        echo Colors::BLUE . Colors::BOLD . str_repeat("═", $width) . Colors::RESET . "\n";
        echo Colors::BLUE . Colors::BOLD . "║" . str_pad(" TEST SUMMARY", $width - 2) . "║" . Colors::RESET . "\n";
        echo Colors::BLUE . Colors::BOLD . str_repeat("═", $width) . Colors::RESET . "\n\n";

        $totalTests = $this->results->getTotalTests();
        $totalPassed = $this->results->getTotalPassed();
        $totalFailed = $this->results->getTotalFailed();
        $totalSkipped = $this->results->getTotalSkipped();
        $duration = $this->results->getDuration();

        // Overall stats
        echo Colors::CYAN . "Total Test Suites: " . Colors::BOLD . count($this->results->getSuites()) . Colors::RESET . "\n";
        echo Colors::CYAN . "Total Tests Run:   " . Colors::BOLD . $totalTests . Colors::RESET . "\n";
        echo Colors::GREEN . "Passed:            " . Colors::BOLD . $totalPassed . Colors::RESET . "\n";

        if ($totalFailed > 0) {
            echo Colors::RED . "Failed:            " . Colors::BOLD . $totalFailed . Colors::RESET . "\n";
        } else {
            echo Colors::GREEN . "Failed:            " . Colors::BOLD . "0" . Colors::RESET . "\n";
        }

        if ($totalSkipped > 0) {
            echo Colors::YELLOW . "Skipped:           " . Colors::BOLD . $totalSkipped . Colors::RESET . "\n";
        }

        // Success rate
        $successRate = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;
        $rateColor = $successRate >= 95 ? Colors::GREEN : ($successRate >= 80 ? Colors::YELLOW : Colors::RED);
        echo $rateColor . "Success Rate:      " . Colors::BOLD . round($successRate, 2) . "%" . Colors::RESET . "\n";

        // Duration
        echo Colors::CYAN . "Total Duration:    " . Colors::BOLD . round($duration, 2) . "s" . Colors::RESET . "\n";

        echo "\n";
        echo Colors::BLUE . str_repeat("─", $width) . Colors::RESET . "\n";

        // Detailed suite breakdown
        echo "\n" . Colors::BOLD . "Suite Breakdown:\n" . Colors::RESET;
        echo Colors::BLUE . str_repeat("─", $width) . Colors::RESET . "\n";

        foreach ($this->results->getSuites() as $suite) {
            $statusIcon = $suite['failed'] === 0 ? Colors::GREEN . "✓" : Colors::RED . "✗";
            $suiteName = str_pad($suite['name'], 35);
            $stats = sprintf("%d passed, %d failed", $suite['passed'], $suite['failed']);
            $duration = round($suite['duration'] * 1000, 2) . "ms";

            echo $statusIcon . Colors::RESET . " " . $suiteName . " " . Colors::YELLOW . $stats . Colors::RESET . " (" . $duration . ")\n";
        }

        echo "\n";
        echo Colors::BLUE . str_repeat("═", $width) . Colors::RESET . "\n";

        // Final verdict
        if ($totalFailed === 0) {
            echo Colors::GREEN . Colors::BOLD . "\n✓✓✓ ALL TESTS PASSED - NO REGRESSIONS DETECTED ✓✓✓\n\n" . Colors::RESET;
            return 0;
        } else {
            echo Colors::RED . Colors::BOLD . "\n✗✗✗ REGRESSIONS DETECTED - REVIEW FAILURES ABOVE ✗✗✗\n\n" . Colors::RESET;

            // Show failed suites
            echo Colors::RED . "Failed Test Suites:\n" . Colors::RESET;
            foreach ($this->results->getSuites() as $suite) {
                if ($suite['failed'] > 0) {
                    echo Colors::RED . "  - " . $suite['name'] . " (" . $suite['failed'] . " failures)\n" . Colors::RESET;
                }
            }
            echo "\n";

            return 1;
        }
    }

    /**
     * Get exit code
     */
    public function getExitCode() {
        return $this->results->getTotalFailed() > 0 ? 1 : 0;
    }
}

// =====================================================
// MAIN EXECUTION
// =====================================================

try {
    $runner = new RegressionTestRunner();
    $runner->runAllTests();
    exit($runner->getExitCode());
} catch (Exception $e) {
    echo Colors::RED . "\nFATAL ERROR: " . $e->getMessage() . "\n" . Colors::RESET;
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
