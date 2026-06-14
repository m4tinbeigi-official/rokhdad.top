# Rokhdad Quick Launch

Use this only to bring up the first public landing page quickly before the full Laravel/Vue stack is ready.

## Requirements

- Ubuntu server
- SSH access as root or sudo user
- Ports 80 and 443 reachable from the internet
- DNS `rokhdad.top` pointing to the server

## One-command Server Bootstrap

Run on the Ubuntu server:

```bash
curl -fsSL https://raw.githubusercontent.com/m4tinbeigi-official/rokhdad.top/refs/heads/main/deploy/scripts/ubuntu-bootstrap-install.sh | sudo sh
```

Expected result:

- Docker is installed if missing.
- Repo is cloned to `/opt/rokhdad`.
- Bootstrap Nginx container starts.
- `http://rokhdad.top` serves the landing page.
- `http://rokhdad.top/api/health` returns JSON.

## Manual Server Bootstrap

```bash
sudo mkdir -p /opt/rokhdad
sudo chown "$USER":"$USER" /opt/rokhdad
cd /opt/rokhdad
git clone https://github.com/m4tinbeigi-official/rokhdad.top.git .
docker compose -f deploy/bootstrap-compose.yml up -d
curl -fsS http://127.0.0.1/api/health
```

## Current Blocker

Codex cannot run these commands directly until SSH username, SSH port, and key/password access for `45.94.215.10` are available.
