@echo off
REM PHPWeave - Run Psalm Security Analysis Locally
REM This script runs Psalm taint analysis to detect security vulnerabilities

echo ============================================
echo PHPWeave Psalm Security Analysis
echo ============================================
echo.

REM Check if Composer is installed
where composer >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found in PATH
    echo Please install Composer: https://getcomposer.org/
    exit /b 1
)

REM Check if vendor directory exists
if not exist "vendor\" (
    echo Installing dependencies...
    composer install --no-interaction
    echo.
)

REM Check if Psalm is installed
if not exist "vendor\bin\psalm" (
    echo Installing Psalm 6.x (PHP 8.4 compatible)...
    composer require --dev vimeo/psalm:^6.0
    echo.
)

echo Running Psalm Taint Analysis (Security Scan)...
echo This may take 30-60 seconds...
echo.

vendor\bin\psalm --taint-analysis --no-cache

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo SUCCESS: No security vulnerabilities found!
    echo ============================================
) else (
    echo.
    echo ============================================
    echo WARNING: Potential security issues detected
    echo Please review the output above
    echo ============================================
)

echo.
echo To run standard analysis: vendor\bin\psalm
echo To run both: composer check
echo.

pause
