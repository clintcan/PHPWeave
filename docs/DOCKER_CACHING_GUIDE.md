# Docker & Container Caching Guide

**Important:** File-based caching in Docker environments requires special considerations.

## Issues with Current Implementation in Docker

### 🔴 Critical Issues

#### 1. **Ephemeral Filesystem (Data Loss)**
**Problem:**
- Docker containers have ephemeral filesystems by default
- Cache files written to `/cache` are lost when container restarts
- Every container restart = cache rebuild = performance hit

**Impact:** Cache is useless in standard Docker deployments

---

#### 2. **Multi-Container Deployments (Cache Inconsistency)**
**Problem:**
- Load balancers typically run multiple containers
- Each container builds its own cache independently
- Cache inconsistency between containers
- Race conditions when writing cache files

**Example Scenario:**
```
Container 1: Loads routes.php → Writes cache v1
Container 2: Loads routes.php → Writes cache v1 (duplicate work)
Developer: Updates routes.php
Container 1: Still serving cache v1 (stale)
Container 2: Still serving cache v1 (stale)
```

**Impact:** Stale cache, wasted CPU, inconsistent behavior

---

#### 3. **Permission Issues**
**Problem:**
- Web server runs as `www-data` or `nginx` user
- Cache directory might not be writable
- Different UID/GID between host and container

**Error:**
```
Warning: file_put_contents(../cache/routes.cache): failed to open stream:
Permission denied
```

**Impact:** Cache writes fail silently (using `@` suppresses warnings)

---

#### 4. **Read-Only Filesystems**
**Problem:**
- Security best practice: run containers with read-only root filesystem
- Cache directory is not writable
- Application fails to create cache

**Impact:** Application may fail in secure environments

---

## Solutions by Deployment Type

### Solution 1: Single Container (Development)

**Use file-based cache with Docker volume:**

```dockerfile
# Dockerfile
FROM php:8.2-apache

WORKDIR /var/www/html

# Create cache directory with proper permissions
RUN mkdir -p cache && \
    chown -R www-data:www-data cache && \
    chmod 755 cache

# Copy application
COPY . .
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  phpweave:
    build: .
    volumes:
      # Persist cache across restarts
      - cache-volume:/var/www/html/cache
    environment:
      - DEBUG=0

volumes:
  cache-volume:
```

**Pros:** Simple, cache persists across restarts
**Cons:** Doesn't scale beyond single container

---

### Solution 2: Multi-Container (Production) - Shared Volume

**Use shared volume with file locking:**

```yaml
# docker-compose.yml
version: '3.8'
services:
  phpweave-1:
    build: .
    volumes:
      - shared-cache:/var/www/html/cache  # Shared cache

  phpweave-2:
    build: .
    volumes:
      - shared-cache:/var/www/html/cache  # Same cache

  nginx:
    image: nginx
    depends_on:
      - phpweave-1
      - phpweave-2

volumes:
  shared-cache:
    driver: local  # Or NFS for Kubernetes
```

**Add file locking to Router class:**

```php
// In router.php saveToCache() method
public static function saveToCache()
{
    if (!self::$cacheFile) {
        return false;
    }

    $cacheDir = dirname(self::$cacheFile);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    // Use file locking to prevent race conditions
    $lockFile = self::$cacheFile . '.lock';
    $lock = fopen($lockFile, 'w');

    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        return false;
    }

    $result = file_put_contents(self::$cacheFile, serialize(self::$routes)) !== false;

    flock($lock, LOCK_UN);
    fclose($lock);

    return $result;
}
```

**Pros:** Works with multiple containers
**Cons:** Shared filesystem required (NFS, EFS, etc.), complexity

---

### Solution 3: Build-Time Caching (Recommended for Docker)

**Generate cache during Docker build, include in image:**

```dockerfile
# Dockerfile
FROM php:8.2-apache

WORKDIR /var/www/html

# Copy application
COPY . .

# Build cache at image build time
RUN php -r "require 'public/index.php'; Router::saveToCache();" || true

# Make cache directory read-only
RUN chmod 444 cache/routes.cache
```

**Update index.php to skip cache writes in production:**

```php
// public/index.php
$cacheFile = '../cache/routes.cache';

// In Docker/production: use pre-built cache (read-only)
if (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    if (file_exists($cacheFile)) {
        Router::enableCache($cacheFile);
        if (!Router::loadFromCache()) {
            // Fallback to routes.php if cache fails
            require_once "../routes.php";
        }
    } else {
        // No cache available, load normally
        require_once "../routes.php";
    }
} else {
    // Development: no caching
    require_once "../routes.php";
}
```

**Pros:**
- No runtime writes needed
- Works with read-only filesystems
- Consistent across all containers
- Fast startup

**Cons:**
- Requires rebuild to update routes
- Slightly more complex build process

---

### Solution 4: In-Memory Caching (APCu/OPcache) - Best for Docker

**Use APCu for route caching instead of files:**

