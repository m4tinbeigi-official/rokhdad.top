# Rokhdad Deployment Plan

## Target

- Domain: `rokhdad.top`
- Runtime: Ubuntu server
- Runtime manager: Docker Compose
- Source of truth: GitHub repository
- Local machine runtime requirement: none

## Required Server Information

Deployment is blocked until these values are available:

- Server IP address: DNS currently resolves `rokhdad.top` to `45.94.215.10`.
- SSH username.
- SSH port.
- GitHub repository URL: `https://github.com/m4tinbeigi-official/rokhdad.top.git`.
- Deployment directory on server, recommended: `/opt/rokhdad`.
- Email address for Let's Encrypt.

## DNS Plan

| Record | Value |
|---|---|
| `A rokhdad.top` | `45.94.215.10` |
| `A www.rokhdad.top` | Ubuntu server IPv4 |

## Observed Server Status

Last checked from local machine:

- `http://rokhdad.top` redirects to `https://rokhdad.top`.
- `https://rokhdad.top` returns `200 OK` and serves the Rokhdad bootstrap landing page.
- `https://rokhdad.top/api/health` returns `{"status":"ok","service":"rokhdad-bootstrap"}`.
- SSH port `22` is reachable on `45.94.215.10`.
- SSH login as `root` works with the server credentials provided by the owner.

The current public site is a fast bootstrap landing page. The Laravel API scaffold exists on the server. `/api/v1`, `/api/health`, and `/api/ready` have passed internal HTTP smoke tests through a temporary PHP container.

Optional:

| Record | Value |
|---|---|
| `AAAA rokhdad.top` | Ubuntu server IPv6 if available |
| `AAAA www.rokhdad.top` | Ubuntu server IPv6 if available |

## Server Pull Workflow

```bash
sudo mkdir -p /opt/rokhdad
sudo chown "$USER":"$USER" /opt/rokhdad
cd /opt/rokhdad
git clone https://github.com/m4tinbeigi-official/rokhdad.top.git .
cp .env.example .env
# edit production env values on server only
docker compose -f deploy/docker-compose.yml up -d --build
docker compose -f deploy/docker-compose.yml ps
curl -fsS https://rokhdad.top/api/health
```

## Fast Bootstrap Workflow

Use this when the full Laravel/Vue images are not ready yet and the domain must show a valid initial page quickly.

One-command install on a fresh Ubuntu server:

```bash
curl -fsSL https://raw.githubusercontent.com/m4tinbeigi-official/rokhdad.top/refs/heads/main/deploy/scripts/ubuntu-bootstrap-install.sh | sudo sh
```

Manual flow:

```bash
cd /opt/rokhdad
git pull --ff-only origin main
docker compose -f deploy/bootstrap-compose.yml up -d
curl -fsS http://127.0.0.1/api/health
```

This serves the static landing page from `public/landing` on port `80` and returns a JSON health response from `/api/health`.

## Update Workflow

Mandatory operator rule for every task:

- Before starting any new task, run `git pull --ff-only origin main`.
- After finishing that task, commit and push the exact change to GitHub.
- After the push succeeds, deploy that exact change to the server and run the relevant smoke checks.

```bash
cd /opt/rokhdad
git fetch origin
git checkout main
git pull --ff-only origin main
docker compose -f deploy/docker-compose.yml up -d --build
deploy/scripts/laravel-db.sh migrate
curl -fsS https://rokhdad.top/api/health
```

## Rollback Workflow

```bash
cd /opt/rokhdad
git log --oneline -5
git checkout <PREVIOUS_GOOD_COMMIT>
docker compose -f deploy/docker-compose.yml up -d --build
curl -fsS https://rokhdad.top/api/health
```

Database rollback must be task-specific. Review `docs/MIGRATIONS_AND_SEEDS.md` and use `ROLLBACK_STEPS=N deploy/scripts/laravel-db.sh rollback-plan` before running a real rollback.

## SSL Plan

Use an Nginx-compatible certificate strategy in Docker Compose. The final implementation may use Certbot, acme-companion, or Caddy if approved before P2 implementation. The production requirement is automatic renewal and HTTPS-only traffic.

## Deployment Acceptance Criteria

- Server can pull from GitHub.
- `.env` exists only on server.
- Docker Compose starts all services.
- `https://rokhdad.top` returns frontend.
- `https://rokhdad.top/api/health` returns backend health.
- `https://rokhdad.top/admin` reaches Filament login after backend implementation.
- SSL certificate is valid and renewable.
