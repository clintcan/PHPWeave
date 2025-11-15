# ModSecurity with OWASP CRS Integration Guide

This guide covers the ModSecurity Web Application Firewall (WAF) integration in PHPWeave's standard Dockerfile.

## Overview

PHPWeave now includes ModSecurity v2 with the OWASP Core Rule Set (CRS) v4.7.0 as an optional Dockerfile (`Dockerfile.modsecurity`). This provides enterprise-grade web application security without requiring additional containers.

**Two Deployment Options:**
1. **Standard Dockerfile** (`Dockerfile`) - Lightweight, fast, basic security headers
2. **ModSecurity Dockerfile** (`Dockerfile.modsecurity`) - Enhanced WAF protection with OWASP CRS

This guide covers the ModSecurity option.

## Features

- **ModSecurity WAF**: Industry-standard web application firewall
- **OWASP CRS v4.7.0**: Latest Core Rule Set with comprehensive attack protection
- **Built-in Protection Against**:
  - SQL Injection
  - Cross-Site Scripting (XSS)
  - Local File Inclusion (LFI)
  - Remote File Inclusion (RFI)
  - Remote Code Execution (RCE)
  - Command Injection
  - Session Fixation
  - XML External Entity (XXE)
  - Server-Side Request Forgery (SSRF)
  - And 100+ other attack patterns

## Architecture

```
Internet → Apache (Port 80) → ModSecurity → PHPWeave Application
```

ModSecurity runs as an Apache module, inspecting all HTTP requests and responses before they reach your application.

## Configuration Files

### 1. Main Configuration
- **Location**: `/etc/modsecurity/modsecurity.conf`
- **Purpose**: Core ModSecurity engine settings
- **Key Settings**:
  - `SecRuleEngine On` - WAF is enabled and blocking
  - `SecRequestBodyLimit` - Maximum request body size
  - `SecAuditEngine` - Audit logging configuration

### 2. OWASP CRS Setup
- **Location**: `/etc/modsecurity/crs/crs-setup.conf`
- **Purpose**: OWASP Core Rule Set configuration
- **Key Settings**:
  - Paranoia level (1-4)
  - Anomaly scoring thresholds
  - Allowed HTTP methods and content types

### 3. OWASP CRS Rules
- **Location**: `/etc/modsecurity/crs/rules/*.conf`
- **Purpose**: Detection rules for various attack patterns
- **Categories**:
  - Protocol enforcement
  - Attack detection
  - Application defenses
  - Scanner detection

### 4. Custom PHPWeave Rules
- **Location**: `/etc/modsecurity/modsecurity-custom.conf`
- **Source**: `docker/modsecurity-custom.conf` (in your project)
- **Purpose**: Application-specific rules and whitelists

## Paranoia Levels

The OWASP CRS uses paranoia levels to control the strictness of protection:

| Level | Description | False Positives | Recommended For |
|-------|-------------|-----------------|-----------------|
| **1** | Basic protection | Very low | Most applications (default) |
| **2** | Extended protection | Low | Applications needing more security |
| **3** | High protection | Moderate | High-security environments |
| **4** | Maximum protection | High | Maximum security (test thoroughly) |

**Default**: Paranoia Level 1 (balanced security with minimal false positives)

## Anomaly Scoring

ModSecurity uses anomaly scoring instead of immediately blocking requests:

- Each triggered rule adds points to an anomaly score
- If the total score exceeds the threshold, the request is blocked
- **Default Thresholds**:
  - Inbound: 5 points
  - Outbound: 4 points

**Example**: A request that triggers 2 rules (2 points + 3 points = 5 points) would be blocked.

## Customization

### Adjusting Paranoia Level

Edit `docker/modsecurity-custom.conf`:

```apache
SecAction \
  "id:900000,\
   phase:1,\
   nolog,\
   pass,\
   t:none,\
   setvar:tx.paranoia_level=2"  # Change this value (1-4)
```

### Adjusting Anomaly Thresholds

Edit `docker/modsecurity-custom.conf`:

```apache
SecAction \
 "id:900110,\
  phase:1,\
  nolog,\
  pass,\
  t:none,\
  setvar:tx.inbound_anomaly_score_threshold=10,\  # Higher = less strict
  setvar:tx.outbound_anomaly_score_threshold=8"
```

### Whitelisting Specific Routes

If a legitimate route triggers false positives, whitelist it:

```apache
# Disable ModSecurity for specific route
SecRule REQUEST_URI "@streq /api/webhook" \
    "id:1000101,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleEngine=Off"
```

