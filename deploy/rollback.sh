#!/bin/bash

# Rokhdad Rollback Script
# ======================
#
# Usage:
#   ./rollback.sh <commit-hash>   # Rollback to specific commit
#   ./rollback.sh                  # Rollback to previous stable version

set -e

DEPLOY_DIR="/opt/rokhdad"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}✓ $1${NC}"; }
log_error() { echo -e "${RED}✗ $1${NC}"; exit 1; }
log_warn() { echo -e "${YELLOW}⚠ $1${NC}"; }
log_step() { echo -e "${BLUE}→ $1${NC}"; }

check_prerequisites() {
    log_step "Checking prerequisites..."
    
    if [ ! -d "$DEPLOY_DIR/.git" ]; then
        log_error "Git repository not found in $DEPLOY_DIR"
    fi
    
    cd "$DEPLOY_DIR"
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker not installed"
    fi
    
    if [ -z "$(docker ps -q)" ]; then
        log_warn "No running Docker containers. Starting services..."
    fi
    
    log_info "Prerequisites OK"
}

backup_before_rollback() {
    log_step "Creating backup before rollback..."
    
    if [ -f "$DEPLOY_DIR/deploy/backup-restore.sh" ]; then
        chmod +x "$DEPLOY_DIR/deploy/backup-restore.sh"
        "$DEPLOY_DIR/deploy/backup-restore.sh" backup
    fi
    
    log_info "Backup created"
}

get_previous_commit() {
    cd "$DEPLOY_DIR"
    git log --oneline -2 | tail -1 | awk '{print $1}'
}

rollback_to_commit() {
    local commit=$1
    
    log_step "Rolling back to commit: $commit"
    
    cd "$DEPLOY_DIR"
    
    # Verify commit exists
    if ! git cat-file -t "$commit" > /dev/null 2>&1; then
        log_error "Commit $commit not found"
    fi
    
    # Stash any uncommitted changes
    git stash || true
    
    # Checkout commit
    git checkout "$commit"
    
    log_info "Checked out commit: $commit"
}

rebuild_containers() {
    log_step "Rebuilding Docker images..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" build --no-cache
    
    log_info "Images rebuilt"
}

restart_services() {
    log_step "Restarting services..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" down
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" up -d
    
    log_info "Services restarted"
}

run_migrations() {
    log_step "Running database migrations..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" exec -T backend \
        php artisan migrate --force || log_warn "Migrations may have failed (OK if downgrading)"
    
    log_info "Migrations checked"
}

health_check() {
    log_step "Running health checks..."
    
    sleep 5
    
    if curl -f http://localhost/api/health > /dev/null 2>&1; then
        log_info "Health check passed"
        return 0
    else
        log_error "Health check failed"
        return 1
    fi
}

prompt_confirmation() {
    local commit=$1
    
    echo ""
    echo -e "${YELLOW}Rollback Summary:${NC}"
    echo "  Commit: $commit"
    echo "  Action: Revert to this version"
    echo ""
    read -p "Continue with rollback? (yes/no): " choice
    
    if [ "$choice" != "yes" ]; then
        log_warn "Rollback cancelled"
        exit 0
    fi
}

# Main flow
check_prerequisites

COMMIT="${1:-$(get_previous_commit)}"

prompt_confirmation "$COMMIT"
backup_before_rollback
rollback_to_commit "$COMMIT"
rebuild_containers
restart_services
run_migrations

if health_check; then
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════${NC}"
    echo -e "${GREEN}✓ Rollback Successful!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════${NC}"
    echo ""
    echo "Rolled back to: $COMMIT"
    echo ""
    echo "To restore from backup if issues occur:"
    echo "  $DEPLOY_DIR/deploy/backup-restore.sh restore <backup-file>"
else
    log_error "Rollback failed health check. Restore from backup:"
    echo "  $DEPLOY_DIR/deploy/backup-restore.sh restore <backup-file>"
fi
