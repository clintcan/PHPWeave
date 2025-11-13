# BunkerWeb Local/Internal Setup

Quick guide for running PHPWeave with BunkerWeb WAF on local networks **without SSL or domain requirements**.

---

## 🎯 Perfect For

✅ **Local development** - Work on your machine with WAF protection
✅ **Internal networks** - Corporate LANs, private networks
✅ **Testing** - Test security features without public exposure
✅ **Staging environments** - Pre-production testing
✅ **Learning** - Understand BunkerWeb without DNS/SSL complexity

---

## ✨ What You Get

**Security Features (No SSL/Domain needed):**
- ✅ ModSecurity WAF with OWASP Core Rule Set
- ✅ DDoS protection (rate limiting)
- ✅ SQL injection protection
- ✅ XSS protection
- ✅ Security headers
- ✅ Caching and compression
- ✅ Admin UI for monitoring

**What's Different from Production Setup:**
- ❌ No SSL/TLS (HTTP only)
- ❌ No Let's Encrypt
- ❌ No domain required
- ❌ No DNSBL checks
- ✅ More relaxed rate limits (100 req/s vs 30 req/s)
- ✅ Works on localhost and LAN IPs

---

## 🚀 Quick Start (2 Minutes!)

### Step 1: Copy Environment File (Optional)

```bash
# Use defaults (works out of the box)
# OR customize:
cp .env.bunkerweb-local.sample .env
nano .env  # Optional: change passwords
```

**Default Access (no .env needed):**
- PHPWeave: http://localhost
- Admin UI: http://localhost:7000 (admin/changeme)
- phpMyAdmin: http://localhost:8081

### Step 2: Start Services

```bash
docker compose -f docker-compose.bunkerweb-local.yml up -d
```

### Step 3: Access

Open your browser:
- **PHPWeave**: http://localhost
- **Admin UI**: http://localhost:7000
- **phpMyAdmin**: http://localhost:8081

**That's it! No domain, no SSL, no DNS configuration needed.**

---

## 📋 Complete Setup Guide

### Prerequisites

- Docker 20.10+ with Docker Compose V2
- 2GB+ RAM
- Ports 80, 7000, 8081 available

### Installation Steps

#### 1. Navigate to PHPWeave Directory

```bash
cd /path/to/PHPWeave
```

#### 2. Configure Environment (Optional)

```bash
# Copy sample
cp .env.bunkerweb-local.sample .env

# Edit (optional)
nano .env
```

**What to change (optional):**
- `BW_ADMIN_PASSWORD` - Admin UI password (default: changeme)
- `DB_PASSWORD` - Database password (default: phpweave_pass)
- `MYSQL_ROOT_PASSWORD` - MySQL root password (default: rootpassword)
- `HTTP_PORT` - Change if port 80 is busy (default: 80)

**Minimal .env (defaults work fine):**
```ini
# Only change if needed
BW_ADMIN_PASSWORD=mypassword
DB_PASSWORD=mydbpass
```

#### 3. Start Services

```bash
# Start all containers
docker compose -f docker-compose.bunkerweb-local.yml up -d

# Watch startup logs
docker compose -f docker-compose.bunkerweb-local.yml logs -f
```

#### 4. Verify Services

```bash
# Check status
docker compose -f docker-compose.bunkerweb-local.yml ps
```

Expected output:
```
NAME                          STATUS
phpweave-bunkerweb-local      Up
phpweave-bw-scheduler-local   Up
phpweave-bw-ui-local          Up
phpweave-bw-db-local          Up
phpweave-redis-local          Up
phpweave-app-local            Up
phpweave-db-local             Up
phpweave-phpmyadmin-local     Up
```

#### 5. Test Access

```bash
# Test PHPWeave
curl -I http://localhost

# Should return: HTTP/1.1 200 OK
```

---

## 🔧 Configuration

### Default Settings

| Setting | Value | Change In |
|---------|-------|-----------|
| PHPWeave URL | http://localhost | .env (HTTP_PORT) |
| Admin UI | http://localhost:7000 | .env (ADMIN_UI_PORT) |
| phpMyAdmin | http://localhost:8081 | .env (PHPMYADMIN_PORT) |
| Admin User | admin | .env (BW_ADMIN_USER) |
| Admin Pass | changeme | .env (BW_ADMIN_PASSWORD) |
| Rate Limit | 100 req/s | .env (RATE_LIMIT) |
| Bot Protection | Disabled | .env (USE_BOT_PROTECTION) |

