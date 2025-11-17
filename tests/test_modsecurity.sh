#!/bin/bash
#
# ModSecurity Integration Test Script
#
# Tests ModSecurity WAF protection by attempting various attacks.
# All attacks should be blocked with 403 Forbidden.
#
# Usage: ./tests/test_modsecurity.sh [base_url]
# Example: ./tests/test_modsecurity.sh http://localhost
#

# Configuration
BASE_URL="${1:-http://localhost}"
PASSED=0
FAILED=0
TIMEOUT=10

# Colors
RED='\033[31m'
GREEN='\033[32m'
YELLOW='\033[33m'
RESET='\033[0m'

# URL encoding function (replaces Python dependency)
urlencode() {
    local string="${1}"
    local strlen=${#string}
    local encoded=""
    local pos c o

    for (( pos=0 ; pos<strlen ; pos++ )); do
        c=${string:$pos:1}
        case "$c" in
            [-_.~a-zA-Z0-9] ) o="${c}" ;;
            * ) printf -v o '%%%02x' "'$c"
        esac
        encoded+="${o}"
    done
    echo "${encoded}"
}

# Arrays to store test results
declare -a TEST_NAMES
declare -a TEST_EXPECTED
declare -a TEST_ACTUAL
declare -a TEST_PASSED

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║        ModSecurity Protection Test Suite                  ║"
printf "║        Testing: %-39s║\n" "$BASE_URL"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

#
# Test helper function
#
# Args:
#   $1 - Test name
#   $2 - URL to test
#   $3 - Expect blocked (1=yes, 0=no) - default: 1
#   $4 - Custom header (optional)
#
test_attack() {
    local name="$1"
    local url="$2"
    local expect_blocked="${3:-1}"
    local custom_header="$4"

    # Build curl command
    local curl_cmd="curl -s -o /dev/null -w '%{http_code}' --max-time $TIMEOUT -L"

    # Disable SSL verification
    curl_cmd="$curl_cmd -k"

    # Add custom header if provided
    if [[ -n "$custom_header" ]]; then
        curl_cmd="$curl_cmd -H '$custom_header'"
    fi

    # Add URL
    curl_cmd="$curl_cmd '$url'"

    # Execute curl and get HTTP code
    local http_code
    http_code=$(eval "$curl_cmd" 2>&1)

    # Check for curl errors
    if [[ ! "$http_code" =~ ^[0-9]{3}$ ]]; then
        http_code="ERROR"
    fi

    # Determine if blocked
    local is_blocked=0
    if [[ "$http_code" == "403" ]]; then
        is_blocked=1
    fi

    # Check if test passed
    local test_passed=0
    if [[ "$is_blocked" == "$expect_blocked" ]]; then
        test_passed=1
        ((PASSED++))
        status="${GREEN}✓ PASS${RESET}"
    else
        ((FAILED++))
        status="${RED}✗ FAIL${RESET}"
    fi

    # Store test results
    TEST_NAMES+=("$name")
    TEST_EXPECTED+=("$([ "$expect_blocked" -eq 1 ] && echo 'Blocked (403)' || echo 'Allowed (200)')")
    TEST_ACTUAL+=("$([ "$http_code" != "ERROR" ] && echo "HTTP $http_code" || echo "$http_code")")
    TEST_PASSED+=("$test_passed")

    printf "%-50s %b\n" "$name" "$status"
}

# Test Categories
echo "Testing OWASP Top 10 Protection:"
echo "─────────────────────────────────────────────────────────────"

# 1. SQL Injection
test_attack \
    "SQL Injection (UNION)" \
    "$BASE_URL/?id=$(urlencode "1' UNION SELECT NULL--")"

test_attack \
    "SQL Injection (Boolean)" \
    "$BASE_URL/?id=$(urlencode "1' OR '1'='1")"

test_attack \
    "SQL Injection (Time-based)" \
    "$BASE_URL/?id=$(urlencode "1'; WAITFOR DELAY '00:00:05'--")"

# 2. Cross-Site Scripting (XSS)
test_attack \
    "XSS (Script tag)" \
    "$BASE_URL/?q=$(urlencode "<script>alert('XSS')</script>")"

test_attack \
    "XSS (Event handler)" \
    "$BASE_URL/?q=$(urlencode "<img src=x onerror=alert(1)>")"

test_attack \
    "XSS (JavaScript protocol)" \
    "$BASE_URL/?url=$(urlencode "javascript:alert(1)")"

# 3. Path Traversal / Local File Inclusion
test_attack \
    "Path Traversal (Linux)" \
    "$BASE_URL/?file=../../../etc/passwd"

test_attack \
    "Path Traversal (Windows)" \
    "$BASE_URL/?file=..\\..\\..\\windows\\system32\\config\\sam"

test_attack \
    "Path Traversal (Encoded)" \
    "$BASE_URL/?file=%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd"

# 4. Remote Code Execution
test_attack \
    "RCE (PHP eval)" \
    "$BASE_URL/?cmd=$(urlencode "<?php system('ls'); ?>")"

test_attack \
    "RCE (Command injection)" \
    "$BASE_URL/?cmd=$(urlencode "ls; cat /etc/passwd")"

test_attack \
    "RCE (Shell command)" \
    "$BASE_URL/?cmd=$(urlencode "| whoami")"

