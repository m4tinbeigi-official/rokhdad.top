# Backend Agent Notes

Scope: Laravel 12 API, Sanctum auth, Filament admin, payments, settlements,
campaigns, webhooks, notifications, and migrations.

- Start with `routes/api.php` for API behavior.
- Use controllers in `app/Http/Controllers/` for request/response flow.
- Use models in `app/Models/` for relationships, fillable fields, casts, and
  domain state.
- Use migrations under `database/migrations/` for schema truth.
- Do not read `vendor/`, `storage/`, `bootstrap/cache/`, `.phpunit.result.cache`,
  or `database/database.sqlite` for routine tasks.
- Preserve Laravel conventions and existing validation/JSON response style.
- Prefer targeted tests with `php artisan test --filter Name`.

Relevant docs: `docs/API_ENDPOINTS.md`, `docs/API_CONTRACTS.md`,
`docs/DATA_MODEL.md`, and the domain-specific doc from `docs/INDEX.md`.