### Customizing Ports

If default ports are in use:

```ini
# In .env
HTTP_PORT=8080          # Instead of 80
ADMIN_UI_PORT=7001      # Instead of 7000
PHPMYADMIN_PORT=8082    # Instead of 8081
```

Then access:
- PHPWeave: http://localhost:8080
- Admin UI: http://localhost:7001
- phpMyAdmin: http://localhost:8082

### Adjusting Security

**More restrictive (closer to production):**
```ini
RATE_LIMIT=30r/s
USE_BOT_PROTECTION=yes
```

**More permissive (for testing):**
```ini
RATE_LIMIT=200r/s
USE_BOT_PROTECTION=no
```

---

## 🌐 Accessing from LAN

To access from other computers on your network:

### 1. Find Your Server IP

```bash
# Linux/macOS
ip addr show
# or
ifconfig

# Windows
ipconfig
```

Example: `192.168.1.100`

### 2. Update Firewall (if needed)

```bash
# Linux (Ubuntu/Debian)
sudo ufw allow 80/tcp
sudo ufw allow 7000/tcp
sudo ufw allow 8081/tcp

# Windows
# Add inbound rules in Windows Firewall for ports 80, 7000, 8081
```

### 3. Access from LAN

From any device on your network:
- PHPWeave: http://192.168.1.100
- Admin UI: http://192.168.1.100:7000
- phpMyAdmin: http://192.168.1.100:8081

---

## 🧪 Testing Security Features

### Test 1: Basic Access

```bash
curl -I http://localhost
# Should return: HTTP/1.1 200 OK
```

### Test 2: WAF Protection (SQL Injection)

```bash
curl "http://localhost/?id=1' OR '1'='1"
# Should return: HTTP/1.1 403 Forbidden (blocked by WAF)
```

### Test 3: Rate Limiting

```bash
# Send 150 rapid requests (limit is 100/s)
for i in {1..150}; do curl -I http://localhost/; done
# Later requests should return: HTTP/1.1 429 Too Many Requests
```

### Test 4: Security Headers

```bash
curl -I http://localhost | grep -E "X-Frame|X-Content|Referrer"
# Should show security headers
```

---

## 📊 Admin UI

Access: http://localhost:7000

**Default Credentials:**
- Username: `admin`
- Password: `changeme` (or your BW_ADMIN_PASSWORD)

**Features:**
- Real-time dashboard
- Security events log
- Configuration editor
- Ban management
- Service monitoring

---

## 🛠️ Management Commands

### View Logs

```bash
# All services
docker compose -f docker-compose.bunkerweb-local.yml logs -f

# BunkerWeb only
docker logs phpweave-bunkerweb-local -f

# App only
docker logs phpweave-app-local -f
```

### Restart Services

```bash
docker compose -f docker-compose.bunkerweb-local.yml restart
```

### Stop Services

```bash
docker compose -f docker-compose.bunkerweb-local.yml stop
```

### Start Services

```bash
docker compose -f docker-compose.bunkerweb-local.yml start
```

### Remove Everything

```bash
# Keep data
docker compose -f docker-compose.bunkerweb-local.yml down

# Remove data too
docker compose -f docker-compose.bunkerweb-local.yml down -v
```

### Check Status

```bash
docker compose -f docker-compose.bunkerweb-local.yml ps
```

---

## 🐛 Troubleshooting

### Port Already in Use

**Error:** `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solution:** Change port in `.env`:
```ini
HTTP_PORT=8080
```

Then access: http://localhost:8080

### Cannot Access from LAN

1. **Check firewall:**
   ```bash
   sudo ufw status  # Linux
   # or check Windows Firewall
   ```

2. **Verify server IP:**
   ```bash
   ip addr show  # Should show LAN IP
   ```

3. **Test from server:**
   ```bash
   curl -I http://localhost  # Should work
   ```

### Admin UI Not Loading

1. **Check container status:**
   ```bash
   docker ps | grep bw-ui-local
   ```

2. **Check logs:**
   ```bash
   docker logs phpweave-bw-ui-local
   ```

3. **Verify port:**
   ```bash
   netstat -tlnp | grep 7000
   ```

### Services Keep Restarting

```bash
# Check which service is failing
docker compose -f docker-compose.bunkerweb-local.yml ps

