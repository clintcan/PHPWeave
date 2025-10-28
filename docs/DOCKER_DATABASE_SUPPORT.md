# PHPWeave Docker Database Support Guide

Complete guide for using different database systems with PHPWeave in Docker environments.

## Supported Database Systems

PHPWeave supports multiple database systems through PDO drivers. All drivers are pre-installed in the Docker image:

- **MySQL/MariaDB** (default) - pdo_mysql
- **PostgreSQL** - pdo_pgsql
- **SQLite** - pdo_sqlite
- **SQL Server** - pdo_sqlsrv, pdo_dblib
- **ODBC** - pdo_odbc (for various databases)

## Quick Start Examples

### MySQL (Default Configuration)

```bash
# Uses default MySQL settings
docker compose -f docker-compose.env.yml up -d
```

**Environment Variables:**
```bash
DB_DRIVER=pdo_mysql
DB_HOST=db
DB_PORT=3306
DB_NAME=phpweave
DB_USER=phpweave_user
DB_PASSWORD=phpweave_pass
DB_CHARSET=utf8mb4
```

---

### PostgreSQL

**1. Set environment variables:**
```bash
export DB_DRIVER=pdo_pgsql
export DB_HOST=db
export DB_PORT=5432
export DB_NAME=phpweave
export DB_USER=phpweave_user
export DB_PASSWORD=phpweave_pass
export DB_CHARSET=utf8
```

**2. Create `docker-compose.postgres.yml`:**
```yaml
services:
  phpweave:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: phpweave-app
    ports:
      - "8080:80"
    environment:
      - DOCKER_ENV=production
      - DB_DRIVER=pdo_pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_NAME=phpweave
      - DB_USER=phpweave_user
      - DB_PASSWORD=phpweave_pass
      - DB_CHARSET=utf8
    depends_on:
      - db
    networks:
      - phpweave-network

  db:
    image: postgres:15-alpine
    container_name: phpweave-postgres
    environment:
      POSTGRES_DB: phpweave
      POSTGRES_USER: phpweave_user
      POSTGRES_PASSWORD: phpweave_pass
    volumes:
      - postgres-data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - phpweave-network

volumes:
  postgres-data:

networks:
  phpweave-network:
    driver: bridge
```

**3. Start services:**
```bash
docker compose -f docker-compose.postgres.yml up -d
```

---

### SQLite (Embedded Database)

**1. Set environment variables:**
```bash
export DB_DRIVER=pdo_sqlite
export DB_NAME=/var/www/html/storage/database.sqlite
```

**2. Create `docker-compose.sqlite.yml`:**
```yaml
services:
  phpweave:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: phpweave-app
    ports:
      - "8080:80"
    environment:
      - DOCKER_ENV=production
      - DB_DRIVER=pdo_sqlite
      - DB_NAME=/var/www/html/storage/database.sqlite
      - DEBUG=0
    volumes:
      # Persist SQLite database file
      - sqlite-data:/var/www/html/storage
    networks:
      - phpweave-network

volumes:
  sqlite-data:

networks:
  phpweave-network:
    driver: bridge
```

**3. Start services:**
```bash
# Create storage directory first
mkdir -p storage
docker compose -f docker-compose.sqlite.yml up -d

# Initialize database (if needed)
docker exec phpweave-app touch /var/www/html/storage/database.sqlite
docker exec phpweave-app chmod 664 /var/www/html/storage/database.sqlite
```

**Notes:**
- No separate database container needed
- Database file stored in volume for persistence
- Ideal for development, testing, or small applications

---

### SQL Server (via FreeTDS)

**1. Set environment variables:**
```bash
export DB_DRIVER=pdo_dblib
export DB_HOST=sqlserver
export DB_PORT=1433
export DB_NAME=phpweave
export DB_USER=sa
export DB_PASSWORD=YourStrong@Passw0rd
```

