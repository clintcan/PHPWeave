# APCu Installation Script for Windows (PowerShell)
# For PHP 8.4 x64 Thread Safe (ZTS)

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "APCu Installation for PHP 8.4 (Windows x64 TS)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$phpDir = "C:\php"
$extDir = "$phpDir\ext"
$phpIni = "$phpDir\php.ini"
$tempDir = $env:TEMP

Write-Host "PHP Directory: $phpDir" -ForegroundColor Yellow
Write-Host "Extension Directory: $extDir" -ForegroundColor Yellow
Write-Host "PHP INI: $phpIni" -ForegroundColor Yellow
Write-Host ""

# Check PHP version
Write-Host "Checking PHP version..." -ForegroundColor Cyan
$phpVersion = & php -v 2>&1 | Select-String "8.4"
if ($phpVersion) {
    Write-Host "✓ PHP 8.4 detected" -ForegroundColor Green
} else {
    Write-Host "✗ ERROR: PHP 8.4 not detected" -ForegroundColor Red
    Write-Host "This script is for PHP 8.4 only" -ForegroundColor Red
    pause
    exit 1
}

# Check if Thread Safe
$threadSafe = & php -i 2>&1 | Select-String "Thread Safety => enabled"
if ($threadSafe) {
    Write-Host "✓ PHP Thread Safe (ZTS) detected" -ForegroundColor Green
} else {
    Write-Host "⚠ WARNING: PHP is not Thread Safe (ZTS)" -ForegroundColor Yellow
    Write-Host "  You may need the NTS version of APCu instead" -ForegroundColor Yellow
}
Write-Host ""

