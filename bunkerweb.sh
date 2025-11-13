#!/bin/bash
#
# BunkerWeb Management Script for PHPWeave
# Simplifies Docker Compose operations for BunkerWeb WAF setup
#
# Usage: ./bunkerweb.sh [command]
#
# IMPORTANT: Make executable with: chmod +x bunkerweb.sh

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Docker Compose file
COMPOSE_FILE="docker-compose.bunkerweb.yml"

# Check if Docker Compose file exists
check_compose_file() {
    if [ ! -f "$COMPOSE_FILE" ]; then
        echo -e "${RED}Error: $COMPOSE_FILE not found!${NC}"
        echo "Please run this script from the PHPWeave root directory."
        exit 1
    fi
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}Error: Docker is not running!${NC}"
        echo "Please start Docker and try again."
        exit 1
    fi
}

# Print header
print_header() {
    echo -e "${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   BunkerWeb WAF Management Script         ║${NC}"
    echo -e "${CYAN}║   PHPWeave v2.6.0                          ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}"
    echo ""
}

# Show menu
show_menu() {
    echo -e "${BLUE}Available Commands:${NC}"
    echo ""
    echo -e "  ${GREEN}Setup & Deployment:${NC}"
    echo "    setup         - Initial setup (copy .env, pull images)"
    echo "    start         - Start all services"
    echo "    stop          - Stop all services"
    echo "    restart       - Restart all services"
    echo "    down          - Stop and remove containers (keeps data)"
    echo "    destroy       - Stop and remove everything (INCLUDING DATA!)"
    echo ""
    echo -e "  ${GREEN}Monitoring & Logs:${NC}"
    echo "    status        - Show service status"
    echo "    logs          - View logs (all services)"
    echo "    logs-bw       - View BunkerWeb logs only"
    echo "    logs-app      - View PHPWeave app logs only"
    echo "    logs-db       - View database logs only"
    echo "    stats         - Show resource usage"
    echo ""
    echo -e "  ${GREEN}Maintenance:${NC}"
    echo "    update        - Pull latest images and recreate containers"
    echo "    reload        - Reload BunkerWeb config (no downtime)"
    echo "    shell-bw      - Open shell in BunkerWeb container"
    echo "    shell-app     - Open shell in PHPWeave container"
    echo "    shell-db      - Open MySQL shell"
    echo ""
    echo -e "  ${GREEN}Testing & Validation:${NC}"
    echo "    test          - Run security tests"
    echo "    verify        - Verify setup and configuration"
    echo "    cert          - Check SSL certificate status"
    echo ""
    echo -e "  ${GREEN}Backup & Restore:${NC}"
    echo "    backup        - Backup volumes and configuration"
    echo "    list-backups  - List available backups"
    echo ""
    echo -e "  ${GREEN}Troubleshooting:${NC}"
    echo "    debug         - Show debug information"
    echo "    health        - Check service health"
    echo "    errors        - Show recent errors"
    echo ""
    echo -e "  ${GREEN}Information:${NC}"
    echo "    info          - Show service URLs and credentials"
    echo "    help          - Show this menu"
    echo ""
}

