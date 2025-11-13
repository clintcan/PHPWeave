# BunkerWeb Management Scripts

Convenient management scripts for running PHPWeave with BunkerWeb WAF.

---

## 📦 Available Scripts

Two versions of the same functionality for different platforms:

1. **`bunkerweb.sh`** - Linux/macOS (Bash)
2. **`bunkerweb.bat`** - Windows (Batch)

Both scripts provide the same commands and functionality.

---

## 🚀 Quick Start

### Linux/macOS

```bash
# Make executable
chmod +x bunkerweb.sh

# Run setup
./bunkerweb.sh setup

# Start services
./bunkerweb.sh start

# View menu
./bunkerweb.sh
```

### Windows

```cmd
# Run setup
bunkerweb.bat setup

# Start services
bunkerweb.bat start

# View menu
bunkerweb.bat
```

---

## 📋 Available Commands

### Setup & Deployment

| Command | Description |
|---------|-------------|
| `setup` | Initial setup (copy .env, pull images) |
| `start` | Start all services |
| `stop` | Stop all services |
| `restart` | Restart all services |
| `down` | Stop and remove containers (keeps data) |
| `destroy` | Stop and remove everything INCLUDING DATA! |

### Monitoring & Logs

| Command | Description |
|---------|-------------|
| `status` | Show service status |
| `logs` | View logs (all services) |
| `logs-bw` | View BunkerWeb logs only |
| `logs-app` | View PHPWeave app logs only |
| `logs-db` | View database logs only |
| `stats` | Show resource usage |

### Maintenance

| Command | Description |
|---------|-------------|
| `update` | Pull latest images and recreate containers |
| `reload` | Reload BunkerWeb config (no downtime) |
| `shell-bw` | Open shell in BunkerWeb container |
| `shell-app` | Open shell in PHPWeave container |
| `shell-db` | Open MySQL shell |

### Testing & Validation

| Command | Description |
|---------|-------------|
| `test` | Run security tests |
| `verify` | Verify setup and configuration |
| `cert` | Check SSL certificate status |

### Backup & Restore

| Command | Description |
|---------|-------------|
| `backup` | Backup volumes and configuration |
| `list-backups` | List available backups |

### Troubleshooting

| Command | Description |
|---------|-------------|
| `debug` | Show debug information |
| `health` | Check service health |
| `errors` | Show recent errors |

### Information

| Command | Description |
|---------|-------------|
| `info` | Show service URLs and credentials |
| `help` | Show command menu |

---

## 📖 Usage Examples

### Initial Setup

```bash
# Linux/macOS
./bunkerweb.sh setup

# Windows
bunkerweb.bat setup
```

**What it does:**
1. Copies `.env.bunkerweb.sample` to `.env`
2. Opens `.env` in editor for configuration
3. Pulls all Docker images

**Required changes in `.env`:**
- `DOMAIN` - Your actual domain (e.g., www.yoursite.com)
- `EMAIL` - Your email for Let's Encrypt
- `BW_ADMIN_PASSWORD` - Admin UI password
- `MYSQL_ROOT_PASSWORD` - Database root password
- `DB_PASSWORD` - Application database password

### Start Services

```bash
# Linux/macOS
./bunkerweb.sh start

# Windows
bunkerweb.bat start
```

**What it does:**
1. Starts all 8 Docker containers
2. Waits 5 seconds for initialization
3. Shows service status
4. Displays access URLs

### View Logs

```bash
# All services
./bunkerweb.sh logs

# BunkerWeb only
./bunkerweb.sh logs-bw

# PHPWeave app only
./bunkerweb.sh logs-app

# Database only
./bunkerweb.sh logs-db
```

Press `Ctrl+C` to exit log viewing.

### Check Status

```bash
./bunkerweb.sh status
```

Shows running status of all containers.

### Run Security Tests

```bash
./bunkerweb.sh test
```

