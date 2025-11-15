# BunkerWeb WAF Integration Guide

**PHPWeave v2.6.0+ with Enterprise-Grade Security**

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [What is BunkerWeb?](#what-is-bunkerweb)
3. [Security Features](#security-features)
4. [Prerequisites](#prerequisites)
5. [Quick Start](#quick-start)
6. [Configuration Guide](#configuration-guide)
7. [Architecture](#architecture)
8. [Production Deployment](#production-deployment)
9. [Monitoring & Management](#monitoring--management)
10. [Troubleshooting](#troubleshooting)
11. [Performance Tuning](#performance-tuning)
12. [Security Best Practices](#security-best-practices)

---

## Overview

This guide shows you how to deploy PHPWeave behind BunkerWeb WAF (Web Application Firewall) for enterprise-grade security. BunkerWeb provides multiple layers of protection including:

- **ModSecurity WAF** with OWASP Core Rule Set
- **DDoS protection** with rate limiting
- **Bot detection** and blocking
- **Automatic SSL/TLS** with Let's Encrypt
- **Security headers** (HSTS, CSP, etc.)
- **Real-time threat blocking**

**When to use BunkerWeb:**
- ✅ Production deployments facing the internet
- ✅ Applications handling sensitive data
- ✅ High-traffic websites needing DDoS protection
- ✅ Compliance requirements (PCI-DSS, HIPAA, etc.)
- ✅ Multi-tenant environments

**When standard setup is sufficient:**
- Simple internal applications
- Development/staging environments
- Low-traffic personal projects
- Applications behind corporate firewall

---

## What is BunkerWeb?

BunkerWeb is an open-source, next-generation Web Application Firewall (WAF) based on NGINX. It acts as a reverse proxy that sits in front of your application, filtering all traffic before it reaches PHPWeave.

**Key Benefits:**
- 🛡️ **Zero-day protection**: Blocks attacks before they reach your application
- 🚀 **Performance**: Built on NGINX, adds minimal latency (<5ms)
- 🔒 **SSL/TLS**: Automatic certificate management with Let's Encrypt
- 📊 **Visibility**: Real-time dashboard and logging
- 🎯 **Precision**: Low false-positive rate
- 🔧 **Flexible**: Extensive configuration options

**Version Used:** BunkerWeb 1.6.5 (January 2025)

---

## Security Features

### 1. ModSecurity WAF
- **OWASP Core Rule Set (CRS) v4**: Industry-standard rule set
- **SQL Injection protection**: Blocks SQLi attempts
- **XSS protection**: Filters cross-site scripting attacks
- **RCE protection**: Prevents remote code execution
- **LFI/RFI protection**: Blocks file inclusion attacks
- **Paranoia levels**: Adjustable strictness (1-4)

### 2. DDoS Protection
- **Rate limiting**: Configurable requests per second/minute
- **Connection limiting**: Max connections per IP
- **Challenge-response**: CPU-based proof-of-work
- **IP reputation**: DNSBL integration
- **Adaptive learning**: Automatic threat detection

### 3. Bot Protection
- **Bad bot blocking**: Known malicious bots
- **Browser validation**: JavaScript challenges
- **User-Agent analysis**: Suspicious pattern detection
- **Referrer checking**: Anti-scraping protection
- **Captcha integration**: Human verification

### 4. SSL/TLS
- **Let's Encrypt**: Automatic certificate issuance & renewal
- **TLS 1.2/1.3**: Modern encryption standards
- **HTTP/2 & HTTP/3**: Performance optimization
- **HSTS**: Strict transport security
- **Perfect forward secrecy**: Enhanced encryption

### 5. Security Headers
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: restrictive
Content-Security-Policy: configurable
Strict-Transport-Security: max-age=31536000
```

### 6. Additional Protections
- **IP whitelisting/blacklisting**: Access control
- **Geo-blocking**: Country-based restrictions
- **Session protection**: Secure cookie flags
- **Anti-automation**: Form protection
- **Request filtering**: Malicious payload detection

---

## Prerequisites

### System Requirements
- **Docker**: 20.10+ with Docker Compose V2
- **Server**: 2GB+ RAM, 20GB+ disk space
- **OS**: Linux (Ubuntu 20.04+, Debian 11+, CentOS 8+) or Windows with WSL2
- **Network**: Public IP address with ports 80/443 accessible

### Domain Requirements
**For SSL/TLS with Let's Encrypt:**
- Valid domain name (e.g., www.example.com)
- DNS A record pointing to your server IP
- Domain accessible from the internet
- Email address for certificate notifications

**For local testing without SSL:**
- Can use localhost or IP address
- Set `AUTO_LETS_ENCRYPT=no` in configuration

### Knowledge Requirements
- Basic Docker/Docker Compose usage
- DNS configuration
- Command-line operations
- Basic networking concepts

---

## Quick Start

### Step 1: Prepare Environment

```bash
# Navigate to PHPWeave directory
cd /path/to/PHPWeave

# Copy environment configuration
cp .env.bunkerweb.sample .env

# Edit configuration (IMPORTANT!)
nano .env
```

**Minimum required changes in `.env`:**
```ini
# Replace with your actual domain
DOMAIN=www.yoursite.com

# Replace with your email
EMAIL=admin@yoursite.com

# Change default passwords!
BW_ADMIN_PASSWORD=YourSecurePassword123!
MYSQL_ROOT_PASSWORD=YourSecureRootPassword123!
DB_PASSWORD=YourSecureDBPassword123!
```

### Step 2: Configure DNS

Point your domain to your server:
```
Type: A Record
Name: www (or @)
Value: Your server's public IP address
TTL: 3600
```

Verify DNS propagation:
```bash
nslookup www.yoursite.com
# Should return your server IP
```

### Step 3: Deploy

```bash
# Pull latest images
docker compose -f docker-compose.bunkerweb.yml pull

# Start all services
docker compose -f docker-compose.bunkerweb.yml up -d

# Monitor startup
docker compose -f docker-compose.bunkerweb.yml logs -f
```

### Step 4: Verify Deployment

**Check service status:**
```bash
docker compose -f docker-compose.bunkerweb.yml ps
```

All services should show "Up" status:
- `phpweave-bunkerweb` (WAF)
- `phpweave-bw-scheduler` (task manager)
- `phpweave-bw-ui` (admin interface)
- `phpweave-bw-db` (settings database)
- `phpweave-redis` (cache)
- `phpweave-app` (your application)
- `phpweave-db` (application database)

**Test access:**
- **PHPWeave**: https://www.yoursite.com
- **Admin UI**: http://your-server-ip:7000
- **phpMyAdmin**: http://your-server-ip:8081

### Step 5: Access Admin UI

1. Navigate to: `http://your-server-ip:7000`
2. Login with credentials from `.env`:
   - Username: `admin` (or your BW_ADMIN_USER)
   - Password: Your BW_ADMIN_PASSWORD

---

## Configuration Guide

### Basic Configuration

The main configuration is in `docker-compose.bunkerweb.yml` under the `bunkerweb` service environment section.

#### Domain Setup
```yaml
environment:
  - SERVER_NAME=www.example.com    # Your domain
  - MULTISITE=no                    # Single site (yes for multiple)
```

#### SSL/TLS Configuration
```yaml
environment:
  - AUTO_LETS_ENCRYPT=yes                    # Enable automatic SSL
  - LETS_ENCRYPT_EMAIL=admin@example.com     # Certificate notifications
```

**For local testing (no SSL):**
```yaml
environment:
  - AUTO_LETS_ENCRYPT=no
  - LISTEN_HTTP=yes
```

#### Reverse Proxy Setup
```yaml
environment:
  - USE_REVERSE_PROXY=yes
  - REVERSE_PROXY_URL=/                      # Forward all traffic
  - REVERSE_PROXY_HOST=http://phpweave:80    # Backend application
  - REVERSE_PROXY_HEADERS=X-Forwarded-For $$remote_addr;X-Forwarded-Proto $$scheme
```

### Advanced Configuration

#### Rate Limiting (DDoS Protection)
```yaml
environment:
  # Request rate limiting
  - USE_LIMIT_REQ=yes
  - LIMIT_REQ_URL=/                  # Apply to all URLs
  - LIMIT_REQ_RATE=30r/s             # 30 requests per second per IP
  - LIMIT_REQ_BURST=60               # Allow burst of 60 requests

  # Connection limiting
  - USE_LIMIT_CONN=yes
  - LIMIT_CONN_MAX_HTTP1=10          # Max 10 HTTP/1 connections per IP
  - LIMIT_CONN_MAX_HTTP2=100         # Max 100 HTTP/2 streams per IP
```

**Recommended values by traffic level:**
```yaml
# Low traffic (personal blog, small business)
LIMIT_REQ_RATE=10r/s

# Medium traffic (e-commerce, corporate site)
LIMIT_REQ_RATE=30r/s

# High traffic (news, social media)
LIMIT_REQ_RATE=100r/s

# API endpoints (may need higher limits)
LIMIT_REQ_RATE=50r/s
```

#### ModSecurity Configuration
```yaml
environment:
  # Enable WAF
  - USE_MODSECURITY=yes
  - USE_MODSECURITY_CRS=yes
  - MODSECURITY_CRS_VERSION=4

  # Paranoia level (1-4, higher = stricter)
  - MODSECURITY_SEC_RULE_ENGINE=On
  - MODSECURITY_SEC_AUDIT_ENGINE=RelevantOnly
```

**Paranoia levels explained:**
- **Level 1** (default): Basic protection, minimal false positives
- **Level 2**: Enhanced protection, some false positives possible
- **Level 3**: Strict protection, more tuning required
- **Level 4**: Maximum protection, high false positive rate

**To change paranoia level:**
```yaml
environment:
  - MODSECURITY_SEC_RULE_ENGINE=On
  - MODSECURITY_PARANOIA_LEVEL=2  # Change this
```

#### Bot Protection
```yaml
environment:
  # Basic bot protection
  - USE_BAD_BEHAVIOR=yes             # Block known bad bots
  - USE_DNSBL=yes                    # DNS-based blacklist

  # Advanced bot protection
  - USE_ANTIBOT=cookie               # Cookie challenge
  # Options: cookie, javascript, captcha, recaptcha

  # Whitelist/Greylist/Blacklist
  - USE_WHITELIST=yes
  - WHITELIST_IP=1.2.3.4 5.6.7.8     # Trusted IPs
  - USE_BLACKLIST=yes
  - BLACKLIST_IP=9.8.7.6             # Blocked IPs
```

#### Security Headers
```yaml
environment:
  # Cookie security
  - COOKIE_FLAGS=* HttpOnly SameStrict Secure
  - COOKIE_AUTO_SECURE_FLAG=yes

  # Frame protection
  - IFRAME_PROTECTION=same-origin    # Options: deny, same-origin, allow

  # Content type
  - CONTENT_TYPE_NOSNIFF=yes
  - X_CONTENT_TYPE_OPTIONS=nosniff

  # Referrer policy
  - REFERRER_POLICY=strict-origin-when-cross-origin

  # HSTS (HTTP Strict Transport Security)
  - STRICT_TRANSPORT_SECURITY=max-age=31536000; includeSubDomains
```

#### Caching & Performance
```yaml
environment:
  # Client-side caching
  - USE_CLIENT_CACHE=yes
  - CLIENT_CACHE_EXTENSIONS=jpg|jpeg|png|gif|ico|svg|css|js|woff|woff2
  - CLIENT_CACHE_CONTROL=public, max-age=2592000

  # Compression
  - USE_GZIP=yes
  - GZIP_TYPES=text/html text/css text/javascript application/json

  # HTTP/2 & HTTP/3
  - USE_HTTP2=yes
  - USE_HTTP3=yes
```

#### Geo-Blocking (Optional)
```yaml
environment:
  # Country whitelist (allow only these countries)
  - USE_COUNTRY=yes
  - WHITELIST_COUNTRY=US CA GB FR DE

  # Or country blacklist (block these countries)
  - BLACKLIST_COUNTRY=CN RU KP
```

#### Custom Error Pages
```yaml
environment:
  - ERROR_403=/errors/403.html
  - ERROR_404=/errors/404.html
  - ERROR_502=/errors/502.html
```

#### Logging
```yaml
environment:
  - LOG_LEVEL=info              # Options: debug, info, notice, warning, error
  - LOG_FORMAT=json             # Options: json, text
```

### Multi-Site Configuration

For hosting multiple domains:

```yaml
environment:
  - MULTISITE=yes
  - SERVER_NAME=site1.com site2.com

  # Site 1 configuration
  - site1.com_USE_REVERSE_PROXY=yes
  - site1.com_REVERSE_PROXY_HOST=http://app1:80

  # Site 2 configuration
  - site2.com_USE_REVERSE_PROXY=yes
  - site2.com_REVERSE_PROXY_HOST=http://app2:80
```

---

## Architecture

### Component Overview

```
                   Internet
                      ↓
         ┌──────────────────────┐
         │   Ports 80/443       │
         │   BunkerWeb WAF      │ ← ModSecurity, Rate Limiting, Bot Detection
         └──────────────────────┘
                      ↓
         ┌──────────────────────┐
         │   PHPWeave App       │ ← Your Framework (port 80, internal)
         │   (Apache + PHP)     │
         └──────────────────────┘
                      ↓
         ┌──────────────────────┐
         │   MySQL Database     │ ← Application Data
         └──────────────────────┘

    Supporting Services:
    ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
    │  BW Scheduler   │  │   BW UI         │  │   Redis Cache   │
    │  (tasks)        │  │   (admin:7000)  │  │   (sessions)    │
    └─────────────────┘  └─────────────────┘  └─────────────────┘
              ↓                    ↓
         ┌─────────────────────────┐
         │   MariaDB (BW config)   │
         └─────────────────────────┘
```

### Traffic Flow

**Normal Request:**
```
1. Client → BunkerWeb (port 443)
2. SSL termination
3. WAF inspection (ModSecurity)
4. Rate limit check
5. Bot detection
6. Security headers added
7. Forward to PHPWeave (internal, port 80)
8. PHPWeave processes request
9. Response → BunkerWeb
10. Response caching (if applicable)
11. BunkerWeb → Client
```

**Blocked Request:**
```
1. Client → BunkerWeb (port 443)
2. Rate limit exceeded OR
3. WAF rule triggered OR
4. Bot detected
5. BunkerWeb blocks request
6. Return 403 Forbidden (never reaches PHPWeave)
7. Log incident
```

### Network Isolation

**Three separate networks:**

1. **bw-universe** (10.20.30.0/24): BunkerWeb internal services
   - bunkerweb ↔ bw-scheduler
   - bunkerweb ↔ bw-ui
   - bw-scheduler ↔ bw-db
   - bunkerweb ↔ redis

2. **bw-services**: Application connection
   - bunkerweb ↔ phpweave

3. **phpweave-network**: Application services
   - phpweave ↔ db
   - phpweave ↔ phpmyadmin

**Security benefit:** PHPWeave app has no direct internet exposure.

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Domain name configured and DNS propagated
- [ ] `.env` file created with secure passwords
- [ ] Server meets system requirements (2GB+ RAM)
- [ ] Docker and Docker Compose installed
- [ ] Firewall rules configured (ports 80, 443, 7000, 8081)
- [ ] Email address valid for Let's Encrypt notifications
- [ ] Backup plan in place
- [ ] Monitoring tools configured

### Deployment Steps

#### 1. Initial Deployment

```bash
# Clone/copy PHPWeave to server
cd /opt/phpweave  # or your preferred location

# Set proper permissions
sudo chown -R $USER:$USER .
chmod 600 .env

# Pull images
docker compose -f docker-compose.bunkerweb.yml pull

# Start services
docker compose -f docker-compose.bunkerweb.yml up -d

# Watch logs
docker compose -f docker-compose.bunkerweb.yml logs -f bunkerweb
```

#### 2. Verify SSL Certificate

```bash
# Check BunkerWeb logs for Let's Encrypt
docker logs phpweave-bunkerweb 2>&1 | grep -i "let's encrypt"

# Should see: "Let's Encrypt certificate successfully obtained"
```

**If certificate fails:**
- Verify domain DNS is correct: `nslookg www.yoursite.com`
- Ensure ports 80/443 are open: `sudo ufw status`
- Check Let's Encrypt rate limits (5 per week per domain)
- Review logs: `docker logs phpweave-bunkerweb`

#### 3. Configure PHPWeave

PHPWeave automatically detects it's behind a reverse proxy. Verify in your application:

```php
// In PHPWeave controller
$realIP = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
$protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http';
```

#### 4. Test Protection

**Test rate limiting:**
```bash
# Send 50 rapid requests
for i in {1..50}; do
  curl -I https://www.yoursite.com/
done

# Should eventually return: HTTP/1.1 429 Too Many Requests
```

**Test bot blocking:**
```bash
# Malicious user agent
curl -A "sqlmap" https://www.yoursite.com/

# Should return: HTTP/1.1 403 Forbidden
```

**Test WAF:**
```bash
# SQL injection attempt
curl "https://www.yoursite.com/?id=1' OR '1'='1"

# Should return: HTTP/1.1 403 Forbidden
```

### Firewall Configuration

**Ubuntu/Debian (UFW):**
```bash
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 7000/tcp # BunkerWeb UI (optional, can restrict)
sudo ufw allow 8081/tcp # phpMyAdmin (optional, can restrict)
sudo ufw enable
```

**CentOS/RHEL (firewalld):**
```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=7000/tcp
sudo firewall-cmd --permanent --add-port=8081/tcp
sudo firewall-cmd --reload
```

**Restrict admin ports to specific IPs:**
```bash
# UFW
sudo ufw allow from YOUR_IP to any port 7000
sudo ufw allow from YOUR_IP to any port 8081

# firewalld
sudo firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="YOUR_IP" port port="7000" protocol="tcp" accept'
```

### SSL/TLS Best Practices

#### Certificate Renewal

Let's Encrypt certificates auto-renew. Verify renewal works:

```bash
# Check certificate expiry
docker exec phpweave-bunkerweb ls -la /data/cache/letsencrypt

# Force renewal test (doesn't actually renew)
docker exec phpweave-bw-scheduler bwcli jobs fire LETS_ENCRYPT --test
```

#### Certificate Monitoring

Add to cron for alerts:
```bash
# /etc/cron.daily/check-ssl.sh
#!/bin/bash
DOMAIN="www.yoursite.com"
EXPIRY=$(echo | openssl s_client -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)
EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
NOW_EPOCH=$(date +%s)
DAYS_LEFT=$(( ($EXPIRY_EPOCH - $NOW_EPOCH) / 86400 ))

if [ $DAYS_LEFT -lt 7 ]; then
    echo "WARNING: SSL certificate expires in $DAYS_LEFT days!" | mail -s "SSL Alert" admin@example.com
fi
```

### Backup Strategy

#### Configuration Backup

```bash
# Backup configuration and volumes
docker run --rm \
  -v $(pwd):/backup \
  -v phpweave_bw-data:/data \
  -v phpweave_db-data:/db \
  alpine tar czf /backup/phpweave-backup-$(date +%Y%m%d).tar.gz /data /db

# Backup .env file
cp .env .env.backup.$(date +%Y%m%d)
```

#### Automated Backup Script

```bash
#!/bin/bash
# /opt/scripts/backup-phpweave.sh

BACKUP_DIR="/opt/backups/phpweave"
DATE=$(date +%Y%m%d-%H%M%S)
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

# Backup volumes
docker run --rm \
  -v $BACKUP_DIR:/backup \
  -v phpweave_bw-data:/bw-data:ro \
  -v phpweave_db-data:/db-data:ro \
  alpine tar czf /backup/volumes-$DATE.tar.gz /bw-data /db-data

# Backup database
docker exec phpweave-db mysqldump -u root -p$MYSQL_ROOT_PASSWORD phpweave | gzip > $BACKUP_DIR/db-$DATE.sql.gz

# Backup configuration
cp /opt/phpweave/.env $BACKUP_DIR/env-$DATE
cp /opt/phpweave/docker-compose.bunkerweb.yml $BACKUP_DIR/compose-$DATE.yml

# Remove old backups
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $DATE"
```

Add to cron:
```bash
# Run daily at 2 AM
0 2 * * * /opt/scripts/backup-phpweave.sh >> /var/log/phpweave-backup.log 2>&1
```

### High Availability Setup

For load balancing multiple PHPWeave instances behind BunkerWeb:

```yaml
# docker-compose.bunkerweb.yml
services:
  bunkerweb:
    environment:
      # Load balance to multiple backends
      - USE_LOADBALANCER=yes
      - LOADBALANCER_POOL=phpweave-1:80 phpweave-2:80 phpweave-3:80
      - LOADBALANCER_METHOD=round_robin  # or: least_conn, ip_hash

  phpweave-1:
    # ... service config

  phpweave-2:
    # ... service config

  phpweave-3:
    # ... service config
```

---

## Monitoring & Management

### BunkerWeb Admin UI

Access: `http://your-server-ip:7000`

**Features:**
- **Dashboard**: Real-time statistics, blocked requests
- **Logs**: Security events, access logs, error logs
- **Configuration**: Change settings without editing YAML
- **Ban Management**: View/modify IP bans
- **Jobs**: Trigger tasks (certificate renewal, cache clear)

**Navigation:**
- **Global Config**: Default settings for all sites
- **Services**: Per-site configuration
- **Cache**: View cached data
- **Reports**: Security incidents
- **Logs**: Filter and search logs

### Command-Line Management

#### View Logs

```bash
# All services
docker compose -f docker-compose.bunkerweb.yml logs -f

# Specific service
docker logs phpweave-bunkerweb -f
docker logs phpweave-bw-scheduler -f
docker logs phpweave-app -f

# Filter logs
docker logs phpweave-bunkerweb 2>&1 | grep "403"     # Blocked requests
docker logs phpweave-bunkerweb 2>&1 | grep "error"   # Errors
docker logs phpweave-bunkerweb 2>&1 | grep "attack"  # Detected attacks
```

#### Service Management

```bash
# Restart services
docker compose -f docker-compose.bunkerweb.yml restart

# Restart specific service
docker compose -f docker-compose.bunkerweb.yml restart bunkerweb

# Stop all services
docker compose -f docker-compose.bunkerweb.yml stop

# Start services
docker compose -f docker-compose.bunkerweb.yml start

# Remove all (WARNING: destructive)
docker compose -f docker-compose.bunkerweb.yml down -v
```

#### Configuration Reload

```bash
# Reload BunkerWeb config without downtime
docker exec phpweave-bw-scheduler bwcli reload

# Or restart scheduler
docker compose -f docker-compose.bunkerweb.yml restart bw-scheduler
```

#### Clear Cache

```bash
# Clear BunkerWeb cache
docker exec phpweave-bunkerweb rm -rf /var/cache/bunkerweb/*

# Restart to reload
docker compose -f docker-compose.bunkerweb.yml restart bunkerweb
```

### Metrics & Statistics

#### View Real-Time Stats

```bash
# BunkerWeb stats
docker exec phpweave-bunkerweb cat /var/cache/bunkerweb/stats.json

# Redis stats
docker exec phpweave-redis redis-cli INFO stats
```

#### Access Logs

```bash
# Live access log
docker exec phpweave-bunkerweb tail -f /var/log/bunkerweb/access.log

# Live error log
docker exec phpweave-bunkerweb tail -f /var/log/bunkerweb/error.log

# ModSecurity audit log
docker exec phpweave-bunkerweb tail -f /var/log/bunkerweb/modsec_audit.log
```

#### Export Logs

```bash
# Copy logs to host
docker cp phpweave-bunkerweb:/var/log/bunkerweb ./bunkerweb-logs/

# Archive logs
tar czf bunkerweb-logs-$(date +%Y%m%d).tar.gz bunkerweb-logs/
```

### Alerts & Notifications

#### Email Alerts (via external monitoring)

Use tools like:
- **Prometheus + Alertmanager**: Metrics-based alerting
- **Grafana**: Visualization with alerts
- **Uptime Robot**: Uptime monitoring
- **Better Uptime**: Status page + alerts

#### Webhook Integration

Configure webhooks in BunkerWeb for security events:

```yaml
environment:
  - USE_WEBHOOK=yes
  - WEBHOOK_URL=https://your-monitoring-tool.com/webhook
  - WEBHOOK_EVENTS=ban,unban,attack,error
```

---

## Troubleshooting

### Common Issues

#### 1. Let's Encrypt Certificate Failed

**Symptoms:**
- Cannot access site via HTTPS
- Logs show: "Let's Encrypt challenge failed"

**Solutions:**
```bash
# Check domain DNS
nslookup www.yoursite.com

# Verify ports 80/443 are open
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443

# Check BunkerWeb is listening
docker exec phpweave-bunkerweb netstat -tlnp

# Verify domain is accessible from internet
curl -I http://www.yoursite.com/.well-known/acme-challenge/test

# Check Let's Encrypt rate limits
# https://letsencrypt.org/docs/rate-limits/

# Force certificate renewal
docker exec phpweave-bw-scheduler bwcli jobs fire LETS_ENCRYPT
```

**Workaround for testing:**
```yaml
# Disable Let's Encrypt temporarily
environment:
  - AUTO_LETS_ENCRYPT=no
  - LISTEN_HTTP=yes
```

#### 2. 502 Bad Gateway

**Symptoms:**
- BunkerWeb returns 502 error
- Cannot reach PHPWeave application

**Solutions:**
```bash
# Check PHPWeave container is running
docker ps | grep phpweave-app

# Check if PHPWeave is responding
docker exec phpweave-bunkerweb curl -I http://phpweave:80

# Check network connectivity
docker network inspect phpweave_bw-services

# Restart PHPWeave
docker compose -f docker-compose.bunkerweb.yml restart phpweave

# Check PHPWeave logs
docker logs phpweave-app
```

#### 3. High False Positive Rate

**Symptoms:**
- Legitimate requests blocked
- Users reporting 403 errors

**Solutions:**
```bash
# Review ModSecurity audit log
docker exec phpweave-bunkerweb grep "403" /var/log/bunkerweb/modsec_audit.log

# Identify triggering rule
docker exec phpweave-bunkerweb grep "id:" /var/log/bunkerweb/modsec_audit.log

# Disable specific rule
# In docker-compose.bunkerweb.yml:
environment:
  - MODSECURITY_CRS_EXCLUSIONS=rule:942100  # Rule ID from log

# Or lower paranoia level
environment:
  - MODSECURITY_PARANOIA_LEVEL=1  # Instead of 2+

# Whitelist specific IPs
environment:
  - WHITELIST_IP=1.2.3.4 5.6.7.8
```

#### 4. Rate Limiting Too Aggressive

**Symptoms:**
- Users complaining about blocked access
- Legitimate traffic being rate-limited

**Solutions:**
```yaml
# Increase rate limits
environment:
  - LIMIT_REQ_RATE=100r/s        # Increase from 30r/s
  - LIMIT_REQ_BURST=200          # Increase burst allowance

# Whitelist trusted IPs (API clients, etc.)
environment:
  - WHITELIST_IP=1.2.3.4

# Disable rate limiting for specific paths
environment:
  - /api_LIMIT_REQ_RATE=0        # No limit for /api
```

#### 5. BunkerWeb UI Not Accessible

**Symptoms:**
- Cannot access http://server:7000
- Connection refused

**Solutions:**
```bash
# Check UI container is running
docker ps | grep phpweave-bw-ui

# Check UI logs
docker logs phpweave-bw-ui

# Verify port is exposed
docker port phpweave-bw-ui

# Check firewall
sudo ufw status | grep 7000

# Restart UI
docker compose -f docker-compose.bunkerweb.yml restart bw-ui

# Check database connection
docker exec phpweave-bw-ui nc -zv bw-db 3306
```

#### 6. Database Connection Errors

**Symptoms:**
- BunkerWeb services showing database errors
- Configuration changes not persisting

**Solutions:**
```bash
# Check database is running
docker ps | grep phpweave-bw-db

# Test database connection
docker exec phpweave-bw-db mysql -u bunkerweb -pchangeme -e "SHOW DATABASES;"

# Recreate database
docker compose -f docker-compose.bunkerweb.yml down bw-db
docker volume rm phpweave_bw-db-data
docker compose -f docker-compose.bunkerweb.yml up -d bw-db

# Check database logs
docker logs phpweave-bw-db
```

### Debug Mode

Enable detailed logging:

```yaml
environment:
  - LOG_LEVEL=debug
  - MODSECURITY_SEC_AUDIT_ENGINE=On
  - MODSECURITY_SEC_AUDIT_LOG=/var/log/bunkerweb/modsec_debug.log
```

Restart and check logs:
```bash
docker compose -f docker-compose.bunkerweb.yml restart bunkerweb
docker logs phpweave-bunkerweb -f
```

### Health Checks

```bash
# Check all container health
docker compose -f docker-compose.bunkerweb.yml ps

# Manual health check
docker exec phpweave-bunkerweb curl -f http://localhost:8080/health || echo "FAILED"

# Check BunkerWeb scheduler
docker exec phpweave-bw-scheduler bwcli test
```

---

## Performance Tuning

### Optimization Tips

#### 1. Enable HTTP/2 & HTTP/3

```yaml
environment:
  - USE_HTTP2=yes
  - USE_HTTP3=yes  # Requires UDP port 443
```

**Benefits:**
- **HTTP/2**: Multiplexing, header compression (20-30% faster)
- **HTTP/3**: QUIC protocol, better for mobile (30-40% faster)

#### 2. Configure Caching

```yaml
environment:
  # Client-side caching
  - USE_CLIENT_CACHE=yes
  - CLIENT_CACHE_EXTENSIONS=jpg|jpeg|png|gif|ico|svg|css|js|woff|woff2|ttf|webp
  - CLIENT_CACHE_CONTROL=public, max-age=31536000

  # Server-side caching (Redis)
  - USE_REDIS=yes
  - REDIS_HOST=redis
```

#### 3. Optimize Compression

```yaml
environment:
  - USE_GZIP=yes
  - GZIP_COMP_LEVEL=6              # Balance: 1 (fast) to 9 (best)
  - GZIP_MIN_LENGTH=1000           # Don't compress small files
  - GZIP_TYPES=text/html text/css text/javascript application/json application/xml
```

#### 4. Worker Processes

```yaml
environment:
  - WORKER_PROCESSES=auto          # Auto-detect CPU cores
  - WORKER_CONNECTIONS=4096        # Max connections per worker
```

#### 5. Database Tuning

**MariaDB (BunkerWeb config):**
```yaml
bw-db:
  command: --innodb-buffer-pool-size=256M --max-connections=100
```

**MySQL (PHPWeave database):**
```yaml
db:
  command: --innodb-buffer-pool-size=512M --max-connections=200
```

#### 6. Redis Memory

```yaml
redis:
  command: redis-server --maxmemory 512mb --maxmemory-policy allkeys-lru
```

### Load Testing

Test your setup under load:

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test 10,000 requests, 100 concurrent
ab -n 10000 -c 100 https://www.yoursite.com/

# Or use wrk (more advanced)
wrk -t12 -c400 -d30s https://www.yoursite.com/
```

**Interpret results:**
- **Requests per second**: Higher is better (target: 1000+)
- **Time per request**: Lower is better (target: <100ms)
- **Failed requests**: Should be 0 (excluding rate limits)

### Resource Allocation

**Minimum production setup:**
```
- CPU: 2 cores
- RAM: 2GB
- Disk: 20GB
- Network: 100Mbps
```

**Recommended production setup:**
```
- CPU: 4 cores
- RAM: 4-8GB
- Disk: 50GB SSD
- Network: 1Gbps
```

**Docker resource limits:**
```yaml
services:
  bunkerweb:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '1'
          memory: 512M
```

---

## Security Best Practices

### 1. Change Default Passwords

```bash
# Generate secure passwords
openssl rand -base64 32

# Update .env
BW_ADMIN_PASSWORD=<generated-password>
MYSQL_ROOT_PASSWORD=<generated-password>
DB_PASSWORD=<generated-password>
```

### 2. Restrict Admin Access

```yaml
# In docker-compose.bunkerweb.yml
bw-ui:
  environment:
    - ADMIN_ALLOWED_IP=YOUR_IP_HERE  # Only allow your IP
```

Or use SSH tunnel:
```bash
# On local machine
ssh -L 7000:localhost:7000 user@your-server

# Access UI via: http://localhost:7000
```

### 3. Regular Updates

```bash
# Update BunkerWeb images
docker compose -f docker-compose.bunkerweb.yml pull

# Restart with new images
docker compose -f docker-compose.bunkerweb.yml up -d

# Check versions
docker exec phpweave-bunkerweb bwcli version
```

### 4. Audit Logs Regularly

```bash
# Review blocked requests
docker exec phpweave-bunkerweb grep "403" /var/log/bunkerweb/access.log | tail -100

# Review attacks
docker exec phpweave-bunkerweb grep "attack" /var/log/bunkerweb/modsec_audit.log | tail -50

# Export for analysis
docker logs phpweave-bunkerweb --since 24h > audit-$(date +%Y%m%d).log
```

### 5. Network Segmentation

Keep services isolated:
- BunkerWeb on public network
- PHPWeave on internal network only
- Database on internal network only
- Admin UI accessible via VPN/SSH tunnel only

### 6. Backup Encryption

```bash
# Encrypt backups
tar czf - /opt/phpweave | gpg --symmetric --cipher-algo AES256 > backup-$(date +%Y%m%d).tar.gz.gpg

# Decrypt
gpg -d backup-20250113.tar.gz.gpg | tar xzf -
```

### 7. Security Headers Validation

Test your security headers:
```bash
# Using securityheaders.com API
curl -s "https://securityheaders.com/?q=https://www.yoursite.com&followRedirects=on" | grep "grade"

# Or use curl
curl -I https://www.yoursite.com/ | grep -E "Strict-Transport|X-Frame|X-Content"
```

### 8. Vulnerability Scanning

```bash
# Scan Docker images
docker scan bunkerity/bunkerweb:1.6.5

# Or use Trivy
trivy image bunkerity/bunkerweb:1.6.5
```

### 9. Compliance

**PCI-DSS Requirements:**
- ✅ TLS 1.2+ encryption
- ✅ WAF protection
- ✅ Access logging
- ✅ Regular updates
- ✅ Secure configuration

**GDPR Considerations:**
- IP address logging (consider anonymization)
- Cookie consent (add to PHPWeave)
- Data retention policy
- Access logs encryption

**HIPAA Compliance:**
- Encryption in transit (TLS)
- Access controls (IP whitelisting)
- Audit logging
- Regular security assessments

---

## Comparison with Other Solutions

### BunkerWeb vs Cloudflare

| Feature | BunkerWeb | Cloudflare |
|---------|-----------|------------|
| **Cost** | Free (self-hosted) | $0-$200+/month |
| **Control** | Full control | Limited config |
| **Privacy** | Your infrastructure | Traffic through Cloudflare |
| **Customization** | Extensive | Moderate |
| **DDoS Protection** | Good | Excellent |
| **Setup Complexity** | Medium | Easy |
| **Performance** | Excellent (local) | Excellent (CDN) |

**Use BunkerWeb when:**
- You want full control
- Privacy is critical
- Self-hosted infrastructure
- No recurring costs

**Use Cloudflare when:**
- Global CDN needed
- Managed solution preferred
- Maximum DDoS protection required

### BunkerWeb vs ModSecurity + NGINX

| Feature | BunkerWeb | ModSecurity + NGINX |
|---------|-----------|---------------------|
| **Setup** | Easy (Docker) | Complex (manual) |
| **Management** | Web UI | Config files |
| **Updates** | Docker pull | Manual |
| **Integration** | Built-in | Manual setup |
| **Learning Curve** | Low | High |
| **Flexibility** | High | Very High |

**Use BunkerWeb when:**
- Quick deployment needed
- Team has limited security expertise
- Docker environment
- Prefer UI management

**Use ModSecurity + NGINX when:**
- Maximum customization needed
- Security experts on team
- Legacy infrastructure
- Custom integrations required

---

## Additional Resources

### Official Documentation
- **BunkerWeb**: https://docs.bunkerweb.io/
- **ModSecurity**: https://github.com/SpiderLabs/ModSecurity
- **OWASP CRS**: https://coreruleset.org/

### Security Resources
- **OWASP Top 10**: https://owasp.org/www-project-top-ten/
- **Let's Encrypt**: https://letsencrypt.org/docs/
- **SSL Labs Test**: https://www.ssllabs.com/ssltest/

### Community
- **BunkerWeb GitHub**: https://github.com/bunkerity/bunkerweb
- **BunkerWeb Discord**: https://discord.gg/bunkerweb
- **PHPWeave Issues**: https://github.com/[your-repo]/PHPWeave/issues

---

## Support & Troubleshooting

### Getting Help

1. **Check logs first**: `docker logs phpweave-bunkerweb`
2. **Review this guide**: Search for error message
3. **BunkerWeb docs**: https://docs.bunkerweb.io/
4. **GitHub Issues**: Search existing issues
5. **Community Discord**: Real-time help

### Reporting Issues

When reporting issues, include:
```bash
# System info
uname -a
docker --version
docker compose version

# Container status
docker compose -f docker-compose.bunkerweb.yml ps

# Relevant logs
docker logs phpweave-bunkerweb --tail 100

# Configuration (remove passwords!)
cat docker-compose.bunkerweb.yml
```

---

**Last Updated**: January 2025
**PHPWeave Version**: v2.6.0+
**BunkerWeb Version**: 1.6.5
**Status**: Production Ready ✅
