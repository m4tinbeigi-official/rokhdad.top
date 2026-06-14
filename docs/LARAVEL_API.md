# Laravel API Scaffold

Task: `P3-001`

## Goal

Scaffold the Laravel API application in `backend/` on the Ubuntu server through Docker, without requiring local PHP or Composer.

## Server Command

Run after cloning or pulling the repository on the server:

```bash
cd /opt/rokhdad
sh deploy/scripts/scaffold-laravel-api.sh
```

## Expected Result

- `backend/artisan` exists.
- `backend/composer.json` exists.
- `php artisan about` runs inside a Docker container.
- The backend Dockerfile can later build the `rokhdad/backend:latest` image.

## Current Status

The server-side scaffold script is committed, but the actual Laravel scaffold still requires SSH access to `45.94.215.10`.