Or disable specific rules:

```apache
# Disable specific rule for a route
SecRule REQUEST_URI "@streq /api/upload" \
    "id:1000102,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleRemoveById=920170"  # Rule ID to disable
```

### Allowed HTTP Methods

By default, these methods are allowed: `GET HEAD POST OPTIONS PUT PATCH DELETE`

To restrict methods, edit `docker/modsecurity-custom.conf`:

```apache
SecAction \
 "id:900200,\
  phase:1,\
  nolog,\
  pass,\
  t:none,\
  setvar:'tx.allowed_methods=GET HEAD POST OPTIONS'"  # Restrict to these only
```

## Building and Deploying

### Build the Docker Image

```bash
# Option 1: Using docker-compose (recommended)
docker compose -f docker-compose.modsecurity.yml up -d --build

# Option 2: Using docker build directly
docker build -f Dockerfile.modsecurity -t phpweave:modsecurity .
docker run -d -p 8080:80 --name phpweave-modsec phpweave:modsecurity
```

### Run with Docker Compose

Use the `docker-compose.modsecurity.yml` file:

```bash
# Start services
docker compose -f docker-compose.modsecurity.yml up -d --build

# Stop services
docker compose -f docker-compose.modsecurity.yml down

# View logs
docker compose -f docker-compose.modsecurity.yml logs -f phpweave
```

## Testing ModSecurity

### 1. Test SQL Injection Protection

```bash
curl "http://localhost/index.php?id=1' OR '1'='1"
```

**Expected**: 403 Forbidden

### 2. Test XSS Protection

```bash
curl "http://localhost/index.php?search=<script>alert('XSS')</script>"
```

**Expected**: 403 Forbidden

### 3. Test Path Traversal Protection

```bash
curl "http://localhost/../../../etc/passwd"
```

**Expected**: 403 Forbidden

### 4. Test .env File Protection

```bash
curl "http://localhost/.env"
```

**Expected**: 403 Forbidden

### 5. View Blocked Request Logs

```bash
docker exec phpweave-modsec tail -f /var/log/apache2/modsec_audit.log
```

## Viewing Logs

### ModSecurity Audit Log

```bash
# View recent blocks
docker exec phpweave-modsec tail -n 100 /var/log/apache2/modsec_audit.log

# Real-time monitoring
docker exec phpweave-modsec tail -f /var/log/apache2/modsec_audit.log
```

### Apache Error Log

```bash
docker exec phpweave-modsec tail -f /var/log/apache2/error.log
```

### Filter for ModSecurity Events

```bash
docker exec phpweave-modsec grep "ModSecurity" /var/log/apache2/error.log
```

## Troubleshooting

### False Positives

**Symptom**: Legitimate requests are being blocked

**Solution**:
1. Check logs to identify the rule ID:
   ```bash
   docker exec phpweave-modsec tail /var/log/apache2/modsec_audit.log
   ```

2. Find the rule ID (e.g., `id "920170"`)

3. Add whitelist rule to `docker/modsecurity-custom.conf`:
   ```apache
   SecRuleRemoveById 920170
   ```

4. Or whitelist for specific routes (see Customization section)

5. Rebuild and restart:
   ```bash
   docker compose -f docker-compose.modsecurity.yml down
   docker compose -f docker-compose.modsecurity.yml up -d --build
   ```

### Performance Issues

**Symptom**: Slow response times

**Solutions**:
1. **Lower paranoia level** to 1 (default)
2. **Disable request body inspection** for specific routes:
   ```apache
   SecRule REQUEST_URI "@streq /api/large-upload" \
       "id:1000200,\
        phase:1,\
        t:none,\
        pass,\
        nolog,\
        ctl:requestBodyAccess=Off"
   ```

3. **Adjust body limits** in `/etc/modsecurity/modsecurity.conf`:
   ```apache
   SecRequestBodyLimit 13107200      # 12.5MB (default)
   SecRequestBodyNoFilesLimit 131072 # 128KB (default)
   ```

### ModSecurity Not Working

**Symptom**: Malicious requests are not being blocked

**Check**:
1. Verify ModSecurity is enabled:
   ```bash
   docker exec phpweave-modsec apache2ctl -M | grep security2
   ```
   Expected: `security2_module`

2. Check ModSecurity engine status:
   ```bash
   docker exec phpweave-modsec grep SecRuleEngine /etc/modsecurity/modsecurity.conf
   ```
   Expected: `SecRuleEngine On`

3. Verify rules are loaded:
   ```bash
   docker exec phpweave-modsec ls -la /etc/modsecurity/crs/rules/
   ```

