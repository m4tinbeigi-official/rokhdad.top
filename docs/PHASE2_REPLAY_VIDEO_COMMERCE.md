# Phase 2 Scope — Replay & Video Commerce (P32-001)

> Status: **scope document** (phase 2, not scheduled). This defines *what* and
> *why*, plus enough of a shape to estimate. It is intentionally not an
> implementation plan. Blocked until the MVP (discovery + paid internal events)
> is complete and stable in production.

## Goal

Let organizers sell access to recorded sessions ("replays") and other digital
video products after — or independently of — a live event, reusing the existing
event, registration, ticketing, and payment machinery rather than building a
parallel commerce stack.

## Why it matters

Most of the value of a webinar or workshop persists after the live date. Today
the platform monetizes only the live moment. Replay/video commerce extends an
event's revenue tail, gives evergreen inventory to organizers with no live date,
and creates a reason for attendees to return to `rokhdad.top` rather than a
third-party video host.

## In scope

- A **digital product** concept attached to an event (or standalone): a recorded
  session, a bundle of recordings, or a downloadable resource.
- **Entitlement-gated playback**: a buyer who completes payment receives a
  time-bound or perpetual entitlement; playback URLs are signed and
  per-entitlement so they cannot be shared freely.
- **Reuse of payments**: replays are sold through the existing payment gateway
  abstraction (ZarinPal/Zibal) and settle through the existing
  `SettlementLedger` (gross credit + platform fee), identical to ticket sales.
- **Reuse of access control**: entitlements piggyback on the registration/ticket
  model where possible (a "ticket type" of kind `replay`).

## Out of scope (this phase)

- Hosting/transcoding video ourselves. Phase 2 assumes an external video host or
  storage + CDN; we store references and signed-URL parameters, not media bytes.
- Live streaming the event itself (that is the webinar-provider track,
  [`PHASE2_WEBINAR_PROVIDERS.md`](PHASE2_WEBINAR_PROVIDERS.md)).
- DRM beyond signed, expiring URLs and per-user watermarking metadata.
- Affiliate/reseller commerce.

## Shape (sketch, not final)

Likely new tables: `digital_products` (event_id nullable, type, price,
availability window, media_ref), `entitlements` (user_id, digital_product_id,
source payment_id, expires_at, status). Playback issues a short-lived signed URL
derived from `media_ref` + entitlement; access checks happen server-side.

Integration points:

- **Payments** — new `purchasable` kind routed through `PaymentController` and the
  gateway registry; success path grants an entitlement (mirrors how a paid
  registration is confirmed).
- **Settlement** — `SettlementService::recordSuccessfulPayment()` is reused
  unchanged; replay sales appear in the organizer ledger and statements.
- **Audit** — entitlement grants/revocations recorded via `AuditLog::record()`.
- **Frontend** — a "watch / buy replay" surface on the event detail page and a
  user "my library" view.

## Open questions

- Refund policy for digital goods (partial-view? expiry pro-rate?).
- Whether entitlements should be transferable.
- Watermarking depth vs. cost.
- Tax treatment of digital vs. event tickets.

## Acceptance for the scope itself

Product review confirms: the digital-product/entitlement model, the decision to
not host media in phase 2, the reuse of payments+settlement, and the open
questions above are agreed before any implementation task is opened.
