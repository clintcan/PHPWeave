# PHPWeave Environment Compatibility Guide
**Version**: 2.6.0
**Last Updated**: 2025-11-12

---

## 📋 Overview

PHPWeave is designed to be cross-platform and works on Linux, Windows, and macOS. However, each environment has specific nuances, optimizations, and limitations that developers should be aware of.

This guide provides detailed information about running PHPWeave in different environments to ensure optimal performance and functionality.

---

## 🐧 Linux (Ubuntu/Debian/CentOS/RHEL)

### ✅ Recommended Environment

Linux is the **recommended production environment** for PHPWeave due to:
- Superior process management
- Better async/background task support
- Optimal APCu performance
- Standard Docker deployment

---

### 🔧 Installation

#### Ubuntu/Debian
```bash
# Install PHP 7.4+ and required extensions
sudo apt-get update
sudo apt-get install -y php php-cli php-mysql php-curl php-json php-mbstring

# Install APCu (highly recommended)
sudo apt-get install -y php-apcu

# Install Composer (optional)
sudo apt-get install -y composer

# Restart PHP-FPM (if using)
sudo systemctl restart php8.1-fpm
```

#### CentOS/RHEL
```bash
# Install PHP 7.4+
sudo yum install -y php php-cli php-mysqlnd php-curl php-json php-mbstring

# Install APCu
sudo yum install -y php-pecl-apcu

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

---

### ⚙️ Configuration

**php.ini** location: `/etc/php/8.1/cli/php.ini` (Ubuntu) or `/etc/php.ini` (CentOS)

**Recommended Settings**:
```ini
; Core PHP
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M

; APCu Configuration
extension=apcu.so
apc.enabled=1
apc.shm_size=64M
apc.enable_cli=1
apc.ttl=7200
apc.gc_ttl=3600

; Sessions
session.save_handler=files
session.gc_maxlifetime=1800

; Date/Time
date.timezone=UTC
```

---

### 🚀 Performance Optimizations

#### 1. APCu Configuration
```bash
# Check APCu status
php -m | grep apcu

# Test APCu
php -r "var_dump(apcu_enabled());"

# View APCu stats
php -r "print_r(apcu_cache_info());"
```

#### 2. File Permissions
```bash
# PHPWeave directories
sudo chown -R www-data:www-data /var/www/phpweave
sudo chmod -R 755 /var/www/phpweave

# Cache directory (must be writable)
sudo chmod -R 775 /var/www/phpweave/cache
sudo chmod -R 775 /var/www/phpweave/storage
```

#### 3. Async Workers
```bash
# Start background worker
nohup php worker.php --daemon > /dev/null 2>&1 &

# Or use systemd service
sudo systemctl start phpweave-worker
sudo systemctl enable phpweave-worker
```

#### 4. Systemd Service (Recommended)
Create `/etc/systemd/system/phpweave-worker.service`:
```ini
[Unit]
Description=PHPWeave Background Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/phpweave
ExecStart=/usr/bin/php worker.php --daemon
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable:
```bash
sudo systemctl daemon-reload
sudo systemctl enable phpweave-worker
sudo systemctl start phpweave-worker
```

---

### ✅ Features - Linux

| Feature | Status | Performance | Notes |
|---------|--------|-------------|-------|
| Core Framework | ✅ Full | Excellent | All features work |
| APCu Caching | ✅ Full | Excellent | 7-14ms speedup |
| Query Builder | ✅ Full | Excellent | All methods work |
| Async::run() | ✅ Full | Excellent | Background tasks execute perfectly |
| Async::queue() | ✅ Full | Excellent | Production-ready |
| HTTP Async | ✅ Full | Excellent | Concurrent requests work |
| File Sessions | ✅ Full | Good | Default location: /tmp |
| DB Sessions | ✅ Full | Excellent | Recommended for production |
| Docker Support | ✅ Full | Excellent | Optimal environment |

