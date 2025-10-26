# PHPWeave Docker Deployment Guide

Complete guide for deploying PHPWeave in Docker environments with optimized caching.

## Quick Start

### 1. Development Setup

```bash
# Copy environment file
cp .env.sample .env

# Edit .env with your settings
nano .env

# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# Access application
open http://localhost:8080
```

### 2. Production Setup

```bash
# Copy environment file
cp .env.sample .env

# Configure production settings in .env
# Set DEBUG=0

# Build and start
docker-compose up -d

# View logs
docker-compose logs -f phpweave
```

---

## Docker Compose Configurations

### Standard Setup (`docker-compose.yml`)
- Single PHP container with APCu
- MySQL database
- phpMyAdmin (optional)
- **Use for:** Single-server production

```bash
docker-compose up -d
```

### Development Setup (`docker-compose.dev.yml`)
- Hot-reload (mounted volumes)
- Xdebug support
- Separate dev database
- **Use for:** Local development

```bash
docker-compose -f docker-compose.dev.yml up -d
```

### Scaled Setup (`docker-compose.scale.yml`)
- 3 PHP containers
- Nginx load balancer
- Shared database
- **Use for:** High-traffic production

```bash
docker-compose -f docker-compose.scale.yml up -d

# Scale to 5 containers
docker-compose -f docker-compose.scale.yml up -d --scale phpweave=5
```

---

## Caching Strategy

PHPWeave automatically selects the best caching strategy based on environment:

### In Docker (Detected Automatically)
1. **APCu (preferred)** - In-memory, per-container
2. **File cache (fallback)** - If cache directory is writable
3. **No cache (fallback)** - If filesystem is read-only

### Detection Logic
```php
// Automatic Docker detection checks:
- /.dockerenv file exists
- DOCKER_ENV environment variable set
- KUBERNETES_SERVICE_HOST environment variable set
```

### APCu Advantages in Docker
✅ No filesystem dependencies
✅ Works with read-only containers
✅ Independent per-container (no race conditions)
✅ Very fast (in-memory)
✅ Survives across requests (not container restarts)

---

## Dockerfile Explained

```dockerfile
FROM php:8.4-apache

# Install APCu for route caching
RUN pecl install apcu && \
    docker-php-ext-enable apcu

# Configure APCu
RUN echo "apc.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini
```

### Building Custom Image

```bash
# Build
docker build -t phpweave:latest .

# Run
docker run -d -p 8080:80 \
  -e DOCKER_ENV=production \
  -v $(pwd)/.env:/var/www/html/.env:ro \
  phpweave:latest
```

---

## Environment Variables

### Required
```bash
# Database connection (.env file)
DBHOST=db
DBNAME=phpweave
DBUSER=phpweave_user
DBPASSWORD=phpweave_pass
DBCHARSET=utf8mb4

# Application mode
DEBUG=0  # Production: 0, Development: 1
```

### Optional
```bash
# Docker environment (auto-detected)
DOCKER_ENV=production

# Force specific caching
DISABLE_CACHE=0  # Set to 1 to disable all caching
```

---

## Testing Docker Deployment

### 1. Test Caching

```bash
# Run caching test
docker exec phpweave-app php tests/test_docker_caching.php

# Expected output:
# ✅ OPTIMAL: APCu enabled - using in-memory caching
```

### 2. Test Application

```bash
# Check health
curl http://localhost:8080

# Check APCu status
docker exec phpweave-app php -r "var_dump(extension_loaded('apcu'));"
# Expected: bool(true)

# Check cache
docker exec phpweave-app php -r "var_dump(apcu_cache_info());"
```

### 3. Load Testing

```bash
# Install Apache Bench
apt-get install apache2-utils

# Test performance
ab -n 1000 -c 10 http://localhost:8080/

# Compare with/without cache
```

---

## Performance Benchmarks

### With APCu Cache
- **Cold start:** ~5-10ms
- **Warm cache:** ~2-5ms
- **Routes:** Loaded from memory instantly
- **Memory:** ~2MB APCu allocation

### Without Cache (File-based)
- **Cold start:** ~10-15ms
- **Warm cache:** ~5-8ms
- **Routes:** Loaded from disk
- **Disk I/O:** Required on each request

### Without Any Cache
- **Every request:** ~15-25ms
- **Route compilation:** Happens every time
- **CPU:** Higher usage

---

## Multi-Container Deployment

### Why APCu is Perfect for Load Balancing

```
┌─────────────────┐
│  Nginx LB       │
└────────┬────────┘
         │
    ┌────┴────┬────────┬────────┐
    │         │        │        │
┌───▼───┐ ┌──▼───┐ ┌──▼───┐ ┌──▼───┐
│ PHP 1 │ │ PHP 2│ │ PHP 3│ │ PHP N│
│ APCu  │ │ APCu │ │ APCu │ │ APCu │ ← Independent caches
└───────┘ └──────┘ └──────┘ └──────┘
```

