@echo off
REM BunkerWeb Management Script for PHPWeave (Windows)
REM Simplifies Docker Compose operations for BunkerWeb WAF setup
REM
REM Usage: bunkerweb.bat [command]
REM        bunkerweb.bat --local [command]  (Use local setup, no SSL/domain)

setlocal enabledelayedexpansion

REM Check for --local flag
set USE_LOCAL=false
set FIRST_ARG=%1
if /i "%1"=="--local" (
    set USE_LOCAL=true
    shift
) else if /i "%1"=="-l" (
    set USE_LOCAL=true
    shift
)

REM Set compose file based on mode
if "%USE_LOCAL%"=="true" (
    set COMPOSE_FILE=docker-compose.bunkerweb-local.yml
    set ENV_SAMPLE=.env.bunkerweb-local.sample
    set SETUP_TYPE=Local/Internal ^(No SSL/Domain^)
) else (
    set COMPOSE_FILE=docker-compose.bunkerweb.yml
    set ENV_SAMPLE=.env.bunkerweb.sample
    set SETUP_TYPE=Production ^(SSL/Domain^)
)

REM Check if Docker Compose file exists
if not exist "%COMPOSE_FILE%" (
    echo Error: %COMPOSE_FILE% not found!
    echo Please run this script from the PHPWeave root directory.
    exit /b 1
)

REM Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo Error: Docker is not running!
    echo Please start Docker Desktop and try again.
    exit /b 1
)

REM If no command, show menu
if "%FIRST_ARG%"=="" goto :show_menu
if "%1"=="" goto :show_menu

REM Process command
if /i "%1"=="setup" goto :cmd_setup
if /i "%1"=="start" goto :cmd_start
if /i "%1"=="stop" goto :cmd_stop
if /i "%1"=="restart" goto :cmd_restart
if /i "%1"=="down" goto :cmd_down
if /i "%1"=="destroy" goto :cmd_destroy
if /i "%1"=="status" goto :cmd_status
if /i "%1"=="logs" goto :cmd_logs
if /i "%1"=="logs-bw" goto :cmd_logs_bw
if /i "%1"=="logs-app" goto :cmd_logs_app
if /i "%1"=="logs-db" goto :cmd_logs_db
if /i "%1"=="stats" goto :cmd_stats
if /i "%1"=="update" goto :cmd_update
if /i "%1"=="reload" goto :cmd_reload
if /i "%1"=="shell-bw" goto :cmd_shell_bw
if /i "%1"=="shell-app" goto :cmd_shell_app
if /i "%1"=="shell-db" goto :cmd_shell_db
if /i "%1"=="test" goto :cmd_test
if /i "%1"=="verify" goto :cmd_verify
if /i "%1"=="cert" goto :cmd_cert
if /i "%1"=="backup" goto :cmd_backup
if /i "%1"=="list-backups" goto :cmd_list_backups
if /i "%1"=="debug" goto :cmd_debug
if /i "%1"=="health" goto :cmd_health
if /i "%1"=="errors" goto :cmd_errors
if /i "%1"=="info" goto :cmd_info
if /i "%1"=="help" goto :show_menu
if /i "%1"=="--help" goto :show_menu
if /i "%1"=="-h" goto :show_menu

echo Unknown command: %1
echo.
goto :show_menu

:show_menu
echo ============================================
echo   BunkerWeb WAF Management Script
echo   PHPWeave v2.6.0
echo ============================================
echo.
echo Mode: %SETUP_TYPE%
echo File: %COMPOSE_FILE%
echo.
if "%USE_LOCAL%"=="false" (
    echo To use local mode: bunkerweb.bat --local [command]
    echo.
)
echo Available Commands:
echo.
echo   Setup ^& Deployment:
echo     setup         - Initial setup (copy .env, pull images)
echo     start         - Start all services
echo     stop          - Stop all services
echo     restart       - Restart all services
echo     down          - Stop and remove containers (keeps data)
echo     destroy       - Stop and remove everything (INCLUDING DATA!)
echo.
echo   Monitoring ^& Logs:
echo     status        - Show service status
echo     logs          - View logs (all services)
echo     logs-bw       - View BunkerWeb logs only
echo     logs-app      - View PHPWeave app logs only
echo     logs-db       - View database logs only
echo     stats         - Show resource usage
echo.
echo   Maintenance:
echo     update        - Pull latest images and recreate containers
echo     reload        - Reload BunkerWeb config (no downtime)
echo     shell-bw      - Open shell in BunkerWeb container
echo     shell-app     - Open shell in PHPWeave container
echo     shell-db      - Open MySQL shell
echo.
echo   Testing ^& Validation:
echo     test          - Run security tests
echo     verify        - Verify setup and configuration
echo     cert          - Check SSL certificate status
echo.
echo   Backup ^& Restore:
echo     backup        - Backup volumes and configuration
echo     list-backups  - List available backups
echo.
echo   Troubleshooting:
echo     debug         - Show debug information
echo     health        - Check service health
echo     errors        - Show recent errors
echo.
echo   Information:
echo     info          - Show service URLs and credentials
echo     help          - Show this menu
echo.
exit /b 0

