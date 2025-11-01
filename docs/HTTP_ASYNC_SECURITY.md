# HTTP Async Library - Security Best Practices

**Version:** 2.2.2+
**Last Updated:** 2025-11-01

This document provides security guidance for using the `http_async` library in production environments.

---

## Quick Security Checklist

✅ **Before deploying to production:**

- [ ] Enable SSL verification
- [ ] Validate all user-supplied URLs
- [ ] Use environment variables for API tokens
- [ ] Set appropriate timeouts
- [ ] Validate API responses
- [ ] Implement error handling
- [ ] Use HTTPS for all external APIs
- [ ] Review API permissions

---

## Critical Security Configurations

### 1. Enable SSL Verification (CRITICAL)

**❌ INSECURE (default for development):**
```php
$http->get('https://api.example.com', 'api');
// SSL verification is OFF by default
```

**✅ SECURE (required for production):**
```php
$http->get('https://api.example.com', 'api', [], [
    'ssl_verify' => true,       // Verify SSL certificate
    'ssl_verify_host' => 2      // Verify hostname
]);
```

**Why:** Prevents man-in-the-middle attacks and SSL stripping.

---

### 2. Validate User-Supplied URLs (CRITICAL)

**❌ INSECURE - SSRF Vulnerability:**
```php
// User input directly used as URL
$userUrl = $_GET['url'];
$http->get($userUrl, 'data'); // DANGEROUS!
```

**✅ SECURE - Validate and Allowlist:**
```php
function safeAPICall($userUrl) {
    // 1. Validate URL format
    if (!filter_var($userUrl, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }

    // 2. Parse URL
    $parsed = parse_url($userUrl);

    // 3. Only allow HTTPS
    if ($parsed['scheme'] !== 'https') {
        throw new Exception('Only HTTPS URLs are allowed');
    }

    // 4. Allowlist domains
    $allowedDomains = [
        'api.github.com',
        'api.stripe.com',
        'api.your-trusted-service.com'
    ];

    if (!in_array($parsed['host'], $allowedDomains)) {
        throw new Exception('Domain not in allowlist');
    }

    // 5. Block private IPs
    $ip = gethostbyname($parsed['host']);
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        throw new Exception('Private IP addresses are not allowed');
    }

    // Now safe to call
    global $PW;
    $http = $PW->libraries->http_async;
    return $http->get($userUrl, 'api', [], ['ssl_verify' => true]);
}
```

**Why:** Prevents Server-Side Request Forgery (SSRF) attacks.

---

### 3. Protect API Credentials (CRITICAL)

**❌ INSECURE - Hardcoded Credentials:**
```php
$http->get('https://api.example.com/data', 'api', [
    'Authorization: Bearer sk_live_abc123xyz456' // NEVER DO THIS!
]);
```

**✅ SECURE - Environment Variables:**
```php
// In .env file (not committed to Git)
API_TOKEN=sk_live_abc123xyz456

// In code
$token = getenv('API_TOKEN');
if (empty($token)) {
    throw new Exception('API_TOKEN not configured');
}

$http->get('https://api.example.com/data', 'api', [
    "Authorization: Bearer $token"
]);

// Clear from memory after use (optional)
unset($token);
```

**Additional Protection:**
```php
// Use PHP-FPM environment variables (not accessible via phpinfo())
// Or use a secrets manager (AWS Secrets Manager, HashiCorp Vault, etc.)
```

---

## Important Security Configurations

### 4. Set Appropriate Timeouts

**❌ INSECURE - No Timeout:**
```php
$http->get('https://slow-api.com/data', 'api');
// Could hang indefinitely
```

**✅ SECURE - Reasonable Timeouts:**
```php
$http->setTimeout(10);              // 10 seconds max
$http->setConnectTimeout(5);        // 5 seconds to connect

// Or per-request
$http->get('https://api.example.com', 'api', [], [
    'timeout' => 10,
    'connect_timeout' => 5
]);
```

**Why:** Prevents resource exhaustion and DoS.

---

### 5. Validate API Responses