**2. Create `docker-compose.sqlserver.yml`:**
```yaml
services:
  phpweave:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: phpweave-app
    ports:
      - "8080:80"
    environment:
      - DOCKER_ENV=production
      - DB_DRIVER=pdo_dblib
      - DB_HOST=sqlserver
      - DB_PORT=1433
      - DB_NAME=phpweave
      - DB_USER=sa
      - DB_PASSWORD=YourStrong@Passw0rd
    depends_on:
      - sqlserver
    networks:
      - phpweave-network

  sqlserver:
    image: mcr.microsoft.com/mssql/server:2022-latest
    container_name: phpweave-sqlserver
    environment:
      ACCEPT_EULA: "Y"
      SA_PASSWORD: YourStrong@Passw0rd
      MSSQL_PID: Express
    volumes:
      - sqlserver-data:/var/opt/mssql
    ports:
      - "1433:1433"
    networks:
      - phpweave-network

volumes:
  sqlserver-data:

networks:
  phpweave-network:
    driver: bridge
```

**3. Start services:**
```bash
docker compose -f docker-compose.sqlserver.yml up -d

# Wait for SQL Server to initialize (30-60 seconds)
docker logs -f phpweave-sqlserver

# Create database
docker exec phpweave-sqlserver /opt/mssql-tools/bin/sqlcmd \
  -S localhost -U sa -P "YourStrong@Passw0rd" \
  -Q "CREATE DATABASE phpweave"
```

---

### ODBC (Custom Connections)

For databases requiring ODBC connections (Firebird, Oracle, etc.):

**1. Set environment variables:**
```bash
export DB_DRIVER=pdo_odbc
export DB_DSN="Driver={Firebird};Dbname=/path/to/database.fdb;Host=localhost;Port=3050"
export DB_USER=SYSDBA
export DB_PASSWORD=masterkey
```

**2. Update docker-compose to include ODBC DSN:**
```yaml
services:
  phpweave:
    environment:
      - DB_DRIVER=pdo_odbc
      - DB_DSN=Driver={Firebird};Dbname=/path/to/database.fdb;Host=localhost;Port=3050
      - DB_USER=SYSDBA
      - DB_PASSWORD=masterkey
```

---

## Environment Variable Reference

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_DRIVER` | PDO driver name | `pdo_mysql` |
| `DB_HOST` | Database hostname | `db` or `localhost` |
| `DB_NAME` | Database name | `phpweave` |
| `DB_USER` | Database username | `phpweave_user` |
| `DB_PASSWORD` | Database password | `secure_password` |

### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `DB_PORT` | Database port | Varies | `3306` (MySQL), `5432` (PostgreSQL) |
| `DB_CHARSET` | Character encoding | `utf8mb4` | `utf8`, `utf8mb4` |
| `DB_DSN` | ODBC DSN string | Empty | `Driver={...};Dbname=...` |

### Legacy Variable Names

PHPWeave also supports legacy naming (without `DB_` prefix):

- `DBHOST` → `DB_HOST`
- `DBNAME` → `DB_NAME`
- `DBUSER` → `DB_USER`
- `DBPASSWORD` → `DB_PASSWORD`
- `DBCHARSET` → `DB_CHARSET`
- `DBPORT` → `DB_PORT`
- `DBDRIVER` → `DB_DRIVER`
- `DBDSN` → `DB_DSN`

Both naming conventions work simultaneously.

---

## Database Port Reference

| Database | Default Port | Environment Variable |
|----------|--------------|---------------------|
| MySQL/MariaDB | 3306 | `DB_PORT=3306` |
| PostgreSQL | 5432 | `DB_PORT=5432` |
| SQLite | N/A | Not applicable |
| SQL Server | 1433 | `DB_PORT=1433` |
| Firebird | 3050 | `DB_PORT=3050` |
| Oracle | 1521 | `DB_PORT=1521` |

---

## Testing Database Connections

### Test from PHPWeave Container

```bash
# MySQL
docker exec phpweave-app php -r "new PDO('mysql:host=db;port=3306;dbname=phpweave', 'phpweave_user', 'phpweave_pass'); echo 'Connected!';"

# PostgreSQL
docker exec phpweave-app php -r "new PDO('pgsql:host=db;port=5432;dbname=phpweave', 'phpweave_user', 'phpweave_pass'); echo 'Connected!';"

# SQLite
docker exec phpweave-app php -r "new PDO('sqlite:/var/www/html/storage/database.sqlite'); echo 'Connected!';"
```

### Test from Database Container

```bash
# MySQL
docker exec phpweave-db mysql -uphpweave_user -pphpweave_pass -e "SELECT 1;"