# Setup
cmd_setup() {
    echo -e "${YELLOW}Running initial setup...${NC}"
    echo ""

    # Check if .env.bunkerweb.sample exists
    if [ ! -f ".env.bunkerweb.sample" ]; then
        echo -e "${RED}Error: .env.bunkerweb.sample not found!${NC}"
        echo "Please make sure you are in the PHPWeave root directory."
        exit 1
    fi

    # Check if .env exists
    if [ -f ".env" ]; then
        echo -e "${GREEN}.env file already exists.${NC}"
        echo ""
        read -p "Do you want to replace it with BunkerWeb config? (y/N): " overwrite
        if [ "$overwrite" != "y" ] && [ "$overwrite" != "Y" ]; then
            echo "Keeping existing .env file."
            echo -e "${YELLOW}Make sure it has BunkerWeb configuration (DOMAIN, BW_ADMIN_PASSWORD, etc.)${NC}"
        else
            echo "Backing up existing .env to .env.backup..."
            cp .env .env.backup
            cp .env.bunkerweb.sample .env
            echo -e "${GREEN}Copied .env.bunkerweb.sample to .env${NC}"
            echo ""
            echo -e "${YELLOW}⚠ IMPORTANT: Edit .env and configure:${NC}"
            echo "  - DOMAIN (your actual domain)"
            echo "  - EMAIL (for Let's Encrypt)"
            echo "  - All passwords (BW_ADMIN_PASSWORD, MYSQL_ROOT_PASSWORD, DB_PASSWORD)"
            echo ""
            read -p "Press Enter to edit .env now, or Ctrl+C to skip..."
            ${EDITOR:-nano} .env
        fi
    else
        echo -e "${GREEN}Copying .env.bunkerweb.sample to .env...${NC}"
        cp .env.bunkerweb.sample .env
        echo -e "${YELLOW}⚠ IMPORTANT: Edit .env and configure:${NC}"
        echo "  - DOMAIN (your actual domain)"
        echo "  - EMAIL (for Let's Encrypt)"
        echo "  - All passwords (BW_ADMIN_PASSWORD, MYSQL_ROOT_PASSWORD, DB_PASSWORD)"
        echo ""
        read -p "Press Enter to edit .env now, or Ctrl+C to skip..."
        ${EDITOR:-nano} .env
    fi

    echo ""
    echo -e "${YELLOW}Pulling Docker images...${NC}"
    docker compose -f "$COMPOSE_FILE" pull

    echo ""
    echo -e "${GREEN}✓ Setup complete!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "  1. Make sure your domain DNS is configured"
    echo "  2. Run: ./bunkerweb.sh start"
    echo ""
}

# Start services
cmd_start() {
    echo -e "${YELLOW}Starting all services...${NC}"
    docker compose -f "$COMPOSE_FILE" up -d

    echo ""
    echo -e "${GREEN}✓ Services started!${NC}"
    echo ""
    echo "Waiting for services to be ready..."
    sleep 5
    cmd_status
    echo ""
    cmd_info
}

# Stop services
cmd_stop() {
    echo -e "${YELLOW}Stopping all services...${NC}"
    docker compose -f "$COMPOSE_FILE" stop
    echo -e "${GREEN}✓ Services stopped.${NC}"
}

# Restart services
cmd_restart() {
    echo -e "${YELLOW}Restarting all services...${NC}"
    docker compose -f "$COMPOSE_FILE" restart
    echo -e "${GREEN}✓ Services restarted.${NC}"
    sleep 3
    cmd_status
}

# Down (remove containers, keep volumes)
cmd_down() {
    echo -e "${YELLOW}Stopping and removing containers (data preserved)...${NC}"
    docker compose -f "$COMPOSE_FILE" down
    echo -e "${GREEN}✓ Containers removed. Volumes preserved.${NC}"
}

# Destroy (remove everything including volumes)
cmd_destroy() {
    echo -e "${RED}WARNING: This will DELETE ALL DATA including databases!${NC}"
    read -p "Are you sure? Type 'yes' to confirm: " confirm
    if [ "$confirm" = "yes" ]; then
        echo -e "${YELLOW}Destroying all containers and volumes...${NC}"
        docker compose -f "$COMPOSE_FILE" down -v
        echo -e "${GREEN}✓ Everything removed.${NC}"
    else
        echo "Cancelled."
    fi
}

# Status
cmd_status() {
    echo -e "${BLUE}Service Status:${NC}"
    docker compose -f "$COMPOSE_FILE" ps
}

# Logs
cmd_logs() {
    echo -e "${YELLOW}Viewing logs (Ctrl+C to exit)...${NC}"
    docker compose -f "$COMPOSE_FILE" logs -f
}

cmd_logs_bw() {
    echo -e "${YELLOW}Viewing BunkerWeb logs (Ctrl+C to exit)...${NC}"
    docker logs phpweave-bunkerweb -f
}

cmd_logs_app() {
    echo -e "${YELLOW}Viewing PHPWeave app logs (Ctrl+C to exit)...${NC}"
    docker logs phpweave-app -f
}

cmd_logs_db() {
    echo -e "${YELLOW}Viewing database logs (Ctrl+C to exit)...${NC}"
    docker logs phpweave-db -f
}

# Stats
cmd_stats() {
    echo -e "${BLUE}Resource Usage:${NC}"
    docker stats --no-stream phpweave-bunkerweb phpweave-app phpweave-db phpweave-redis
}