**❌ INSECURE - Trust All Data:**
```php
$results = $http->executeJson();
$userId = $results['api']['json']['user_id']; // Blind trust
```

**✅ SECURE - Validate Everything:**
```php
$results = $http->executeJson();

// 1. Check HTTP status
if ($results['api']['status'] !== 200) {
    error_log('API call failed: ' . $results['api']['status']);
    throw new Exception('API request failed');
}

// 2. Check JSON decode success
if ($results['api']['json_error'] !== 'No error') {
    error_log('JSON decode failed: ' . $results['api']['json_error']);
    throw new Exception('Invalid API response');
}

// 3. Validate structure
if (!isset($results['api']['json']['user_id'])) {
    throw new Exception('Missing required field: user_id');
}

// 4. Validate data types
$userId = filter_var(
    $results['api']['json']['user_id'],
    FILTER_VALIDATE_INT
);

if ($userId === false) {
    throw new Exception('Invalid user_id format');
}

// 5. Sanitize before use in output
$userName = htmlspecialchars(
    $results['api']['json']['name'],
    ENT_QUOTES,
    'UTF-8'
);
```

---

### 6. Limit Concurrent Requests

**❌ INSECURE - Unlimited Requests:**
```php
// Could exhaust memory
foreach ($thousands_of_urls as $url) {
    $http->get($url, "req_" . uniqid());
}
$http->execute(); // Memory exhaustion!
```

**✅ SECURE - Batch Processing:**
```php
$batches = array_chunk($urls, 10); // Process 10 at a time

foreach ($batches as $batch) {
    $http->reset(); // Clear previous batch

    foreach ($batch as $i => $url) {
        $http->get($url, "req_$i");
    }

    $results = $http->execute();
    processResults($results);

    // Optional: Rate limiting
    usleep(100000); // 100ms delay between batches
}
```

---

### 7. Handle Errors Securely

**❌ INSECURE - Expose Error Details:**
```php
try {
    $results = $http->execute();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage(); // Exposes internals
}
```

**✅ SECURE - Log, Don't Expose:**
```php
try {
    $results = $http->execute();

    // Check for errors
    foreach ($results as $key => $result) {
        if (!empty($result['error'])) {
            // Log detailed error (not shown to user)
            error_log("HTTP request failed [$key]: " . $result['error']);

            // Generic message to user
            throw new Exception('External service temporarily unavailable');
        }
    }
} catch (Exception $e) {
    // Log detailed error
    error_log('HTTP async error: ' . $e->getMessage());

    // Generic user message
    return ['error' => 'Service temporarily unavailable'];
}
```

---

## Common Attack Vectors and Mitigations

### SSRF (Server-Side Request Forgery)

**Attack:**
```php
// Attacker passes internal URL
$_GET['url'] = 'http://169.254.169.254/latest/meta-data/iam/security-credentials/';
```

**Defense:**
- Use domain allowlist (recommended)
- Block private IP ranges
- Block cloud metadata IPs (169.254.169.254)
- Only allow HTTPS
- Validate URL format

### Man-in-the-Middle (MITM)

**Attack:**
- Intercept HTTP traffic
- Steal API tokens
- Modify responses

**Defense:**
```php
// ALWAYS use HTTPS in production
$http->get('https://api.example.com', 'api', [], [
    'ssl_verify' => true,
    'ssl_verify_host' => 2
]);
```

### API Token Exposure

**Attack:**
- Hardcoded tokens in Git
- Tokens in logs
- Tokens in error messages

**Defense:**
```php
// Use environment variables
$token = getenv('API_TOKEN');

// Don't log tokens
error_log('API call to: ' . $url); // OK
error_log('API call with token: ' . $token); // NEVER!

// Don't include in errors
throw new Exception('API call failed'); // OK
throw new Exception('API call failed with token: ' . $token); // NEVER!
```

### Denial of Service (DoS)

**Attack:**
- Request large files
- Queue thousands of requests
- Cause timeout cascades