---

### 🐛 Known Issues - Linux

#### Issue 1: Permission Denied on Cache Directory
**Symptom**: Cache operations fail with "Permission denied"

**Solution**:
```bash
# Fix permissions
sudo chown -R www-data:www-data cache/
sudo chmod -R 775 cache/

# Or disable cache temporarily
CACHE_DRIVER=memory  # In .env
```

#### Issue 2: Worker Process Dies
**Symptom**: Async jobs not processing

**Solution**:
```bash
# Check worker status
ps aux | grep worker.php

# Check logs
tail -f storage/logs/worker.log

# Restart with systemd
sudo systemctl restart phpweave-worker
```

---

## 🪟 Windows

### ⚠️ Development-Friendly, Production-Possible

Windows works well for **development** but has specific limitations for **production use**, particularly around background processes and performance.

---

### 🔧 Installation

#### PHP Installation
```powershell
# Download PHP from windows.php.net
# Extract to C:\php

# Add to PATH
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\php", "Machine")

# Verify
php -v
```

#### APCu Installation
```powershell
# Download from windows.php.net/downloads/pecl/releases/apcu/
# Example: php_apcu-5.1.27-8.4-ts-vs17-x64.zip

# Extract php_apcu.dll to C:\php\ext\

# Edit php.ini
extension=apcu
apc.enabled=1
apc.shm_size=32M
apc.enable_cli=1

# Verify
php -m | findstr apcu
```

**See**: `install_apcu.bat` or `install_apcu.ps1` for automated installation

---

### ⚙️ Configuration

**php.ini** location: `C:\php\php.ini`

**Recommended Settings**:
```ini
; Core PHP
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M

; APCu Configuration
extension=apcu
apc.enabled=1
apc.shm_size=32M
apc.enable_cli=1

; Extensions (enable as needed)
extension=curl
extension=mysqli
extension=pdo_mysql
extension=mbstring
extension=openssl

; Sessions
session.save_handler=files
session.gc_maxlifetime=1800

; Date/Time
date.timezone=UTC

; Windows-specific
realpath_cache_size=4M
realpath_cache_ttl=600
```

---

### 🚀 Performance Optimizations

#### 1. APCu Installation (Critical)
Without APCu, performance is significantly reduced:
- .env parsing: **Slow** (no caching)
- File discovery: **Slow** (no caching)
- Overall: **7-14ms slower** per request

**Install APCu**: See `install_apcu.bat` or `check_apcu.php`

#### 2. Use SSD
PHPWeave performs better on SSD drives due to frequent file I/O for:
- Route caching
- File-based sessions
- Log files

#### 3. Disable Windows Defender for Dev
Add exclusion for your PHPWeave directory:
```powershell
Add-MpPreference -ExclusionPath "D:\Projects\PHPWeave"
```

---

### ⚠️ Limitations - Windows

#### 1. Async Background Processes (CLI Mode) ⚠️

**Issue**: `Async::run()` fire-and-forget doesn't work reliably in CLI mode

**Why**: Windows process model and PHP's `proc_open()`/`popen()` have limitations when creating truly detached background processes in CLI.

**Evidence**:
```bash
# Task files are created but not executed
dir %TEMP%\phpweave_task_*.php
# Shows: Multiple files exist but never ran
```

**What Works**:
- ✅ `Async::queue()` + worker - **Works perfectly**
- ✅ `Async::defer()` - Works (runs after response)
- ⚠️ `Async::run()` - **Limited in CLI**

**Workaround for Production**:
```php
// ❌ Don't use fire-and-forget on Windows
Async::run(['EmailHelper', 'send'], [$email]);

// ✅ Use job queue (works everywhere)
Async::queue('SendEmailJob', ['email' => $email]);

// Run worker as Windows service or scheduled task
```

**Web Server Context**: May work better with Apache/IIS (not tested in CLI)

---

#### 2. Path Separators

