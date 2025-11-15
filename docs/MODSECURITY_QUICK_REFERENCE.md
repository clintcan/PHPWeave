# ModSecurity Quick Reference

Quick commands and snippets for managing ModSecurity in PHPWeave.

## Common Commands

### View Logs

```bash
# View last 100 lines of audit log
docker exec phpweave-modsec tail -n 100 /var/log/apache2/modsec_audit.log

# Real-time monitoring
docker exec phpweave-modsec tail -f /var/log/apache2/modsec_audit.log

# Search for specific IP
docker exec phpweave-modsec grep "192.168.1.100" /var/log/apache2/modsec_audit.log

# Count blocked requests today
docker exec phpweave-modsec grep "$(date +%d/%b/%Y)" /var/log/apache2/modsec_audit.log | grep -c "403"
```

### Test Protection

```bash
# Test SQL injection
curl "http://localhost/?id=1' OR '1'='1"

# Test XSS
curl "http://localhost/?q=<script>alert(1)</script>"

# Test path traversal
curl "http://localhost/../../../etc/passwd"

# Test .env access
curl "http://localhost/.env"

# All should return 403 Forbidden
```

### Verify Installation

```bash
# Check if mod_security2 is loaded
docker exec phpweave-modsec apache2ctl -M | grep security2

# Check ModSecurity version
docker exec phpweave-modsec dpkg -l | grep modsecurity

# Verify rules are loaded
docker exec phpweave-modsec ls -la /etc/modsecurity/crs/rules/ | wc -l

# Check configuration
docker exec phpweave-modsec grep SecRuleEngine /etc/modsecurity/modsecurity.conf
```

## Common Configurations

### Whitelist a Route

Add to `docker/modsecurity-custom.conf`:

```apache
# Completely disable ModSecurity for a route
SecRule REQUEST_URI "@streq /api/webhook" \
    "id:1000101,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleEngine=Off"
```

### Disable Specific Rule

```apache
# Disable rule globally
SecRuleRemoveById 920170

# Disable rule for specific route
SecRule REQUEST_URI "@streq /upload" \
    "id:1000102,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleRemoveById=920170"
```

### Whitelist IP Address

```apache
# Skip ModSecurity for trusted IP
SecRule REMOTE_ADDR "@ipMatch 192.168.1.100" \
    "id:1000103,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleEngine=Off"

# Whitelist IP range
SecRule REMOTE_ADDR "@ipMatch 10.0.0.0/8" \
    "id:1000104,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:ruleEngine=Off"
```

### Allow Large File Uploads

```apache
# Increase body limit for specific route
SecRule REQUEST_URI "@streq /api/upload" \
    "id:1000105,\
     phase:1,\
     t:none,\
     pass,\
     nolog,\
     ctl:requestBodyLimit=52428800"  # 50MB
```

### Custom Block Rule

```apache
# Block requests containing specific string
SecRule ARGS "@contains badword" \
    "id:1000200,\
     phase:2,\
     t:none,\
     block,\
     log,\
     msg:'Custom rule: badword detected',\
     severity:CRITICAL"
```

## Log Analysis

### Find Most Blocked IPs

```bash
docker exec phpweave-modsec bash -c "grep -oP 'client: \K[0-9.]+' /var/log/apache2/modsec_audit.log | sort | uniq -c | sort -rn | head -10"
```

### Find Most Triggered Rules

```bash
docker exec phpweave-modsec bash -c "grep -oP '\[id \"\K[0-9]+' /var/log/apache2/modsec_audit.log | sort | uniq -c | sort -rn | head -10"
```

### Today's Blocks by Hour

```bash
docker exec phpweave-modsec bash -c "grep '$(date +%d/%b/%Y)' /var/log/apache2/modsec_audit.log | grep -oP '\d{2}:\d{2}:\d{2}' | cut -d: -f1 | sort | uniq -c"
```

### Export Blocked Requests to CSV

```bash
docker exec phpweave-modsec bash -c "grep 'ModSecurity: Access denied' /var/log/apache2/error.log" | \
  awk '{print $1","$2","$NF}' > blocked_requests.csv
```

## Paranoia Levels

| Level | Use Case | False Positives |
|-------|----------|-----------------|
| 1 | Standard websites, APIs | Very low |
| 2 | E-commerce, sensitive data | Low |
| 3 | Financial services | Moderate |
| 4 | Maximum security | High |

Change in `docker/modsecurity-custom.conf`:
```apache
setvar:tx.paranoia_level=2
```

## Anomaly Score Thresholds

| Threshold | Sensitivity | Recommended For |
|-----------|-------------|-----------------|
| 3 | Very strict | High-security environments |
| 5 | Strict (default) | Most applications |
| 10 | Moderate | Applications with complex forms |
| 20 | Lenient | Development/testing |

Change in `docker/modsecurity-custom.conf`:
```apache
setvar:tx.inbound_anomaly_score_threshold=10
```

## Troubleshooting

### False Positive Workflow

1. **Identify the blocked request**:
   ```bash
   docker exec phpweave-modsec tail /var/log/apache2/modsec_audit.log
   ```

2. **Find the rule ID** (e.g., `[id "920170"]`)

