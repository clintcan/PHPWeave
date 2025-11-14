<?php
/**
 * ModSecurity Integration Test Script
 *
 * Tests ModSecurity WAF protection by attempting various attacks.
 * All attacks should be blocked with 403 Forbidden.
 *
 * Usage: php tests/test_modsecurity.php [base_url]
 * Example: php tests/test_modsecurity.php http://localhost
 */

// Configuration
$baseUrl = $argv[1] ?? 'http://localhost';
$testResults = [];
$passed = 0;
$failed = 0;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        ModSecurity Protection Test Suite                  ║\n";
echo "║        Testing: $baseUrl" . str_repeat(' ', 39 - strlen($baseUrl)) . "║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

/**
 * Test helper function
 */
function testAttack($name, $url, $expectBlocked = true) {
    global $testResults, $passed, $failed;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $isBlocked = ($httpCode == 403);
    $testPassed = ($isBlocked === $expectBlocked);

    if ($testPassed) {
        $passed++;
        $status = "✓ PASS";
        $color = "\033[32m"; // Green
    } else {
        $failed++;
        $status = "✗ FAIL";
        $color = "\033[31m"; // Red
    }

    $testResults[] = [
        'name' => $name,
        'expected' => $expectBlocked ? 'Blocked (403)' : 'Allowed (200)',
        'actual' => "HTTP $httpCode",
        'passed' => $testPassed
    ];

    echo $color . sprintf("%-50s %s\033[0m\n", $name, $status);

    return $testPassed;
}

// Test Categories
echo "Testing OWASP Top 10 Protection:\n";
echo "─────────────────────────────────────────────────────────────\n";

// 1. SQL Injection
testAttack(
    "SQL Injection (UNION)",
    "$baseUrl/?id=1' UNION SELECT NULL--"
);

testAttack(
    "SQL Injection (Boolean)",
    "$baseUrl/?id=1' OR '1'='1"
);

testAttack(
    "SQL Injection (Time-based)",
    "$baseUrl/?id=1'; WAITFOR DELAY '00:00:05'--"
);

// 2. Cross-Site Scripting (XSS)
testAttack(
    "XSS (Script tag)",
    "$baseUrl/?q=" . urlencode("<script>alert('XSS')</script>")
);

testAttack(
    "XSS (Event handler)",
    "$baseUrl/?q=" . urlencode("<img src=x onerror=alert(1)>")
);

testAttack(
    "XSS (JavaScript protocol)",
    "$baseUrl/?url=" . urlencode("javascript:alert(1)")
);

// 3. Path Traversal / Local File Inclusion
testAttack(
    "Path Traversal (Linux)",
    "$baseUrl/?file=../../../etc/passwd"
);

testAttack(
    "Path Traversal (Windows)",
    "$baseUrl/?file=..\\..\\..\\windows\\system32\\config\\sam"
);

testAttack(
    "Path Traversal (Encoded)",
    "$baseUrl/?file=%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd"
);

// 4. Remote Code Execution
testAttack(
    "RCE (PHP eval)",
    "$baseUrl/?cmd=" . urlencode("<?php system('ls'); ?>")
);

testAttack(
    "RCE (Command injection)",
    "$baseUrl/?cmd=" . urlencode("ls; cat /etc/passwd")
);

testAttack(
    "RCE (Shell command)",
    "$baseUrl/?cmd=" . urlencode("| whoami")
);

// 5. File Upload Attacks
testAttack(
    "File Upload (PHP shell)",
    "$baseUrl/?file=" . urlencode("shell.php.jpg")
);

// 6. XML External Entity (XXE)
testAttack(
    "XXE Attack",
    "$baseUrl/?xml=" . urlencode('<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>')
);

// 7. Server-Side Request Forgery (SSRF)
testAttack(
    "SSRF (Internal IP)",
    "$baseUrl/?url=" . urlencode("http://127.0.0.1/admin")
);

testAttack(
    "SSRF (Metadata service)",
    "$baseUrl/?url=" . urlencode("http://169.254.169.254/latest/meta-data/")
);