**Defense:**
```php
// Limit concurrent requests
$maxRequests = 10;

// Set timeouts
$http->setTimeout(10);

// Batch processing
$batches = array_chunk($urls, 10);
```

---

## Production Deployment Checklist

### Before Going Live:

**Configuration:**
- [ ] SSL verification enabled (`ssl_verify => true`)
- [ ] Timeouts configured (10-30 seconds)
- [ ] API tokens in environment variables
- [ ] Error logging enabled
- [ ] Rate limiting implemented

**Code Review:**
- [ ] No hardcoded credentials
- [ ] All user input validated
- [ ] URL allowlist implemented
- [ ] API responses validated
- [ ] Errors handled securely

**Testing:**
- [ ] Test with invalid SSL certificates
- [ ] Test with private IP addresses
- [ ] Test with cloud metadata IPs
- [ ] Test with malformed URLs
- [ ] Test with large responses
- [ ] Test timeout behavior

**Monitoring:**
- [ ] Log failed requests
- [ ] Monitor API error rates
- [ ] Alert on SSL failures
- [ ] Track response times

---

## Example: Secure Production Implementation

```php
<?php
/**
 * Secure API Client Example
 */
class SecureAPIClient
{
    private $http;
    private $allowedDomains = [
        'api.github.com',
        'api.stripe.com'
    ];

    public function __construct()
    {
        global $PW;
        $this->http = $PW->libraries->http_async;

        // Configure security defaults
        $this->http->setTimeout(10);
        $this->http->setConnectTimeout(5);
    }

    /**
     * Make a secure API call
     */
    public function call($url, $method = 'GET', $data = [])
    {
        // 1. Validate URL
        $this->validateUrl($url);

        // 2. Get credentials securely
        $token = $this->getAPIToken();

        // 3. Make request with security options
        $headers = ["Authorization: Bearer $token"];
        $options = [
            'ssl_verify' => true,
            'ssl_verify_host' => 2
        ];

        if ($method === 'GET') {
            $this->http->get($url, 'api', $headers, $options);
        } else {
            $this->http->post($url, $data, 'api', $headers, $options);
        }

        // 4. Execute and validate
        $results = $this->http->executeJson();

        return $this->validateResponse($results['api']);
    }

    /**
     * Validate URL for SSRF protection
     */
    private function validateUrl($url)
    {
        // Format check
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }

        // Parse URL
        $parsed = parse_url($url);

        // Protocol check
        if ($parsed['scheme'] !== 'https') {
            throw new Exception('Only HTTPS is allowed');
        }

        // Domain allowlist
        if (!in_array($parsed['host'], $this->allowedDomains)) {
            throw new Exception('Domain not in allowlist');
        }

        // IP range check
        $ip = gethostbyname($parsed['host']);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new Exception('Private IPs not allowed');
        }

        return true;
    }

    /**
     * Get API token securely
     */
    private function getAPIToken()
    {
        $token = getenv('API_TOKEN');

        if (empty($token)) {
            throw new Exception('API_TOKEN not configured');
        }

        return $token;
    }

    /**
     * Validate API response
     */
    private function validateResponse($result)
    {
        // Check HTTP status
        if ($result['status'] !== 200) {
            error_log('API failed: ' . $result['status']);
            throw new Exception('API request failed');
        }

        // Check errors
        if (!empty($result['error'])) {
            error_log('API error: ' . $result['error']);
            throw new Exception('API request failed');
        }

        // Check JSON
        if ($result['json_error'] !== 'No error') {
            error_log('JSON decode failed: ' . $result['json_error']);
            throw new Exception('Invalid API response');
        }

        return $result['json'];
    }
}

// Usage
$client = new SecureAPIClient();
$data = $client->call('https://api.github.com/users/octocat');
```

---

## Additional Resources

- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **OWASP SSRF Prevention:** https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html
- **Full Security Audit:** `/SECURITY_AUDIT_HTTP_ASYNC.md`
- **PHPWeave Security Guide:** `/docs/SECURITY_BEST_PRACTICES.md`

---

**Last Updated:** 2025-11-01
**Version:** 2.2.2+
