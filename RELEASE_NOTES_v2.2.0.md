# PHPWeave v2.2.0 Release Notes

**Release Date:** October 29, 2025

## 🎉 What's New in v2.2.0

PHPWeave v2.2.0 brings **comprehensive multi-database support** to the framework! You can now seamlessly use MySQL, PostgreSQL, SQLite, SQL Server, and more in both traditional and Docker deployments.

---

## 🚀 Major Features

### Multi-Database Driver Support

PHPWeave now supports **6 different database systems** through PDO drivers:

| Database | Driver | Default Port | Status |
|----------|--------|--------------|--------|
| MySQL/MariaDB | `pdo_mysql` | 3306 | ✅ Default |
| PostgreSQL | `pdo_pgsql` | 5432 | ✅ Ready |
| SQLite | `pdo_sqlite` | N/A | ✅ Ready |
| SQL Server | `pdo_dblib` | 1433 | ✅ Ready |
| SQL Server | `pdo_sqlsrv` | 1433 | ✅ Ready |
| ODBC | `pdo_odbc` | Varies | ✅ Ready |

### New Configuration Variables

Three new configuration variables (with defaults for backward compatibility):

```ini
# .env file
DBDRIVER=pdo_mysql   # Database driver
DBPORT=3306          # Database port
DBDSN=               # ODBC DSN string (optional)
```

**Environment variable naming:**
- Modern: `DB_DRIVER`, `DB_PORT`, `DB_DSN`
- Legacy: `DBDRIVER`, `DBPORT`, `DBDSN`

Both naming conventions work simultaneously!

### Docker Image Enhancements

The Docker image now includes **all PDO extensions pre-installed**:
- PostgreSQL support (`pdo_pgsql`)
- SQLite support (`pdo_sqlite`)
- SQL Server support (`pdo_dblib`, `pdo_odbc`)

**Zero configuration needed** - all drivers ready to use!

---

## 🔧 Technical Improvements

### Database Connection Class (`DBConnection`)

**Fixed Critical Bugs:**
- ✅ Missing `break` statement in ODBC case (would always throw exception)
- ✅ Incorrect SQL Server DSN format (now uses comma for port separator)
- ✅ PostgreSQL charset parameter (now uses proper `client_encoding`)
- ✅ SQLite credential handling (no longer passes unnecessary username/password)
- ✅ Added validation for ODBC connections (ensures DSN is provided)

**Enhancements:**
- ✅ Added `pdo_dblib` driver support (SQL Server via FreeTDS)
- ✅ Proper DSN formatting for all database types
- ✅ Nullable `$user` and `$password` properties for SQLite compatibility
- ✅ Added `$port` property with proper PHPDoc
- ✅ Backward compatibility with default values

### Configuration Loading

**Enhanced `public/index.php`:**
- ✅ Automatic defaults for new configuration variables
- ✅ Works with both `.env` files and environment variables
- ✅ Full backward compatibility (old `.env` files work without changes)

---

## 📚 Documentation

### New Documentation

**`docs/DOCKER_DATABASE_SUPPORT.md`** - 550+ line comprehensive guide including:
- Quick start examples for all database types
- Complete docker-compose examples
- Environment variable reference
- Database port reference
- Testing procedures
- Troubleshooting guide
- Production best practices
- Migration guide between databases
- Performance optimization tips

### Updated Documentation

- ✅ `docs/DOCKER_DEPLOYMENT.md` - Added database driver table and multi-database info
- ✅ `docs/README.md` - Added new guide to documentation index
- ✅ `README.md` - Updated server requirements and docs list
- ✅ `CLAUDE.md` - Complete database driver documentation
- ✅ `.env.docker.sample` - Consolidated configuration with all drivers documented

---

## 🐳 Docker Deployment

### Quick Start Examples

**PostgreSQL:**
```bash
export DB_DRIVER=pdo_pgsql
export DB_PORT=5432
docker compose -f docker-compose.env.yml up -d
```

**SQLite (no database container needed):**
```bash
export DB_DRIVER=pdo_sqlite
export DB_NAME=/var/www/html/storage/database.sqlite
docker compose -f docker-compose.env.yml up -d
```

**SQL Server:**
```bash
export DB_DRIVER=pdo_dblib
export DB_PORT=1433
docker compose -f docker-compose.sqlserver.yml up -d
```

All docker-compose files now include `DB_DRIVER`, `DB_PORT`, and `DB_DSN` environment variables.

---

## 🔄 Migration Guide

### From v2.1.x to v2.2.0

**Good News: Zero Breaking Changes!**

Your existing applications will work without any modifications:
- ✅ Old `.env` files work (defaults to MySQL)
- ✅ Old code continues to work
- ✅ No changes to models, controllers, or views needed

