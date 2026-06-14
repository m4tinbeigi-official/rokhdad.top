#!/usr/bin/env sh
set -eu

DEPLOY_DIR="${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"
APP_DIR="${DEPLOY_DIR}/backend"
LARAVEL_VERSION="${LARAVEL_VERSION:-^11.0}"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required on the Ubuntu server." >&2
  exit 1
fi

cd "$DEPLOY_DIR"

if [ -f "$APP_DIR/artisan" ]; then
  echo "Laravel app already exists at $APP_DIR"
else
  docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$DEPLOY_DIR:/app" \
    -w /app \
    composer:2 \
    composer create-project "laravel/laravel:${LARAVEL_VERSION}" backend
fi

if [ ! -f "$APP_DIR/.env" ] && [ -f "$APP_DIR/.env.example" ]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$APP_DIR:/app" \
  -w /app \
  composer:2 \
  composer install --no-interaction --prefer-dist

docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$APP_DIR:/app" \
  -w /app \
  php:8.3-cli \
  php artisan about

echo "P3-001 Laravel API scaffold completed."

