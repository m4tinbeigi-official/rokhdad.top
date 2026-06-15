# Rokhdad Migration And Seed Policy

Production database changes must be explicit, reviewable, and reversible before deploy.

## Rules

- Every schema change must be a Laravel migration committed with the related feature.
- Migrations must be idempotent at the application level: use unique indexes, foreign keys, and nullable/backfill steps intentionally.
- Destructive changes require a task note that names the affected tables, data risk, and rollback plan.
- Production migrations run only through `deploy/scripts/laravel-db.sh migrate`.
- Rollbacks require an explicit step count through `ROLLBACK_STEPS=N deploy/scripts/laravel-db.sh rollback`.
- Seeds are not automatic in production.
- Production seeds must be dedicated seeder classes and run with `SEED_CLASS=ClassName deploy/scripts/laravel-db.sh seed`.
- `DatabaseSeeder` stays empty in production; do not add demo users or sample data there.

## Server Commands

Check migration state:

```bash
cd /opt/rokhdad
deploy/scripts/laravel-db.sh status
```

Run pending migrations:

```bash
cd /opt/rokhdad
deploy/scripts/laravel-db.sh migrate
```

Review rollback target before changing data:

```bash
cd /opt/rokhdad
ROLLBACK_STEPS=1 deploy/scripts/laravel-db.sh rollback-plan
```

Run an approved rollback:

```bash
cd /opt/rokhdad
ROLLBACK_STEPS=1 deploy/scripts/laravel-db.sh rollback
```

Run an approved production seed:

```bash
cd /opt/rokhdad
SEED_CLASS=ReferenceDataSeeder deploy/scripts/laravel-db.sh seed
```

## Rollback Checklist

- Confirm the Git commit being rolled back from and the target commit.
- Confirm `deploy/scripts/laravel-db.sh status` output.
- Confirm whether the migration touched user data or only schema.
- Confirm the rollback migration is safe for current production data.
- Take or verify a recent MariaDB backup for destructive changes.
- Run `ROLLBACK_STEPS=N deploy/scripts/laravel-db.sh rollback-plan`.
- Run the rollback only after the task owner approves the specific step count.
- Re-run health checks and affected feature tests after rollback.

## Seed Checklist

- Use a named seeder class for reference data only.
- Make the seeder idempotent with `updateOrCreate`, `upsert`, or stable unique keys.
- Never seed test users, fake events, or sample orders into production.
- Document the command and expected row count in the task that introduces the seeder.