# 5. File Upload Attacks
test_attack \
    "File Upload (PHP backdoor)" \
    "$BASE_URL/?upload=$(urlencode "<?php eval(\$_POST[1]);?>")"

# 6. XML External Entity (XXE)
test_attack \
    "XXE Attack" \
    "$BASE_URL/?xml=$(urlencode '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>')"

# 7. Server-Side Request Forgery (SSRF)
test_attack \
    "SSRF (Internal IP)" \
    "$BASE_URL/?url=$(urlencode "http://127.0.0.1/admin")"

test_attack \
    "SSRF (Metadata service)" \
    "$BASE_URL/?url=$(urlencode "http://169.254.169.254/latest/meta-data/")"

# 8. Security Bypass Attempts
test_attack \
    "User-Agent Scanner Detection" \
    "$BASE_URL/" \
    1 \
    "User-Agent: Nikto/2.1.5"

echo ""
echo "Testing PHPWeave-Specific Protection:"
echo "─────────────────────────────────────────────────────────────"

# PHPWeave-specific protections
test_attack \
    "Access to .env file" \
    "$BASE_URL/.env"

test_attack \
    "Access to composer.json" \
    "$BASE_URL/composer.json"

test_attack \
    "Access to package.json" \
    "$BASE_URL/package.json"

test_attack \
    "Access to .git directory" \
    "$BASE_URL/.git/config"

echo ""
echo "Testing Legitimate Requests (Should NOT be blocked):"
echo "─────────────────────────────────────────────────────────────"

# Legitimate requests (should pass)
test_attack \
    "Normal page request" \
    "$BASE_URL/" \
    0

test_attack \
    "Normal GET parameter" \
    "$BASE_URL/?page=1" \
    0

test_attack \
    "Normal search query" \
    "$BASE_URL/?q=hello+world" \
    0

test_attack \
    "Health check endpoint" \
    "$BASE_URL/health.php" \
    0

# Results Summary
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                    Test Results Summary                    ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

TOTAL=$((PASSED + FAILED))
printf "Total Tests:  %d\n" "$TOTAL"
printf "${GREEN}Passed:       %d${RESET}\n" "$PASSED"
printf "${RED}Failed:       %d${RESET}\n" "$FAILED"

# Calculate success rate
if [[ $TOTAL -gt 0 ]]; then
    SUCCESS_RATE=$(awk "BEGIN {printf \"%.1f\", ($PASSED / $TOTAL) * 100}")
else
    SUCCESS_RATE="0.0"
fi
printf "Success Rate: %s%%\n\n" "$SUCCESS_RATE"

# Detailed results for failures
if [[ $FAILED -gt 0 ]]; then
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗"
    echo "║                      Failed Tests                          ║"
    echo -e "╚════════════════════════════════════════════════════════════╝${RESET}"
    echo ""

    for i in "${!TEST_NAMES[@]}"; do
        if [[ "${TEST_PASSED[$i]}" == "0" ]]; then
            printf "  • %s\n" "${TEST_NAMES[$i]}"
            printf "    Expected: %s | Got: %s\n\n" "${TEST_EXPECTED[$i]}" "${TEST_ACTUAL[$i]}"
        fi
    done

    echo -e "\n${YELLOW}Troubleshooting:${RESET}"
    echo "  1. Check if ModSecurity is enabled:"
    echo "     docker exec phpweave-app apache2ctl -M | grep security2"
    echo ""
    echo "  2. Verify ModSecurity engine status:"
    echo "     docker exec phpweave-app grep SecRuleEngine /etc/modsecurity/modsecurity.conf"
    echo ""
    echo "  3. Check ModSecurity logs:"
    echo "     docker exec phpweave-app tail /var/log/apache2/modsec_audit.log"
    echo ""
fi

# Security Score
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                     Security Score                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

SCORE=$(awk "BEGIN {printf \"%.0f\", ($PASSED / $TOTAL) * 100}")

if [[ $SCORE -ge 95 ]]; then
    echo -e "${GREEN}  Grade: A+ (Excellent Security)${RESET}"
    echo "  Your ModSecurity configuration provides excellent protection."
elif [[ $SCORE -ge 85 ]]; then
    echo -e "${GREEN}  Grade: A (Good Security)${RESET}"
    echo "  Your ModSecurity configuration provides good protection."
elif [[ $SCORE -ge 75 ]]; then
    echo -e "${YELLOW}  Grade: B (Moderate Security)${RESET}"
    echo "  Consider reviewing failed tests and adjusting configuration."
elif [[ $SCORE -ge 60 ]]; then
    echo -e "${YELLOW}  Grade: C (Basic Security)${RESET}"
    echo "  Your ModSecurity may need configuration adjustments."
else
    echo -e "${RED}  Grade: F (Insufficient Security)${RESET}"
    echo "  ModSecurity may not be enabled or configured correctly."
fi

echo ""
echo "Next Steps:"
echo "  • Review ModSecurity logs for blocked requests"
echo "  • Adjust paranoia level if needed (docs/MODSECURITY_GUIDE.md)"
echo "  • Add custom rules in docker/modsecurity-custom.conf"
echo "  • Monitor false positives in production"
echo ""

# Exit code
exit $([[ $FAILED -gt 0 ]] && echo 1 || echo 0)
