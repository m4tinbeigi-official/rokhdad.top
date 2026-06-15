#!/usr/bin/env sh
set -eu

DEPLOY_DIR="${ROKHDAD_DEPLOY_DIR:-/opt/rokhdad}"
BACKEND_ENV="${DEPLOY_DIR}/backend/.env"
ACTION="${1:-status}"

cd "$DEPLOY_DIR"

if [ ! -f "$BACKEND_ENV" ]; then
  echo "Missing backend env file: $BACKEND_ENV" >&2
  exit 1
fi

if ! docker image inspect rokhdad/backend:latest >/dev/null 2>&1; then
  docker build -f deploy/backend.Dockerfile -t rokhdad/backend:latest backend
fi

artisan() {
  docker run --rm \
    --env-file "$BACKEND_ENV" \
    --network rokhdad-internal \
    rokhdad/backend:latest \
    php artisan "$@"
}

case "$ACTION" in
  status)
    artisan migrate:status
    ;;
  migrate)
    artisan migrate --force
    artisan migrate:status
    ;;
  rollback-plan)
    steps="${ROLLBACK_STEPS:-1}"
    case "$steps" in
      ''|*[!0-9]*)
        echo "ROLLBACK_STEPS must be a positive integer." >&2
        exit 1
        ;;
    esac
    if [ "$steps" -lt 1 ]; then
      echo "ROLLBACK_STEPS must be at least 1." >&2
      exit 1
    fi
    echo "Rollback plan: php artisan migrate:rollback --force --step=$steps"
    artisan migrate:status
    ;;
  rollback)
    steps="${ROLLBACK_STEPS:-}"
    if [ -z "$steps" ]; then
      echo "Set ROLLBACK_STEPS before running a production rollback." >&2
      exit 1
    fi
    case "$steps" in
      *[!0-9]*)
        echo "ROLLBACK_STEPS must be a positive integer." >&2
        exit 1
        ;;
    esac
    if [ "$steps" -lt 1 ]; then
      echo "ROLLBACK_STEPS must be at least 1." >&2
      exit 1
    fi
    artisan migrate:rollback --force --step="$steps"
    artisan migrate:status
    ;;
  seed)
    if [ -z "${SEED_CLASS:-}" ]; then
      echo "Set SEED_CLASS to an explicit seeder class before running seeds." >&2
      exit 1
    fi
    artisan db:seed --force --class="$SEED_CLASS"
    ;;
  *)
    echo "Usage: $0 status|migrate|rollback-plan|rollback|seed" >&2
    exit 1
    ;;
esac
