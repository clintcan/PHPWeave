@echo off
REM APCu Installation Script for Windows
REM For PHP 8.4 x64 Thread Safe (ZTS)

echo ============================================================
echo APCu Installation for PHP 8.4 (Windows x64 TS)
echo ============================================================
echo.

REM Check if we're running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running with administrator privileges... OK
) else (
    echo WARNING: Not running as administrator
    echo Some operations may fail. Consider running as admin.
)
echo.

REM Set variables
set PHP_DIR=C:\php
set EXT_DIR=%PHP_DIR%\ext
set PHP_INI=%PHP_DIR%\php.ini

echo PHP Directory: %PHP_DIR%
echo Extension Directory: %EXT_DIR%
echo PHP INI: %PHP_INI%
echo.

REM Check PHP version
echo Checking PHP version...
php -v | findstr "8.4"
if %errorLevel% == 0 (
    echo PHP 8.4 detected... OK
) else (
    echo ERROR: PHP 8.4 not detected
    pause
    exit /b 1
)
echo.

REM Download APCu DLL
echo ============================================================
echo STEP 1: Download APCu Extension
echo ============================================================
echo.
echo You need to download APCu for PHP 8.4 TS x64
echo.
echo Visit: https://windows.php.net/downloads/pecl/releases/apcu/
echo.
echo Look for: php_apcu-5.x.x-8.4-ts-vs16-x64.zip
echo (Use the latest version available)
echo.
echo Alternative PECL site: https://pecl.php.net/package/APCu
echo.
echo Once downloaded:
echo 1. Extract the ZIP file
echo 2. Find php_apcu.dll
echo 3. Copy it to: %EXT_DIR%
echo.

set /p DOWNLOADED="Have you downloaded and copied php_apcu.dll to %EXT_DIR%? (y/n): "
if /i not "%DOWNLOADED%"=="y" (
    echo.
    echo Please download and copy the file first, then run this script again.
    echo.
    echo Opening browser to PECL downloads...
    start https://windows.php.net/downloads/pecl/releases/apcu/
    pause
    exit /b 0
)

REM Verify DLL exists
if not exist "%EXT_DIR%\php_apcu.dll" (
    echo.
    echo ERROR: php_apcu.dll not found in %EXT_DIR%
    echo Please copy the file and try again.
    pause
    exit /b 1
)

echo.
echo Found: %EXT_DIR%\php_apcu.dll ... OK
echo.

REM Backup php.ini
echo ============================================================
echo STEP 2: Configure PHP
echo ============================================================
echo.
echo Creating backup of php.ini...
copy "%PHP_INI%" "%PHP_INI%.backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul 2>&1
if %errorLevel% == 0 (
    echo Backup created... OK
) else (
    echo WARNING: Could not create backup
)
echo.

REM Check if already enabled
findstr /C:"extension=apcu" "%PHP_INI%" >nul 2>&1
if %errorLevel% == 0 (
    echo APCu extension already configured in php.ini
    echo Checking if it's enabled...
    findstr /C:";extension=apcu" "%PHP_INI%" >nul 2>&1
    if %errorLevel% == 0 (
        echo Extension is commented out. Uncommenting...
        powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=apcu', 'extension=apcu' | Set-Content '%PHP_INI%'"
    )
) else (
    echo Adding APCu extension to php.ini...
    echo. >> "%PHP_INI%"
    echo ; APCu - PHP opcode cache >> "%PHP_INI%"
    echo extension=apcu >> "%PHP_INI%"
    echo apc.enabled=1 >> "%PHP_INI%"
    echo apc.shm_size=32M >> "%PHP_INI%"
    echo apc.enable_cli=1 >> "%PHP_INI%"
    echo. >> "%PHP_INI%"
    echo Configuration added... OK
)

echo.
echo ============================================================
echo STEP 3: Verify Installation
echo ============================================================
echo.

REM Test APCu
echo Testing APCu installation...
php -m | findstr -i "apcu"
if %errorLevel% == 0 (
    echo.
    echo ============================================================
    echo SUCCESS! APCu is now installed and enabled
    echo ============================================================
    echo.
    php -r "echo 'APCu Version: ' . phpversion('apcu') . PHP_EOL;"
    echo.
    echo APCu is ready to use!
    echo.
    echo Configuration:
    php -r "echo '  Memory Size: ' . ini_get('apc.shm_size') . PHP_EOL;"
    php -r "echo '  CLI Enabled: ' . (ini_get('apc.enable_cli') ? 'Yes' : 'No') . PHP_EOL;"
    echo.
    echo You can now run the regression tests again to see improved results.
    echo.
) else (
    echo.
    echo ============================================================
    echo ERROR: APCu not detected
    echo ============================================================
    echo.
    echo Troubleshooting:
    echo 1. Verify php_apcu.dll is in %EXT_DIR%
    echo 2. Check PHP version matches (8.4 TS x64)
    echo 3. Ensure Visual C++ Redistributable 2022 is installed
    echo 4. Check php.ini has: extension=apcu
    echo 5. Restart your web server if using one
    echo.
    echo Run: php -m
    echo to see all loaded modules
    echo.
)

echo.
pause