## Comparison: Standalone vs BunkerWeb

### Standalone ModSecurity (This Setup)

**Pros**:
- ✅ Simple architecture (single container)
- ✅ Lower resource usage (~50-100MB RAM)
- ✅ Direct Apache integration
- ✅ Faster response times (no reverse proxy overhead)
- ✅ Easier debugging

**Cons**:
- ❌ No web UI for management
- ❌ Manual rule updates
- ❌ No advanced features (DDoS protection, bot detection)
- ❌ Configuration requires Docker rebuild

**Best For**:
- Development environments
- Small to medium applications
- Cost-conscious deployments
- When you need full control

### BunkerWeb Setup

**Pros**:
- ✅ Web-based management UI
- ✅ Automatic rule updates
- ✅ Advanced features (DDoS protection, bot detection, auto-SSL)
- ✅ Runtime configuration changes
- ✅ Redis caching
- ✅ Better for multiple applications

**Cons**:
- ❌ Complex architecture (5+ containers)
- ❌ Higher resource usage (~500MB+ RAM)
- ❌ Additional reverse proxy latency
- ❌ More moving parts to manage

**Best For**:
- Production environments
- Large applications
- When you need advanced features
- When you want a management UI

## Performance Impact

ModSecurity adds minimal overhead to request processing:

- **Typical overhead**: 1-5ms per request
- **With paranoia level 1**: ~1-2ms
- **With paranoia level 4**: ~5-10ms

**Recommendation**: Start with paranoia level 1 and increase only if needed.

## Security Considerations

### What ModSecurity Does

✅ Protects against OWASP Top 10 vulnerabilities
✅ Blocks known attack patterns
✅ Validates HTTP protocol compliance
✅ Logs security events for analysis
✅ Provides anomaly-based detection

### What ModSecurity Does NOT Do

❌ Does not replace input validation in your code
❌ Does not protect against business logic flaws
❌ Does not prevent zero-day attacks (unless pattern matches)
❌ Does not provide DDoS protection (use rate limiting)
❌ Does not encrypt data (use HTTPS)

**Best Practice**: ModSecurity is a defense-in-depth layer. Continue following secure coding practices.

## Maintenance

### Updating OWASP CRS

To update to the latest CRS version:

1. Edit `Dockerfile.modsecurity` and change version:
   ```dockerfile
   wget https://github.com/coreruleset/coreruleset/archive/refs/tags/v4.8.0.tar.gz
   ```

2. Rebuild:
   ```bash
   docker compose -f docker-compose.modsecurity.yml down
   docker compose -f docker-compose.modsecurity.yml up -d --build
   ```

### Regular Audits

1. Review logs weekly:
   ```bash
   docker exec phpweave-modsec grep -c "ModSecurity: Warning" /var/log/apache2/error.log
   ```

2. Analyze blocked requests
3. Adjust rules as needed
4. Document any whitelists

## Environment Variables

Add these to `.env` for easier management:

```env
# ModSecurity Settings
MODSEC_PARANOIA_LEVEL=1
MODSEC_INBOUND_THRESHOLD=5
MODSEC_OUTBOUND_THRESHOLD=4
MODSEC_AUDIT_LOG=/var/log/apache2/modsec_audit.log
```

Then reference in custom config (requires additional scripting).

## Additional Resources

- **ModSecurity Documentation**: https://github.com/SpiderLabs/ModSecurity
- **OWASP CRS Documentation**: https://coreruleset.org/docs/
- **Rule IDs Reference**: https://coreruleset.org/docs/rules/
- **PHPWeave Security Guide**: `SECURITY_BEST_PRACTICES.md`
- **BunkerWeb Setup**: `BUNKERWEB_SETUP.md` (for advanced deployments)

## Support

For ModSecurity-specific issues:
1. Check logs: `docker exec phpweave-modsec tail /var/log/apache2/modsec_audit.log`
2. Review OWASP CRS documentation
3. Create GitHub issue with log snippets

## Next Steps

1. **Test the installation**: Run the test commands in the "Testing ModSecurity" section
2. **Monitor logs**: Check for false positives in your application
3. **Customize rules**: Add application-specific whitelists in `docker/modsecurity-custom.conf`
4. **Regular audits**: Review blocked requests weekly
5. **Consider BunkerWeb**: For production, evaluate `docker-compose.bunkerweb.yml`

---

**Version**: 1.0
**Last Updated**: 2025-01-15
**OWASP CRS Version**: 4.7.0
**ModSecurity Version**: 2.9.x