**Issue**: Windows uses backslashes `\`, Linux uses forward slashes `/`

**PHPWeave Handling**: Framework automatically handles this, but be aware:

```php
// ✅ Good - PHPWeave handles conversion
$path = PHPWEAVE_ROOT . '/models/user_model.php';

// ⚠️ Avoid hardcoding backslashes
$path = 'D:\Projects\PHPWeave\models\user_model.php';

// ✅ Better - use DIRECTORY_SEPARATOR or PHPWeave constants
$path = __DIR__ . DIRECTORY_SEPARATOR . 'models';
```

---

#### 3. Case Sensitivity

**Issue**: Windows filesystem is case-insensitive, Linux is case-sensitive

**Best Practice**:
```php
// ✅ Always use correct case
require_once 'models/User_model.php';  // Must match exact filename

// ⚠️ Works on Windows, fails on Linux
require_once 'models/user_model.php';  // If file is User_model.php
```

---

#### 4. File Permissions

**Issue**: Windows doesn't have Unix-style permissions (777, 755, etc.)

**PHPWeave Handling**: Framework uses Windows-compatible file operations

**Note**: All cache/storage directories work out-of-the-box on Windows

---

### ✅ Features - Windows

| Feature | Status | Performance | Notes |
|---------|--------|-------------|-------|
| Core Framework | ✅ Full | Excellent | All features work |
| APCu Caching | ✅ Full | Excellent | After installation |
| Query Builder | ✅ Full | Excellent | All methods work |
| Async::run() | ⚠️ Limited | Poor | CLI fire-and-forget limited |
| Async::queue() | ✅ Full | Good | Production-ready |
| HTTP Async | ✅ Full | Good | Concurrent requests work |
| File Sessions | ✅ Full | Good | Default location: C:\Windows\Temp |
| DB Sessions | ✅ Full | Excellent | Recommended |
| Docker Support | ✅ Full | Good | Works with Docker Desktop |

---

### 🎯 Windows Best Practices

1. **Install APCu** - Critical for performance
2. **Use job queue** - Don't rely on `Async::run()` in CLI
3. **Use SSD** - Improves file I/O performance
4. **Match case** - Prepare for Linux deployment
5. **Test on Linux** - Before production deployment

---

### 🐛 Known Issues - Windows

#### Issue 1: Async Tasks Don't Execute
**Symptom**: `Async::run()` creates task files but they don't run

**Solution**: Use `Async::queue()` instead:
```php
Async::queue('MyJob', $data);
```

Run worker:
```bash
php worker.php --daemon
```

#### Issue 2: Slow Without APCu
**Symptom**: Requests take 20-50ms longer than expected

**Solution**: Install APCu:
```bash
php check_apcu.php  # Check status
# Follow instructions in install_apcu.bat
```

#### Issue 3: Path Issues When Deploying to Linux
**Symptom**: Works on Windows, fails on Linux with "file not found"

**Solution**: Use PHPWeave path constants:
```php
// ✅ Good - works everywhere
require_once PHPWEAVE_ROOT . '/models/user_model.php';

// ❌ Bad - hardcoded path
require_once 'D:\PHPWeave\models\user_model.php';
```

---

## 🍎 macOS

### ✅ Excellent Development Environment

macOS is Unix-based and behaves similarly to Linux, making it an excellent development environment.

---

### 🔧 Installation

#### Using Homebrew (Recommended)
```bash
# Install PHP 8.x
brew install php

# Install APCu
pecl install apcu

# Or use Homebrew PHP with APCu
brew tap shivammathur/php
brew install shivammathur/php/php@8.1
brew install shivammathur/extensions/apcu@8.1

# Verify
php -v
php -m | grep apcu
```

---

### ⚙️ Configuration

**php.ini** location:
```bash
# Find php.ini
php --ini

