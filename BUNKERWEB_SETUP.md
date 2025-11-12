# BunkerWeb WAF Setup for PHPWeave

Quick setup guide for deploying PHPWeave with BunkerWeb WAF protection.

---

## 🛡️ What is BunkerWeb?

BunkerWeb is a next-generation, open-source Web Application Firewall (WAF) that provides:

- **ModSecurity WAF** with OWASP Core Rule Set v4
- **DDoS Protection** with rate limiting and connection limits
- **Bot Detection** and automatic blocking
- **Automatic SSL/TLS** with Let's Encrypt
- **Security Headers** (HSTS, CSP, X-Frame-Options, etc.)
- **Reverse Proxy** with caching and compression
- **Real-time Dashboard** for monitoring

---

## 📦 What's Included

### Files Created

1. **`docker-compose.bunkerweb.yml`** - Complete Docker Compose configuration
   - BunkerWeb WAF (reverse proxy)
   - BunkerWeb Scheduler (task management)
   - BunkerWeb UI (web admin interface on port 7000)
   - MariaDB (BunkerWeb configuration storage)
   - Redis (caching and sessions)
   - PHPWeave Application (your framework)
   - MySQL Database (application data)
   - phpMyAdmin (optional database management)

2. **`.env.bunkerweb.sample`** - Environment configuration template
   - Domain and SSL settings
   - Admin credentials
   - Database passwords
   - Security settings

3. **`docs/BUNKERWEB_WAF_GUIDE.md`** - Comprehensive documentation (1,000+ lines)
   - Complete setup guide
   - Configuration reference
   - Security best practices
   - Troubleshooting
   - Performance tuning

---

## 🚀 Quick Start (5 Minutes)

### Prerequisites

- Docker 20.10+ with Docker Compose V2
- Valid domain name pointing to your server
- Ports 80, 443 open on firewall
- 2GB+ RAM, 20GB+ disk space

### Step 1: Configure Environment

```bash
# Copy environment template
cp .env.bunkerweb.sample .env

# Edit configuration
nano .env
```

**Required changes:**
```ini
# Your domain (MUST be accessible from internet)
DOMAIN=www.yoursite.com

# Your email for Let's Encrypt
EMAIL=admin@yoursite.com

# Change ALL passwords!
BW_ADMIN_PASSWORD=YourSecurePassword123!
MYSQL_ROOT_PASSWORD=YourSecureRootPassword123!
DB_PASSWORD=YourSecureDBPassword123!
```

### Step 2: Configure DNS

Point your domain to your server:
```
Type: A Record
Host: www (or @)
Value: Your server's public IP
TTL: 3600
```

Verify:
```bash
nslookup www.yoursite.com
```

### Step 3: Deploy

```bash
# Pull latest images
docker compose -f docker-compose.bunkerweb.yml pull

# Start all services
docker compose -f docker-compose.bunkerweb.yml up -d

# Watch startup logs
docker compose -f docker-compose.bunkerweb.yml logs -f
```

### Step 4: Verify

Check all services are running:
```bash
docker compose -f docker-compose.bunkerweb.yml ps
```

**Access points:**
- **PHPWeave App**: https://www.yoursite.com (via WAF)
- **Admin UI**: http://your-server-ip:7000
- **phpMyAdmin**: http://your-server-ip:8081

---

## 🔒 Security Features Enabled

### Automatic Protection

✅ **SQL Injection** - ModSecurity OWASP CRS blocks SQLi attempts
✅ **XSS (Cross-Site Scripting)** - Request filtering and output encoding
✅ **DDoS Protection** - Rate limiting: 30 req/sec per IP
✅ **Bot Blocking** - Known bad bots automatically banned
✅ **Brute Force** - Connection limits prevent password attacks
✅ **SSL/TLS** - Automatic Let's Encrypt certificates (auto-renewal)
✅ **Security Headers** - HSTS, CSP, X-Frame-Options, etc.

### Default Settings

```yaml
Rate Limiting:      30 requests/second per IP
Connection Limits:  10 HTTP/1.1, 100 HTTP/2 per IP
WAF Engine:         ModSecurity with OWASP CRS v4
SSL/TLS:            Auto Let's Encrypt (TLS 1.2+)
Bot Protection:     Bad behavior + DNSBL
Compression:        Gzip enabled for text files
Caching:            Static assets cached client-side
```

---

## 📊 Architecture

