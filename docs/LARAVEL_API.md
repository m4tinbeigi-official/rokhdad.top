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
- Default scaffold version is Laravel `^12.0`.

## Current Status

Laravel 12.62.0 was scaffolded on the Ubuntu server and synced back into `backend/` without `vendor`, `.env`, or the SQLite runtime database. `php artisan about` passed on the server with PHP 8.4.22.