:cmd_setup
echo Running initial setup (%SETUP_TYPE%)...
echo.

REM Check if env sample exists
if not exist "%ENV_SAMPLE%" (
    echo Error: %ENV_SAMPLE% not found!
    echo Please make sure you are in the PHPWeave root directory.
    exit /b 1
)

REM Check if .env exists
if exist ".env" (
    echo .env file already exists.
    echo.
    set /p overwrite="Do you want to replace it with %SETUP_TYPE% config? (y/N): "
    if /i not "!overwrite!"=="y" (
        echo Keeping existing .env file.
        echo Make sure it has BunkerWeb configuration
        goto :setup_pull_images
    )
    echo Backing up existing .env to .env.backup...
    copy .env .env.backup >nul
)

echo Copying %ENV_SAMPLE% to .env...
copy "%ENV_SAMPLE%" .env >nul
echo.
echo WARNING: Edit .env and configure:
if "%USE_LOCAL%"=="false" (
    echo   - DOMAIN (your actual domain)
    echo   - EMAIL (for Let's Encrypt)
)
echo   - All passwords (BW_ADMIN_PASSWORD, MYSQL_ROOT_PASSWORD, DB_PASSWORD)
echo.
echo Opening .env in Notepad...
start notepad .env
pause

:setup_pull_images

echo.
echo Pulling Docker images...
docker compose -f "%COMPOSE_FILE%" pull

echo.
echo Setup complete!
echo.
echo Next steps:
echo   1. Make sure your domain DNS is configured
echo   2. Run: bunkerweb.bat start
echo.
goto :eof

:cmd_start
echo Starting all services...
docker compose -f "%COMPOSE_FILE%" up -d

echo.
echo Services started!
echo.
echo Waiting for services to be ready...
timeout /t 5 /nobreak >nul
call :cmd_status
echo.
call :cmd_info
goto :eof

:cmd_stop
echo Stopping all services...
docker compose -f "%COMPOSE_FILE%" stop
echo Services stopped.
goto :eof

:cmd_restart
echo Restarting all services...
docker compose -f "%COMPOSE_FILE%" restart
echo Services restarted.
timeout /t 3 /nobreak >nul
call :cmd_status
goto :eof

:cmd_down
echo Stopping and removing containers (data preserved)...
docker compose -f "%COMPOSE_FILE%" down
echo Containers removed. Volumes preserved.
goto :eof

:cmd_destroy
echo WARNING: This will DELETE ALL DATA including databases!
set /p confirm="Are you sure? Type 'yes' to confirm: "
if /i "%confirm%"=="yes" (
    echo Destroying all containers and volumes...
    docker compose -f "%COMPOSE_FILE%" down -v
    echo Everything removed.
) else (
    echo Cancelled.
)
goto :eof

:cmd_status
echo Service Status:
docker compose -f "%COMPOSE_FILE%" ps
goto :eof

:cmd_logs
echo Viewing logs (Ctrl+C to exit)...
docker compose -f "%COMPOSE_FILE%" logs -f
goto :eof

:cmd_logs_bw
echo Viewing BunkerWeb logs (Ctrl+C to exit)...
docker logs phpweave-bunkerweb -f
goto :eof

:cmd_logs_app
echo Viewing PHPWeave app logs (Ctrl+C to exit)...
docker logs phpweave-app -f
goto :eof

:cmd_logs_db
echo Viewing database logs (Ctrl+C to exit)...
docker logs phpweave-db -f
goto :eof

:cmd_stats
echo Resource Usage:
docker stats --no-stream phpweave-bunkerweb phpweave-app phpweave-db phpweave-redis
goto :eof

:cmd_update
echo Pulling latest images...
docker compose -f "%COMPOSE_FILE%" pull

echo.
echo Recreating containers with new images...
docker compose -f "%COMPOSE_FILE%" up -d

echo.
echo Update complete!
timeout /t 3 /nobreak >nul
call :cmd_status
goto :eof

:cmd_reload
echo Reloading BunkerWeb configuration...
docker exec phpweave-bw-scheduler bwcli reload
echo Configuration reloaded.
goto :eof

:cmd_shell_bw
echo Opening shell in BunkerWeb container...
docker exec -it phpweave-bunkerweb sh
goto :eof

:cmd_shell_app
echo Opening shell in PHPWeave container...
docker exec -it phpweave-app bash
goto :eof

:cmd_shell_db
echo Opening MySQL shell...
echo Enter MySQL root password when prompted.
docker exec -it phpweave-db mysql -u root -p
goto :eof

:cmd_test
if not exist ".env" (
    echo Error: .env file not found. Run 'bunkerweb.bat setup' first.
    exit /b 1
)

REM Get domain from .env
for /f "tokens=2 delims==" %%a in ('findstr /b "DOMAIN=" .env') do set DOMAIN=%%a
set DOMAIN=%DOMAIN:"=%
set DOMAIN=%DOMAIN:'=%

if "%DOMAIN%"=="" set DOMAIN=localhost
if "%DOMAIN%"=="www.example.com" set DOMAIN=localhost

if "%DOMAIN%"=="localhost" (
    set PROTOCOL=http
) else (
    set PROTOCOL=https
)

echo Running security tests against: %PROTOCOL%://%DOMAIN%
echo.

echo Test 1: Basic connectivity
curl -sI "%PROTOCOL%://%DOMAIN%" | findstr /r "^HTTP"
echo.

echo Test 2: Security headers
curl -sI "%PROTOCOL%://%DOMAIN%" | findstr /i "Strict-Transport X-Frame X-Content Referrer-Policy"
echo.

echo Test 3: WAF protection (SQL injection)
curl -s -o nul -w "HTTP Status: %%{http_code}" "%PROTOCOL%://%DOMAIN%/?id=1'%%20OR%%20'1'='1"
echo  (expected: 403)
echo.

echo Test 4: Bot detection
curl -s -A "sqlmap" -o nul -w "HTTP Status: %%{http_code}" "%PROTOCOL%://%DOMAIN%/"
echo  (expected: 403)
echo.

echo Tests complete!
goto :eof

:cmd_verify
echo Verifying BunkerWeb setup...
echo.

REM Check .env
if exist ".env" (
    echo [OK] .env file exists

    for /f "tokens=2 delims==" %%a in ('findstr /b "DOMAIN=" .env') do set DOMAIN=%%a
    if "!DOMAIN!"=="www.example.com" (
        echo [WARNING] DOMAIN not configured (still using example.com)
    ) else (
        echo [OK] DOMAIN configured: !DOMAIN!
    )
) else (
    echo [ERROR] .env file missing
)

echo.
echo Checking containers...
docker compose -f "%COMPOSE_FILE%" ps
echo.

echo Verification complete!
goto :eof

:cmd_cert
if not exist ".env" (
    echo Error: .env file not found.
    exit /b 1
)

for /f "tokens=2 delims==" %%a in ('findstr /b "DOMAIN=" .env') do set DOMAIN=%%a
set DOMAIN=%DOMAIN:"=%
set DOMAIN=%DOMAIN:'=%

if "%DOMAIN%"=="" (
    echo Domain not configured in .env
    exit /b 1
)
if "%DOMAIN%"=="www.example.com" (
    echo Domain not configured in .env
    exit /b 1
)

echo Checking SSL certificate for: %DOMAIN%
echo.

docker exec phpweave-bunkerweb ls /data/cache/letsencrypt 2>nul | findstr "%DOMAIN%" >nul
if errorlevel 1 (
    echo Certificate not found or not yet issued
    echo.
    echo Check BunkerWeb logs for Let's Encrypt status:
    docker logs phpweave-bunkerweb 2>&1 | findstr /i "let's encrypt" | more
) else (
    echo Let's Encrypt certificate found
)
goto :eof

:cmd_backup
set BACKUP_DIR=backups
set TIMESTAMP=%date:~-4%%date:~-10,2%%date:~-7,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo Creating backup...

REM Backup volumes
docker run --rm -v "%cd%\%BACKUP_DIR%:/backup" -v phpweave_bw-data:/bw-data:ro -v phpweave_db-data:/db-data:ro alpine tar czf "/backup/volumes-%TIMESTAMP%.tar.gz" /bw-data /db-data

REM Backup .env
copy .env "%BACKUP_DIR%\env-%TIMESTAMP%" >nul

REM Backup compose file
copy "%COMPOSE_FILE%" "%BACKUP_DIR%\compose-%TIMESTAMP%.yml" >nul

echo Backup created: %BACKUP_DIR%\volumes-%TIMESTAMP%.tar.gz
echo Config backed up: %BACKUP_DIR%\env-%TIMESTAMP%
goto :eof

:cmd_list_backups
if not exist "backups" (
    echo No backups found.
    goto :eof
)

echo Available backups:
dir /b backups
goto :eof

:cmd_debug
echo Debug Information:
echo.

echo === System Info ===
ver
echo.

echo === Docker Version ===
docker --version
docker compose version
echo.

echo === Container Status ===
docker compose -f "%COMPOSE_FILE%" ps
echo.

echo === Recent BunkerWeb Logs ===
docker logs phpweave-bunkerweb --tail 20
echo.

echo === Recent Scheduler Logs ===
docker logs phpweave-bw-scheduler --tail 20
echo.
goto :eof

:cmd_health
echo Health Check:
echo.

set services=phpweave-bunkerweb phpweave-bw-scheduler phpweave-bw-ui phpweave-bw-db phpweave-redis phpweave-app phpweave-db

for %%s in (%services%) do (
    docker ps | findstr "%%s" >nul
    if errorlevel 1 (
        echo [ERROR] %%s: not running
    ) else (
        echo [OK] %%s: running
    )
)
goto :eof

:cmd_errors
echo Recent Errors:
echo.

echo === BunkerWeb Errors ===
docker logs phpweave-bunkerweb 2>&1 | findstr /i error | more

echo.
echo === PHPWeave App Errors ===
docker logs phpweave-app 2>&1 | findstr /i error | more

echo.
echo === Database Errors ===
docker logs phpweave-db 2>&1 | findstr /i error | more
goto :eof

:cmd_info
echo Service Information:
echo.

if exist ".env" (
    for /f "tokens=2 delims==" %%a in ('findstr /b "DOMAIN=" .env 2^>nul') do set DOMAIN=%%a
    set DOMAIN=!DOMAIN:"=!
    set DOMAIN=!DOMAIN:'=!

    for /f "tokens=2 delims==" %%a in ('findstr /b "BW_ADMIN_USER=" .env 2^>nul') do set ADMIN_USER=%%a
    set ADMIN_USER=!ADMIN_USER:"=!
    set ADMIN_USER=!ADMIN_USER:'=!

    if "!ADMIN_USER!"=="" set ADMIN_USER=admin

    echo Access URLs:
    if "!DOMAIN!"=="www.example.com" (
        echo   PHPWeave (via WAF): http://localhost (configure DOMAIN in .env)
    ) else if "!DOMAIN!"=="" (
        echo   PHPWeave (via WAF): http://localhost (configure DOMAIN in .env)
    ) else (
        echo   PHPWeave (via WAF): https://!DOMAIN!
    )
    echo   Admin UI:           http://localhost:7000
    echo   phpMyAdmin:         http://localhost:8081
    echo.

    echo Admin Credentials:
    echo   Username: !ADMIN_USER!
    echo   Password: (see BW_ADMIN_PASSWORD in .env)
    echo.
)

echo Management Commands:
echo   View logs:    bunkerweb.bat logs
echo   Check status: bunkerweb.bat status
echo   Run tests:    bunkerweb.bat test
echo   Get help:     bunkerweb.bat help
echo.
goto :eof

:eof
endlocal
