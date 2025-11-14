# Docker Configuration Files

This directory contains Docker-related configuration files for PHPWeave.

## Files

### `modsecurity-custom.conf`

Custom ModSecurity rules and configuration for PHPWeave.

**Used by**: `Dockerfile.modsecurity` (copied to `/etc/modsecurity/modsecurity-custom.conf`)

**Purpose**:
- Application-specific ModSecurity rules
- Paranoia level configuration (default: 1)
- Anomaly score thresholds (default: 5 inbound, 4 outbound)
- PHPWeave-specific protections (.env, composer.json, etc.)
- Whitelist examples for routes that need custom handling

**Customization**:

1. **Change Paranoia Level** (1-4, higher = stricter):
   ```apache
   setvar:tx.paranoia_level=2
   ```

2. **Adjust Anomaly Thresholds** (higher = less strict):
   ```apache
   setvar:tx.inbound_anomaly_score_threshold=10
   setvar:tx.outbound_anomaly_score_threshold=8
   ```

3. **Whitelist a Route**:
   ```apache
   SecRule REQUEST_URI "@streq /api/webhook" \
       "id:1000101,phase:1,t:none,pass,nolog,ctl:ruleEngine=Off"
   ```

4. **Disable Specific Rule**:
   ```apache
   SecRuleRemoveById 920170
   ```

**After Changes**:
Rebuild the Docker image to apply changes:
```bash
docker compose -f docker-compose.modsecurity.yml down
docker compose -f docker-compose.modsecurity.yml up -d --build
```

## Documentation

For complete ModSecurity documentation:
- **Full Guide**: `docs/MODSECURITY_GUIDE.md`
- **Quick Reference**: `docs/MODSECURITY_QUICK_REFERENCE.md`
- **Integration Summary**: `MODSECURITY_INTEGRATION.md`

## Testing

Test ModSecurity protection:
```bash
php tests/test_modsecurity.php http://localhost:8080
```

## Support

For issues or questions:
1. Check logs: `docker exec phpweave-modsec tail /var/log/apache2/modsec_audit.log`
2. Review documentation in `docs/`
3. Create GitHub issue with log snippets