# Update
cmd_update() {
    echo -e "${YELLOW}Pulling latest images...${NC}"
    docker compose -f "$COMPOSE_FILE" pull

    echo ""
    echo -e "${YELLOW}Recreating containers with new images...${NC}"
    docker compose -f "$COMPOSE_FILE" up -d

    echo ""
    echo -e "${GREEN}✓ Update complete!${NC}"
    sleep 3
    cmd_status
}

# Reload config
cmd_reload() {
    echo -e "${YELLOW}Reloading BunkerWeb configuration...${NC}"
    docker exec phpweave-bw-scheduler bwcli reload
    echo -e "${GREEN}✓ Configuration reloaded.${NC}"
}

# Shell access
cmd_shell_bw() {
    echo -e "${YELLOW}Opening shell in BunkerWeb container...${NC}"
    docker exec -it phpweave-bunkerweb sh
}

cmd_shell_app() {
    echo -e "${YELLOW}Opening shell in PHPWeave container...${NC}"
    docker exec -it phpweave-app bash
}

cmd_shell_db() {
    echo -e "${YELLOW}Opening MySQL shell...${NC}"
    echo "Enter MySQL root password when prompted."
    docker exec -it phpweave-db mysql -u root -p
}

# Test
cmd_test() {
    if [ ! -f ".env" ]; then
        echo -e "${RED}Error: .env file not found. Run './bunkerweb.sh setup' first.${NC}"
        exit 1
    fi

    # Get domain from .env
    DOMAIN=$(grep "^DOMAIN=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")

    if [ -z "$DOMAIN" ] || [ "$DOMAIN" = "www.example.com" ]; then
        echo -e "${YELLOW}⚠ Domain not configured in .env (using localhost)${NC}"
        DOMAIN="localhost"
        PROTOCOL="http"
    else
        PROTOCOL="https"
    fi

    echo -e "${BLUE}Running security tests against: ${PROTOCOL}://${DOMAIN}${NC}"
    echo ""

    echo -e "${YELLOW}Test 1: Basic connectivity${NC}"
    if curl -sI "${PROTOCOL}://${DOMAIN}" | head -1; then
        echo -e "${GREEN}✓ Server responding${NC}"
    else
        echo -e "${RED}✗ Server not responding${NC}"
    fi
    echo ""

    echo -e "${YELLOW}Test 2: Security headers${NC}"
    curl -sI "${PROTOCOL}://${DOMAIN}" | grep -E "(Strict-Transport|X-Frame|X-Content|Referrer-Policy)" || echo -e "${YELLOW}(Headers may not show on HTTP)${NC}"
    echo ""

    echo -e "${YELLOW}Test 3: WAF protection (SQL injection)${NC}"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${PROTOCOL}://${DOMAIN}/?id=1'%20OR%20'1'='1")
    if [ "$HTTP_CODE" = "403" ]; then
        echo -e "${GREEN}✓ WAF blocked malicious request (403 Forbidden)${NC}"
    else
        echo -e "${YELLOW}⚠ Got HTTP $HTTP_CODE (expected 403)${NC}"
    fi
    echo ""

    echo -e "${YELLOW}Test 4: Bot detection${NC}"
    HTTP_CODE=$(curl -s -A "sqlmap" -o /dev/null -w "%{http_code}" "${PROTOCOL}://${DOMAIN}/")
    if [ "$HTTP_CODE" = "403" ]; then
        echo -e "${GREEN}✓ Bot blocked (403 Forbidden)${NC}"
    else
        echo -e "${YELLOW}⚠ Got HTTP $HTTP_CODE (expected 403)${NC}"
    fi
    echo ""

    echo -e "${GREEN}Tests complete!${NC}"
}

