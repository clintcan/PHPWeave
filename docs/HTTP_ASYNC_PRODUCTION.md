# HTTP Async Library - Production Configuration Guide

**Version:** 2.2.2+
**Security:** Production-Ready with Secure Defaults

This guide explains how to configure the `http_async` library for different environments.

---

## Quick Start

### Production Mode (Secure by Default)

```php
<?php
// Production mode is ON by default
global $PW;
$http = $PW->libraries->http_async;

// Configure allowed domains (recommended for SSRF protection)
$http->setAllowedDomains([
    'api.github.com',
    'api.stripe.com',
    'api.your-trusted-service.com'
]);

// Make secure requests
$results = $http->get('https://api.github.com/users/octocat')->execute();
```

**What's Enabled in Production Mode:**
- ✅ SSL verification (`CURLOPT_SSL_VERIFYPEER = true`)
- ✅ SSL host verification (`CURLOPT_SSL_VERIFYHOST = 2`)
- ✅ URL validation (SSRF protection)
- ✅ Header injection protection
- ✅ Redirect limits (max 3)
- ✅ Protocol restrictions (HTTP/HTTPS only)
- ✅ Concurrent request limits (max 50)
- ✅ Security event logging

### Development Mode

```php
<?php
// Disable security for local testing
$http = new http_async(['production_mode' => false]);

// Or toggle individual settings
$http = new http_async();
$http->setProductionMode(false)
     ->setUrlValidation(false);

// Now safe to call local/test URLs
$results = $http->get('http://localhost:8000/api')->execute();
```

---

## Configuration Options

### Constructor Options

```php
$http = new http_async([
    'production_mode' => true,           // Enable secure defaults
    'max_concurrent_requests' => 50,     // DoS protection
    'max_redirects' => 3,                // Redirect limit
    'allowed_domains' => [],             // Domain allowlist (empty = allow all)
    'enable_url_validation' => true,     // SSRF protection
    'enable_security_logging' => true    // Log security events
]);
```

### Configuration Methods

```php
// Set production mode
$http->setProductionMode(true);   // Secure defaults
$http->setProductionMode(false);  // Development mode

// Configure URL validation
$http->setUrlValidation(true);    // Enable SSRF protection
$http->setUrlValidation(false);   // Disable (dev only!)

// Set domain allowlist
$http->setAllowedDomains([
    'api.github.com',
    'api.stripe.com'
]);

// Set timeouts
$http->setTimeout(30);             // Request timeout (seconds)
$http->setConnectTimeout(10);      // Connection timeout (seconds)
```

---

## Environment-Specific Configurations

### 1. Production (Public APIs)

**Strictest security - recommended for production:**

```php
<?php
global $PW;
$http = $PW->libraries->http_async;

// Production mode ON (default)
$http->setProductionMode(true);

// REQUIRED: Set allowed domains (prevents SSRF)
$http->setAllowedDomains([
    'api.github.com',
    'api.stripe.com',
    'api.sendgrid.com'
]);

// Recommended: Set appropriate timeouts
$http->setTimeout(15);
$http->setConnectTimeout(5);

// SSL is ENABLED by default
$results = $http->get('https://api.github.com/users', 'github')->execute();
```

### 2. Production (Trusted Internal APIs)

**Moderate security - for internal microservices:**

```php
<?php
$http = new http_async();

// Keep production mode ON for SSL
$http->setProductionMode(true);

// Disable URL validation for internal IPs (use with caution!)
$http->setUrlValidation(false);

// Make request to internal service
$results = $http->get('https://internal-api.company.local/data', 'internal')->execute();
```

### 3. Staging Environment

**Balanced security - similar to production:**

```php
<?php
$http = new http_async([
    'production_mode' => true,
    'allowed_domains' => [
        'staging-api.example.com',
        'test-api.stripe.com'
    ]
]);

$results = $http->get('https://staging-api.example.com/data')->execute();
```

### 4. Development (Local Testing)

**Minimal security - for development only:**

```php
<?php
$http = new http_async(['production_mode' => false]);

// Disable all security for local testing
$http->setUrlValidation(false);

// Now safe to use HTTP, localhost, etc.
$results = $http
    ->get('http://localhost:8000/api', 'local')
    ->get('http://192.168.1.100:3000/test', 'test')
    ->execute();
```

### 5. Testing/CI Environment

**Configured via environment detection:**

```php
<?php
// Detect environment
$isProduction = (getenv('APP_ENV') === 'production');

$http = new http_async([
    'production_mode' => $isProduction,
    'enable_url_validation' => $isProduction
]);

if ($isProduction) {
    $http->setAllowedDomains(['api.prod.example.com']);
} else {
    // Testing environment - less strict
    $http->setUrlValidation(false);
}
```

---

## Security Features Explained

### 1. SSL Verification (Enabled by Default)

**Production (Secure):**
```php
// SSL ON by default in production mode
$http = new http_async(); // production_mode = true by default
$http->get('https://api.example.com', 'api');
// ✅ Certificate verified
// ✅ Hostname verified
```

