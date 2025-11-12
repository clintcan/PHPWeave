# Cache Dashboard

**Version:** 2.5.0
**Status:** Production Ready

The Cache Dashboard provides a modern, real-time web interface for monitoring cache performance and statistics in PHPWeave applications.

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Quick Start](#quick-start)
4. [Configuration](#configuration)
5. [Security](#security)
6. [Dashboard UI](#dashboard-ui)
7. [API Endpoints](#api-endpoints)
8. [Monitoring Best Practices](#monitoring-best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Overview

The Cache Dashboard is a built-in monitoring tool that provides real-time insights into your application's cache performance. It helps you:

- Monitor cache hit/miss rates
- Track cache operations (reads, writes, deletes)
- Identify performance bottlenecks
- Optimize caching strategies
- Debug cache-related issues

### Dashboard URL

Once enabled, access the dashboard at:

```
http://yourapp.com/cache/dashboard
```

---

## Features

### Real-Time Monitoring
- **Live Statistics**: Auto-refreshing cache metrics (configurable intervals: 2s, 5s, 10s, 30s)
- **Hit/Miss Rates**: Visual progress bars showing cache efficiency
- **Operation Counters**: Track hits, misses, writes, and deletes
- **Driver Information**: Current cache driver and availability status

### Interactive Controls
- **Auto-Refresh Toggle**: Enable/disable automatic updates
- **Manual Refresh**: Update statistics on demand
- **Reset Statistics**: Clear counters and start fresh
- **Flush Cache**: Clear all cached data (with confirmation)

### Performance Metrics
- **Hit Rate Percentage**: Measure cache effectiveness
- **Total Requests**: Track cache usage volume
- **Efficiency Score**: ⭐-rated performance indicator
- **Driver Details**: Active cache backend information

### Modern UI
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Beautiful Gradients**: Modern purple gradient theme
- **Card-Based Layout**: Clean, organized statistics display
- **Animated Transitions**: Smooth updates and interactions

---

## Quick Start

### 1. Enable Dashboard

Edit your `.env` file:

```bash
# Enable dashboard
CACHE_DASHBOARD_ENABLED=1

# Optional: Disable authentication (not recommended for production)
CACHE_DASHBOARD_AUTH=0
```

### 2. Configure Authentication (Recommended)

```bash
CACHE_DASHBOARD_AUTH=1
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=your_secure_password
```

### 3. Access Dashboard

Open your browser and navigate to:

```
http://yourapp.com/cache/dashboard
```

If authentication is enabled, enter your credentials when prompted.

---

## Configuration

All configuration is done via environment variables in `.env`:

### Basic Configuration

```bash
# Enable/disable dashboard (default: enabled in DEBUG mode)
CACHE_DASHBOARD_ENABLED=1
```

**Values:**
- `1` or `true` = Dashboard enabled
- `0` or `false` = Dashboard disabled
- Not set = Enabled if `DEBUG=1`, otherwise disabled

### Authentication

```bash
# Require HTTP Basic Authentication
CACHE_DASHBOARD_AUTH=1

# Credentials (only used if CACHE_DASHBOARD_AUTH=1)
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=changeme
```

**Security Note:** Always use strong passwords in production!

### IP Whitelist

Restrict dashboard access to specific IP addresses:

```bash
# Comma-separated list of allowed IPs
CACHE_DASHBOARD_IPS=127.0.0.1,192.168.1.100,10.0.0.50
```

**Leave empty to allow all IPs** (not recommended for production).

### Cache Statistics

Enable/disable statistics tracking:

```bash
# Enable statistics (required for dashboard)
CACHE_STATS=1
```

---

## Security

The Cache Dashboard includes multiple security layers:

### 1. Enable/Disable Control

Dashboard is disabled by default in production:

```bash
# Production: Disabled
CACHE_DASHBOARD_ENABLED=0

# Development: Enabled
CACHE_DASHBOARD_ENABLED=1
# OR
DEBUG=1  # Dashboard auto-enabled in debug mode
```

### 2. HTTP Basic Authentication

Require username/password:

```bash
CACHE_DASHBOARD_AUTH=1
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=secure_password_here
```

The browser will prompt for credentials before allowing access.

### 3. IP Whitelist

Restrict to specific IP addresses:

```bash
# Only allow localhost and office IP
CACHE_DASHBOARD_IPS=127.0.0.1,203.0.113.50
```

### 4. HTTPS Recommendation

Always use HTTPS in production to protect credentials:

```bash
# nginx example
server {
    listen 443 ssl;
    # ... SSL configuration ...
}
```

### Best Practices

✅ **DO:**
- Enable authentication in production
- Use strong passwords
- Restrict by IP when possible
- Use HTTPS
- Disable dashboard in production (use only when needed)
- Monitor access logs

❌ **DON'T:**
- Leave default passwords (`changeme`)
- Expose dashboard publicly without auth
- Use dashboard on untrusted networks
- Share credentials

---

## Dashboard UI

### Main Statistics Cards

Four main metric cards display:

1. **Cache Hits** (Green)
   - Number of successful cache retrievals
   - Current hit rate percentage
   - Color: #27ae60

2. **Cache Misses** (Red)
   - Number of cache misses (data not found)
   - Current miss rate percentage
   - Color: #e74c3c

3. **Cache Writes** (Blue)
   - Number of items stored in cache
   - Total write operations
   - Color: #3498db

4. **Cache Deletes** (Orange)
   - Number of items removed from cache
   - Total delete operations
   - Color: #f39c12

### Hit/Miss Ratio Chart

Visual progress bars showing:
- **Hits**: Green bar with percentage
- **Misses**: Red bar with percentage
- **Total Requests**: Below the bars

### Driver Information

Displays:
- **Active Driver**: Badge with driver name
- **Driver Class**: Full class name
- **Efficiency Score**: ⭐-rated performance
- **Last Updated**: Timestamp of last refresh

### Control Panel

- **Auto-Refresh Toggle**: Switch to enable/disable
- **Refresh Interval**: Dropdown (2s, 5s, 10s, 30s)
- **Refresh Now**: Manual update button
- **Reset Stats**: Clear statistics (with confirmation)

---

## API Endpoints

The dashboard provides JSON API endpoints for custom integrations:

### GET /cache/stats

Get current cache statistics.

**Response:**
```json
{
  "hits": 1250,
  "misses": 120,
  "writes": 350,
  "deletes": 45,
  "total_requests": 1370,
  "hit_rate": 91.24,
  "miss_rate": 8.76,
  "driver": "APCuCacheDriver"
}
```

**Example:**
```bash
curl http://yourapp.com/cache/stats
```

### POST /cache/reset

Reset cache statistics to zero.

**Response:**
```json
{
  "success": true,
  "message": "Cache statistics have been reset successfully"
}
```

**Example:**
```bash
curl -X POST http://yourapp.com/cache/reset
```

### POST /cache/flush

Clear all cached data.

**Response:**
```json
{
  "success": true,
  "message": "All cache has been flushed successfully"
}
```

**Example:**
```bash
curl -X POST http://yourapp.com/cache/flush
```

### GET /cache/driver

Get cache driver information and availability.

**Response:**
```json
{
  "driver": "APCuCacheDriver",
  "available_drivers": {
    "memory": {
      "name": "Memory Cache",
      "available": true,
      "description": "In-memory cache (request-scoped)"
    },
    "apcu": {
      "name": "APCu Cache",
      "available": true,
      "description": "PHP APCu extension cache"
    },
    "file": {
      "name": "File Cache",
      "available": true,
      "description": "File-based cache storage"
    },
    "redis": {
      "name": "Redis Cache",
      "available": false,
      "description": "Redis server cache"
    },
    "memcached": {
      "name": "Memcached Cache",
      "available": false,
      "description": "Memcached server cache"
    }
  }
}
```

**Example:**
```bash
curl http://yourapp.com/cache/driver
```

### Authentication for API Endpoints

All endpoints require the same authentication as the dashboard UI:

```bash
# With HTTP Basic Auth
curl -u admin:password http://yourapp.com/cache/stats

# With IP whitelist (if configured)
curl http://yourapp.com/cache/stats
```

---

## Monitoring Best Practices

### Interpreting Statistics

#### Excellent Performance (Hit Rate ≥ 90%)
```
⭐⭐⭐⭐⭐ Excellent
Hit Rate: 95%
```

Your cache strategy is working well! Most requests are served from cache.

#### Good Performance (Hit Rate 75-89%)
```
⭐⭐⭐⭐ Good
Hit Rate: 82%
```

Solid performance, but there's room for optimization.

#### Fair Performance (Hit Rate 60-74%)
```
⭐⭐⭐ Fair
Hit Rate: 68%
```

Consider increasing cache TTLs or caching more queries.

#### Poor Performance (Hit Rate 40-59%)
```
⭐⭐ Poor
Hit Rate: 45%
```

Review your caching strategy. Many requests are missing cache.

#### Very Poor Performance (Hit Rate < 40%)
```
⭐ Very Poor
Hit Rate: 25%
```

Cache is not effective. Check TTLs, cache keys, and query patterns.

### Optimization Tips

1. **High Miss Rate?**
   - Increase cache TTL values
   - Cache more frequently accessed queries
   - Check if cache is being invalidated too often

2. **High Write Rate?**
   - Writes are expensive; consider longer TTLs
   - Use cache warming for critical data
   - Batch write operations when possible

3. **High Delete Rate?**
   - Review cache invalidation strategy
   - Use cache tagging for efficient group invalidation
   - Check for unnecessary flush operations

4. **Low Total Requests?**
   - Cache might not be used enough
   - Review codebase for caching opportunities
   - Add `->cache()` to Query Builder queries

### When to Monitor

- **Development**: Use dashboard frequently to verify caching works
- **Staging**: Monitor before deployment to catch issues
- **Production**: Check periodically or after code changes
- **Troubleshooting**: Enable when investigating performance issues

### Setting Up Alerts

You can create monitoring scripts using the API:

```php
<?php
// Check cache health
$stats = json_decode(file_get_contents('http://yourapp.com/cache/stats'), true);

if ($stats['hit_rate'] < 50) {
    // Send alert - hit rate too low!
    mail('admin@example.com', 'Cache Hit Rate Low',
         "Hit rate: {$stats['hit_rate']}%");
}
```

---

## Troubleshooting

### Dashboard Not Accessible

**Problem:** 404 error when accessing `/cache/dashboard`

**Solutions:**
1. Check dashboard is enabled:
   ```bash
   CACHE_DASHBOARD_ENABLED=1
   ```

2. Verify routes are loaded:
   ```bash
   # Clear route cache
   rm -rf cache/routes/*
   ```

3. Check `.env` file exists and is loaded

### "Dashboard is disabled" Message

**Problem:** 403 error with message about dashboard being disabled

**Solution:**
Enable dashboard in `.env`:
```bash
CACHE_DASHBOARD_ENABLED=1
```

Or enable DEBUG mode:
```bash
DEBUG=1
```

### "Authentication required" Prompt

**Problem:** Browser keeps asking for username/password

**Solutions:**
1. Check credentials in `.env`:
   ```bash
   CACHE_DASHBOARD_USER=admin
   CACHE_DASHBOARD_PASS=your_password
   ```

2. Make sure you're entering correct credentials

3. Disable authentication temporarily:
   ```bash
   CACHE_DASHBOARD_AUTH=0
   ```

### "Access denied: IP not whitelisted"

**Problem:** Cannot access dashboard due to IP restriction

**Solutions:**
1. Add your IP to whitelist:
   ```bash
   CACHE_DASHBOARD_IPS=127.0.0.1,YOUR_IP_HERE
   ```

2. Find your IP:
   ```bash
   curl ifconfig.me
   ```

3. Disable IP whitelist (not recommended for production):
   ```bash
   CACHE_DASHBOARD_IPS=
   ```

### Statistics Not Updating

**Problem:** Dashboard shows zero or outdated statistics

**Solutions:**
1. Check statistics are enabled:
   ```bash
   CACHE_STATS=1
   ```

2. Generate some traffic to your app

3. Check cache driver is working:
   ```bash
   # In your code
   Cache::put('test', 'value', 60);
   ```

4. Check browser console for JavaScript errors

### Auto-Refresh Not Working

**Problem:** Statistics don't update automatically

**Solutions:**
1. Check auto-refresh toggle is enabled (blue)
2. Check JavaScript console for errors
3. Verify API endpoint works:
   ```bash
   curl http://yourapp.com/cache/stats
   ```
4. Try a different browser

---

## Example Configurations

### Development Environment

```bash
# .env for development
DEBUG=1
CACHE_DASHBOARD_ENABLED=1
CACHE_DASHBOARD_AUTH=0  # No auth in dev
CACHE_STATS=1
```

### Production Environment

```bash
# .env for production
DEBUG=0
CACHE_DASHBOARD_ENABLED=0  # Disabled by default
CACHE_DASHBOARD_AUTH=1     # Auth required
CACHE_DASHBOARD_USER=admin
CACHE_DASHBOARD_PASS=very_secure_password_here
CACHE_DASHBOARD_IPS=203.0.113.50,203.0.113.51  # Office IPs only
CACHE_STATS=1
```

### Staging Environment

```bash
# .env for staging
DEBUG=0
CACHE_DASHBOARD_ENABLED=1
CACHE_DASHBOARD_AUTH=1
CACHE_DASHBOARD_USER=staging_admin
CACHE_DASHBOARD_PASS=staging_password
CACHE_DASHBOARD_IPS=192.168.1.0/24  # Internal network
CACHE_STATS=1
```

---

## Integration Examples

### Custom Monitoring Dashboard

Create your own dashboard using the API:

```php
<!DOCTYPE html>
<html>
<head>
    <title>Custom Cache Monitor</title>
</head>
<body>
    <h1>Cache Stats</h1>
    <div id="stats"></div>

    <script>
        async function loadStats() {
            const response = await fetch('/cache/stats');
            const stats = await response.json();

            document.getElementById('stats').innerHTML = `
                <p>Hit Rate: ${stats.hit_rate}%</p>
                <p>Total Requests: ${stats.total_requests}</p>
            `;
        }

        loadStats();
        setInterval(loadStats, 5000);
    </script>
</body>
</html>
```

### CLI Monitoring

Monitor cache from command line:

```bash
#!/bin/bash
# monitor-cache.sh

while true; do
    clear
    echo "=== Cache Statistics ==="
    curl -s http://localhost/cache/stats | jq '.'
    echo ""
    echo "Press Ctrl+C to exit"
    sleep 5
done
```

### Slack Notifications

Send cache alerts to Slack:

```php
<?php
// cache-monitor.php
$stats = json_decode(file_get_contents('http://yourapp.com/cache/stats'), true);

if ($stats['hit_rate'] < 60) {
    $message = "⚠️ Cache hit rate is low: {$stats['hit_rate']}%";

    // Send to Slack webhook
    $payload = json_encode(['text' => $message]);

    $ch = curl_init('https://hooks.slack.com/services/YOUR/WEBHOOK/URL');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
```

---

## Related Documentation

- [Advanced Caching Guide](ADVANCED_CACHING.md) - Complete caching documentation
- [Query Builder Guide](QUERY_BUILDER.md) - Query Builder caching integration
- [Performance Optimization](OPTIMIZATIONS_APPLIED.md) - Framework performance tips

---

**Need Help?**

- Check [Troubleshooting](#troubleshooting) section above
- Review configuration in `.env` file
- Verify routes in `routes/routes.php`
- Check web server error logs
- Open an issue on GitHub

**Security Concerns?**

Always prioritize security:
- Use strong passwords
- Enable HTTPS
- Restrict by IP in production
- Disable dashboard when not needed
- Monitor access logs