# Common locations
/usr/local/etc/php/8.1/php.ini  # Homebrew
/Applications/MAMP/bin/php/php8.1.0/conf/php.ini  # MAMP
```

**Recommended Settings**: Same as Linux (see above)

---

### ✅ Features - macOS

| Feature | Status | Performance | Notes |
|---------|--------|-------------|-------|
| Core Framework | ✅ Full | Excellent | All features work |
| APCu Caching | ✅ Full | Excellent | Via pecl or Homebrew |
| Query Builder | ✅ Full | Excellent | All methods work |
| Async::run() | ✅ Full | Excellent | Background tasks execute |
| Async::queue() | ✅ Full | Excellent | Production-ready |
| HTTP Async | ✅ Full | Excellent | Concurrent requests work |
| File Sessions | ✅ Full | Good | Default location: /tmp |
| DB Sessions | ✅ Full | Excellent | Recommended |
| Docker Support | ✅ Full | Good | Docker Desktop |

---

### 🎯 macOS Best Practices

1. **Use Homebrew** - Simplest PHP management
2. **Install APCu** via pecl or Homebrew extensions
3. **Use .ddev or Laravel Valet** - For local development
4. **Test on Linux** - macOS is similar but not identical

---

### 🐛 Known Issues - macOS

#### Issue 1: APCu Not Working After Install
**Symptom**: `php -m | grep apcu` shows nothing

**Solution**:
```bash
# Check which php.ini is being used
php --ini

# Add to correct php.ini
echo "extension=apcu.so" >> /usr/local/etc/php/8.1/php.ini
echo "apc.enabled=1" >> /usr/local/etc/php/8.1/php.ini
echo "apc.shm_size=32M" >> /usr/local/etc/php/8.1/php.ini
echo "apc.enable_cli=1" >> /usr/local/etc/php/8.1/php.ini

# Restart PHP-FPM
brew services restart php
```

#### Issue 2: Permission Denied on /tmp
**Symptom**: Session or cache errors about /tmp

**Solution**:
```bash
# Check permissions
ls -la /tmp

# Fix if needed
sudo chmod 1777 /tmp
```

---

## 🐳 Docker (All Platforms)

### ✅ Recommended for Production

Docker provides the **most consistent environment** across all platforms (Linux, Windows, macOS).

---

### 🔧 Quick Start

```bash
# Build and run
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f

# Stop
docker-compose down
```

---

### ⚙️ Docker Configurations

PHPWeave includes multiple Docker configurations:

#### 1. Development
```bash
docker-compose -f docker-compose.dev.yml up
```
- PHP with Xdebug
- Hot-reload enabled
- Dev tools included

#### 2. Production
```bash
docker-compose up -d
```
- Optimized PHP-FPM
- APCu enabled (optimal)
- Security hardened

#### 3. With Database
```bash
docker-compose -f docker-compose.env.yml up
```
- Includes MySQL/PostgreSQL
- Database connections pre-configured

#### 4. Scaled
```bash
docker-compose -f docker-compose.scale.yml up --scale app=3
```
- Multiple app instances
- Load balanced
- Shared cache (Redis)

---

### 🚀 Docker Performance

Docker + APCu provides **optimal performance**:

**Why Docker is Best**:
1. ✅ APCu works perfectly (native Linux container)
2. ✅ Async background tasks work (Linux process model)
3. ✅ Consistent across all host platforms
4. ✅ Easy horizontal scaling
5. ✅ Production-ready out of the box

**Performance**: Same as native Linux (negligible overhead)

---

### ✅ Features - Docker

| Feature | Status | Performance | Notes |
|---------|--------|-------------|-------|
| Core Framework | ✅ Full | Excellent | All features work |
| APCu Caching | ✅ Full | Excellent | Pre-configured |
| Query Builder | ✅ Full | Excellent | All methods work |
| Async::run() | ✅ Full | Excellent | Background tasks work |
| Async::queue() | ✅ Full | Excellent | Production-ready |
| HTTP Async | ✅ Full | Excellent | Concurrent requests work |
| File Sessions | ✅ Full | Good | Container volume |
| DB Sessions | ✅ Full | Excellent | Recommended |
| Scaling | ✅ Full | Excellent | Horizontal scaling ready |

---

### 🎯 Docker Best Practices

1. **Use volumes** for persistent data:
```yaml
volumes:
  - ./storage:/var/www/html/storage
  - ./cache:/var/www/html/cache
