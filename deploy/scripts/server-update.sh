#!/usr/bin/env sh
set -eu

cd "${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"

git fetch origin
git checkout main
git pull --ff-only origin main

docker compose -f deploy/docker-compose.yml up -d --build
docker compose -f deploy/docker-compose.yml ps

sh deploy/scripts/healthcheck.sh

