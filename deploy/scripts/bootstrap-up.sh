#!/usr/bin/env sh
set -eu

cd "${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"

docker compose -f deploy/bootstrap-compose.yml up -d
docker compose -f deploy/bootstrap-compose.yml ps

curl -fsS "http://127.0.0.1/api/health"
echo

