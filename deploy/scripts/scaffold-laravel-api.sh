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
  tmp_dir="$(mktemp -d)"
  trap 'rm -rf "$tmp_dir"' EXIT
  docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$tmp_dir:/app" \
    -w /app \
    composer:2 \
    composer create-project "laravel/laravel:${LARAVEL_VERSION}" backend
  mkdir -p "$APP_DIR"
  cp -a "$tmp_dir/backend/." "$APP_DIR/"
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