# Verify setup
cmd_verify() {
    echo -e "${BLUE}Verifying BunkerWeb setup...${NC}"
    echo ""

    # Check .env
    if [ -f ".env" ]; then
        echo -e "${GREEN}✓ .env file exists${NC}"

        DOMAIN=$(grep "^DOMAIN=" .env | cut -d'=' -f2)
        if [ "$DOMAIN" = "www.example.com" ] || [ -z "$DOMAIN" ]; then
            echo -e "${YELLOW}⚠ DOMAIN not configured (still using example.com)${NC}"
        else
            echo -e "${GREEN}✓ DOMAIN configured: $DOMAIN${NC}"
        fi
    else
        echo -e "${RED}✗ .env file missing${NC}"
    fi

    # Check containers
    echo ""
    RUNNING=$(docker compose -f "$COMPOSE_FILE" ps -q | wc -l)
    EXPECTED=8
    if [ "$RUNNING" -eq "$EXPECTED" ]; then
        echo -e "${GREEN}✓ All $EXPECTED containers running${NC}"
    else
        echo -e "${YELLOW}⚠ Only $RUNNING of $EXPECTED containers running${NC}"
    fi

    # Check BunkerWeb
    echo ""
    if docker exec phpweave-bunkerweb nginx -t 2>&1 | grep -q "successful"; then
        echo -e "${GREEN}✓ BunkerWeb NGINX config valid${NC}"
    else
        echo -e "${RED}✗ BunkerWeb NGINX config has errors${NC}"
    fi

    # Check ports
    echo ""
    if nc -z localhost 80 2>/dev/null || netstat -an 2>/dev/null | grep -q ":80 "; then
        echo -e "${GREEN}✓ Port 80 (HTTP) is open${NC}"
    else
        echo -e "${YELLOW}⚠ Port 80 not accessible${NC}"
    fi

    if nc -z localhost 443 2>/dev/null || netstat -an 2>/dev/null | grep -q ":443 "; then
        echo -e "${GREEN}✓ Port 443 (HTTPS) is open${NC}"
    else
        echo -e "${YELLOW}⚠ Port 443 not accessible${NC}"
    fi

    echo ""
    echo -e "${BLUE}Verification complete!${NC}"
}

# Check certificate
cmd_cert() {
    if [ ! -f ".env" ]; then
        echo -e "${RED}Error: .env file not found.${NC}"
        exit 1
    fi

    DOMAIN=$(grep "^DOMAIN=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")

    if [ -z "$DOMAIN" ] || [ "$DOMAIN" = "www.example.com" ]; then
        echo -e "${YELLOW}Domain not configured in .env${NC}"
        exit 1
    fi

    echo -e "${BLUE}Checking SSL certificate for: $DOMAIN${NC}"
    echo ""

    # Check if certificate exists in container
    if docker exec phpweave-bunkerweb ls /data/cache/letsencrypt 2>/dev/null | grep -q "$DOMAIN"; then
        echo -e "${GREEN}✓ Let's Encrypt certificate found${NC}"

        # Try to get expiry info
        echo ""
        echo "Certificate details:"
        echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -dates 2>/dev/null || echo "Could not retrieve certificate details"
    else
        echo -e "${YELLOW}⚠ Certificate not found or not yet issued${NC}"
        echo ""
        echo "Check BunkerWeb logs for Let's Encrypt status:"
        docker logs phpweave-bunkerweb 2>&1 | grep -i "let's encrypt" | tail -10
    fi
}