# View logs of failing service
docker logs <container-name>

# Common issue: Database not ready
# Wait 30 seconds and check again
```

---

## 🔄 Migrating to Production

When ready to deploy to production with SSL:

### Option 1: Use Production Compose File

```bash
# Stop local setup
docker compose -f docker-compose.bunkerweb-local.yml down

# Configure domain
cp .env.bunkerweb.sample .env
nano .env  # Set DOMAIN, EMAIL

# Start production setup
docker compose -f docker-compose.bunkerweb.yml up -d
```

### Option 2: Keep Both

```bash
# Local for development
docker compose -f docker-compose.bunkerweb-local.yml up -d

# Production on server
docker compose -f docker-compose.bunkerweb.yml up -d
```

See: `BUNKERWEB_SETUP.md` for full production deployment guide.

---

## 📚 Comparison: Local vs Production

| Feature | Local Setup | Production Setup |
|---------|-------------|------------------|
| **SSL/TLS** | ❌ No | ✅ Auto (Let's Encrypt) |
| **Domain** | ❌ Not required | ✅ Required |
| **Access** | localhost / LAN IP | Domain name |
| **Rate Limit** | 100 req/s | 30 req/s |
| **Bot Protection** | Optional | Enabled |
| **DNSBL** | Disabled | Enabled |
| **Use Case** | Dev / Testing / Internal | Production / Public |
| **Setup Time** | 2 minutes | 5 minutes |
| **Complexity** | Simple | Medium |

---

## 💡 Use Cases

### Development Workflow

```bash
# Morning: Start local BunkerWeb
docker compose -f docker-compose.bunkerweb-local.yml up -d

# Develop with WAF protection
# http://localhost

# Evening: Stop
docker compose -f docker-compose.bunkerweb-local.yml stop
```

### Internal Corporate Application

```bash
# Deploy on internal server (192.168.1.50)
docker compose -f docker-compose.bunkerweb-local.yml up -d

# Employees access: http://192.168.1.50
# IT accesses admin: http://192.168.1.50:7000
```

### Testing Environment

```bash
# Run security tests against local setup
./test-security.sh http://localhost

# No need to configure DNS or SSL!
```

---

## ❓ FAQ

**Q: Do I need a domain name?**
A: No! Access via localhost or LAN IP.

**Q: Do I need SSL certificates?**
A: No! This setup uses HTTP only.

**Q: Can I access from other computers?**
A: Yes! Use your server's LAN IP (e.g., http://192.168.1.100).

**Q: Is it secure enough for production?**
A: No. Use this for internal/dev only. For production, use `docker-compose.bunkerweb.yml` with SSL.

**Q: What's the difference from production setup?**
A: No SSL, no domain, more relaxed limits, HTTP-only cookies.

**Q: Can I use this on Windows?**
A: Yes! Works on Windows, macOS, Linux.

**Q: Default passwords secure?**
A: No. Change `BW_ADMIN_PASSWORD` and database passwords in `.env`.

**Q: Can I run both local and production setups?**
A: Not simultaneously (port conflicts). Use different servers or change ports.

---

## 🎓 Next Steps

1. ✅ Try the local setup: `docker compose -f docker-compose.bunkerweb-local.yml up -d`
2. ✅ Explore Admin UI: http://localhost:7000
3. ✅ Test security features (SQL injection, rate limiting)
4. ✅ Read full WAF guide: `BUNKERWEB_WAF_GUIDE.md`
5. ✅ When ready for production: `BUNKERWEB_SETUP.md`

---

## 📞 Related Documentation

- **Full WAF Guide**: `BUNKERWEB_WAF_GUIDE.md` (complete reference)
- **Production Setup**: `BUNKERWEB_SETUP.md` (with SSL/domain)
- **Management Scripts**: `BUNKERWEB_SCRIPTS_README.md` (automated tools)
- **Troubleshooting**: `TROUBLESHOOTING_GUIDE.md`

---

**Last Updated**: January 2025
**PHPWeave Version**: v2.6.0+
**BunkerWeb Version**: 1.6.5
**Setup Type**: Local/Internal (No SSL)
**Status**: Ready to Use ✅