```

2. **Use Redis** for shared cache (scaling):
```bash
docker-compose -f docker-compose.scale.yml up
```

3. **Monitor resources**:
```bash
docker stats phpweave-app
```

4. **Keep images updated**:
```bash
docker-compose pull
docker-compose up -d --build
```

---

## 📊 Performance Comparison

### Request Speed (avg, with APCu)

| Environment | Request Time | Relative | Notes |
|-------------|--------------|----------|-------|
| Linux (bare metal) | 15-25ms | 100% (baseline) | Fastest |
| Docker (Linux host) | 15-27ms | 98% | Negligible overhead |
| macOS (bare metal) | 18-30ms | 85% | Slightly slower I/O |
| Docker (macOS) | 20-35ms | 75% | Docker Desktop overhead |
| Docker (Windows) | 22-40ms | 65% | Docker Desktop overhead |
| Windows (bare metal, APCu) | 20-35ms | 80% | With APCu installed |
| Windows (bare metal, no APCu) | 35-65ms | 45% | **Not recommended** |

**Note**: Times vary based on hardware. Relative performance is more important.

---

## 🔄 Cross-Platform Compatibility Checklist

### Code Portability

```php
// ✅ Do - Use framework constants
$path = PHPWEAVE_ROOT . '/models/user_model.php';
$cacheDir = CACHE_DIR;

// ✅ Do - Use DIRECTORY_SEPARATOR for paths
$path = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// ❌ Don't - Hardcode paths
$path = 'C:\PHPWeave\models\user.php';  // Windows only
$path = '/var/www/phpweave/models/user.php';  // Linux only

// ✅ Do - Use lowercase filenames (safe everywhere)
user_model.php  // Works on both Linux and Windows

// ⚠️ Caution - Mixed case (works on Windows, may fail on Linux)
User_model.php  // Case matters on Linux
```

### Configuration Portability

**Use .env for environment-specific settings**:
```ini
# .env
APP_ENV=production
CACHE_DRIVER=apcu        # Linux/Docker
# CACHE_DRIVER=file      # Fallback for Windows without APCu

# Database
DBHOST=localhost         # Local development
# DBHOST=mysql           # Docker container name

# Async (environment-specific)
ASYNC_MODE=queue         # Works everywhere
# ASYNC_MODE=fireforget  # Only Linux/Docker
```

---

## 🧪 Testing Across Environments

### Run Regression Tests

```bash
# On Linux
php tests/test_regression_suite.php

# On Windows
php tests/test_regression_suite.php

# On macOS
php tests/test_regression_suite.php

