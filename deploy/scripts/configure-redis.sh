#!/usr/bin/env sh
set -eu

DEPLOY_DIR="${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"
ROOT_ENV="${DEPLOY_DIR}/.env"
BACKEND_ENV="${DEPLOY_DIR}/backend/.env"

cd "$DEPLOY_DIR"

if [ ! -f "$ROOT_ENV" ]; then
  cp .env.example "$ROOT_ENV"
fi

if [ ! -f "$BACKEND_ENV" ]; then
  cp backend/.env.example "$BACKEND_ENV"
fi

rotated_placeholder_password="$(
python3 - "$ROOT_ENV" "$BACKEND_ENV" <<'PY'
import base64
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
    path.write_text("\n".join(f"{key}={value}" for key, value in values.items()) + "\n")


def secret() -> str:
    return base64.urlsafe_b64encode(secrets.token_bytes(32)).decode().rstrip("=")


root = read_env(root_env)
backend = read_env(backend_env)

password = root.get("REDIS_PASSWORD")
rotated = "0"
if not password or password == "change-me":
    password = secret()
    rotated = "1"

redis_url = f"redis://:{password}@redis:6379/0"

root.update(
    {
        "REDIS_PASSWORD": password,
        "REDIS_URL": redis_url,
        "REDIS_CLIENT": "predis",
        "REDIS_HOST": "redis",
        "REDIS_PORT": "6379",
        "CACHE_STORE": "redis",
        "QUEUE_CONNECTION": "redis",
    }
)

backend.update(
    {
        "REDIS_CLIENT": "predis",
        "REDIS_HOST": "redis",
        "REDIS_PASSWORD": password,
        "REDIS_PORT": "6379",
        "REDIS_URL": redis_url,
        "CACHE_STORE": "redis",
        "QUEUE_CONNECTION": "redis",
    }
)

write_env(root_env, root)
write_env(backend_env, backend)
print(rotated)
PY
)"

set -a
. "$ROOT_ENV"
set +a

if [ "$rotated_placeholder_password" = "1" ] && docker ps -a --format '{{.Names}}' | grep -qx 'rokhdad-redis'; then
  docker rm -f rokhdad-redis >/dev/null 2>&1 || true
  if [ "$(docker volume ls -q --filter name=rokhdad_redis_data)" = "rokhdad_redis_data" ]; then
    docker volume rm rokhdad_redis_data >/dev/null 2>&1 || true
  fi
fi

docker compose --env-file "$ROOT_ENV" -f deploy/docker-compose.yml up -d redis

echo "Waiting for Redis healthcheck..."
for _ in $(seq 1 60); do
  status="$(docker inspect -f '{{.State.Health.Status}}' rokhdad-redis 2>/dev/null || true)"
  if [ "$status" = "healthy" ]; then
    break
  fi
  sleep 2
done

if [ "$(docker inspect -f '{{.State.Health.Status}}' rokhdad-redis)" != "healthy" ]; then
  docker logs rokhdad-redis --tail=80
  echo "Redis did not become healthy." >&2
  exit 1
fi

if [ "$(docker run --rm --network rokhdad-internal redis:7-alpine redis-cli -h redis -a "$REDIS_PASSWORD" ping)" != "PONG" ]; then
  echo "Redis ping smoke test failed." >&2
  exit 1
fi

docker build -f deploy/backend.Dockerfile -t rokhdad/backend:latest backend

laravel_smoke="$(
docker run --rm \
  --env-file "$BACKEND_ENV" \
  --network rokhdad-internal \
  rokhdad/backend:latest \
  php artisan tinker --execute='cache()->store("redis")->put("p4-003", "ok", 60); if (cache()->store("redis")->get("p4-003") !== "ok") { throw new RuntimeException("Redis cache smoke failed"); } app("queue")->connection("redis")->size("default"); echo "ok";'
)"

if [ "$laravel_smoke" != "ok" ]; then
  echo "$laravel_smoke"
  echo "Laravel Redis cache/queue smoke test failed." >&2
  exit 1
fi

echo "P4-003 Redis cache and queue connection configured and smoke tests passed."