```
                Internet
                   ↓
      ┌──────────────────────┐
      │  BunkerWeb WAF       │ ← Ports 80/443
      │  (ModSecurity)       │ ← Rate limiting, bot detection
      └──────────────────────┘
                   ↓
      ┌──────────────────────┐
      │  PHPWeave App        │ ← Internal only (not exposed)
      │  (Apache + PHP)      │
      └──────────────────────┘
                   ↓
      ┌──────────────────────┐
      │  MySQL Database      │
      └──────────────────────┘

Supporting Services:
┌────────────┐  ┌────────────┐  ┌────────────┐
│ Scheduler  │  │  Admin UI  │  │   Redis    │
└────────────┘  └────────────┘  └────────────┘
```

**Key benefit:** PHPWeave has no direct internet exposure. All traffic filtered through WAF first.

---

## 🎯 Testing Your Setup

### Test 1: SSL Certificate

```bash
curl -I https://www.yoursite.com
# Should return: HTTP/2 200
```

### Test 2: Rate Limiting

```bash
# Send 50 rapid requests
for i in {1..50}; do curl -I https://www.yoursite.com/; done
# Should eventually return: HTTP/1.1 429 Too Many Requests
```

### Test 3: WAF Protection (SQL Injection)

```bash
curl "https://www.yoursite.com/?id=1' OR '1'='1"
# Should return: HTTP/1.1 403 Forbidden
```

### Test 4: Bot Blocking

```bash
curl -A "sqlmap" https://www.yoursite.com/
# Should return: HTTP/1.1 403 Forbidden
```

---

## 📱 Admin UI

Access: `http://your-server-ip:7000`

**Default credentials:**
- Username: `admin` (or your `BW_ADMIN_USER`)
- Password: Your `BW_ADMIN_PASSWORD` from `.env`