# Backup
cmd_backup() {
    BACKUP_DIR="./backups"
    DATE=$(date +%Y%m%d-%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/phpweave-bunkerweb-$DATE.tar.gz"

    mkdir -p "$BACKUP_DIR"

    echo -e "${YELLOW}Creating backup...${NC}"

    # Backup volumes
    docker run --rm \
        -v "$(pwd)/$BACKUP_DIR:/backup" \
        -v phpweave_bw-data:/bw-data:ro \
        -v phpweave_db-data:/db-data:ro \
        alpine tar czf "/backup/volumes-$DATE.tar.gz" /bw-data /db-data

    # Backup .env
    cp .env "$BACKUP_DIR/env-$DATE"

    # Backup compose file
    cp "$COMPOSE_FILE" "$BACKUP_DIR/compose-$DATE.yml"

    echo -e "${GREEN}✓ Backup created: $BACKUP_DIR/volumes-$DATE.tar.gz${NC}"
    echo -e "${GREEN}✓ Config backed up: $BACKUP_DIR/env-$DATE${NC}"
}

# List backups
cmd_list_backups() {
    BACKUP_DIR="./backups"

    if [ ! -d "$BACKUP_DIR" ]; then
        echo -e "${YELLOW}No backups found.${NC}"
        return
    fi

    echo -e "${BLUE}Available backups:${NC}"
    ls -lh "$BACKUP_DIR" | grep -E "(volumes|env|compose)"
}

# Debug
cmd_debug() {
    echo -e "${BLUE}Debug Information:${NC}"
    echo ""

    echo "=== System Info ==="
    uname -a
    echo ""

    echo "=== Docker Version ==="
    docker --version
    docker compose version
    echo ""

    echo "=== Container Status ==="
    docker compose -f "$COMPOSE_FILE" ps
    echo ""

    echo "=== Recent BunkerWeb Logs ==="
    docker logs phpweave-bunkerweb --tail 20
    echo ""

    echo "=== Recent Scheduler Logs ==="
    docker logs phpweave-bw-scheduler --tail 20
    echo ""
}

# Health check
cmd_health() {
    echo -e "${BLUE}Health Check:${NC}"
    echo ""

    # Check each service
    services=("phpweave-bunkerweb" "phpweave-bw-scheduler" "phpweave-bw-ui" "phpweave-bw-db" "phpweave-redis" "phpweave-app" "phpweave-db")

    for service in "${services[@]}"; do
        if docker ps | grep -q "$service"; then
            STATUS=$(docker inspect --format='{{.State.Status}}' "$service")
            if [ "$STATUS" = "running" ]; then
                echo -e "${GREEN}✓${NC} $service: $STATUS"
            else
                echo -e "${RED}✗${NC} $service: $STATUS"
            fi
        else
            echo -e "${RED}✗${NC} $service: not found"
        fi
    done
}

# Show errors
cmd_errors() {
    echo -e "${BLUE}Recent Errors:${NC}"
    echo ""

    echo "=== BunkerWeb Errors ==="
    docker logs phpweave-bunkerweb 2>&1 | grep -i error | tail -20

    echo ""
    echo "=== PHPWeave App Errors ==="
    docker logs phpweave-app 2>&1 | grep -i error | tail -20

    echo ""
    echo "=== Database Errors ==="
    docker logs phpweave-db 2>&1 | grep -i error | tail -20
}

# Show info
cmd_info() {
    echo -e "${BLUE}Service Information:${NC}"
    echo ""

    if [ -f ".env" ]; then
        DOMAIN=$(grep "^DOMAIN=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
        ADMIN_USER=$(grep "^BW_ADMIN_USER=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")

        if [ -z "$ADMIN_USER" ]; then
            ADMIN_USER="admin"
        fi

        echo -e "${GREEN}Access URLs:${NC}"
        if [ "$DOMAIN" != "www.example.com" ] && [ ! -z "$DOMAIN" ]; then
            echo "  PHPWeave (via WAF): https://$DOMAIN"
        else
            echo "  PHPWeave (via WAF): http://localhost (configure DOMAIN in .env)"
        fi
        echo "  Admin UI:           http://localhost:7000"
        echo "  phpMyAdmin:         http://localhost:8081"
        echo ""

        echo -e "${GREEN}Admin Credentials:${NC}"
        echo "  Username: $ADMIN_USER"
        echo "  Password: (see BW_ADMIN_PASSWORD in .env)"
        echo ""
    fi

    echo -e "${GREEN}Management Commands:${NC}"
    echo "  View logs:    ./bunkerweb.sh logs"
    echo "  Check status: ./bunkerweb.sh status"
    echo "  Run tests:    ./bunkerweb.sh test"
    echo "  Get help:     ./bunkerweb.sh help"
    echo ""
}

# Main script
main() {
    check_compose_file
    check_docker

    # If no command, show menu
    if [ $# -eq 0 ]; then
        print_header
        show_menu
        exit 0
    fi

    # Process command
    case "$1" in
        setup)          cmd_setup ;;
        start)          cmd_start ;;
        stop)           cmd_stop ;;
        restart)        cmd_restart ;;
        down)           cmd_down ;;
        destroy)        cmd_destroy ;;
        status)         cmd_status ;;
        logs)           cmd_logs ;;
        logs-bw)        cmd_logs_bw ;;
        logs-app)       cmd_logs_app ;;
        logs-db)        cmd_logs_db ;;
        stats)          cmd_stats ;;
        update)         cmd_update ;;
        reload)         cmd_reload ;;
        shell-bw)       cmd_shell_bw ;;
        shell-app)      cmd_shell_app ;;
        shell-db)       cmd_shell_db ;;
        test)           cmd_test ;;
        verify)         cmd_verify ;;
        cert)           cmd_cert ;;
        backup)         cmd_backup ;;
        list-backups)   cmd_list_backups ;;
        debug)          cmd_debug ;;
        health)         cmd_health ;;
        errors)         cmd_errors ;;
        info)           cmd_info ;;
        help|--help|-h) print_header; show_menu ;;
        *)
            echo -e "${RED}Unknown command: $1${NC}"
            echo ""
            show_menu
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
