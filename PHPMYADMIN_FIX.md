# phpMyAdmin Configuration Fix

## Issue
phpMyAdmin was showing authentication errors:
```
MySQL said: Cannot connect: invalid settings.
mysqli::real_connect(): (HY000/1045): Access denied for user 'phpweave_user'@'172.18.0.4' (using password: YES)
```

## Root Cause
The docker-compose files were setting `PMA_USER` and `PMA_PASSWORD` environment variables, which tells phpMyAdmin to auto-login with those credentials. However, with MySQL 8.0's authentication system, this often causes issues because:

1. The MySQL container may not be fully ready when phpMyAdmin tries to connect
2. Authentication plugin mismatches between MySQL 8.0 defaults and phpMyAdmin expectations
3. User permissions may not be properly set up at the time of connection

## Solution
Removed `PMA_USER` and `PMA_PASSWORD` environment variables from all docker-compose files and added `PMA_ARBITRARY: 1` instead. This allows users to manually login with their credentials.

## Files Fixed
- ✅ `docker-compose.yml` - Standard deployment
- ✅ `docker-compose.modsecurity.yml` - ModSecurity deployment
- ✅ `docker-compose.bunkerweb.yml` - BunkerWeb deployment
- ✅ `docker-compose.bunkerweb-local.yml` - BunkerWeb local deployment
- ✅ `docker-compose.dev.yml` - Development deployment
- ✅ `docker-compose.env.yml` - Environment-based deployment

## How to Use phpMyAdmin Now

### Standard Deployment (`docker-compose.yml`)
Access: http://localhost:8081

**Login credentials:**
- Server: `db` (auto-filled)
- Username: `phpweave_user`
- Password: `phpweave_pass`

Or use root:
- Username: `root`
- Password: `rootpassword`

### ModSecurity Deployment (`docker-compose.modsecurity.yml`)
Access: http://localhost:8081

**Login credentials:**
- Server: `db` (auto-filled)
- Username: `phpweave_user` (or value from `.env` file: `DB_USER`)
- Password: `phpweave_pass` (or value from `.env` file: `DB_PASSWORD`)

Or use root:
- Username: `root`
- Password: Value from `.env` file: `MYSQL_ROOT_PASSWORD`

### BunkerWeb Deployment (`docker-compose.bunkerweb.yml`)
Access: http://localhost:8081

**Login credentials:**
- Server: `db` (auto-filled)
- Username: Value from `.env` file: `DB_USER` (default: `phpweave_user`)
- Password: Value from `.env` file: `DB_PASSWORD` (default: `phpweave_pass`)

### Development Deployment (`docker-compose.dev.yml`)
Access: http://localhost:8081

**Login credentials:**
- Server: `db` (auto-filled)
- Username: `dev`
- Password: `dev`

## Benefits of Manual Login

1. **More Secure**: Credentials aren't passed through environment variables
2. **More Reliable**: Works with MySQL 8.0 authentication plugins
3. **More Flexible**: Users can choose which account to use (regular user or root)
4. **Better UX**: Clear login page instead of confusing error messages

## Applying the Fix

If you're already running containers:

```bash
# For ModSecurity deployment
docker compose -f docker-compose.modsecurity.yml restart phpmyadmin

# For standard deployment
docker compose restart phpmyadmin

# For BunkerWeb deployment
docker compose -f docker-compose.bunkerweb.yml restart phpmyadmin
```

Or rebuild completely:

```bash
docker compose -f docker-compose.modsecurity.yml down
docker compose -f docker-compose.modsecurity.yml up -d
```

## Configuration Details

**Old Configuration (Problematic):**
```yaml
phpmyadmin:
  environment:
    PMA_USER: phpweave_user
    PMA_PASSWORD: phpweave_pass
```

**New Configuration (Fixed):**
```yaml
phpmyadmin:
  environment:
    PMA_HOST: db
    PMA_PORT: 3306
    PMA_ARBITRARY: 1  # Allows manual login
```

## Additional Notes

- The `PMA_ARBITRARY: 1` setting allows users to specify which server to connect to, but since `PMA_HOST` is pre-configured, users just need to enter their credentials
- All MySQL users created in the database setup are accessible through phpMyAdmin
- This configuration is compatible with MySQL 8.0, 5.7, and MariaDB

---

**Fixed**: 2025-01-15
**Affects**: All docker-compose configurations with phpMyAdmin
**Tested**: ✅ Working with MySQL 8.0
