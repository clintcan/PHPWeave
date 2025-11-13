# BunkerWeb Management Script for PHPWeave (PowerShell)
# Simplifies Docker Compose operations for BunkerWeb WAF setup
#
# Usage: .\bunkerweb.ps1 [command]
#
# If execution policy prevents running, use:
# PowerShell -ExecutionPolicy Bypass -File .\bunkerweb.ps1 [command]

param(
    [Parameter(Position=0)]
    [string]$Command = ""
)

$ComposeFile = "docker-compose.bunkerweb.yml"

# Colors
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Warning { Write-Host $args -ForegroundColor Yellow }
function Write-Error { Write-Host $args -ForegroundColor Red }
function Write-Info { Write-Host $args -ForegroundColor Cyan }
function Write-Header { Write-Host $args -ForegroundColor Magenta }

# Check if Docker Compose file exists
function Test-ComposeFile {
    if (-not (Test-Path $ComposeFile)) {
        Write-Error "Error: $ComposeFile not found!"
        Write-Host "Please run this script from the PHPWeave root directory."
        exit 1
    }
}

# Check if Docker is running
function Test-Docker {
    try {
        docker info 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "Docker not running"
        }
    }
    catch {
        Write-Error "Error: Docker is not running!"
        Write-Host "Please start Docker Desktop and try again."
        exit 1
    }
}

# Print header
function Show-Header {
    Write-Info "╔════════════════════════════════════════════╗"
    Write-Info "║   BunkerWeb WAF Management Script         ║"
    Write-Info "║   PHPWeave v2.6.0                          ║"
    Write-Info "╚════════════════════════════════════════════╝"
    Write-Host ""
}

# Show menu
function Show-Menu {
    Write-Info "Available Commands:"
    Write-Host ""
    Write-Success "  Setup & Deployment:"
    Write-Host "    setup         - Initial setup (copy .env, pull images)"
    Write-Host "    start         - Start all services"
    Write-Host "    stop          - Stop all services"
    Write-Host "    restart       - Restart all services"
    Write-Host "    down          - Stop and remove containers (keeps data)"
    Write-Host "    destroy       - Stop and remove everything (INCLUDING DATA!)"
    Write-Host ""
    Write-Success "  Monitoring & Logs:"
    Write-Host "    status        - Show service status"
    Write-Host "    logs          - View logs (all services)"
    Write-Host "    logs-bw       - View BunkerWeb logs only"
    Write-Host "    logs-app      - View PHPWeave app logs only"
    Write-Host "    logs-db       - View database logs only"
    Write-Host "    stats         - Show resource usage"
    Write-Host ""
    Write-Success "  Maintenance:"
    Write-Host "    update        - Pull latest images and recreate containers"
    Write-Host "    reload        - Reload BunkerWeb config (no downtime)"
    Write-Host "    shell-bw      - Open shell in BunkerWeb container"
    Write-Host "    shell-app     - Open shell in PHPWeave container"
    Write-Host "    shell-db      - Open MySQL shell"
    Write-Host ""
    Write-Success "  Testing & Validation:"
    Write-Host "    test          - Run security tests"
    Write-Host "    verify        - Verify setup and configuration"
    Write-Host "    cert          - Check SSL certificate status"
    Write-Host ""
    Write-Success "  Backup & Restore:"
    Write-Host "    backup        - Backup volumes and configuration"
    Write-Host "    list-backups  - List available backups"
    Write-Host ""
    Write-Success "  Troubleshooting:"
    Write-Host "    debug         - Show debug information"
    Write-Host "    health        - Check service health"
    Write-Host "    errors        - Show recent errors"
    Write-Host ""
    Write-Success "  Information:"
    Write-Host "    info          - Show service URLs and credentials"
    Write-Host "    help          - Show this menu"
    Write-Host ""
}

