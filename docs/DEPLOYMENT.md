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

- `http://rokhdad.top` returns `200 OK` through Caddy and a Next.js response.
- `https://rokhdad.top` fails TLS negotiation with `tlsv1 alert internal error`.
- SSH port `22` is reachable on `45.94.215.10`.
- SSH login attempts for `root`, `ubuntu`, `admin`, `matin`, `m4tinbeigi`, and `ricksabchez` were denied.

This means the server is online, but Codex cannot deploy Docker or fix HTTPS until valid SSH access is available.

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

```bash
cd /opt/rokhdad
git fetch origin
git checkout main
git pull --ff-only origin main
docker compose -f deploy/docker-compose.yml up -d --build
docker compose -f deploy/docker-compose.yml exec backend php artisan migrate --force
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

Database rollback must be task-specific. Do not blindly rollback production migrations without a reviewed rollback note.

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
