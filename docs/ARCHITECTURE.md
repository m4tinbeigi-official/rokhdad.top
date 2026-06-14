# Rokhdad Architecture

## Runtime Rule

All runtime services run only on the Ubuntu server through Docker Compose. The local machine is not required to run Docker, PHP, Node.js, Python, MariaDB, MongoDB, Redis, or Nginx.

## Monorepo Layout

```text
Rokhdad.ToP/
├── backend/              # Laravel API and Filament admin
├── frontend/             # Vue.js public web app and PWA
├── workers/              # Python worker services
├── deploy/               # Docker, Nginx, SSL, and server scripts
├── docs/                 # Planning, architecture, deployment, env, and task board
└── README.md
```

## Service Map

- `nginx`: Public edge router for `rokhdad.top`, API, admin, frontend assets, and SSL challenge handling.
- `backend`: Laravel API and Filament admin.
- `frontend`: Vue build served behind Nginx.
- `worker-ingestion`: Python worker for source API fetching and scraping.
- `worker-normalization`: Python worker for normalization, deduplication, field history, and enrichment jobs.
- `worker-images`: Python worker for image download and resize jobs.
- `mariadb`: Canonical relational database.
- `mongodb`: Raw payloads, source snapshots, enrichment responses, worker logs, and field history payloads.
- `redis`: Queues, cache, locks, rate limits.

## URL Routing

- `https://rokhdad.top/`: Vue frontend.
- `https://rokhdad.top/api/*`: Laravel API.
- `https://rokhdad.top/admin`: Filament admin.
- `https://rokhdad.top/storage/*`: Public media assets if served through backend/storage.

## Data Ownership

- MariaDB owns canonical records and transactional data.
- MongoDB owns source-native payloads, snapshots, and unstructured worker outputs.
- Redis owns ephemeral operational state only.

## Backend Boundaries

- Laravel exposes versioned APIs under `/api/v1`.
- Filament is used for internal admin workflows.
- Payment, notification, source, and search logic must use service interfaces before concrete providers.

## Worker Boundaries

- Workers do not directly mutate business-critical MariaDB data without idempotency keys.
- Raw ingestion writes source payloads to MongoDB first.
- Normalization jobs convert raw payloads to canonical event candidates.
- Deduplication jobs merge or link candidates to canonical events.

## Deployment Flow

1. Code is committed and pushed to GitHub.
2. Ubuntu server pulls the target branch.
3. Server reads `.env` files already present on the server.
4. Docker Compose builds or pulls images.
5. Migrations run explicitly.
6. Services restart.
7. Health checks run.
8. Rollback uses previous Git commit plus database-safe rollback notes.

