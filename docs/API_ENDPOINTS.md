# API Endpoint Reference

Full route map of the Laravel API. This complements [`API_CONTRACTS.md`](API_CONTRACTS.md) (which documents entity field shapes) by listing every endpoint, its method, and auth requirement. Routes are defined in `backend/routes/api.php` and `backend/routes/settlement-routes.php`.

Base path: `/api`. Versioned routes live under `/api/v1`. Authenticated routes require a Sanctum Bearer token (see [`AUTHENTICATION.md`](AUTHENTICATION.md)).

## Health

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/api/health` | — | Liveness probe. |
| GET | `/api/ready` | — | Readiness probe. |
| GET | `/api/v1/` | — | API index (name/version/status). |

## Auth

| Method | Path | Auth |
|---|---|---|
| POST | `/api/v1/auth/register` | — |
| POST | `/api/v1/auth/login` | — |
| POST | `/api/v1/auth/otp/request` | — |
| POST | `/api/v1/auth/otp/verify` | — |
| GET | `/api/v1/auth/me` | Bearer |
| POST | `/api/v1/auth/logout` | Bearer |

## Public Discovery

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET | `/api/v1/events` | — | List/filter events. |
| GET | `/api/v1/events/{slug}` | — | Event detail. |
| GET | `/api/v1/events/{slug}/comments` | — | List comments. |
| GET | `/api/v1/events/{slug}/ratings` | — | List ratings. |
| GET | `/api/v1/categories` | — | Category lookup. |
| GET | `/api/v1/cities` | — | City lookup. |
| GET | `/api/v1/organizers` | — | List organizers. |
| GET | `/api/v1/organizers/{slug}` | — | Organizer detail. |
| GET | `/api/v1/people` | — | List people. |
| GET | `/api/v1/people/{slug}` | — | Person detail. |
| GET | `/api/v1/payments/callback/{gateway}` | — | Gateway return URL (see [`PAYMENTS.md`](PAYMENTS.md)). |

## Authenticated — Attendee

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/tickets/validate/{token}` | Validate a ticket QR token. |
| POST | `/api/v1/events/{slug}/registrations` | Register for an event. |
| POST | `/api/v1/events/{slug}/comments` | Post a comment. |
| DELETE | `/api/v1/comments/{id}` | Delete own comment. |
| POST | `/api/v1/events/{slug}/ratings` | Rate an event. |
| GET | `/api/v1/events/{slug}/my-rating` | Get own rating. |
| DELETE | `/api/v1/events/{slug}/ratings` | Remove own rating. |
| POST | `/api/v1/events/{slug}/save` | Bookmark an event. |
| DELETE | `/api/v1/events/{slug}/save` | Remove bookmark. |
| POST | `/api/v1/registrations/{id}/pay` | Initiate payment. |
| GET | `/api/v1/payments/{id}` | Payment status. |

## Authenticated — Profile & Personalization

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/me/preferences` | Get preferences. |
| PUT | `/api/v1/me/preferences` | Update preferences. |
| GET | `/api/v1/me/saved-events` | List saved events. |
| GET | `/api/v1/me/personalized-events` | Personalized ranking. |

## Authenticated — Organizer

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/me/organizer-dashboard` | Dashboard summary. |
| GET | `/api/v1/me/events/{id}/attendees/export` | Export attendees. |
| POST | `/api/v1/me/events/{id}/attendees/import` | Import attendees. |
| GET | `/api/v1/me/campaigns` | List campaigns. |
| POST | `/api/v1/me/campaigns` | Create campaign. |
| POST | `/api/v1/me/campaigns/{id}/simulate` | Simulate a send. |
| GET | `/api/v1/me/webhook-subscriptions` | List webhooks. |
| POST | `/api/v1/me/webhook-subscriptions` | Create webhook. |
| PUT | `/api/v1/me/webhook-subscriptions/{id}` | Update webhook. |
| DELETE | `/api/v1/me/webhook-subscriptions/{id}` | Delete webhook. |

## Authenticated — Settlement (⚠️ defined but NOT registered)

These routes live in `routes/settlement-routes.php`, which `bootstrap/app.php` **never loads** — so they are currently **unreachable**. Listed here for completeness; see the status note in [`SETTLEMENTS.md`](SETTLEMENTS.md).

| Method | Path (if wired) | Purpose |
|---|---|---|
| GET | `/api/organizer/settlement/dashboard` | Balance + statement summary. |
| POST | `/api/organizer/settlement/request-payout` | Request a payout. |
| GET | `/api/organizer/settlement/statements` | Settlement statements. |
| GET | `/api/organizer/settlement/ledger` | Ledger entries. |

## Web routes (non-API)

Defined in `routes/web.php`:

| Method | Path | Purpose |
|---|---|---|
| GET | `/` | Laravel `welcome` view (the SPA/landing is served by nginx + the Vue build in production). |
| GET | `/sitemap.xml` | SEO sitemap (`SitemapController@index`). |
| GET | `/robots.txt` | Robots file (`SitemapController@robots`). |

Laravel's built-in health route is mounted at `/up` (configured in `bootstrap/app.php`), in addition to the custom `/api/health` and `/api/ready` above.

See [`CAMPAIGNS.md`](CAMPAIGNS.md), [`WEBHOOKS.md`](WEBHOOKS.md), and [`SETTLEMENTS.md`](SETTLEMENTS.md) for behavior of the organizer routes.
