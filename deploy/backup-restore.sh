#!/bin/bash

# Rokhdad Backup & Restore Script
# ================================
#
# Usage:
#   ./backup-restore.sh backup     # Create backup
#   ./backup-restore.sh restore <file>  # Restore from backup
#   ./backup-restore.sh list       # List backups

set -e

BACKUP_DIR="/opt/rokhdad/backups"
DEPLOY_DIR="/opt/rokhdad"
DATE=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}✓ $1${NC}"; }
log_error() { echo -e "${RED}✗ $1${NC}"; exit 1; }
log_warn() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Create backups directory
mkdir -p "$BACKUP_DIR"

backup_database() {
    echo "Backing up MariaDB..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" exec -T mariadb \
        mysqldump \
        -u rokhdad \
        -p$(grep MARIADB_PASSWORD "$DEPLOY_DIR/.env" | cut -d= -f2) \
        rokhdad \
        > "$BACKUP_DIR/mariadb_${DATE}.sql"
    
    log_info "Database backup: mariadb_${DATE}.sql"
}

backup_mongodb() {
    echo "Backing up MongoDB..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" exec -T mongodb \
        mongodump \
        --out "$BACKUP_DIR/mongodb_${DATE}" \
        --gzip
    
    log_info "MongoDB backup: mongodb_${DATE}"
}

backup_files() {
    echo "Backing up storage files..."
    
    tar -czf "$BACKUP_DIR/storage_${DATE}.tar.gz" \
        -C "$DEPLOY_DIR" backend/storage || true
    
    log_info "Files backup: storage_${DATE}.tar.gz"
}

backup_all() {
    echo -e "${YELLOW}Starting full backup...${NC}\n"
    
    backup_database
    backup_mongodb
    backup_files
    
    # Compress backup directory
    tar -czf "$BACKUP_DIR/backup_${DATE}.tar.gz" \
        -C "$BACKUP_DIR" \
        mariadb_${DATE}.sql \
        mongodb_${DATE} \
        storage_${DATE}.tar.gz
    
    # Cleanup individual files
    rm -f "$BACKUP_DIR/mariadb_${DATE}.sql"
    rm -rf "$BACKUP_DIR/mongodb_${DATE}"
    rm -f "$BACKUP_DIR/storage_${DATE}.tar.gz"
    
    echo ""
    log_info "Full backup complete: backup_${DATE}.tar.gz"
    echo "Location: $BACKUP_DIR/backup_${DATE}.tar.gz"
}

restore_database() {
    local backup_file=$1
    
    echo "Restoring MariaDB from $backup_file..."
    
    docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" exec -T mariadb \
        mysql \
        -u rokhdad \
        -p$(grep MARIADB_PASSWORD "$DEPLOY_DIR/.env" | cut -d= -f2) \
        rokhdad \
        < "$backup_file"
    
    log_info "Database restored"
}

restore_all() {
    local backup_file=$1
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
    fi
    
    echo -e "${YELLOW}Restoring from $backup_file...${NC}\n"
    
    # Extract backup
    tar -xzf "$backup_file" -C "$BACKUP_DIR"
    
    # Restore database
    local db_file=$(ls "$BACKUP_DIR"/mariadb_*.sql 2>/dev/null | head -1)
    if [ -f "$db_file" ]; then
        restore_database "$db_file"
        rm "$db_file"
    fi
    
    # Restore MongoDB
    local mongo_dir=$(ls -d "$BACKUP_DIR"/mongodb_* 2>/dev/null | head -1)
    if [ -d "$mongo_dir" ]; then
        docker compose -f "$DEPLOY_DIR/deploy/docker-compose.yml" exec -T mongodb \
            mongorestore "$mongo_dir" --gzip
        rm -rf "$mongo_dir"
        log_info "MongoDB restored"
    fi
    
    # Restore files
    if [ -f "$BACKUP_DIR/storage_*.tar.gz" ]; then
        tar -xzf "$BACKUP_DIR/storage_*.tar.gz" -C "$DEPLOY_DIR"
        rm "$BACKUP_DIR/storage_*.tar.gz"
        log_info "Files restored"
    fi
    
    log_info "Full restore complete"
}

list_backups() {
    echo "Available backups:"
    echo ""
    ls -lh "$BACKUP_DIR"/*.tar.gz 2>/dev/null || echo "No backups found"
}

# Main
case "${1:-}" in
    backup)
        backup_all
        ;;
    restore)
        if [ -z "$2" ]; then
            log_error "Backup file required: $0 restore <file>"
        fi
        restore_all "$2"
        ;;
    list)
        list_backups
        ;;
    *)
        echo "Usage: $0 {backup|restore|list}"
        echo ""
        echo "Examples:"
        echo "  $0 backup                          # Create full backup"
        echo "  $0 restore /path/to/backup.tar.gz  # Restore from backup"
        echo "  $0 list                            # List available backups"
        exit 1
        ;;
esac