// 8. Security Bypass Attempts
testAttack(
    "User-Agent Scanner Detection",
    "$baseUrl/"
);

echo "\nTesting PHPWeave-Specific Protection:\n";
echo "─────────────────────────────────────────────────────────────\n";

// PHPWeave-specific protections
testAttack(
    "Access to .env file",
    "$baseUrl/.env"
);

testAttack(
    "Access to composer.json",
    "$baseUrl/composer.json"
);

testAttack(
    "Access to package.json",
    "$baseUrl/package.json"
);

testAttack(
    "Access to .git directory",
    "$baseUrl/.git/config"
);

echo "\nTesting Legitimate Requests (Should NOT be blocked):\n";
echo "─────────────────────────────────────────────────────────────\n";

// Legitimate requests (should pass)
testAttack(
    "Normal page request",
    "$baseUrl/",
    false
);

testAttack(
    "Normal GET parameter",
    "$baseUrl/?page=1",
    false
);

testAttack(
    "Normal search query",
    "$baseUrl/?q=hello+world",
    false
);

testAttack(
    "Health check endpoint",
    "$baseUrl/health.php",
    false
);

// Results Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Test Results Summary                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo sprintf("Total Tests:  %d\n", $passed + $failed);
echo sprintf("\033[32mPassed:       %d\033[0m\n", $passed);
echo sprintf("\033[31mFailed:       %d\033[0m\n", $failed);
echo sprintf("Success Rate: %.1f%%\n\n", ($passed / ($passed + $failed)) * 100);

// Detailed results for failures
if ($failed > 0) {
    echo "\033[31m╔════════════════════════════════════════════════════════════╗\n";
    echo "║                      Failed Tests                          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\033[0m\n\n";

    foreach ($testResults as $result) {
        if (!$result['passed']) {
            echo sprintf(
                "  • %s\n    Expected: %s | Got: %s\n\n",
                $result['name'],
                $result['expected'],
                $result['actual']
            );
        }
    }

    echo "\n\033[33mTroubleshooting:\033[0m\n";
    echo "  1. Check if ModSecurity is enabled:\n";
    echo "     docker exec phpweave-app apache2ctl -M | grep security2\n\n";
    echo "  2. Verify ModSecurity engine status:\n";
    echo "     docker exec phpweave-app grep SecRuleEngine /etc/modsecurity/modsecurity.conf\n\n";
    echo "  3. Check ModSecurity logs:\n";
    echo "     docker exec phpweave-app tail /var/log/apache2/modsec_audit.log\n\n";
}

// Security Score
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                     Security Score                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$score = ($passed / ($passed + $failed)) * 100;

if ($score >= 95) {
    echo "\033[32m  Grade: A+ (Excellent Security)\033[0m\n";
    echo "  Your ModSecurity configuration provides excellent protection.\n";
} elseif ($score >= 85) {
    echo "\033[32m  Grade: A (Good Security)\033[0m\n";
    echo "  Your ModSecurity configuration provides good protection.\n";
} elseif ($score >= 75) {
    echo "\033[33m  Grade: B (Moderate Security)\033[0m\n";
    echo "  Consider reviewing failed tests and adjusting configuration.\n";
} elseif ($score >= 60) {
    echo "\033[33m  Grade: C (Basic Security)\033[0m\n";
    echo "  Your ModSecurity may need configuration adjustments.\n";
} else {
    echo "\033[31m  Grade: F (Insufficient Security)\033[0m\n";
    echo "  ModSecurity may not be enabled or configured correctly.\n";
}

echo "\n";
echo "Next Steps:\n";
echo "  • Review ModSecurity logs for blocked requests\n";
echo "  • Adjust paranoia level if needed (docs/MODSECURITY_GUIDE.md)\n";
echo "  • Add custom rules in docker/modsecurity-custom.conf\n";
echo "  • Monitor false positives in production\n\n";

// Exit code
exit($failed > 0 ? 1 : 0);