# Check if APCu already installed
$apcuInstalled = & php -m 2>&1 | Select-String -Pattern "apcu" -Quiet
if ($apcuInstalled) {
    Write-Host "✓ APCu is already installed and loaded!" -ForegroundColor Green
    $apcuVersion = & php -r "echo phpversion('apcu');" 2>&1
    Write-Host "  Version: $apcuVersion" -ForegroundColor Green
    Write-Host ""
    Write-Host "No installation needed. APCu is ready to use!" -ForegroundColor Green
    pause
    exit 0
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "STEP 1: Download APCu Extension" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Check if DLL already exists
if (Test-Path "$extDir\php_apcu.dll") {
    Write-Host "✓ php_apcu.dll already exists in extension directory" -ForegroundColor Green
    $skipDownload = $true
} else {
    $skipDownload = $false
    Write-Host "Searching for APCu download..." -ForegroundColor Yellow
    Write-Host ""

    # Try to find the latest APCu version for PHP 8.4
    $peclUrl = "https://windows.php.net/downloads/pecl/releases/apcu/"

    Write-Host "Note: For PHP 8.4, you need:" -ForegroundColor Yellow
    Write-Host "  - APCu 5.x.x" -ForegroundColor Yellow
    Write-Host "  - PHP 8.4" -ForegroundColor Yellow
    Write-Host "  - Thread Safe (TS)" -ForegroundColor Yellow
    Write-Host "  - x64 architecture" -ForegroundColor Yellow
    Write-Host "  - VS16 or VS17 build" -ForegroundColor Yellow
    Write-Host ""

    Write-Host "Opening PECL download page in browser..." -ForegroundColor Cyan
    Start-Process $peclUrl
    Write-Host ""
    Write-Host "Please download the appropriate ZIP file manually:" -ForegroundColor Yellow
    Write-Host "  Example: php_apcu-5.1.24-8.4-ts-vs17-x64.zip" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "After downloading:" -ForegroundColor Yellow
    Write-Host "  1. Extract the ZIP file" -ForegroundColor Yellow
    Write-Host "  2. Copy php_apcu.dll to: $extDir" -ForegroundColor Yellow
    Write-Host ""

    $continue = Read-Host "Have you copied php_apcu.dll to the extension directory? (y/n)"
    if ($continue -ne "y") {
        Write-Host ""
        Write-Host "Please download and copy the file, then run this script again." -ForegroundColor Yellow
        pause
        exit 0
    }
}

# Verify DLL exists
if (-not (Test-Path "$extDir\php_apcu.dll")) {
    Write-Host ""
    Write-Host "✗ ERROR: php_apcu.dll not found in $extDir" -ForegroundColor Red
    Write-Host "Please copy the file and try again." -ForegroundColor Red
    pause
    exit 1
}

Write-Host "✓ Found: php_apcu.dll" -ForegroundColor Green
Write-Host ""

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "STEP 2: Configure PHP" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Backup php.ini
$backupDate = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = "$phpIni.backup_$backupDate"

Write-Host "Creating backup of php.ini..." -ForegroundColor Yellow
try {
    Copy-Item $phpIni $backupPath -ErrorAction Stop
    Write-Host "✓ Backup created: $backupPath" -ForegroundColor Green
} catch {
    Write-Host "⚠ WARNING: Could not create backup: $_" -ForegroundColor Yellow
}
Write-Host ""

# Check if APCu is already in php.ini
$iniContent = Get-Content $phpIni
$hasApcu = $iniContent | Select-String -Pattern "extension=apcu" -Quiet

if ($hasApcu) {
    Write-Host "APCu extension already configured in php.ini" -ForegroundColor Yellow

    # Check if it's commented out
    $commented = $iniContent | Select-String -Pattern ";extension=apcu" -Quiet
    if ($commented) {
        Write-Host "Extension is commented out. Uncommenting..." -ForegroundColor Yellow
        $iniContent = $iniContent -replace ";extension=apcu", "extension=apcu"
        $iniContent | Set-Content $phpIni
        Write-Host "✓ Uncommented extension=apcu" -ForegroundColor Green
    }
} else {
    Write-Host "Adding APCu configuration to php.ini..." -ForegroundColor Yellow

    $apcuConfig = @"

; APCu - PHP opcode cache
extension=apcu
apc.enabled=1
apc.shm_size=32M
apc.enable_cli=1

"@

    Add-Content -Path $phpIni -Value $apcuConfig
    Write-Host "✓ Configuration added" -ForegroundColor Green
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "STEP 3: Verify Installation" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Testing APCu installation..." -ForegroundColor Yellow
$apcuLoaded = & php -m 2>&1 | Select-String -Pattern "apcu" -Quiet

if ($apcuLoaded) {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "SUCCESS! APCu is now installed and enabled" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""

    $version = & php -r "echo phpversion('apcu');" 2>&1
    Write-Host "APCu Version: $version" -ForegroundColor Cyan
    Write-Host ""

    Write-Host "Configuration:" -ForegroundColor Cyan
    $shmSize = & php -r "echo ini_get('apc.shm_size');" 2>&1
    $cliEnabled = & php -r "echo ini_get('apc.enable_cli') ? 'Yes' : 'No';" 2>&1
    Write-Host "  Memory Size: $shmSize" -ForegroundColor White
    Write-Host "  CLI Enabled: $cliEnabled" -ForegroundColor White
    Write-Host ""

    Write-Host "✓ APCu is ready to use!" -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now run the regression tests again to see improved results." -ForegroundColor Yellow
    Write-Host ""

} else {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Red
    Write-Host "ERROR: APCu not detected" -ForegroundColor Red
    Write-Host "============================================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "  1. Verify php_apcu.dll is in $extDir" -ForegroundColor White
    Write-Host "  2. Check PHP version matches (8.4 TS x64)" -ForegroundColor White
    Write-Host "  3. Ensure Visual C++ Redistributable 2022 is installed" -ForegroundColor White
    Write-Host "     Download: https://aka.ms/vs/17/release/vc_redist.x64.exe" -ForegroundColor White
    Write-Host "  4. Check php.ini has: extension=apcu" -ForegroundColor White
    Write-Host "  5. Restart your web server if using one" -ForegroundColor White
    Write-Host ""
    Write-Host "Run: php -m" -ForegroundColor Yellow
    Write-Host "to see all loaded modules" -ForegroundColor Yellow
    Write-Host ""
}

pause
