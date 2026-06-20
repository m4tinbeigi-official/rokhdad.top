#!/bin/bash

# 🚀 COMPLETE DEPLOY WORKFLOW
# ===========================
# 
# This script handles:
# 1. Git pull
# 2. Create feature branch
# 3. Add all files
# 4. Commit changes
# 5. Push to GitHub
# 6. Instructions for PR merge
# 7. Deploy to server
# 8. Verify deployment

set -e

PROJECT_DIR="/Users/ricksabchez/Desktop/Rokhdad.ToP"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_step() { echo -e "${BLUE}→ $1${NC}"; }
log_success() { echo -e "${GREEN}✓ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Step 1: Change to project directory
log_step "Changing to project directory..."
cd "$PROJECT_DIR"
log_success "In: $PROJECT_DIR"

# Step 2: Git pull
log_step "Pulling latest from GitHub..."
git pull origin main --ff-only
log_success "Pulled successfully"

# Step 3: Create feature branch
log_step "Creating feature branch..."
git checkout -b feature/complete-pending-tasks-p28-p31
log_success "Branch: feature/complete-pending-tasks-p28-p31"

# Step 4: Add all files
log_step "Staging all changes..."
git add -A
log_success "All changes staged"

# Step 5: Show what's being committed
echo ""
echo -e "${BLUE}Files to commit:${NC}"
git diff --cached --name-only | head -20
echo ""

# Step 6: Commit
log_step "Creating commit..."
git commit -m "feat: complete all PENDING tasks (P28-P31)

- P28-002: Webhook framework with delivery and retry
- P28-003: Attendee import/export from CSV
- P29-001: Organizer analytics dashboard
- P30-001: Settlement ledger for accounting
- P31-001: Centralized logging service
- P31-002: Backup and restore automation
- P31-003: Rollback deployment workflow
- P31-004: Security hardening checklist

All systems now production-ready."

log_success "Commit created"

# Step 7: Push to GitHub
log_step "Pushing to GitHub..."
git push origin feature/complete-pending-tasks-p28-p31
log_success "Pushed to GitHub"

# Step 8: Instructions
echo ""
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${BLUE}  NEXT STEPS (Manual)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo ""
echo "1️⃣  Go to GitHub:"
echo "   https://github.com/yourrepo/rokhdad.top"
echo ""
echo "2️⃣  Create Pull Request:"
echo "   - Branch: feature/complete-pending-tasks-p28-p31"
echo "   - Title: feat: complete all PENDING tasks"
echo "   - Description: See commit message"
echo ""
echo "3️⃣  Review and Merge PR"
echo ""
echo "4️⃣  Deploy to server:"
echo ""
echo "   ssh root@45.94.215.10"
echo "   cd /opt/rokhdad"
echo "   git pull origin main"
echo "   docker compose -f deploy/docker-compose.yml up -d --build"
echo "   docker compose -f deploy/docker-compose.yml exec backend php artisan migrate --force"
echo ""
echo "5️⃣  Verify deployment:"
echo "   curl https://rokhdad.top/api/health"
echo ""
echo -e "${GREEN}Status: Ready for GitHub PR and deployment${NC}"
echo ""
