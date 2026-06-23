#!/bin/bash
set -e

# ─── تنظیمات ───────────────────────────────────────────────
SERVER_IP="193.151.139.93"
SERVER_USER="root"
SERVER_PASS="7s@*1#OzB8Dx"
REMOTE_DIR="/var/www/rokhdad"
LOCAL_BACKEND="$(dirname "$0")/backend"
LOCAL_FRONTEND="$(dirname "$0")/frontend"

# رنگ‌ها
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# بررسی ابزارهای لازم
command -v rsync >/dev/null 2>&1 || err "rsync نصب نیست. brew install rsync"
command -v sshpass >/dev/null 2>&1 || err "sshpass نصب نیست. brew install sshpass"
command -v npm >/dev/null 2>&1 || err "npm نصب نیست."

SSH_CMD="sshpass -p '$SERVER_PASS' ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP"
RSYNC_CMD="sshpass -p '$SERVER_PASS' rsync -avz --progress -e 'ssh -o StrictHostKeyChecking=no'"

echo ""
echo "═══════════════════════════════════════════"
echo "   دیپلوی Rokhdad.ToP → $SERVER_IP"
echo "═══════════════════════════════════════════"
echo ""

# ─── مرحله ۱: بیلد فرانت‌اند ────────────────────────────────
log "در حال بیلد frontend..."
cd "$LOCAL_FRONTEND"
npm install --silent
npm run build
log "بیلد frontend کامل شد."

# ─── مرحله ۲: آماده‌سازی سرور ──────────────────────────────
log "در حال آماده‌سازی سرور..."
eval "$SSH_CMD" << 'REMOTE_SETUP'
set -e

# نصب وابستگی‌ها اگر نیاز باشد
if ! command -v php &>/dev/null; then
    apt-get update -qq
    apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
        php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
        php8.2-intl php8.2-redis composer nginx mysql-server -qq
fi

if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# ساخت دایرکتوری
mkdir -p /var/www/rokhdad/backend
mkdir -p /var/www/rokhdad/frontend
REMOTE_SETUP
log "سرور آماده شد."

# ─── مرحله ۳: آپلود backend ─────────────────────────────────
log "در حال آپلود backend..."
eval "$RSYNC_CMD \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    '$LOCAL_BACKEND/' '$SERVER_USER@$SERVER_IP:$REMOTE_DIR/backend/'"
log "backend آپلود شد."

# ─── مرحله ۴: آپلود frontend build ─────────────────────────
log "در حال آپلود frontend dist..."
eval "$RSYNC_CMD \
    '$LOCAL_FRONTEND/dist/' '$SERVER_USER@$SERVER_IP:$REMOTE_DIR/frontend/'"
log "frontend آپلود شد."

# ─── مرحله ۵: تنظیمات روی سرور ─────────────────────────────
log "در حال اجرای تنظیمات روی سرور..."
eval "$SSH_CMD" << REMOTE_CONFIG
set -e

cd $REMOTE_DIR/backend

# composer install
composer install --no-dev --optimize-autoloader --no-interaction -q

# .env اگر وجود نداشته باشد بساز
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || cat > .env << 'ENVFILE'
APP_NAME=Rokhdad
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://$SERVER_IP

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rokhdad
DB_USERNAME=rokhdad
DB_PASSWORD=change_this_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
ENVFILE

    php artisan key:generate
fi

# permissions
chown -R www-data:www-data $REMOTE_DIR/backend
chmod -R 755 $REMOTE_DIR/backend
chmod -R 775 $REMOTE_DIR/backend/storage
chmod -R 775 $REMOTE_DIR/backend/bootstrap/cache

# cache و migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ تنظیمات کامل شد"
REMOTE_CONFIG

# ─── مرحله ۶: تنظیم Nginx ───────────────────────────────────
log "در حال تنظیم Nginx..."
eval "$SSH_CMD" << NGINX_CONFIG
cat > /etc/nginx/sites-available/rokhdad << 'NGINXCONF'
server {
    listen 80;
    server_name $SERVER_IP rokhdad.top www.rokhdad.top;

    # Frontend (Vue SPA)
    root /var/www/rokhdad/frontend;
    index index.html;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Backend API
    location /api {
        alias /var/www/rokhdad/backend/public;
        try_files \$uri \$uri/ @backend;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/rokhdad/backend/public\$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    location @backend {
        rewrite /api/(.*)$ /index.php?/\$1 last;
    }

    # Laravel backend مستقیم
    location /admin {
        root /var/www/rokhdad/backend/public;
        try_files \$uri \$uri/ /index.php?\$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/rokhdad/backend/public\$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    client_max_body_size 50M;
}
NGINXCONF

ln -sf /etc/nginx/sites-available/rokhdad /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl restart nginx
systemctl enable nginx
systemctl restart php8.2-fpm 2>/dev/null || true

echo "✓ Nginx راه‌اندازی شد"
NGINX_CONFIG

echo ""
echo "═══════════════════════════════════════════"
log "دیپلوی با موفقیت انجام شد!"
echo ""
echo "  🌐 سایت:   http://$SERVER_IP"
echo "  🔧 بکند:   http://$SERVER_IP/api"
echo "  📊 ادمین:  http://$SERVER_IP/admin"
echo ""
warn "مراحل بعدی:"
echo "  ۱. دیتابیس MySQL را تنظیم کنید"
echo "  ۲. فایل .env را با اطلاعات DB به‌روز کنید"
echo "  ۳. دستور زیر را روی سرور اجرا کنید:"
echo "     php artisan migrate --force"
echo "═══════════════════════════════════════════"
