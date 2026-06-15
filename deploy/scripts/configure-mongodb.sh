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

username = root.get("MONGO_INITDB_ROOT_USERNAME") or "rokhdad"
database = root.get("MONGODB_DATABASE") or "rokhdad_raw"
password = root.get("MONGO_INITDB_ROOT_PASSWORD")
rotated = "0"
if not password or password == "change-me":
    password = secret()
    rotated = "1"

uri = f"mongodb://{username}:{password}@mongodb:27017/{database}?authSource=admin"

root.update(
    {
        "MONGO_INITDB_ROOT_USERNAME": username,
        "MONGO_INITDB_ROOT_PASSWORD": password,
        "MONGODB_DATABASE": database,
        "MONGODB_URI": uri,
    }
)
backend.update({"MONGODB_URI": uri})

write_env(root_env, root)
write_env(backend_env, backend)
print(rotated)
PY
)"

set -a
. "$ROOT_ENV"
set +a

if [ "$rotated_placeholder_password" = "1" ] && docker ps -a --format '{{.Names}}' | grep -qx 'rokhdad-mongodb'; then
  docker rm -f rokhdad-mongodb >/dev/null 2>&1 || true
  if [ "$(docker volume ls -q --filter name=rokhdad_mongodb_data)" = "rokhdad_mongodb_data" ]; then
    docker volume rm rokhdad_mongodb_data >/dev/null 2>&1 || true
  fi
fi

docker compose --env-file "$ROOT_ENV" -f deploy/docker-compose.yml up -d mongodb

echo "Waiting for MongoDB to accept authenticated connections..."
for _ in $(seq 1 60); do
  if docker run --rm --env-file "$ROOT_ENV" --network rokhdad-internal mongo:7 \
    mongosh --quiet \
      --username "$MONGO_INITDB_ROOT_USERNAME" \
      --password "$MONGO_INITDB_ROOT_PASSWORD" \
      --authenticationDatabase admin \
      "mongodb://mongodb:27017/$MONGODB_DATABASE" \
      --eval 'db.runCommand({ ping: 1 }).ok' 2>/dev/null | grep -qx '1'; then
    break
  fi
  sleep 2
done

if ! docker run --rm --env-file "$ROOT_ENV" --network rokhdad-internal mongo:7 \
  mongosh --quiet \
    --username "$MONGO_INITDB_ROOT_USERNAME" \
    --password "$MONGO_INITDB_ROOT_PASSWORD" \
    --authenticationDatabase admin \
    "mongodb://mongodb:27017/$MONGODB_DATABASE" \
    --eval 'db.runCommand({ ping: 1 }).ok' | grep -qx '1'; then
  docker logs rokhdad-mongodb --tail=80
  echo "MongoDB did not become ready." >&2
  exit 1
fi

readback="$(
docker run --rm --env-file "$ROOT_ENV" --network rokhdad-internal mongo:7 \
  mongosh --quiet \
    --username "$MONGO_INITDB_ROOT_USERNAME" \
    --password "$MONGO_INITDB_ROOT_PASSWORD" \
    --authenticationDatabase admin \
    "mongodb://mongodb:27017/$MONGODB_DATABASE" \
    --eval 'const marker = "p4-002"; db.connection_smoke.updateOne({_id: marker}, {$set: {status: "ok", checked_at: new Date()}}, {upsert: true}); print(db.connection_smoke.findOne({_id: marker}).status);'
)"

if [ "$readback" != "ok" ]; then
  echo "MongoDB insert/read smoke test failed." >&2
  exit 1
fi

echo "P4-002 MongoDB connection configured and insert/read smoke test passed."
