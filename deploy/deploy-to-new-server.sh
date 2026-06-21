#!/bin/bash

# 🚀 ROKHDAD DEPLOYMENT ON NEW SERVER
# ====================================
#
# Server: 193.151.139.93
# OS: Ubuntu 20.04 LTS
# Deploy: Rokhdad Project

echo "🚀 Connecting to new server..."
echo "IP: 193.151.139.93"
echo "User: root"
echo ""

# Step 1: SSH Connection
echo "1️⃣  Connecting via SSH..."
ssh root@193.151.139.93 << 'REMOTE_COMMANDS'

echo "✅ Connected to new server!"
echo ""

# Step 2: System Update
echo "2️⃣  Updating system packages..."
apt-get update
apt-get upgrade -y

# Step 3: Install Docker & Git
echo "3️⃣  Installing Docker and Git..."
apt-get install -y docker.io docker-compose git curl wget

# Step 4: Create deployment directory
echo "4️⃣  Creating deployment directory..."
mkdir -p /opt/rokhdad
cd /opt/rokhdad

# Step 5: Clone repository
echo "5️⃣  Cloning Rokhdad repository..."
git clone https://github.com/yourrepo/rokhdad.top.git .

# Step 6: Copy environment file
echo "6️⃣  Setting up environment..."
cp .env.example .env

echo ""
echo "⚠️  IMPORTANT: Edit /opt/rokhdad/.env with production values:"
echo "   nano .env"
echo ""
echo "Required variables to set:"
echo "  - APP_KEY (auto-generated)"
echo "  - DB_PASSWORD"
echo "  - REDIS_PASSWORD"
echo "  - ZARINPAL_MERCHANT_ID"
echo "  - ZIBAL_MERCHANT_ID"
echo "  - SMSIR_API_KEY"
echo "  - MAIL_PASSWORD"
echo ""

# Step 7: Build Docker images
echo "7️⃣  Building Docker images..."
docker compose -f deploy/docker-compose.yml build

# Step 8: Start services
echo "8️⃣  Starting services..."
docker compose -f deploy/docker-compose.yml up -d

# Step 9: Run migrations
echo "9️⃣  Running database migrations..."
sleep 10
docker compose -f deploy/docker-compose.yml exec backend php artisan migrate --force

# Step 10: Health check
echo "🔟 Health check..."
curl http://localhost/api/health

echo ""
echo "✅ Deployment complete!"
echo "Website: https://rokhdad.top"
echo "API: https://rokhdad.top/api/v1"
echo ""

REMOTE_COMMANDS

echo ""
echo "✨ Rokhdad is now deployed on 193.151.139.93!"