**Features:**
- Real-time dashboard (requests, blocks, attacks)
- Live log viewer (access logs, security events)
- Configuration editor (change settings without redeploying)
- Ban management (view/modify blocked IPs)
- Certificate status (Let's Encrypt monitoring)

---

## 🔧 Common Configuration Changes

### 1. Adjust Rate Limiting

**For API endpoints (need higher limits):**
```yaml
# In docker-compose.bunkerweb.yml
environment:
  - LIMIT_REQ_RATE=100r/s  # Increase from 30r/s
  - LIMIT_REQ_BURST=200    # Allow burst traffic
```

### 2. Whitelist Trusted IPs

**Skip rate limiting for specific IPs:**
```yaml
environment:
  - WHITELIST_IP=1.2.3.4 5.6.7.8  # Your office, API clients, etc.
```

### 3. Disable SSL (Local Testing)

**For localhost testing without domain:**
```yaml
environment:
  - AUTO_LETS_ENCRYPT=no
  - LISTEN_HTTP=yes
  - SERVER_NAME=localhost
```

### 4. Change Paranoia Level

**Adjust WAF strictness:**
```yaml
environment:
  - MODSECURITY_PARANOIA_LEVEL=1  # 1=basic, 2=normal, 3=strict, 4=max
```

---

## 🛠️ Management Commands

### View Logs

```bash
# All logs
docker compose -f docker-compose.bunkerweb.yml logs -f

# BunkerWeb only
docker logs phpweave-bunkerweb -f

# Filter for blocked requests
docker logs phpweave-bunkerweb 2>&1 | grep "403"
```

### Restart Services

```bash
# Restart all
docker compose -f docker-compose.bunkerweb.yml restart

# Restart BunkerWeb only
docker compose -f docker-compose.bunkerweb.yml restart bunkerweb

# Reload configuration (no downtime)
docker exec phpweave-bw-scheduler bwcli reload
```

### Stop/Start

```bash
# Stop all
docker compose -f docker-compose.bunkerweb.yml stop

# Start all
docker compose -f docker-compose.bunkerweb.yml start

# Remove all (WARNING: deletes volumes with -v)
docker compose -f docker-compose.bunkerweb.yml down
docker compose -f docker-compose.bunkerweb.yml down -v  # Including data
```

### Check Status

```bash
# Service status
docker compose -f docker-compose.bunkerweb.yml ps

# Resource usage
docker stats phpweave-bunkerweb phpweave-app
```

---

## 🐛 Troubleshooting

### Issue: Let's Encrypt Certificate Failed

**Check:**
1. Domain DNS is correct: `nslookup www.yoursite.com`
2. Ports 80/443 are open: `sudo ufw status`
3. Domain accessible from internet: `curl http://www.yoursite.com`

**Solution:**
```bash
# Check logs
docker logs phpweave-bunkerweb 2>&1 | grep -i "let's encrypt"

# Force renewal
docker exec phpweave-bw-scheduler bwcli jobs fire LETS_ENCRYPT
```

### Issue: 502 Bad Gateway

**Check:**
```bash
# Is PHPWeave running?
docker ps | grep phpweave-app

# Can BunkerWeb reach PHPWeave?
docker exec phpweave-bunkerweb curl -I http://phpweave:80

# Check PHPWeave logs
docker logs phpweave-app
```

### Issue: Too Many False Positives

**Solution:**
```yaml
# Lower paranoia level
environment:
  - MODSECURITY_PARANOIA_LEVEL=1

# Or disable specific rule (get ID from logs)
environment:
  - MODSECURITY_CRS_EXCLUSIONS=rule:942100
```

### Issue: Admin UI Not Accessible

**Check:**
```bash
# Is UI running?
docker ps | grep phpweave-bw-ui

# Check firewall
sudo ufw allow 7000/tcp

# Check logs
docker logs phpweave-bw-ui
```

---

## 📚 Full Documentation

For complete documentation, configuration options, and advanced topics:

**See:** `docs/BUNKERWEB_WAF_GUIDE.md`

**Includes:**
- Detailed configuration reference (30+ options)
- Multi-site setup
- Geo-blocking
- Custom error pages
- Load balancing
- Performance tuning
- Production deployment checklist
- Backup strategies
- High availability setup
- Monitoring integration
- Compliance (PCI-DSS, HIPAA, GDPR)

---

## 🆚 Comparison

### BunkerWeb vs Standard Setup

| Aspect | Standard | With BunkerWeb |
|--------|----------|----------------|
| **Security** | Basic PHP | Enterprise WAF |
| **DDoS Protection** | None | Rate limiting |
| **Bot Protection** | None | Automatic |
| **SSL** | Manual | Auto Let's Encrypt |
| **Headers** | Manual | Automatic |
| **Latency** | Baseline | +2-5ms |
| **Complexity** | Low | Medium |
| **Cost** | Free | Free (self-hosted) |

### When to Use BunkerWeb

✅ **Production deployments** facing the internet
✅ **Sensitive data** (user info, payments, health data)
✅ **High-traffic sites** needing DDoS protection
✅ **Compliance requirements** (PCI-DSS, HIPAA, etc.)
✅ **Multi-tenant** environments

### When Standard Setup is Fine

✅ Internal applications (behind corporate firewall)
✅ Development/staging environments
✅ Low-traffic personal projects
✅ Learning/testing

---

## 🔐 Security Best Practices

### Before Production

- [ ] Change ALL default passwords in `.env`
- [ ] Configure valid domain with DNS
- [ ] Enable firewall (allow only 80, 443, SSH)
- [ ] Restrict admin UI to specific IPs or VPN
- [ ] Test rate limiting and WAF rules
- [ ] Set up monitoring/alerting
- [ ] Configure backup strategy
- [ ] Review security logs regularly

### Passwords to Change

```ini
# In .env file:
BW_ADMIN_PASSWORD=<generate-secure-password>
MYSQL_ROOT_PASSWORD=<generate-secure-password>
DB_PASSWORD=<generate-secure-password>
```

Generate secure passwords:
```bash
openssl rand -base64 32
```

---

## 📈 Performance Impact

**Overhead:** BunkerWeb adds approximately 2-5ms latency

**Benefits:**
- HTTP/2 & HTTP/3 support (20-40% faster)
- Gzip compression (reduces bandwidth)
- Client-side caching (static assets)
- DDoS protection (prevents overload)

**Net result:** Better performance under load, minimal overhead for normal traffic

---

## 🎓 Next Steps

1. ✅ Review full documentation: `docs/BUNKERWEB_WAF_GUIDE.md`
2. ✅ Configure domain and deploy
3. ✅ Test security features
4. ✅ Review logs in Admin UI
5. ✅ Tune rate limits for your traffic
6. ✅ Set up monitoring
7. ✅ Configure backups
8. ✅ Plan maintenance schedule

---

## 📞 Support

**Issues with BunkerWeb:**
- GitHub: https://github.com/bunkerity/bunkerweb
- Docs: https://docs.bunkerweb.io/
- Discord: https://discord.gg/bunkerweb

**Issues with PHPWeave:**
- See: `docs/README.md`
- Check: `TROUBLESHOOTING_GUIDE.md`

---

**Last Updated**: January 2025
**BunkerWeb Version**: 1.6.5
**PHPWeave Version**: v2.6.0+
**Status**: Production Ready ✅