# Setup
function Invoke-Setup {
    Write-Warning "Running initial setup..."
    Write-Host ""

    # Check if .env.bunkerweb.sample exists
    if (-not (Test-Path ".env.bunkerweb.sample")) {
        Write-Error "Error: .env.bunkerweb.sample not found!"
        Write-Host "Please make sure you are in the PHPWeave root directory."
        exit 1
    }

    # Check if .env exists
    if (Test-Path ".env") {
        Write-Success ".env file already exists."
        Write-Host ""
        $overwrite = Read-Host "Do you want to replace it with BunkerWeb config? (y/N)"
        if ($overwrite -ne "y") {
            Write-Host "Keeping existing .env file."
            Write-Warning "Make sure it has BunkerWeb configuration (DOMAIN, BW_ADMIN_PASSWORD, etc.)"
        }
        else {
            Write-Host "Backing up existing .env to .env.backup..."
            Copy-Item ".env" ".env.backup"
            Copy-Item ".env.bunkerweb.sample" ".env"
            Write-Success "Copied .env.bunkerweb.sample to .env"
            Write-Host ""
            Write-Warning "⚠ IMPORTANT: Edit .env and configure:"
            Write-Host "  - DOMAIN (your actual domain)"
            Write-Host "  - EMAIL (for Let's Encrypt)"
            Write-Host "  - All passwords (BW_ADMIN_PASSWORD, MYSQL_ROOT_PASSWORD, DB_PASSWORD)"
            Write-Host ""

            $edit = Read-Host "Open .env in editor now? (Y/n)"
            if ($edit -ne "n") {
                notepad .env
            }
        }
    }
    else {
        Write-Success "Copying .env.bunkerweb.sample to .env..."
        Copy-Item ".env.bunkerweb.sample" ".env"
        Write-Host ""
        Write-Warning "⚠ IMPORTANT: Edit .env and configure:"
        Write-Host "  - DOMAIN (your actual domain)"
        Write-Host "  - EMAIL (for Let's Encrypt)"
        Write-Host "  - All passwords (BW_ADMIN_PASSWORD, MYSQL_ROOT_PASSWORD, DB_PASSWORD)"
        Write-Host ""

        $edit = Read-Host "Open .env in editor now? (Y/n)"
        if ($edit -ne "n") {
            notepad .env
        }
    }

    Write-Host ""
    Write-Warning "Pulling Docker images..."
    docker compose -f $ComposeFile pull

    Write-Host ""
    Write-Success "✓ Setup complete!"
    Write-Host ""
    Write-Warning "Next steps:"
    Write-Host "  1. Make sure your domain DNS is configured"
    Write-Host "  2. Run: .\bunkerweb.ps1 start"
    Write-Host ""
}

# Start services
function Invoke-Start {
    Write-Warning "Starting all services..."
    docker compose -f $ComposeFile up -d

    Write-Host ""
    Write-Success "✓ Services started!"
    Write-Host ""
    Write-Host "Waiting for services to be ready..."
    Start-Sleep -Seconds 5
    Invoke-Status
    Write-Host ""
    Invoke-Info
}

# Stop services
function Invoke-Stop {
    Write-Warning "Stopping all services..."
    docker compose -f $ComposeFile stop
    Write-Success "✓ Services stopped."
}

# Restart services
function Invoke-Restart {
    Write-Warning "Restarting all services..."
    docker compose -f $ComposeFile restart
    Write-Success "✓ Services restarted."
    Start-Sleep -Seconds 3
    Invoke-Status
}

# Down
function Invoke-Down {
    Write-Warning "Stopping and removing containers (data preserved)..."
    docker compose -f $ComposeFile down
    Write-Success "✓ Containers removed. Volumes preserved."
}

# Destroy
function Invoke-Destroy {
    Write-Error "WARNING: This will DELETE ALL DATA including databases!"
    $confirm = Read-Host "Are you sure? Type 'yes' to confirm"
    if ($confirm -eq "yes") {
        Write-Warning "Destroying all containers and volumes..."
        docker compose -f $ComposeFile down -v
        Write-Success "✓ Everything removed."
    }
    else {
        Write-Host "Cancelled."
    }
}

# Status
function Invoke-Status {
    Write-Info "Service Status:"
    docker compose -f $ComposeFile ps
}

# Logs
function Invoke-Logs {
    Write-Warning "Viewing logs (Ctrl+C to exit)..."
    docker compose -f $ComposeFile logs -f
}

function Invoke-LogsBw {
    Write-Warning "Viewing BunkerWeb logs (Ctrl+C to exit)..."
    docker logs phpweave-bunkerweb -f
}

function Invoke-LogsApp {
    Write-Warning "Viewing PHPWeave app logs (Ctrl+C to exit)..."
    docker logs phpweave-app -f
}

function Invoke-LogsDb {
    Write-Warning "Viewing database logs (Ctrl+C to exit)..."
    docker logs phpweave-db -f
}

# Stats
function Invoke-Stats {
    Write-Info "Resource Usage:"
    docker stats --no-stream phpweave-bunkerweb phpweave-app phpweave-db phpweave-redis
}

