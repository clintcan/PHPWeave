# PHPWeave Deployment Options

PHPWeave offers multiple deployment configurations to suit different security and complexity requirements.

## Quick Comparison

| Feature | Standard | ModSecurity | BunkerWeb |
|---------|----------|-------------|-----------|
| **Containers** | 2 (app + db) | 2 (app + db) | 6+ (waf + scheduler + ui + redis + db + app) |
| **Memory Usage** | ~100-150MB | ~150-200MB | ~500MB+ |
| **Setup Complexity** | ⭐ Simple | ⭐⭐ Moderate | ⭐⭐⭐ Complex |
| **Security Level** | ⭐⭐ Basic | ⭐⭐⭐⭐ High | ⭐⭐⭐⭐⭐ Maximum |
| **Management UI** | ❌ No | ❌ No | ✅ Yes |
| **WAF Protection** | ❌ No | ✅ Yes (OWASP CRS) | ✅ Yes (OWASP CRS) |
| **Auto SSL/TLS** | ❌ No | ❌ No | ✅ Yes (Let's Encrypt) |
| **DDoS Protection** | ❌ No | ❌ No | ✅ Yes |
| **Bot Detection** | ❌ No | ❌ No | ✅ Yes |
| **Auto Updates** | ❌ Manual | ❌ Manual | ✅ Yes |
| **Best For** | Development, Testing | Production (small-medium) | Production (large) |

---

## Option 1: Standard Dockerfile (Recommended for Development)

### Overview
Lightweight deployment with basic security headers. Perfect for development, testing, and small applications.

### Files
- **Dockerfile**: `Dockerfile`
- **Docker Compose**: `docker-compose.yml`

### Quick Start
```bash
# 1. Create .env file
cp .env.sample .env
nano .env

# 2. Start services
docker compose up -d --build

# 3. Access application
http://localhost:8080
```

### Security Features
- ✅ Basic security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- ✅ Apache hardening (ServerTokens Prod, TraceEnable Off)
- ✅ PDO prepared statements (SQL injection protection)
- ✅ Path traversal protection
- ✅ Secure JSON serialization

### Pros
- **Simplest setup** - Just 2 containers
- **Lowest resource usage** - ~100-150MB RAM
- **Fastest** - No WAF overhead
- **Easy debugging** - Fewer moving parts
- **Quick iterations** - Fast rebuild times

### Cons
- **No WAF** - No OWASP CRS protection
- **Manual security** - Rely on application-level security
- **Limited protection** - Against advanced attacks

### When to Use
- ✅ Development environments
- ✅ Testing and staging
- ✅ Internal applications (behind firewall)
- ✅ Small personal projects
- ✅ Learning and prototyping
- ✅ Cost-sensitive deployments

---

## Option 2: ModSecurity Dockerfile (Recommended for Production)

### Overview
Enhanced security with ModSecurity WAF and OWASP Core Rule Set v4.7.0. Provides enterprise-grade protection without additional containers.

### Files
- **Dockerfile**: `Dockerfile.modsecurity`
- **Docker Compose**: `docker-compose.modsecurity.yml`
- **Configuration**: `docker/modsecurity-custom.conf`

### Quick Start
```bash
# 1. Create .env file
cp .env.sample .env
nano .env

# 2. (Optional) Customize ModSecurity rules
nano docker/modsecurity-custom.conf

# 3. Start services with ModSecurity
docker compose -f docker-compose.modsecurity.yml up -d --build

# 4. Test protection
php tests/test_modsecurity.php http://localhost:8080

# 5. Access application
http://localhost:8080
```

### Security Features
- ✅ **All features from Standard, PLUS:**
- ✅ ModSecurity WAF (Web Application Firewall)
- ✅ OWASP Core Rule Set v4.7.0
- ✅ SQL Injection protection (advanced)
- ✅ Cross-Site Scripting (XSS) protection
- ✅ Path Traversal protection (advanced)
- ✅ Remote Code Execution (RCE) blocking
- ✅ XXE and SSRF protection
- ✅ File Upload attack prevention
- ✅ Scanner detection
- ✅ Anomaly-based detection
- ✅ Customizable rules

### Pros
- **Enterprise-grade security** - OWASP Top 10 protection
- **Simple architecture** - Still just 2 containers
- **Moderate resource usage** - ~150-200MB RAM
- **Fast response times** - 1-2ms overhead only
- **Customizable** - Application-specific rules
- **Audit logging** - Security event tracking
- **File-based config** - Easy version control

### Cons
- **No web UI** - Configuration via files
- **Manual updates** - OWASP CRS updates require rebuild
- **Rebuild required** - For config changes
- **Slightly slower** - 1-2ms per request overhead
- **False positives** - May need rule tuning

### When to Use
- ✅ **Production environments** (small to medium)
- ✅ Public-facing applications
- ✅ E-commerce sites
- ✅ Applications handling sensitive data
- ✅ Compliance requirements (PCI DSS, HIPAA)
- ✅ When you need WAF but want simplicity
- ✅ Cost-conscious production deployments

### Documentation
- **Complete Guide**: `docs/MODSECURITY_GUIDE.md`
- **Quick Reference**: `docs/MODSECURITY_QUICK_REFERENCE.md`
- **Integration Summary**: `MODSECURITY_INTEGRATION.md`
- **Test Suite**: `tests/test_modsecurity.php`

---

## Option 3: BunkerWeb (Maximum Security)

### Overview
Enterprise-grade WAF platform with web UI, automatic SSL, DDoS protection, and advanced bot detection. Best for large production deployments.

### Files
- **Docker Compose**: `docker-compose.bunkerweb.yml`
- **Local/Internal**: `docker-compose.bunkerweb-local.yml`

### Quick Start
```bash
# 1. Create .env file and configure domain
cp .env.sample .env
nano .env
# Add: DOMAIN=www.example.com, EMAIL=admin@example.com

# 2. Start services
docker compose -f docker-compose.bunkerweb.yml up -d

# 3. Access admin UI
http://your-server:7000
# Default credentials: admin / changeme (CHANGE THESE!)

# 4. Access application
https://www.example.com (with auto SSL)
```

### Security Features
- ✅ **All features from ModSecurity, PLUS:**
- ✅ **Web-based admin UI** - Easy management
- ✅ **Automatic SSL/TLS** - Let's Encrypt integration
- ✅ **DDoS protection** - Rate limiting and connection limits
- ✅ **Bot detection** - Bad bot blocking
- ✅ **Automatic updates** - OWASP CRS auto-updates
- ✅ **Redis caching** - Performance optimization
- ✅ **QUIC/HTTP3 support** - Modern protocol support
- ✅ **Greylist/Blacklist** - IP reputation
- ✅ **DNSBL integration** - DNS-based blacklists
- ✅ **Runtime configuration** - No rebuild needed

### Architecture
```
Internet → BunkerWeb (80/443) → Scheduler → PHPWeave
                ↓
            Redis + MariaDB + Admin UI
```

### Pros
- **Maximum security** - All features enabled
- **Web UI** - Easy management and monitoring
- **Automatic SSL** - Let's Encrypt integration
- **Auto updates** - OWASP CRS updates automatically
- **Advanced features** - DDoS, bot detection, caching
- **Runtime config** - No rebuild for changes
- **Production-ready** - Battle-tested platform
- **Multi-app support** - Can protect multiple apps

### Cons
- **Complex setup** - 6+ containers
- **High resource usage** - ~500MB+ RAM
- **Steeper learning curve** - More components to understand
- **Reverse proxy overhead** - Additional latency (~5-10ms)
- **More maintenance** - More services to monitor
- **Overkill for small apps** - Unnecessary complexity

### When to Use
- ✅ **Large production environments**
- ✅ **Multiple applications** - Protecting several apps
- ✅ **High traffic** - Sites with DDoS risk
- ✅ **Enterprise deployments** - Organizations requiring UI
- ✅ **Managed hosting** - When you want set-it-and-forget-it
- ✅ **Advanced security needs** - Maximum protection
- ✅ **Team management** - Multiple admins

### Documentation
- **Production Setup**: `docs/BUNKERWEB_SETUP.md`
- **Local/Internal Setup**: `docs/BUNKERWEB_LOCAL_SETUP.md`
- **Management Scripts**: `docs/BUNKERWEB_SCRIPTS_README.md`
- **Complete Guide**: `docs/BUNKERWEB_WAF_GUIDE.md`

---

## Decision Matrix

### Choose Standard Dockerfile if:
- You're developing or testing
- The app is internal only
- You want the fastest performance
- You need the simplest setup
- Resource usage is critical
- You're comfortable with application-level security

### Choose ModSecurity Dockerfile if:
- You're deploying to production
- The app is public-facing
- You need OWASP Top 10 protection
- You want a balance of security and simplicity
- You prefer file-based configuration
- You want version-controlled security rules
- **Recommended for most production use cases**

### Choose BunkerWeb if:
- You have a large production deployment
- You need a management UI
- You want automatic SSL/TLS
- You require DDoS protection
- You're protecting multiple applications
- You have a team managing security
- You need runtime configuration changes
- Resource usage is not a concern

---

## Migration Path

### From Standard → ModSecurity
```bash
# 1. Stop standard deployment
docker compose down

# 2. Start ModSecurity deployment
docker compose -f docker-compose.modsecurity.yml up -d --build

# 3. Test protection
php tests/test_modsecurity.php http://localhost:8080

# 4. Monitor logs for false positives
docker exec phpweave-modsec tail -f /var/log/apache2/modsec_audit.log
```

### From ModSecurity → BunkerWeb
```bash
# 1. Stop ModSecurity deployment
docker compose -f docker-compose.modsecurity.yml down

# 2. Configure domain in .env
echo "DOMAIN=www.example.com" >> .env
echo "EMAIL=admin@example.com" >> .env

# 3. Start BunkerWeb
docker compose -f docker-compose.bunkerweb.yml up -d

# 4. Access admin UI
http://your-server:7000
```

### From BunkerWeb → ModSecurity (Downgrade)
```bash
# 1. Stop BunkerWeb
docker compose -f docker-compose.bunkerweb.yml down

# 2. Start ModSecurity
docker compose -f docker-compose.modsecurity.yml up -d --build
```

---

## Performance Comparison

| Metric | Standard | ModSecurity | BunkerWeb |
|--------|----------|-------------|-----------|
| **Request Overhead** | Baseline | +1-2ms | +5-10ms |
| **Memory (App)** | 50-80MB | 80-120MB | 80-120MB |
| **Memory (Total)** | 100-150MB | 150-200MB | 500-600MB |
| **Startup Time** | ~5s | ~8s | ~30s |
| **Build Time** | ~60s | ~90s | N/A (pre-built) |

---

## Cost Comparison (Cloud Hosting)

**Minimum Server Requirements:**

| Option | RAM | vCPU | Monthly Cost* |
|--------|-----|------|---------------|
| **Standard** | 512MB | 1 | $5-10 |
| **ModSecurity** | 1GB | 1 | $10-15 |
| **BunkerWeb** | 2GB | 2 | $20-30 |

*Estimated costs for DigitalOcean, Linode, or similar VPS providers

---

## Summary Recommendation

### For Most Users: ModSecurity Dockerfile ⭐
The **ModSecurity option** (`Dockerfile.modsecurity`) provides the best balance of:
- ✅ Enterprise-grade security
- ✅ Simple architecture
- ✅ Reasonable resource usage
- ✅ Easy maintenance
- ✅ File-based configuration (version control friendly)

**Use Standard** for development/testing only.
**Use BunkerWeb** if you need the advanced features and have the resources.

---

## Support

- **Standard**: See main `README.md`
- **ModSecurity**: See `docs/MODSECURITY_GUIDE.md`
- **BunkerWeb**: See `docs/BUNKERWEB_SETUP.md`

For questions or issues:
1. Check relevant documentation
2. Review logs
3. Create GitHub issue

---

**Last Updated**: January 2025
**PHPWeave Version**: 2.6.0+