**Benefits:**
- Each container has its own APCu cache
- No shared filesystem needed
- No cache synchronization issues
- No race conditions
- Fast scaling (just add containers)

---

## Kubernetes Deployment

### Basic Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: phpweave
spec:
  replicas: 3
  selector:
    matchLabels:
      app: phpweave
  template:
    metadata:
      labels:
        app: phpweave
    spec:
      containers:
      - name: phpweave
        image: your-registry/phpweave:latest
        ports:
        - containerPort: 80
        env:
        - name: DOCKER_ENV
          value: "production"
        - name: KUBERNETES_SERVICE_HOST
          valueFrom:
            fieldRef:
              fieldPath: status.hostIP
        resources:
          limits:
            memory: "256Mi"
            cpu: "500m"
          requests:
            memory: "128Mi"
            cpu: "250m"
```

### Service

```yaml
apiVersion: v1
kind: Service
metadata:
  name: phpweave-service
spec:
  selector:
    app: phpweave
  ports:
  - port: 80
    targetPort: 80
  type: LoadBalancer
```

---

## Troubleshooting

### APCu Not Working

**Check if APCu is loaded:**
```bash
docker exec phpweave-app php -m | grep apcu
```

**Check APCu configuration:**
```bash
docker exec phpweave-app php -i | grep apc
```

**Enable APCu for CLI:**
```bash
echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini
```

### Cache Not Saving

**Check permissions:**
```bash
docker exec phpweave-app ls -la /var/www/html/cache
```

**Make writable:**
```bash
docker exec phpweave-app chown -R www-data:www-data /var/www/html/cache
docker exec phpweave-app chmod 755 /var/www/html/cache
```

### Routes Not Updating

**Clear cache:**
```bash
docker exec phpweave-app php -r "Router::clearCache();"
```

**Or restart container:**
```bash
docker-compose restart phpweave
```

### Read-Only Filesystem

If running containers with read-only root filesystem:

```yaml
# docker-compose.yml
services:
  phpweave:
    read_only: true  # Secure but requires APCu
    tmpfs:
      - /tmp
      - /var/tmp
```

APCu will still work because it stores data in shared memory, not on disk.

---

## Best Practices

### 1. Use APCu in Production
✅ Install APCu extension
✅ Configure proper TTL (3600s default)
✅ Monitor memory usage

### 2. Security
✅ Use read-only containers when possible
✅ Run as non-root user (www-data)
✅ Set DEBUG=0 in production
✅ Use secrets for database credentials

### 3. Monitoring
✅ Enable health checks
✅ Monitor APCu cache hit ratio
✅ Track response times
✅ Set up logging

### 4. Scaling
✅ Use APCu (not file cache) for horizontal scaling
✅ Add containers behind load balancer
✅ Monitor database connections
✅ Use connection pooling

---

## Common Deployment Patterns

### Pattern 1: Small Application (1-2 containers)
```bash
docker-compose up -d
```
- Single container
- APCu or file cache
- Simple and efficient

### Pattern 2: Medium Application (3-10 containers)
```bash
docker-compose -f docker-compose.scale.yml up -d --scale phpweave=5
```
- Multiple containers
- APCu per container
- Nginx load balancer

### Pattern 3: Large Application (10+ containers)
- Kubernetes with auto-scaling
- APCu in all pods
- External load balancer
- Redis for session storage

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Deploy to Docker

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Build Docker image
        run: docker build -t phpweave:${{ github.sha }} .

      - name: Test image
        run: |
          docker run -d --name test phpweave:${{ github.sha }}
          docker exec test php tests/test_docker_caching.php

      - name: Push to registry
        run: |
          docker tag phpweave:${{ github.sha }} registry.example.com/phpweave:latest
          docker push registry.example.com/phpweave:latest
```

---

## FAQ

**Q: Do I need volumes for cache?**
A: No, if using APCu. Cache is in memory and doesn't persist (which is fine).

**Q: What happens when container restarts?**
A: APCu cache is cleared. Routes are recompiled on first request (takes 1-3ms).

**Q: Can I use Redis instead?**
A: Yes, but APCu is faster for route caching (in-memory, no network).

**Q: How do I clear cache in production?**
A: Restart containers or call `Router::clearCache()` via CLI.

**Q: Does cache work with auto-scaling?**
A: Yes! Each container maintains its own APCu cache independently.

---

## Next Steps

1. ✅ Test locally with `docker-compose.dev.yml`
2. ✅ Run `test_docker_caching.php` to verify setup
3. ✅ Deploy to staging with `docker-compose.yml`
4. ✅ Load test to verify performance
5. ✅ Scale with `docker-compose.scale.yml` or Kubernetes
6. ✅ Monitor APCu cache hit ratio
7. ✅ Optimize based on metrics

---

**Ready to deploy!** 🚀

See also:
- `DOCKER_CACHING_GUIDE.md` - Detailed caching strategies
- `OPTIMIZATIONS_APPLIED.md` - Performance improvements
- `Dockerfile` - Production-ready image
- `docker-compose*.yml` - Various deployment scenarios
