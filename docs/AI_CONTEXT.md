# AI Context Map

Use this file as the first stop for Claude, Gemini, Antigravity, and other code
agents. It is intentionally short so routine work does not require loading the
full documentation set.

## Product

Rokhdad (`rokhdad.top`) is a Persian event aggregation, discovery, registration,
ticketing, and organizer tooling project. Core domains: events, organizers,
people, categories, cities, auth/OTP, registrations, tickets, payments,
settlements, comments, ratings, saved events, campaigns, webhooks, notifications,
external-source ingestion, enrichment, and admin operations.

## High-Signal Paths

- API routes: `backend/routes/api.php`, `backend/routes/web.php`,
  `backend/routes/settlement-routes.php`
- Controllers: `backend/app/Http/Controllers/`
- Models: `backend/app/Models/`
- Payments: `backend/app/Payments/`
- Notifications: `backend/app/Notifications/`
- Webhooks: `backend/app/Webhooks/`
- Admin: `backend/app/Providers/Filament/`,
  `backend/app/Filament/`
- Frontend app: `frontend/src/App.vue`
- Frontend API/client helpers: `frontend/src/api/`
- Frontend feature helpers: `frontend/src/events/`,
  `frontend/src/lookups/`, `frontend/src/organizer/`
- Workers: `workers/rokhdad_workers/`
- Worker sources and normalizers:
  `workers/rokhdad_workers/sources/`,
  `workers/rokhdad_workers/normalizers/`
- Deployment: `deploy/docker-compose.yml`, `deploy/scripts/`,
  `deploy/*.Dockerfile`

## Docs Routing

Open `docs/INDEX.md` first, then only the relevant doc:

- API routes/contracts: `docs/API_ENDPOINTS.md`, `docs/API_CONTRACTS.md`
- Data model: `docs/DATA_MODEL.md`
- Auth/OTP: `docs/AUTHENTICATION.md`
- Payments: `docs/PAYMENTS.md`
- Settlements: `docs/SETTLEMENTS.md`
- Notifications: `docs/NOTIFICATIONS.md`
- Campaigns: `docs/CAMPAIGNS.md`
- Webhooks: `docs/WEBHOOKS.md`
- Admin: `docs/ADMIN_PANEL.md`
- Ingestion/source pipeline: `docs/INGESTION_SOURCES.md`
- Mobile/Capacitor: `docs/MOBILE_APP.md`
- Deployment/infrastructure: `docs/DEPLOYMENT.md`,
  `docs/DOCKER_INFRASTRUCTURE.md`
- Task planning/status: `docs/TASK_BOARD.md`, `docs/MVP.md`

Avoid loading `docs/site/` unless the task is specifically about an external
provider API reference.

## Commands

Run commands from the relevant subdirectory.

- Backend install/test/build: `cd backend && composer install`,
  `php artisan test`, `npm run build`
- Backend targeted tests: `cd backend && php artisan test --filter Name`
- Frontend install/test/build: `cd frontend && npm install`, `npm test`,
  `npm run build`
- Workers install/test: `cd workers && python -m pip install -e .`;
  if pytest is available, run `python -m pytest`

Prefer targeted tests first. Some services are intended for the production
Docker environment, so do not assume local MariaDB, MongoDB, Redis, or Nginx are
running.

## Token-Saving Rules

- Do not open `backend/vendor/`, `frontend/node_modules/`, `frontend/dist/`,
  `workers/.venv/`, `workers/*.egg-info/`, `backend/storage/`, or lockfiles for
  routine coding tasks.
- Do not read `.server-credentials`, `.env*`, local SQLite files, logs, or server
  notes unless the user explicitly asks and it is safe to do so.
- If README status conflicts with code, trust current source and targeted docs;
  some planning text may be stale.
- When answering architecture questions, cite this map and one or two concrete
  files instead of dumping directory listings.