**To Use New Database Drivers:**

1. **Update your `.env` file** (optional):
```ini
DBDRIVER=pdo_pgsql  # Change to desired driver
DBPORT=5432         # Update port if needed
```

2. **Or use environment variables**:
```bash
export DB_DRIVER=pdo_pgsql
export DB_PORT=5432
```

3. **That's it!** No code changes required.

---

## 🧪 Testing

All changes have been tested with:
- ✅ PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- ✅ MySQL 5.6+, 8.0
- ✅ PHPStan level 5 static analysis
- ✅ GitHub Actions CI/CD workflows
- ✅ Docker container deployments

---

## 📊 Compatibility

### PHP Version Support
- PHP 7.4+ ✅
- PHP 8.0+ ✅
- PHP 8.1+ ✅
- PHP 8.2+ ✅
- PHP 8.3+ ✅
- PHP 8.4 ✅

### Database Support (NEW!)
- MySQL 5.6+ ✅
- MariaDB 10.0+ ✅
- PostgreSQL 9.6+ ✅
- SQLite 3.x ✅
- SQL Server 2012+ ✅
- ODBC connections ✅

### Backward Compatibility
- 100% backward compatible with v2.1.x ✅
- Old `.env` files work without changes ✅
- All existing code continues to work ✅

---

## 🔒 Security

All new code follows PHPWeave's security standards:
- ✅ Prepared statements for all database types
- ✅ Secure DSN formatting
- ✅ Input validation for configuration
- ✅ No serialization vulnerabilities
- ✅ PHPStan level 5 compliant

---

## 📦 Files Changed

**Core Files:**
- `coreapp/dbconnection.php` - Multi-database support, bug fixes
- `public/index.php` - Enhanced configuration loading with defaults

**Docker Files:**
- `Dockerfile` - Added PDO extensions for all databases
- `docker-compose.yml` - Updated environment variables
- `docker-compose.env.yml` - Added DB_DRIVER, DB_PORT, DB_DSN with examples
- `docker-compose.dev.yml` - Added database configuration variables
- `docker-compose.scale.yml` - Added database configuration variables

**Configuration Files:**
- `.env.docker.sample` - Consolidated with comprehensive driver documentation
- `.env.sample` - Already includes DBDRIVER and DBPORT

**Documentation:**
- `docs/DOCKER_DATABASE_SUPPORT.md` - NEW comprehensive guide
- `docs/DOCKER_DEPLOYMENT.md` - Updated with database support info
- `docs/README.md` - Added new guide to index
- `README.md` - Updated database support information
- `CLAUDE.md` - Complete database driver documentation
- `CHANGELOG.md` - Complete changelog with v2.2.0 entry

**Removed:**
- `.env.sample.docker` - Consolidated into `.env.docker.sample`

---

## 🎯 What's Next

Future improvements planned:
- Database connection pooling for high-traffic scenarios
- Built-in migration system
- Database-agnostic query builder (optional)
- Performance benchmarks across database types

---

## 📝 Upgrade Instructions

### Step 1: Update Your Code

```bash
git pull origin main
# or
git fetch origin
git checkout v2.2.0
```

### Step 2: Update Dependencies (if using Composer)

```bash
composer update
```

### Step 3: Test Your Application

```bash
# Test with existing MySQL setup (should work unchanged)
php tests/test_*.php

# Test Docker deployment
docker compose build
docker compose up -d
```

### Step 4: (Optional) Switch Database

If you want to use a different database:

1. Update `.env` with new driver:
```ini
DBDRIVER=pdo_pgsql
DBPORT=5432
```

2. Update docker-compose to use new database service
3. Restart containers

---

## 🙏 Acknowledgments

This release includes contributions and improvements to:
- Multi-database support architecture
- Docker image optimization
- Comprehensive documentation
- Backward compatibility maintenance

---

## 📞 Support

- **Documentation**: `docs/DOCKER_DATABASE_SUPPORT.md`
- **Issues**: https://github.com/clintcan/PHPWeave/issues
- **Security**: See `SECURITY.md`
- **Community**: See `CODE_OF_CONDUCT.md`

---

## 🔗 Links

- **GitHub Repository**: https://github.com/clintcan/PHPWeave
- **Full Changelog**: See `CHANGELOG.md`
- **Migration Guide**: This document, section above
- **Docker Guide**: `docs/DOCKER_DEPLOYMENT.md`
- **Database Guide**: `docs/DOCKER_DATABASE_SUPPORT.md`

---

**PHPWeave v2.2.0** - Multi-database support for modern PHP applications! 🚀