```php
// Add to Router class
private static $useAPCu = false;

public static function enableAPCuCache()
{
    self::$useAPCu = function_exists('apcu_fetch') && apcu_enabled();
}

public static function loadFromCache()
{
    if (self::$useAPCu) {
        $cached = apcu_fetch('phpweave_routes');
        if ($cached !== false) {
            self::$routes = $cached;
            self::$loadedFromCache = true;
            return true;
        }
        return false;
    }

    // Fallback to file cache...
    if (!self::$cacheFile || !file_exists(self::$cacheFile)) {
        return false;
    }

    $cached = @unserialize(file_get_contents(self::$cacheFile));
    if ($cached === false) {
        return false;
    }

    self::$routes = $cached;
    self::$loadedFromCache = true;
    return true;
}

public static function saveToCache()
{
    if (self::$useAPCu) {
        return apcu_store('phpweave_routes', self::$routes, 3600);
    }

    // Fallback to file cache...
    if (!self::$cacheFile) {
        return false;
    }

    $cacheDir = dirname(self::$cacheFile);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    return @file_put_contents(self::$cacheFile, serialize(self::$routes)) !== false;
}
```

**Dockerfile with APCu:**

```dockerfile
FROM php:8.2-apache

# Install APCu
RUN pecl install apcu && \
    docker-php-ext-enable apcu

# Configure APCu for CLI (needed for builds)
RUN echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

COPY . /var/www/html
```

**Update index.php:**

```php
// Check if running in Docker/production
if (getenv('DOCKER_ENV') === 'production') {
    Router::enableAPCuCache();
} elseif (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    Router::enableCache('../cache/routes.cache');
}

if (!Router::loadFromCache()) {
    require_once "../routes.php";
    Router::saveToCache();
}
```

**Pros:**
- ✅ Survives container restarts (if persistent)
- ✅ No file permissions issues
- ✅ No shared filesystem needed
- ✅ Very fast (in-memory)
- ✅ Works with read-only filesystems
- ✅ Independent per container (no race conditions)

**Cons:**
- Requires APCu extension
- Cache is per-container (but that's actually fine)

---

### Solution 5: Disable Caching in Docker (Fallback)

**If caching causes issues, disable it in Docker:**

```dockerfile
# Dockerfile
ENV DISABLE_CACHE=1
```

```php
// public/index.php
if (!getenv('DISABLE_CACHE') &&
    (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG'])) {
    Router::enableCache('../cache/routes.cache');
}

if (!Router::loadFromCache()) {
    require_once "../routes.php";
    Router::saveToCache();
}
```

**Pros:** Eliminates all cache-related issues
**Cons:** Loses performance benefits (1-3ms per request)

---

## Recommended Approach

### For Development (Docker Compose)
✅ **Use file cache with named volume** (Solution 1)

### For Production (Kubernetes/Multi-Container)
✅ **Use APCu in-memory cache** (Solution 4)
✅ **Or build-time caching** (Solution 3)

### For Serverless/Lambda
✅ **Build-time caching only** (Solution 3)

---

## Implementation Priority

### Immediate (Do Now)
1. Add environment detection to index.php
2. Add volume to docker-compose.yml if using Docker

### Short-term (Recommended)
3. Implement APCu caching support
4. Add Dockerfile with APCu extension

### Long-term (Optional)
5. Add Redis/Memcached support for distributed caching

---

## Quick Fix for Current Implementation

**Update public/index.php to be Docker-aware:**

```php
// Detect Docker environment
$isDocker = file_exists('/.dockerenv') || getenv('DOCKER_ENV');

// Enable route caching in production (disable in DEBUG mode or Docker without volume)
if (!isset($GLOBALS['configs']['DEBUG']) || !$GLOBALS['configs']['DEBUG']) {
    // In Docker, only use cache if volume is mounted (directory is writable)
    if (!$isDocker || is_writable('../cache')) {
        Router::enableCache('../cache/routes.cache');
    }
}

// Load routes (from cache if available, otherwise from routes.php)
if (!Router::loadFromCache()) {
    require_once "../routes.php";

    // Only save cache if directory is writable
    if (is_writable('../cache')) {
        Router::saveToCache();
    }
}
```

This makes caching gracefully degrade in Docker environments where `/cache` isn't writable.

---

## Testing in Docker

```bash
# Test cache permissions
docker exec phpweave-container ls -la /var/www/html/cache

# Test cache creation
docker exec phpweave-container touch /var/www/html/cache/test.txt

# Check if cache is being used
docker exec phpweave-container cat /var/www/html/cache/routes.cache

# Monitor cache creation
docker logs -f phpweave-container
```

---

## Summary

| Solution | Development | Production | Complexity | Performance |
|----------|-------------|------------|------------|-------------|
| File cache + volume | ✅ Good | ⚠️ Limited | Low | Medium |
| Shared filesystem | ❌ Overkill | ✅ Works | High | Medium |
| Build-time cache | ✅ Good | ✅ Best | Medium | High |
| APCu cache | ✅ Great | ✅ Best | Medium | Very High |
| No cache | ✅ Simple | ⚠️ Slower | None | Low |

**Recommendation:** Use **APCu caching** (Solution 4) for production Docker, **file cache with volume** (Solution 1) for development.

---

## Next Steps

Would you like me to:
1. ✅ Implement APCu support in Router class
2. ✅ Add Docker environment detection
3. ✅ Create example Dockerfile with APCu
4. ✅ Update index.php with smart caching logic

Let me know and I'll apply the changes!