**Development (Override):**
```php
// Disable SSL for self-signed certificates (dev only!)
$http = new http_async(['production_mode' => false]);
// OR override per-request:
$http->get('https://local.test', 'api', [], [
    'ssl_verify' => false,
    'ssl_verify_host' => 0
]);
```

### 2. SSRF Protection

**What it Blocks:**
- ❌ Private IP ranges (10.x.x.x, 192.168.x.x, 172.16-31.x.x)
- ❌ Loopback (127.0.0.1, localhost)
- ❌ Link-local (169.254.x.x)
- ❌ Cloud metadata IPs (169.254.169.254)
- ❌ Non-HTTP(S) protocols (file://, ftp://, etc.)
- ❌ Domains not in allowlist (if configured)

**Example:**
```php
$http = new http_async();
$http->setAllowedDomains(['api.github.com']);

// ✅ Allowed
$http->get('https://api.github.com/users', 'ok');

// ❌ Blocked: not in allowlist
$http->get('https://api.stripe.com/charges', 'blocked');
// Throws: Exception: Domain not in allowlist

// ❌ Blocked: private IP
$http->get('http://192.168.1.1/admin', 'blocked');
// Throws: Exception: Access to private/internal IP addresses is not allowed

// ❌ Blocked: cloud metadata
$http->get('http://169.254.169.254/latest/meta-data/', 'blocked');
// Throws: Exception: Access to cloud metadata services is not allowed
```

### 3. Header Injection Protection

**Automatic sanitization:**
```php
// Malicious header with newline injection
$headers = [
    "X-Custom: value\r\nX-Injected: malicious"
];

$http->get($url, 'api', $headers);
// ✅ Automatically sanitized to: "X-Custom: valueX-Injected: malicious"
// \r and \n characters removed
```

### 4. Redirect Limits

**Prevents redirect chains:**
```php
// Max 3 redirects allowed (prevents infinite loops)
$http = new http_async();
$http->get('https://example.com/redirect-loop', 'api');
// ✅ Stops after 3 redirects
```

### 5. Concurrent Request Limits

**Prevents DoS:**
```php
$http = new http_async(['max_concurrent_requests' => 50]);

// Queue 100 requests
for ($i = 0; $i < 100; $i++) {
    $http->get("https://api.example.com/$i", "req_$i");
}
// ❌ Throws: Exception: Maximum concurrent requests limit reached (50)
```

**Solution: Batch processing**
```php
$urls = range(1, 100);
$batches = array_chunk($urls, 50);

foreach ($batches as $batch) {
    $http->reset();
    foreach ($batch as $id) {
        $http->get("https://api.example.com/$id", "req_$id");
    }
    $results = $http->execute();
}
```

### 6. Security Logging

**Logged Events:**
- SSL verification disabled (development mode)
- Blocked protocols (file://, ftp://, etc.)
- Domains not in allowlist
- Private IP blocks
- Cloud metadata IP blocks

**Log Format:**
```
[HTTP_ASYNC_SECURITY] ssl_verification_disabled: {"url":"https://api.example.com","production_mode":false}
[HTTP_ASYNC_SECURITY] domain_not_allowed: {"url":"https://bad.com","host":"bad.com"}
[HTTP_ASYNC_SECURITY] private_ip_blocked: {"url":"http://192.168.1.1","host":"192.168.1.1","ip":"192.168.1.1"}
```

**Custom Logging via Hooks:**
```php
// In your hooks file
Hook::register('http_async_security_event', function($data) {
    // Send to monitoring service
    error_log('[SECURITY] ' . $data['event'] . ': ' . json_encode($data['context']));

    // Or log to database
    global $PW;
    $PW->models->security_log->create([
        'event' => $data['event'],
        'context' => json_encode($data['context']),
        'timestamp' => $data['timestamp']
    ]);

    return $data;
});
```

---

## Common Scenarios

### Scenario 1: Multiple External APIs

```php
<?php
global $PW;
$http = $PW->libraries->http_async;

// Configure all allowed APIs
$http->setAllowedDomains([
    'api.github.com',
    'api.stripe.com',
    'api.sendgrid.com',
    'api.twilio.com'
]);

// Make concurrent requests
$http->get('https://api.github.com/users/octocat', 'github')
     ->get('https://api.stripe.com/v1/charges', 'stripe', [
         'Authorization: Bearer ' . getenv('STRIPE_KEY')
     ])
     ->post('https://api.sendgrid.com/v3/mail/send', $emailData, 'email', [
         'Authorization: Bearer ' . getenv('SENDGRID_KEY')
     ]);

$results = $http->executeJson();
```

### Scenario 2: Microservices Architecture

```php
<?php
// Allow internal microservices
$http = new http_async();
$http->setAllowedDomains([
    'auth-service.internal',
    'payment-service.internal',
    'notification-service.internal'
]);

// Call microservices concurrently
$http->get('https://auth-service.internal/validate', 'auth')
     ->get('https://payment-service.internal/balance', 'payment')
     ->post('https://notification-service.internal/send', $data, 'notify');

$results = $http->executeJson();
```

### Scenario 3: Public + Internal APIs

```php
<?php
// Option 1: Disable URL validation (less secure)
$http = new http_async();
$http->setUrlValidation(false); // Allows both public and private IPs

// Option 2: Use two separate instances (recommended)
$publicHttp = new http_async();
$publicHttp->setAllowedDomains(['api.github.com']);

$internalHttp = new http_async();
$internalHttp->setUrlValidation(false);

$publicResults = $publicHttp->get('https://api.github.com/users')->execute();
$internalResults = $internalHttp->get('https://internal-api.local/data')->execute();
```

### Scenario 4: Environment-Based Configuration

```php
<?php
class APIClient
{
    private $http;

    public function __construct()
    {
        $env = getenv('APP_ENV') ?: 'production';

        $config = match($env) {
            'production' => [
                'production_mode' => true,
                'allowed_domains' => [
                    'api.stripe.com',
                    'api.sendgrid.com'
                ],
                'max_concurrent_requests' => 20
            ],
            'staging' => [
                'production_mode' => true,
                'allowed_domains' => [
                    'api.stripe.com',
                    'staging-api.example.com'
                ],
                'max_concurrent_requests' => 50
            ],
            'development' => [
                'production_mode' => false,
                'enable_url_validation' => false,
                'max_concurrent_requests' => 100
            ]
        };

        $this->http = new http_async($config);
    }

    public function getHttp()
    {
        return $this->http;
    }
}
```

---

## Migration from v2.2.1 to v2.2.2

### Breaking Changes

**SSL Verification:**
- **Old (v2.2.1):** SSL verification **OFF by default**
- **New (v2.2.2):** SSL verification **ON by default** in production mode

**Migration:**
```php
// OLD CODE (v2.2.1) - Still works
$http = new http_async();
$http->get($url, 'api', [], ['ssl_verify' => true]); // Had to enable explicitly

// NEW CODE (v2.2.2) - Secure by default
$http = new http_async(); // SSL already enabled!
$http->get($url, 'api');

// For development/testing
$http = new http_async(['production_mode' => false]); // Disable security
```

### New Features

1. **Production mode flag**
2. **URL validation (SSRF protection)**
3. **Domain allowlist**
4. **Redirect limits**
5. **Protocol restrictions**
6. **Security logging**
7. **Concurrent request limits**

### Update Checklist

- [ ] Review all `http_async` instantiations
- [ ] Add `production_mode => false` for development code
- [ ] Configure `allowed_domains` for production
- [ ] Remove explicit `ssl_verify => true` (now default)
- [ ] Test with new security features enabled
- [ ] Update environment configurations
- [ ] Review security logs

---

## Troubleshooting

### Problem: "Domain not in allowlist" Error

**Cause:** Trying to access a domain not in the allowlist.

**Solution:**
```php
// Add domain to allowlist
$http->setAllowedDomains([
    'existing-domain.com',
    'new-domain.com'  // Add this
]);

// Or disable allowlist (less secure)
$http->setAllowedDomains([]); // Empty = allow all
```

### Problem: "Access to private/internal IP addresses is not allowed"

**Cause:** Trying to access internal/private IPs with URL validation enabled.

**Solution:**
```php
// Disable URL validation (use with caution!)
$http->setUrlValidation(false);

// Or use production_mode = false
$http = new http_async(['production_mode' => false]);
```

### Problem: "Maximum concurrent requests limit reached"

**Cause:** Queuing too many requests at once.

**Solution:**
```php
// Use batching
$batches = array_chunk($urls, 50);
foreach ($batches as $batch) {
    $http->reset();
    foreach ($batch as $url) {
        $http->get($url, uniqid());
    }
    $http->execute();
}
```

### Problem: Tests Failing After Upgrade

**Cause:** Tests using test/local URLs blocked by URL validation.

**Solution:**
```php
// In test files, use development mode
$http = new http_async(['production_mode' => false]);

// Or disable validation
$http->setUrlValidation(false);
```

---

## Best Practices

1. **Always use production mode in production** ✅
2. **Always configure domain allowlist** ✅
3. **Use environment variables for API tokens** ✅
4. **Set appropriate timeouts** ✅
5. **Handle security exceptions gracefully** ✅
6. **Monitor security logs** ✅
7. **Test security features** ✅
8. **Document allowed domains** ✅

---

## Security Checklist

Before deploying to production:

- [ ] Production mode enabled (`new http_async()` default)
- [ ] Domain allowlist configured
- [ ] No hardcoded API tokens
- [ ] Timeouts configured (10-30 seconds)
- [ ] Error handling in place
- [ ] Security logging enabled
- [ ] All tests passing
- [ ] Security audit completed

---

## Additional Resources

- **Security Guide:** `/docs/HTTP_ASYNC_SECURITY.md`
- **Security Audit:** `/SECURITY_AUDIT_HTTP_ASYNC.md`
- **Usage Guide:** `/docs/HTTP_ASYNC_GUIDE.md`
- **OWASP Top 10:** https://owasp.org/www-project-top-ten/

---

**Last Updated:** 2025-11-01
**Version:** 2.2.2+