# Update
function Invoke-Update {
    Write-Warning "Pulling latest images..."
    docker compose -f $ComposeFile pull

    Write-Host ""
    Write-Warning "Recreating containers with new images..."
    docker compose -f $ComposeFile up -d

    Write-Host ""
    Write-Success "✓ Update complete!"
    Start-Sleep -Seconds 3
    Invoke-Status
}

# Reload config
function Invoke-Reload {
    Write-Warning "Reloading BunkerWeb configuration..."
    docker exec phpweave-bw-scheduler bwcli reload
    Write-Success "✓ Configuration reloaded."
}

# Shell access
function Invoke-ShellBw {
    Write-Warning "Opening shell in BunkerWeb container..."
    docker exec -it phpweave-bunkerweb sh
}

function Invoke-ShellApp {
    Write-Warning "Opening shell in PHPWeave container..."
    docker exec -it phpweave-app bash
}

function Invoke-ShellDb {
    Write-Warning "Opening MySQL shell..."
    Write-Host "Enter MySQL root password when prompted."
    docker exec -it phpweave-db mysql -u root -p
}

# Test
function Invoke-Test {
    if (-not (Test-Path ".env")) {
        Write-Error "Error: .env file not found. Run '.\bunkerweb.ps1 setup' first."
        exit 1
    }

    # Get domain from .env
    $domain = (Get-Content .env | Select-String "^DOMAIN=" | ForEach-Object { $_ -replace 'DOMAIN=', '' } | ForEach-Object { $_ -replace '["\']', '' }).Trim()

    if ([string]::IsNullOrEmpty($domain) -or $domain -eq "www.example.com") {
        $domain = "localhost"
        $protocol = "http"
    }
    else {
        $protocol = "https"
    }

    Write-Info "Running security tests against: ${protocol}://${domain}"
    Write-Host ""

    Write-Warning "Test 1: Basic connectivity"
    try {
        $response = Invoke-WebRequest -Uri "${protocol}://${domain}" -Method Head -UseBasicParsing -ErrorAction Stop
        Write-Success "✓ Server responding: $($response.StatusCode)"
    }
    catch {
        Write-Error "✗ Server not responding"
    }
    Write-Host ""

    Write-Warning "Test 2: Security headers"
    try {
        $response = Invoke-WebRequest -Uri "${protocol}://${domain}" -Method Head -UseBasicParsing -ErrorAction Stop
        $headers = $response.Headers
        if ($headers.ContainsKey("Strict-Transport-Security") -or
            $headers.ContainsKey("X-Frame-Options") -or
            $headers.ContainsKey("X-Content-Type-Options")) {
            Write-Success "✓ Security headers present"
            $headers.Keys | Where-Object { $_ -match "Strict-Transport|X-Frame|X-Content|Referrer" } | ForEach-Object {
                Write-Host "  $_: $($headers[$_])"
            }
        }
    }
    catch {
        Write-Warning "(Headers check failed or not accessible)"
    }
    Write-Host ""

    Write-Warning "Test 3: WAF protection (SQL injection)"
    try {
        $response = Invoke-WebRequest -Uri "${protocol}://${domain}/?id=1'%20OR%20'1'='1" -UseBasicParsing -ErrorAction Stop
        Write-Warning "⚠ Got HTTP $($response.StatusCode) (expected 403)"
    }
    catch {
        if ($_.Exception.Response.StatusCode.value__ -eq 403) {
            Write-Success "✓ WAF blocked malicious request (403 Forbidden)"
        }
        else {
            Write-Warning "⚠ Got error: $($_.Exception.Message)"
        }
    }
    Write-Host ""

    Write-Warning "Test 4: Bot detection"
    try {
        $headers = @{ "User-Agent" = "sqlmap" }
        $response = Invoke-WebRequest -Uri "${protocol}://${domain}/" -Headers $headers -UseBasicParsing -ErrorAction Stop
        Write-Warning "⚠ Got HTTP $($response.StatusCode) (expected 403)"
    }
    catch {
        if ($_.Exception.Response.StatusCode.value__ -eq 403) {
            Write-Success "✓ Bot blocked (403 Forbidden)"
        }
        else {
            Write-Warning "⚠ Got error: $($_.Exception.Message)"
        }
    }
    Write-Host ""

    Write-Success "Tests complete!"
}