# In Docker
docker exec phpweave-app php tests/test_regression_suite.php
```

### Expected Results by Environment

| Environment | Expected Pass Rate | Notes |
|-------------|-------------------|-------|
| Linux + APCu | 98-99% | Optimal |
| macOS + APCu | 98-99% | Excellent |
| Docker + APCu | 98-99% | Optimal |
| Windows + APCu | 93-95% | Async CLI limitations |
| Windows (no APCu) | 90-93% | Slower, some cache tests fail |

---

## 🚨 Critical Environment-Specific Issues

### 1. Background Jobs (Async)

| Environment | Async::run() | Async::queue() | Recommendation |
|-------------|--------------|----------------|----------------|
| Linux | ✅ Works | ✅ Works | Use either |
| macOS | ✅ Works | ✅ Works | Use either |
| Docker | ✅ Works | ✅ Works | Use either |
| Windows CLI | ⚠️ Limited | ✅ Works | **Use queue only** |
| Windows IIS | ❓ Untested | ✅ Works | Use queue |

**Universal Solution**: Always use `Async::queue()` for production

---

### 2. APCu Availability

| Environment | APCu Ease | Performance Impact |
|-------------|-----------|-------------------|
| Linux | Easy (apt/yum) | +7-14ms if missing |
| macOS | Medium (pecl/brew) | +7-14ms if missing |
| Docker | Pre-installed | N/A (included) |
| Windows | Hard (manual DLL) | +7-14ms if missing |

**Recommendation**: Always install APCu, especially on Windows

---

### 3. File Permissions

| Environment | Complexity | Issues |
|-------------|------------|--------|
| Linux | Medium | Must set 775 on cache/storage |
| macOS | Low | Usually works out-of-box |
| Docker | Low | Handled by Dockerfile |
| Windows | None | No Unix permissions |

---

## 🎯 Deployment Recommendations

### Development
- **Windows**: ✅ Good with APCu, use `Async::queue()`
- **macOS**: ✅ Excellent, similar to Linux
- **Linux**: ✅ Best option if available
- **Docker**: ✅ Most consistent across team

### Staging
- **Docker**: ✅ Recommended (consistent with production)
- **Linux**: ✅ Good alternative

### Production
- **Docker**: ✅ **Highly Recommended** (scalable, consistent)
- **Linux (bare metal)**: ✅ Excellent for dedicated servers
- **Windows Server**: ⚠️ Possible but not recommended
- **macOS Server**: ❌ Not recommended

---

## 📝 Migration Path

### From Windows Dev → Linux Production

```bash
# 1. Test on Linux (locally via Docker)
docker-compose up -d
docker exec phpweave-app php tests/test_regression_suite.php

# 2. Check for issues
# - Path separators (should be fine with PHPWEAVE_ROOT)
# - File case sensitivity
# - Async functionality

# 3. Deploy to Linux
git clone your-repo
composer install  # If using Composer
cp .env.sample .env
# Configure .env for production

# 4. Verify
php tests/test_regression_suite.php
```

---

## 📚 Additional Resources

### Documentation by Environment
- **Linux**: `docs/DOCKER_DEPLOYMENT.md`
- **Docker**: `docs/DOCKER_DATABASE_SUPPORT.md`, `docs/DOCKER_CACHING_GUIDE.md`
- **Windows**: `install_apcu.bat`, `TROUBLESHOOTING_GUIDE.md`
- **All**: `REGRESSION_TEST_REPORT.md`, `INVESTIGATION_REPORT.md`

### Quick Checks
```bash
# Check environment
php -v
php -m | grep apcu
php check_apcu.php

# Test functionality
php tests/test_regression_suite.php

# Check async
php tests/test_async_params.php  # Will show limitations on Windows
```

---

## ✅ Summary Matrix

| Feature | Linux | macOS | Windows | Docker |
|---------|-------|-------|---------|--------|
| **Core Framework** | ✅ | ✅ | ✅ | ✅ |
| **Performance** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Async (full)** | ✅ | ✅ | ⚠️ | ✅ |
| **APCu Install** | Easy | Medium | Hard | Pre-installed |
| **Production Ready** | ✅ | ❌ | ⚠️ | ✅ |
| **Development** | ✅ | ✅ | ✅ | ✅ |
| **Scaling** | Good | N/A | Poor | Excellent |

---

**Recommendation**:
- **Development**: Use your preferred OS (install APCu on Windows)
- **Production**: Use Docker or Linux for best results
- **Scaling**: Docker with Redis is the optimal choice

---

**Last Updated**: 2025-11-12
**PHPWeave Version**: 2.6.0
**Tested Environments**: Linux (Ubuntu 20.04/22.04), Windows 10/11, macOS 13+, Docker 20+
