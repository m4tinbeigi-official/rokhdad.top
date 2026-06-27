#!/usr/bin/env bash
#
# Verify the admin-panel + settlement + Hermes changes.
# Run this on the server (or any machine with PHP 8.2+ and the project deps).
#
#   cd backend && bash scripts/verify_changes.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> PHP version"
php -v | head -1

echo "==> Refresh autoloader (new models/resources)"
composer dump-autoload -o

echo "==> Run migrations (settlement_ledgers, payouts; hermes_errors already present)"
php artisan migrate --force

echo "==> Clear & rebuild caches so new Filament resources are discovered"
php artisan config:clear
php artisan route:clear
php artisan filament:optimize-clear || true

echo "==> Run the new test suites"
php artisan test --filter='SettlementLedgerTest|HermesServiceTest'

echo
echo "==> (Optional) full backend test suite:"
echo "    php artisan test"
echo
echo "Done. If all green, the admin panel now manages every model and the"
echo "settlement + Hermes subsystems are live. Hermes stays OFF unless"
echo "HERMES_ENABLED=true (auto-on only when APP_ENV=local)."