# Verify setup
function Invoke-Verify {
    Write-Info "Verifying BunkerWeb setup..."
    Write-Host ""

    # Check .env
    if (Test-Path ".env") {
        Write-Success "✓ .env file exists"

        $domain = (Get-Content .env | Select-String "^DOMAIN=" | ForEach-Object { $_ -replace 'DOMAIN=', '' }).Trim()
        if ($domain -eq "www.example.com" -or [string]::IsNullOrEmpty($domain)) {
            Write-Warning "⚠ DOMAIN not configured (still using example.com)"
        }
        else {
            Write-Success "✓ DOMAIN configured: $domain"
        }
    }
    else {
        Write-Error "✗ .env file missing"
    }

    # Check containers
    Write-Host ""
    $containers = docker compose -f $ComposeFile ps -q
    $running = ($containers | Measure-Object).Count
    $expected = 8
    if ($running -eq $expected) {
        Write-Success "✓ All $expected containers running"
    }
    else {
        Write-Warning "⚠ Only $running of $expected containers running"
    }

    Write-Host ""
    Write-Info "Verification complete!"
}

# Check certificate
function Invoke-Cert {
    if (-not (Test-Path ".env")) {
        Write-Error "Error: .env file not found."
        exit 1
    }

    $domain = (Get-Content .env | Select-String "^DOMAIN=" | ForEach-Object { $_ -replace 'DOMAIN=', '' } | ForEach-Object { $_ -replace '["\']', '' }).Trim()

    if ([string]::IsNullOrEmpty($domain) -or $domain -eq "www.example.com") {
        Write-Warning "Domain not configured in .env"
        exit 1
    }

    Write-Info "Checking SSL certificate for: $domain"
    Write-Host ""

    $certCheck = docker exec phpweave-bunkerweb ls /data/cache/letsencrypt 2>$null | Select-String $domain
    if ($certCheck) {
        Write-Success "✓ Let's Encrypt certificate found"
    }
    else {
        Write-Warning "⚠ Certificate not found or not yet issued"
        Write-Host ""
        Write-Host "Check BunkerWeb logs for Let's Encrypt status:"
        docker logs phpweave-bunkerweb 2>&1 | Select-String "let's encrypt" -CaseSensitive:$false | Select-Object -Last 10
    }
}

# Backup
function Invoke-Backup {
    $backupDir = "backups"
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

    if (-not (Test-Path $backupDir)) {
        New-Item -ItemType Directory -Path $backupDir | Out-Null
    }

    Write-Warning "Creating backup..."

    # Backup volumes
    docker run --rm `
        -v "${PWD}\${backupDir}:/backup" `
        -v phpweave_bw-data:/bw-data:ro `
        -v phpweave_db-data:/db-data:ro `
        alpine tar czf "/backup/volumes-${timestamp}.tar.gz" /bw-data /db-data

    # Backup .env
    Copy-Item .env "${backupDir}\env-${timestamp}"

    # Backup compose file
    Copy-Item $ComposeFile "${backupDir}\compose-${timestamp}.yml"

    Write-Success "✓ Backup created: ${backupDir}\volumes-${timestamp}.tar.gz"
    Write-Success "✓ Config backed up: ${backupDir}\env-${timestamp}"
}

# List backups
function Invoke-ListBackups {
    $backupDir = "backups"

    if (-not (Test-Path $backupDir)) {
        Write-Warning "No backups found."
        return
    }

    Write-Info "Available backups:"
    Get-ChildItem $backupDir | Where-Object { $_.Name -match "volumes|env|compose" } | Format-Table Name, Length, LastWriteTime
}

# Debug
function Invoke-Debug {
    Write-Info "Debug Information:"
    Write-Host ""

    Write-Host "=== System Info ==="
    Get-ComputerInfo | Select-Object WindowsVersion, OsArchitecture
    Write-Host ""

    Write-Host "=== Docker Version ==="
    docker --version
    docker compose version
    Write-Host ""

    Write-Host "=== Container Status ==="
    docker compose -f $ComposeFile ps
    Write-Host ""

    Write-Host "=== Recent BunkerWeb Logs ==="
    docker logs phpweave-bunkerweb --tail 20
    Write-Host ""

    Write-Host "=== Recent Scheduler Logs ==="
    docker logs phpweave-bw-scheduler --tail 20
    Write-Host ""
}