# PostgreSQL
docker exec phpweave-postgres psql -U phpweave_user -d phpweave -c "SELECT 1;"

# SQL Server
docker exec phpweave-sqlserver /opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P "YourStrong@Passw0rd" -Q "SELECT 1"
```

---

## Troubleshooting

### Connection Refused

**Problem:** Can't connect to database from PHP container.

**Solutions:**
1. Verify database container is running: `docker ps`
2. Check database container logs: `docker logs phpweave-db`
3. Verify network connectivity: `docker exec phpweave-app ping db`
4. Ensure correct hostname (use service name from docker-compose)

### Driver Not Found

**Problem:** `could not find driver` error.

**Solutions:**
1. Verify driver is installed: `docker exec phpweave-app php -m | grep pdo`
2. Rebuild Docker image: `docker compose build --no-cache`
3. Check Dockerfile includes driver installation

### Authentication Failed

**Problem:** Access denied or authentication error.

**Solutions:**
1. Verify credentials match in both containers
2. Check database user has correct permissions
3. For SQL Server, ensure password meets complexity requirements

### Database Not Created

**Problem:** Database doesn't exist after starting containers.

**Solutions:**
1. For MySQL/PostgreSQL, database is created automatically by environment variables
2. For SQL Server, create database manually (see SQL Server section)
3. For SQLite, create file manually: `touch storage/database.sqlite`

---

## Production Best Practices

### 1. Use Secrets for Passwords

**Docker Swarm:**
```yaml
services:
  phpweave:
    environment:
      - DB_PASSWORD_FILE=/run/secrets/db_password
    secrets:
      - db_password

secrets:
  db_password:
    external: true
```

**Kubernetes:**
```yaml
env:
  - name: DB_PASSWORD
    valueFrom:
      secretKeyRef:
        name: phpweave-secrets
        key: db-password
```

### 2. Separate Database Servers

For production, use managed database services:
- AWS RDS (MySQL, PostgreSQL, SQL Server)
- Azure Database
- Google Cloud SQL
- DigitalOcean Managed Databases

**Example with external database:**
```yaml
services:
  phpweave:
    environment:
      - DB_DRIVER=pdo_mysql
      - DB_HOST=mysql-prod.abc123.us-east-1.rds.amazonaws.com
      - DB_PORT=3306
      - DB_NAME=phpweave_prod
      - DB_USER=phpweave_app
      - DB_PASSWORD=${DB_PASSWORD}  # From environment
```

### 3. Connection Pooling

For high-traffic applications, consider using connection pooling:
- ProxySQL (MySQL)
- PgBouncer (PostgreSQL)
- SQL Server Connection Pooling (built-in)

### 4. Health Checks

Add database health checks to docker-compose:

```yaml
services:
  db:
    image: mysql:8.0
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  phpweave:
    depends_on:
      db:
        condition: service_healthy
```

---

## Migration Guide

### From MySQL to PostgreSQL

**1. Export MySQL data:**
```bash
docker exec phpweave-db mysqldump -u phpweave_user -pphpweave_pass phpweave > dump.sql
```

**2. Convert to PostgreSQL (use pgloader or manual conversion)**

**3. Update environment variables:**
```bash
export DB_DRIVER=pdo_pgsql
export DB_PORT=5432
```

**4. Import to PostgreSQL:**
```bash
docker exec -i phpweave-postgres psql -U phpweave_user -d phpweave < converted_dump.sql
```

### From PostgreSQL to MySQL

Similar process in reverse using `pg_dump` and MySQL import.

---

## Performance Optimization

### MySQL

```yaml
db:
  image: mysql:8.0
  command: --default-authentication-plugin=mysql_native_password --max-connections=200 --innodb-buffer-pool-size=256M
```

### PostgreSQL

```yaml
db:
  image: postgres:15-alpine
  command: -c max_connections=200 -c shared_buffers=256MB -c effective_cache_size=1GB
```

---

## Next Steps

1. Choose your database system
2. Copy appropriate docker-compose example
3. Update environment variables
4. Start containers
5. Test connection
6. Run migrations/seed data

For more information:
- **DOCKER_DEPLOYMENT.md** - General Docker deployment guide
- **DOCKER_CACHING_GUIDE.md** - APCu caching strategies
- **README.md** - Main PHPWeave documentation
