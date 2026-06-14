#!/usr/bin/env sh
set -eu

DEPLOY_DIR="${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"
ROOT_ENV="${DEPLOY_DIR}/.env"
BACKEND_ENV="${DEPLOY_DIR}/backend/.env"

cd "$DEPLOY_DIR"

random_secret() {
  openssl rand -base64 32 | tr -d '\n'
}

if [ ! -f "$ROOT_ENV" ]; then
  cp .env.example "$ROOT_ENV"
fi

if [ ! -f "$BACKEND_ENV" ]; then
  cp backend/.env.example "$BACKEND_ENV"
fi

python3 - "$ROOT_ENV" "$BACKEND_ENV" <<'PY'
import base64
import os
import secrets
import sys
from pathlib import Path

root_env = Path(sys.argv[1])
backend_env = Path(sys.argv[2])


def read_env(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.exists():
        return values
    for line in path.read_text().splitlines():
        if not line or line.lstrip().startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"')
    return values


def write_env(path: Path, values: dict[str, str]) -> None:
    lines = [f"{key}={value}" for key, value in values.items()]
    path.write_text("\n".join(lines) + "\n")


def secret() -> str:
    return base64.urlsafe_b64encode(secrets.token_bytes(32)).decode().rstrip("=")


root = read_env(root_env)
backend = read_env(backend_env)

mariadb_password = root.get("MARIADB_PASSWORD")
if not mariadb_password or mariadb_password == "change-me":
    mariadb_password = secret()

mariadb_root_password = root.get("MARIADB_ROOT_PASSWORD")
if not mariadb_root_password or mariadb_root_password == "change-me":
    mariadb_root_password = secret()

app_key = backend.get("APP_KEY") or root.get("APP_KEY")
if not app_key:
    app_key = "base64:" + base64.b64encode(secrets.token_bytes(32)).decode()

common = {
    "APP_NAME": "Rokhdad",
    "APP_ENV": "production",
    "APP_KEY": app_key,
    "APP_DEBUG": "false",
    "APP_URL": "https://rokhdad.top",
    "DB_CONNECTION": "mariadb",
    "DB_HOST": "mariadb",
    "DB_PORT": "3306",
    "DB_DATABASE": "rokhdad",
    "DB_USERNAME": "rokhdad",
    "DB_PASSWORD": mariadb_password,
    "CACHE_STORE": "database",
    "QUEUE_CONNECTION": "database",
    "SESSION_DRIVER": "database",
}

root.update(
    {
        **common,
        "API_BASE_URL": "https://rokhdad.top/api/v1",
        "ADMIN_URL": "https://rokhdad.top/admin",
        "DOMAIN": "rokhdad.top",
        "MARIADB_DATABASE": "rokhdad",
        "MARIADB_USER": "rokhdad",
        "MARIADB_PASSWORD": mariadb_password,
        "MARIADB_ROOT_PASSWORD": mariadb_root_password,
        "DATABASE_URL": f"mysql://rokhdad:{mariadb_password}@mariadb:3306/rokhdad",
        "MONGO_INITDB_ROOT_USERNAME": root.get("MONGO_INITDB_ROOT_USERNAME", "rokhdad"),
        "MONGO_INITDB_ROOT_PASSWORD": root.get("MONGO_INITDB_ROOT_PASSWORD") or secret(),
        "MONGODB_DATABASE": root.get("MONGODB_DATABASE", "rokhdad_raw"),
        "REDIS_PASSWORD": root.get("REDIS_PASSWORD") or secret(),
    }
)

backend.update(common)

write_env(root_env, root)
write_env(backend_env, backend)
PY

docker compose -f deploy/docker-compose.yml up -d mariadb

echo "Waiting for MariaDB healthcheck..."
for _ in $(seq 1 60); do
  status="$(docker inspect -f '{{.State.Health.Status}}' rokhdad-mariadb 2>/dev/null || true)"
  if [ "$status" = "healthy" ]; then
    break
  fi
  sleep 2
done

if [ "$(docker inspect -f '{{.State.Health.Status}}' rokhdad-mariadb)" != "healthy" ]; then
  docker logs rokhdad-mariadb --tail=80
  echo "MariaDB did not become healthy." >&2
  exit 1
fi

docker build -t rokhdad/backend:latest backend

docker run --rm \
  --env-file "$BACKEND_ENV" \
  --network rokhdad-internal \
  rokhdad/backend:latest \
  php artisan migrate --force

docker run --rm \
  --env-file "$BACKEND_ENV" \
  --network rokhdad-internal \
  rokhdad/backend:latest \
  php artisan migrate:status

echo "P4-001 MariaDB connection configured and migrations passed."