# Health check
function Invoke-Health {
    Write-Info "Health Check:"
    Write-Host ""

    $services = @(
        "phpweave-bunkerweb",
        "phpweave-bw-scheduler",
        "phpweave-bw-ui",
        "phpweave-bw-db",
        "phpweave-redis",
        "phpweave-app",
        "phpweave-db"
    )

    foreach ($service in $services) {
        $running = docker ps | Select-String $service
        if ($running) {
            Write-Success "✓ ${service}: running"
        }
        else {
            Write-Error "✗ ${service}: not running"
        }
    }
}

# Show errors
function Invoke-Errors {
    Write-Info "Recent Errors:"
    Write-Host ""

    Write-Host "=== BunkerWeb Errors ==="
    docker logs phpweave-bunkerweb 2>&1 | Select-String "error" -CaseSensitive:$false | Select-Object -Last 20
    Write-Host ""

    Write-Host "=== PHPWeave App Errors ==="
    docker logs phpweave-app 2>&1 | Select-String "error" -CaseSensitive:$false | Select-Object -Last 20
    Write-Host ""

    Write-Host "=== Database Errors ==="
    docker logs phpweave-db 2>&1 | Select-String "error" -CaseSensitive:$false | Select-Object -Last 20
}

# Show info
function Invoke-Info {
    Write-Info "Service Information:"
    Write-Host ""

    if (Test-Path ".env") {
        $domain = (Get-Content .env | Select-String "^DOMAIN=" | ForEach-Object { $_ -replace 'DOMAIN=', '' } | ForEach-Object { $_ -replace '["\']', '' }).Trim()
        $adminUser = (Get-Content .env | Select-String "^BW_ADMIN_USER=" | ForEach-Object { $_ -replace 'BW_ADMIN_USER=', '' } | ForEach-Object { $_ -replace '["\']', '' }).Trim()

        if ([string]::IsNullOrEmpty($adminUser)) {
            $adminUser = "admin"
        }

        Write-Success "Access URLs:"
        if ($domain -ne "www.example.com" -and -not [string]::IsNullOrEmpty($domain)) {
            Write-Host "  PHPWeave (via WAF): https://$domain"
        }
        else {
            Write-Host "  PHPWeave (via WAF): http://localhost (configure DOMAIN in .env)"
        }
        Write-Host "  Admin UI:           http://localhost:7000"
        Write-Host "  phpMyAdmin:         http://localhost:8081"
        Write-Host ""

        Write-Success "Admin Credentials:"
        Write-Host "  Username: $adminUser"
        Write-Host "  Password: (see BW_ADMIN_PASSWORD in .env)"
        Write-Host ""
    }

    Write-Success "Management Commands:"
    Write-Host "  View logs:    .\bunkerweb.ps1 logs"
    Write-Host "  Check status: .\bunkerweb.ps1 status"
    Write-Host "  Run tests:    .\bunkerweb.ps1 test"
    Write-Host "  Get help:     .\bunkerweb.ps1 help"
    Write-Host ""
}

# Main script
Test-ComposeFile
Test-Docker

# Process command
if ([string]::IsNullOrEmpty($Command)) {
    Show-Header
    Show-Menu
    exit 0
}

switch ($Command.ToLower()) {
    "setup"         { Invoke-Setup }
    "start"         { Invoke-Start }
    "stop"          { Invoke-Stop }
    "restart"       { Invoke-Restart }
    "down"          { Invoke-Down }
    "destroy"       { Invoke-Destroy }
    "status"        { Invoke-Status }
    "logs"          { Invoke-Logs }
    "logs-bw"       { Invoke-LogsBw }
    "logs-app"      { Invoke-LogsApp }
    "logs-db"       { Invoke-LogsDb }
    "stats"         { Invoke-Stats }
    "update"        { Invoke-Update }
    "reload"        { Invoke-Reload }
    "shell-bw"      { Invoke-ShellBw }
    "shell-app"     { Invoke-ShellApp }
    "shell-db"      { Invoke-ShellDb }
    "test"          { Invoke-Test }
    "verify"        { Invoke-Verify }
    "cert"          { Invoke-Cert }
    "backup"        { Invoke-Backup }
    "list-backups"  { Invoke-ListBackups }
    "debug"         { Invoke-Debug }
    "health"        { Invoke-Health }
    "errors"        { Invoke-Errors }
    "info"          { Invoke-Info }
    "help"          { Show-Header; Show-Menu }
    "--help"        { Show-Header; Show-Menu }
    "-h"            { Show-Header; Show-Menu }
    default {
        Write-Error "Unknown command: $Command"
        Write-Host ""
        Show-Menu
        exit 1
    }
}
