# Webhooks

Organizers can subscribe to server-to-server webhooks for registration and payment events. The implementation lives in `backend/app/Webhooks/` with subscriptions and deliveries persisted in MariaDB.

## Event Catalog

`App\Webhooks\WebhookEventCatalog` is the source of truth for valid event names:

| Event | Fired when |
|---|---|
| `registration.created` | A registration is created. |
| `registration.confirmed` | A registration is confirmed. |
| `payment.paid` | A gateway payment is verified successfully. |
| `payment.failed` | A payment fails or is cancelled. |

## Subscriptions

Managed by the organizer via `WebhookSubscriptionController` (all routes auth-only):

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/v1/me/webhook-subscriptions` | GET | List the organizer's subscriptions. |
| `/api/v1/me/webhook-subscriptions` | POST | Create a subscription. |
| `/api/v1/me/webhook-subscriptions/{id}` | PUT | Update a subscription. |
| `/api/v1/me/webhook-subscriptions/{id}` | DELETE | Remove a subscription. |

`webhook_subscriptions` columns: `organizer_id`, `name`, `target_url`, `secret`, `subscribed_events` (JSON list), `is_active`, `last_delivered_at`, `last_failed_at`. A subscription only receives an event when `listensTo(event)` matches its `subscribed_events`.

## Delivery

`WebhookDispatcher::dispatchForOrganizer($organizerId, $eventName, $payload)`:

1. Loads active subscriptions for the organizer that listen to the event.
2. Builds the delivery body:
   ```json
   { "event": "payment.paid", "occurred_at": "<ISO-8601>", "data": { ... } }
   ```
3. Computes an HMAC-SHA256 signature over the JSON body using the subscription `secret`, formatted as `sha256=<hex>`.
4. Persists a `webhook_deliveries` row (`status=pending`, `attempt_count=1`) then POSTs to `target_url` with a 10s timeout.

### Request Headers

| Header | Value |
|---|---|
| `X-Rokhdad-Event` | Event name. |
| `X-Rokhdad-Delivery` | Delivery id. |
| `X-Rokhdad-Signature` | `sha256=<hmac>` over the raw body. |

### Verifying the Signature (receiver side)

Recompute `HMAC-SHA256(raw_body, secret)` and compare with the `X-Rokhdad-Signature` header. The body is serialized with unescaped Unicode and slashes — verify against the exact received bytes.

## Delivery Records

`webhook_deliveries` columns: `webhook_subscription_id`, `event_name`, `status` (`pending`/`delivered`/`failed`), `attempt_count`, `signature`, `payload` (JSON), `response_status`, `response_body` (truncated to 5000 chars), `delivered_at`, `failed_at`. A 2xx response marks the delivery `delivered` and updates `last_delivered_at`; anything else marks it `failed` and updates `last_failed_at`.

Payment events are dispatched from `PaymentController` (see [`PAYMENTS.md`](PAYMENTS.md)).
