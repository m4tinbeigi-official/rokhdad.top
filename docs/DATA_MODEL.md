# Data Model

This is a reference for the canonical MariaDB schema as defined by the migrations in `backend/database/migrations/`. Field-level data contracts for public API responses live in [`API_CONTRACTS.md`](API_CONTRACTS.md); this document maps the tables and their relationships. All money columns are integer **Rials (IRR)**.

## Domain Groups

### Identity & Access
- **users** — base identity; extended by `extend_users_for_identity` with `phone_e164`, `phone_verified_at`, `status`, `locale` (`fa`), `timezone` (`Asia/Tehran`), `last_login_at`.
- **roles**, **permissions**, **permission_role**, **role_user** — RBAC. Roles and permissions have `name` (unique) + `label`; pivots use composite primary keys.
- **personal_access_tokens** — Sanctum tokens.
- **otp_codes** — `phone`, masked `code`, `code_hash`, `purpose` (`login`/`verify`), `used`, `attempts`, `expires_at`.

### Catalog & Taxonomy
- **categories** — self-referencing tree via `parent_id`; `name`, `slug`, `is_active`, `sort_order`.
- **cities** — `name`, `slug`, `province`, `country_code` (default `IR`), `latitude`/`longitude`.
- **organizers** — `name`, `slug`, `city_id`, contact + `social_links`; later extended with source metadata.
- **people** — speakers/figures; `full_name`, `slug`, `title`, `bio`, contacts.
- **organizer_person** — pivot linking organizers and people (`role_title`).

### Events
- **events** — core entity: `title`, `slug` (unique), `summary`, `description`, `starts_at`/`ends_at`, `timezone`, `event_type` (`in_person`/online), `status` (`draft`…), `venue_*`, `latitude`/`longitude`, `online_url`, `canonical_url`, `metadata` (JSON), `is_featured`, plus FKs `category_id`, `city_id`, `organizer_id`. Heavily indexed on status/date/city/category.
  - `add_visibility_and_recurrence` adds `visibility` (`public`…), `series_slug`, `recurrence_rule`, `recurrence_ends_at`, and registration fields `is_internal`, `registration_open`, `capacity`, `registration_starts_at`/`ends_at`, `requires_approval`, `registration_instructions`.
- **event_people** — pivot of events ↔ people with `role_title`, `sort_order`.
- **event_ticket_types** — `name`, `price`, `currency`, `capacity`, `sold_count`, `max_per_user`, `is_active`, sale window.
- **event_promo_codes** — `code`, `discount_type` (`fixed`/percent), `discount_value`, `max_uses`, `used_count`, quantity bounds, active window; unique per `(event_id, code)`.
- **event_field_overrides** / **event_field_locks** — manual edits and locks protecting fields from being overwritten by ingestion.

### Aggregation Sources
- **event_sources** — registered external sources: `source_key` (unique), `name`, URLs, `auth_type`, `status`, `is_enabled`, `rate_limit_per_minute`, `config` (JSON), plus health tracking (`health_status`, `consecutive_failures`, `last_success_at`, `last_failure_at`, `last_error_message`).
- **event_source_api_keys** — credentials per source.
- **event_source_attributions** — links a canonical event back to its source record(s).

### Registrations, Tickets & Payments
- **registrations** — `event_id` + `user_id` (unique together), `status` (`pending`/`confirmed`/`cancelled`/`attended`), `payment_status` (`unpaid`/`paid`/`refunded`/`free`), `quantity`, `total_amount`, `currency`, `form_data` (JSON), `confirmed_at`, `cancelled_at`.
- **tickets** — one per attendee seat: `ticket_number` (unique), `qr_code_token` (unique), `status` (`issued`/`used`/`cancelled`/`expired`), `price`, `seat_info`, `used_at`, `expires_at`.
- **payments** — gateway transactions; see [`PAYMENTS.md`](PAYMENTS.md).
- **settlement ledger** — organizer balances; see [`SETTLEMENTS.md`](SETTLEMENTS.md).

### Engagement & Personalization
- **comments**, **ratings** — public event feedback (auth to create).
- **saved_events** — user ↔ event bookmarks (unique per pair).
- **user_preferences** — `favorite_category_ids`, `favorite_city_ids`, `preferred_event_type`, `notification_channel`, `notify_new_events`, `notify_reminders`.

### Messaging & Integrations
- **notification_logs** — every SMS/email send; see [`NOTIFICATIONS.md`](NOTIFICATIONS.md).
- **campaigns** (+ `campaign_messages`, `campaign_analytics`) — organizer outreach; see [`CAMPAIGNS.md`](CAMPAIGNS.md).
- **webhook_subscriptions**, **webhook_deliveries** — organizer webhooks; see [`WEBHOOKS.md`](WEBHOOKS.md).

### Infrastructure
- **cache**, **jobs** — Laravel cache/queue tables.

## Notes
- MariaDB owns canonical relational data; raw payloads, snapshots, and field-history live in MongoDB (see [`ARCHITECTURE.md`](ARCHITECTURE.md) and [`INGESTION_SOURCES.md`](INGESTION_SOURCES.md)).
- Migration and seed policy is documented in [`MIGRATIONS_AND_SEEDS.md`](MIGRATIONS_AND_SEEDS.md).