**Tests performed:**
1. Basic connectivity (HTTP request)
2. Security headers (HSTS, X-Frame-Options, etc.)
3. WAF protection (SQL injection attempt)
4. Bot detection (malicious user agent)

### Verify Setup

```bash
./bunkerweb.sh verify
```

**Checks:**
- `.env` file exists and configured
- All containers running (8 expected)
- BunkerWeb NGINX config valid
- Ports 80/443 accessible

### Check SSL Certificate

```bash
./bunkerweb.sh cert
```

Shows Let's Encrypt certificate status and expiry date.

### Reload Configuration

```bash
./bunkerweb.sh reload
```

Reloads BunkerWeb configuration without downtime (doesn't restart containers).

### Update Services

```bash
./bunkerweb.sh update
```

**What it does:**
1. Pulls latest Docker images
2. Recreates containers with new images
3. Preserves all data

### Backup

```bash
./bunkerweb.sh backup
```

**Creates backup of:**
- Docker volumes (BunkerWeb data, database)
- `.env` configuration
- `docker-compose.bunkerweb.yml`

**Location:** `./backups/`

### List Backups

```bash
./bunkerweb.sh list-backups
```

Shows all available backups with timestamps.

### Troubleshooting

```bash
# Show debug information
./bunkerweb.sh debug

# Check service health
./bunkerweb.sh health

# Show recent errors
./bunkerweb.sh errors
```

### Shell Access

```bash
# BunkerWeb container shell
./bunkerweb.sh shell-bw

# PHPWeave app shell
./bunkerweb.sh shell-app

# MySQL shell
./bunkerweb.sh shell-db
```

### Stop Services

```bash
# Stop (containers remain, can restart)
./bunkerweb.sh stop

# Down (remove containers, keep data)
./bunkerweb.sh down

# Destroy (remove EVERYTHING including data)
./bunkerweb.sh destroy
```

⚠️ **Warning:** `destroy` command deletes all data including databases!

---

## 🎯 Common Workflows

### First Time Deployment

```bash
# 1. Setup
./bunkerweb.sh setup

# 2. Edit .env (change DOMAIN, EMAIL, passwords)
nano .env

# 3. Configure DNS to point to your server

# 4. Start services
./bunkerweb.sh start

# 5. Verify everything works
./bunkerweb.sh verify

# 6. Run security tests
./bunkerweb.sh test

# 7. Check certificate
./bunkerweb.sh cert
```

### Daily Monitoring

```bash
# Check status
./bunkerweb.sh status

# View recent logs
./bunkerweb.sh logs | tail -50

# Check for errors
./bunkerweb.sh errors

# Check resource usage
./bunkerweb.sh stats
```

### Weekly Maintenance

```bash
# Create backup
./bunkerweb.sh backup

# Update to latest versions
./bunkerweb.sh update

# Verify health
./bunkerweb.sh health

# Run security tests
./bunkerweb.sh test
```

### Troubleshooting Issues

```bash
# 1. Check container health
./bunkerweb.sh health

# 2. View recent errors
./bunkerweb.sh errors

# 3. Check specific service logs
./bunkerweb.sh logs-bw    # or logs-app, logs-db

# 4. Get debug info
./bunkerweb.sh debug

# 5. Verify configuration
./bunkerweb.sh verify

# 6. If stuck, restart services
./bunkerweb.sh restart
```

---

## 🔧 Script Features

### All Scripts Include

✅ **Docker validation** - Checks Docker is running before commands
✅ **Compose file validation** - Verifies docker-compose.bunkerweb.yml exists
✅ **Color-coded output** - Success (green), warnings (yellow), errors (red)
✅ **Helpful messages** - Clear feedback on operations
✅ **Safety checks** - Confirmation prompts for destructive operations
✅ **Error handling** - Graceful failures with helpful messages

### Platform-Specific Features

**Linux/macOS (bunkerweb.sh):**
- POSIX-compliant Bash script
- Color ANSI escape codes
- Unix command utilities
- Native curl for testing

**Windows Batch (bunkerweb.bat):**
- Works on all Windows versions
- No special permissions needed
- CMD.exe compatible
- Uses findstr for filtering

---

## ⚙️ Configuration

### Customize Script Behavior

**Change Docker Compose file:**

```bash
# In script, modify this line:
COMPOSE_FILE="docker-compose.bunkerweb.yml"
```

**Change backup directory:**

```bash
# In backup function, modify:
BACKUP_DIR="./backups"
```

### Create Aliases (Linux/macOS)

Add to `~/.bashrc` or `~/.zshrc`:

```bash
alias bw='/path/to/PHPWeave/bunkerweb.sh'
```

Then use:
```bash
bw start
bw status
bw logs
```

---

## 🐛 Troubleshooting Scripts

### Linux/macOS: Permission Denied

```bash
# Problem
./bunkerweb.sh: Permission denied

# Solution
chmod +x bunkerweb.sh
```

### Windows PowerShell: Execution Policy Error

```powershell
# Problem
cannot be loaded because running scripts is disabled

# Solution 1: Bypass for single execution
PowerShell -ExecutionPolicy Bypass -File .\bunkerweb.ps1 start

# Solution 2: Allow scripts permanently
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Script Can't Find Docker

```bash
# Check Docker is running
docker ps

# If not running, start Docker Desktop

# Verify Docker Compose
docker compose version
```

### Script Can't Find docker-compose.bunkerweb.yml

```bash
# Problem
Error: docker-compose.bunkerweb.yml not found!

# Solution: Run from PHPWeave root directory
cd /path/to/PHPWeave
./bunkerweb.sh start
```

---

## 📚 Related Documentation

- **Full Setup Guide:** `docs/BUNKERWEB_WAF_GUIDE.md`
- **Quick Setup:** `BUNKERWEB_SETUP.md`
- **Docker Compose:** `docker-compose.bunkerweb.yml`
- **Environment Config:** `.env.bunkerweb.sample`

---

## 💡 Tips & Tricks

### 1. Quick Status Check

```bash
# Create function (add to ~/.bashrc)
bw-check() {
    ./bunkerweb.sh status && ./bunkerweb.sh health
}
```

### 2. Watch Logs in Real-Time

```bash
# Terminal 1: BunkerWeb logs
./bunkerweb.sh logs-bw

# Terminal 2: App logs
./bunkerweb.sh logs-app

# Or use tmux/screen for split view
```

### 3. Automated Backups

```bash
# Add to crontab (daily at 2 AM)
0 2 * * * cd /path/to/PHPWeave && ./bunkerweb.sh backup
```

### 4. Status Bar Integration

```bash
# Get container count for status bar
docker compose -f docker-compose.bunkerweb.yml ps -q | wc -l
```

### 5. Combine Commands

```bash
# Stop, update, and restart
./bunkerweb.sh down && ./bunkerweb.sh update

# Backup before destroying
./bunkerweb.sh backup && ./bunkerweb.sh destroy
```

---

## 🔐 Security Notes

- **Scripts do NOT store passwords** - Reads from `.env` file
- **No credentials in script output** - Info command shows location only
- **Safe defaults** - Destructive commands require confirmation
- **Read-only operations** - Most commands don't modify state

---

## 🆘 Getting Help

### Script Help

```bash
# Show menu
./bunkerweb.sh

# or
./bunkerweb.sh help
```

### Detailed Documentation

- Quick reference: `BUNKERWEB_SETUP.md`
- Complete guide: `docs/BUNKERWEB_WAF_GUIDE.md`
- Troubleshooting: `TROUBLESHOOTING_GUIDE.md`

### Check Script Version

Scripts are synchronized with PHPWeave version:
- **PHPWeave:** v2.6.0
- **BunkerWeb:** 1.6.5
- **Scripts:** 2025-01-13

---

**Last Updated:** January 2025
**Tested On:** Linux (Ubuntu 22.04), macOS (13+), Windows 10/11
**Status:** Production Ready ✅