3. **Research the rule**:
   ```bash
   docker exec phpweave-modsec grep -r "920170" /etc/modsecurity/crs/rules/
   ```

4. **Create whitelist** in `docker/modsecurity-custom.conf`

5. **Rebuild and test**:
   ```bash
   docker compose -f docker-compose.modsecurity.yml down
   docker compose -f docker-compose.modsecurity.yml up -d --build
   ```

### Performance Issues

1. **Check current paranoia level**:
   ```bash
   docker exec phpweave-modsec grep "tx.paranoia_level" /etc/modsecurity/modsecurity-custom.conf
   ```

2. **Reduce to level 1** if higher

3. **Disable body inspection for large uploads**:
   ```apache
   SecRule REQUEST_URI "@streq /upload" \
       "id:1000300,\
        phase:1,\
        pass,\
        nolog,\
        ctl:requestBodyAccess=Off"
   ```

### ModSecurity Not Blocking

1. **Verify engine is On**:
   ```bash
   docker exec phpweave-modsec grep SecRuleEngine /etc/modsecurity/modsecurity.conf
   ```

2. **Check module is loaded**:
   ```bash
   docker exec phpweave-modsec apache2ctl -M | grep security2
   ```

3. **Test with known attack**:
   ```bash
   curl "http://localhost/?id=1' UNION SELECT NULL--"
   ```

## Quick Disable/Enable

### Temporarily Disable ModSecurity

```bash
# Edit modsecurity.conf
docker exec phpweave-modsec sed -i 's/SecRuleEngine On/SecRuleEngine DetectionOnly/' /etc/modsecurity/modsecurity.conf

# Restart Apache
docker exec phpweave-modsec apache2ctl graceful
```

### Re-enable ModSecurity

```bash
# Edit modsecurity.conf
docker exec phpweave-modsec sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf

# Restart Apache
docker exec phpweave-modsec apache2ctl graceful
```

**Note**: Changes are lost on container restart. For permanent changes, edit `Dockerfile.modsecurity` and rebuild.

## Useful Rule Patterns

### Allow Specific User-Agent

```apache
SecRule REQUEST_HEADERS:User-Agent "@streq MyApp/1.0" \
    "id:1000400,\
     phase:1,\
     pass,\
     nolog,\
     ctl:ruleEngine=Off"
```

### Block Specific User-Agent

```apache
SecRule REQUEST_HEADERS:User-Agent "@contains BadBot" \
    "id:1000401,\
     phase:1,\
     deny,\
     status:403,\
     log,\
     msg:'Bad bot detected'"
```

### Rate Limiting (Basic)

```apache
# Block IP after 100 requests in 60 seconds
SecAction "id:1000500,\
    phase:1,\
    pass,\
    initcol:ip=%{REMOTE_ADDR},\
    setvar:ip.counter=+1,\
    expirevar:ip.counter=60"

SecRule IP:COUNTER "@gt 100" \
    "id:1000501,\
     phase:1,\
     deny,\
     status:429,\
     msg:'Rate limit exceeded'"
```

### Geographic Blocking (if GeoIP enabled)

```apache
# Block specific countries (example: XX country code)
SecRule REMOTE_ADDR "@geoLookup" \
    "chain,id:1000600,\
     phase:1,\
     deny,\
     status:403,\
     log,\
     msg:'Geographic blocking'"
SecRule GEO:COUNTRY_CODE "@streq XX"
```

## Configuration Locations

| File | Purpose |
|------|---------|
| `/etc/modsecurity/modsecurity.conf` | Main ModSecurity config |
| `/etc/modsecurity/crs/crs-setup.conf` | OWASP CRS setup |
| `/etc/modsecurity/crs/rules/*.conf` | OWASP CRS rules |
| `/etc/modsecurity/modsecurity-custom.conf` | PHPWeave custom rules |
| `/var/log/apache2/modsec_audit.log` | Audit log (blocked requests) |
| `/var/log/apache2/error.log` | Apache error log |
| `/var/cache/modsecurity` | ModSecurity data directory |

## Emergency Procedures

### Complete ModSecurity Disable

```bash
# Option 1: Use standard Dockerfile instead
docker compose -f docker-compose.yml up -d --build

# Option 2: Stop ModSecurity container
docker compose -f docker-compose.modsecurity.yml down
```

### Reset to Default Configuration

```bash
# Remove custom config
docker exec phpweave-modsec rm /etc/modsecurity/modsecurity-custom.conf

# Restart
docker exec phpweave-modsec apache2ctl graceful
```

## Health Checks

```bash
# All-in-one status check
docker exec phpweave-modsec bash -c "
echo 'ModSecurity Module:' && apache2ctl -M | grep security2
echo 'Rule Engine:' && grep SecRuleEngine /etc/modsecurity/modsecurity.conf | grep -v '^#'
echo 'Rules Loaded:' && ls /etc/modsecurity/crs/rules/*.conf | wc -l
echo 'Recent Blocks:' && grep -c 'Access denied' /var/log/apache2/modsec_audit.log
"
```

---

**See also**: `MODSECURITY_GUIDE.md` for detailed documentation
