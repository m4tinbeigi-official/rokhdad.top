# Admin Panel & Console Commands

## Filament Admin

The backend ships a [Filament](https://filamentphp.com) admin panel configured in `app/Providers/Filament/AdminPanelProvider.php`.

- **Panel id:** `admin`
- **Path:** `/admin` (i.e. `https://rokhdad.top/admin`)
- **Auth:** Filament login enabled; access is granted to admin users (see the `app:setup-admin-user` command below).
- **Auto-discovery:** resources, pages, and widgets are auto-discovered from `app/Filament/Resources`, `app/Filament/Pages`, and `app/Filament/Widgets`.

### Resources

Resources are grouped in the navigation by domain. Each follows Filament's
structure (`Pages/`, `Schemas/` for forms, `Tables/` for list views).

**Core (pre-existing)**

| Resource | Manages |
|---|---|
| `Users` | Application users / identities. |
| `EventSources` | Registered external aggregation sources and their health/config. |
| `Comments` | Moderation of event comments. |
| `Events` | Canonical events. |
| `Organizers` | Event organizers. |
| `People` | People (speakers/contacts) with event & organizer relations. |

**هوش مصنوعی (AI)**

| Resource | Manages |
|---|---|
| `EventMergeProposals` | Dedup merge proposals awaiting review. |
| `AiServices` | AI service configuration (custom settings & API keys). |
| `AiSearchQueries` | Log search queries and metadata/filters extracted by AI. |

**فروش و مالی (Sales & Finance)**

| Resource | Manages |
|---|---|
| `Payments` | Payment transactions (ZarinPal/Zibal). Amounts in integer Rials. |
| `Registrations` | Event registrations. |
| `Tickets` | Issued tickets. |
| `EventTicketTypes` | Ticket types & pricing per event. |
| `EventPromoCodes` | Discount/promo codes. |
| `SettlementLedgers` | Organizer settlement ledger (credits, platform fees, payouts). Now populated automatically on each paid payment. |
| `Payouts` | Organizer payout/withdrawal requests. Approve (`تکمیل`) records the debit in the ledger; reject closes the request. |

**محتوا (Content)**

| Resource | Manages |
|---|---|
| `Categories` | Event categories (hierarchical via `parent`). |
| `Cities` | Cities. |
| `Ratings` | User ratings & reviews. |
| `SavedEvents` | Events saved/bookmarked by users. |

**کمپین و اطلاع‌رسانی (Campaigns & Notifications)**

| Resource | Manages |
|---|---|
| `Campaigns` | Marketing campaigns. |
| `CampaignMessages` | Campaign messages. |
| `CampaignAnalytics` | Campaign metrics. |
| `NotificationLogs` | Sent SMS/email log (sms.ir / Pakett). |
| `OtpCodes` | One-time codes (debug/audit). |

**دسترسی و کاربران (Access & Users)**

| Resource | Manages |
|---|---|
| `Roles` | Roles and their permissions. |
| `Permissions` | Permissions. |
| `UserPreferences` | Per-user preferences. |

**کیفیت داده و منابع (Data Quality & Sources)**

| Resource | Manages |
|---|---|
| `EventFieldOverrides` | Manual field overrides on events. |
| `EventFieldLocks` | Field locks protecting values from importer overwrite. |
| `EventSourceApiKeys` | API keys per source (`encrypted_secret` is hidden). |
| `EventSourceAttributions` | Source attribution records. |

**وب‌هوک (Webhooks)**

| Resource | Manages |
|---|---|
| `WebhookSubscriptions` | Webhook subscriptions. |
| `WebhookDeliveries` | Webhook delivery attempts. |

**سیستم (System)**

| Resource | Manages |
|---|---|
| `Hermes` (custom page) | Configure the Hermes endpoint/API key and run search/trace/snippet queries. Admin-only, and only when Hermes is enabled. |
| `HermesErrors` | View/clear Hermes request failures logged to `hermes_errors`. Admin-only, read-only. |
| `RequestLogs` | Per outbound source HTTP request log (status, proxy, timing). Read-only. |
| `AuditLogs` | Durable "who did what" trail — authentication and money-movement events. Read-only (no create/edit/delete). |

> **Hermes is dev/admin tooling, gated by `HERMES_ENABLED`.** Hermes is a client
> to an external code knowledge-graph server — unrelated to the event domain. It
> is OFF by default and auto-enabled only when `APP_ENV=local`; set
> `HERMES_ENABLED=true` to use it elsewhere. When disabled, the admin pages are
> hidden and the proxy routes (`/api/v1/hermes/*`, behind `auth:sanctum`) return 404.
> Request failures are logged to `hermes_errors` via `App\Services\HermesService`.
> Coverage: `tests/Feature/HermesServiceTest.php`.

> **Settlement subsystem — now active (2026-06-27).** Previously dormant; wired up:
> - `bootstrap/app.php` now loads `routes/settlement-routes.php` via the routing
>   `then:` callback, exposing the organizer endpoints under `/api/organizer/settlement/*`.
> - New migration `create_settlement_tables` adds the `settlement_ledgers` and
>   `payouts` tables (neither existed before).
> - New `App\Services\SettlementService` (platform fee = 10%) backs the dashboard
>   controller, which previously referenced a non-existent class.
> - New `App\Models\Payout` + `Organizer->payouts()`/`settlementLedgers()` relations
>   and a `User->organizer` accessor (controller used the singular form).
> - `PaymentController::callback()` now records a gross credit + platform-fee debit
>   in the ledger (inside a DB transaction) when a payment verifies as paid.
> - Approving a `Payout` in Filament records the payout debit via `Payout::markCompleted()`
>   (idempotent). Coverage: `tests/Feature/SettlementLedgerTest.php`.

> **Audit log (P6-003).** `App\Models\AuditLog` records a durable "who did what"
> trail in the `audit_logs` table, independent of rolling stdout logs (see
> [`LOGGING.md`](LOGGING.md)). Captured automatically today:
> - `auth.login` / `auth.logout` — via Laravel auth events wired in `AppServiceProvider::boot()`.
> - `payout.completed` / `payout.rejected` — recorded inside `Payout::markCompleted()`/`reject()`.
>
> Use `AuditLog::record($action, $description, $subject, $properties)` to capture
> additional sensitive actions. The `AuditLogs` Filament resource is **read-only**
> (`canCreate`/`canEdit`/`canDelete` all false). Coverage: `tests/Feature/AuditLogTest.php`.

> **Note on JSON fields.** Editable JSON-ish fields are exposed as `KeyValue`
> inputs (e.g. `target_audience`, `settings`, `metadata`, `subscribed_events`).
> Deeply nested log payloads (`gateway_response`, `provider_response`, webhook
> `payload`/`response_body`, field-override `original_value`/`override_value`) are
> intentionally omitted from forms to avoid corrupting nested data.

### Widgets

- `StatsOverview` — dashboard stats overview.
- `EvandSyncStatsWidget` — surfaces Evand synchronization statistics on the dashboard.

## Localization & RTL Configuration (راست‌چین و فارسی‌سازی)

The admin panel is configured to be fully Right-to-Left (RTL) and translated to Persian:

- **Locale Setting:** Set to `'locale' => 'fa'` in `config/app.php`.
- **RTL Direction:** Managed via the translation file key `'direction' => 'rtl'` in `lang/vendor/filament-panels/fa/layout.php`. Filament detects this setting and automatically switches the panel layout direction to RTL.
- **Custom Font & Styles:** A custom CSS stylesheet `resources/css/custom-filament.css` is registered in `AdminPanelProvider.php` to load and apply the **Vazirmatn** Persian font and adjust typography across all Filament elements.
- **Asset Publishing:** Run `php artisan filament:assets` after making changes to compile and copy the custom styles to `public/css/app/custom-filament-styles.css`.

## Console Commands

Custom Artisan commands live in `app/Console/Commands/`:

| Command | Purpose |
|---|---|
| `app:setup-admin-user` | Creates the default admin user and ensures the `admin` role/permissions exist. Run once after a fresh deploy. |
| `evand:historical-sync` | Walks Evand organizers to backfill all historical events, bypassing per-listing rate limits. Maps raw Evand payloads into canonical organizers/categories/events. |

Run inside the backend container, e.g.:

```bash
php artisan app:setup-admin-user
php artisan evand:historical-sync
```

Scheduled jobs and one-off ingestion helpers are also defined in `routes/console.php`. See [`INGESTION_SOURCES.md`](INGESTION_SOURCES.md) for the worker-side pipeline and [`AGGREGATION_SETUP.md`](../AGGREGATION_SETUP.md) for source setup.
