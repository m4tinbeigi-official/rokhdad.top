# Rokhdad Docker Infrastructure

## Compose File

Primary compose file:

```text
deploy/docker-compose.yml
```

## Services

- `nginx`: public HTTP/HTTPS edge for `rokhdad.top`.
- `backend`: Laravel API and Filament admin HTTP service.
- `frontend`: Vue public frontend service.
- `worker-ingestion`: Python worker for external source ingestion.
- `worker-normalization`: Python worker for normalization and deduplication.
- `worker-images`: Python worker for image download and processing.
- `mariadb`: canonical relational database.
- `mongodb`: raw payload and snapshot database.
- `redis`: queues, cache, locks, and rate limits.
- `certbot`: optional SSL certificate issuance and renewal profile.

## Networks

- `rokhdad-public`: public-facing network for Nginx and certificate flow.
- `rokhdad-internal`: internal-only network for application and data services.

## Volumes

- `mariadb_data`: MariaDB data.
- `mongodb_data`: MongoDB data.
- `redis_data`: Redis append-only data.
- `app_public_storage`: public uploads and processed images.
- `app_logs`: backend logs.
- `certbot_webroot`: ACME challenge files.
- `certbot_certs`: Let's Encrypt certificates.

## Current Limitation

The compose map references future images:

- `rokhdad/backend:latest`
- `rokhdad/frontend:latest`

The worker image is built from `deploy/worker.Dockerfile`; backend and frontend images are produced by later implementation tasks. P2 validates service boundaries, routing, networks, volumes, and server commands.
