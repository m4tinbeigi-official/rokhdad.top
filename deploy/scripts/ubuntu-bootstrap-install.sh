#!/usr/bin/env sh
set -eu

REPO_URL="${REPO_URL:-https://github.com/m4tinbeigi-official/rokhdad.top.git}"
DEPLOY_DIR="${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this script as root or with sudo." >&2
  exit 1
fi

apt-get update
apt-get install -y ca-certificates curl git

if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  . /etc/os-release
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
  apt-get update
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

mkdir -p "$DEPLOY_DIR"

if [ -d "$DEPLOY_DIR/.git" ]; then
  git -C "$DEPLOY_DIR" fetch origin
  git -C "$DEPLOY_DIR" checkout main
  git -C "$DEPLOY_DIR" pull --ff-only origin main
else
  git clone "$REPO_URL" "$DEPLOY_DIR"
fi

cd "$DEPLOY_DIR"
docker compose -f deploy/bootstrap-compose.yml up -d
docker compose -f deploy/bootstrap-compose.yml ps
curl -fsS http://127.0.0.1/api/health
echo
echo "Rokhdad bootstrap is running on port 80."

