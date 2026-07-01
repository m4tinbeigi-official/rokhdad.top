# Phase 2 Scope — Webinar Provider Abstraction (P32-002)

> Status: **scope document** (phase 2, not scheduled). Defines the contract and
> boundaries for integrating live-webinar providers. Blocked until the MVP is
> complete. This mirrors the design philosophy already used for payment gateways
> and notification providers: a small, stable interface with swappable adapters.

## Goal

Allow an internal Rokhdad event to be backed by a live-webinar session on a
third-party provider (e.g. a Zoom-/BigBlueButton-style service) through a single
provider-agnostic contract, so that creating the session, admitting registered
attendees, and retrieving the recording all happen without provider-specific
code leaking into the event/registration domain.

## Why it matters

Organizers already run live online events elsewhere and re-enter attendee data by
hand. Native webinar support turns Rokhdad into the system of record:
registration → automatic provider session + join link → attendance → recording
(which feeds the replay/video-commerce track,
[`PHASE2_REPLAY_VIDEO_COMMERCE.md`](PHASE2_REPLAY_VIDEO_COMMERCE.md)). The
abstraction prevents lock-in to any one provider.

## In scope

- A **`WebinarProvider` contract** with a minimal surface:
  - `createSession(event, options)` → provider session id + host/start URL.
  - `issueJoinLink(session, registration)` → per-attendee join URL.
  - `getAttendance(session)` → attendee join/leave records.
  - `getRecording(session)` → recording reference once available (handed to the
    replay track as a `media_ref`).
- A **provider registry** resolving an adapter by key (matching
  `PaymentGatewayRegistry`), configured per organizer or per event.
- **Lifecycle wiring**: confirming a registration for a webinar-backed event
  issues a join link; the event's start/end drives session creation and recording
  retrieval (likely a worker job).
- **Credential storage** reusing the encrypted-secret pattern already used for
  source API keys.

## Out of scope (this phase)

- Embedding the live video player in-app (join links open the provider).
- Building our own conferencing/SFU.
- Replay sales (separate track) — this track only *produces* the recording ref.
- Real-time in-event chat/Q&A moderation.

## Shape (sketch, not final)

- `App\Webinars\WebinarProvider` interface + `WebinarProviderRegistry` (parallels
  `App\Payments\PaymentGatewayRegistry`).
- One adapter per provider under `App\Webinars\Providers\`.
- New columns/table linking an event to a provider session
  (`webinar_sessions`: event_id, provider, provider_session_id, host_url,
  recording_ref, status).
- A Python worker job (per [`WORKERS.md`](WORKERS.md)) to poll for attendance and
  recording availability after the event ends, using the structured logging
  standard in [`LOGGING.md`](LOGGING.md).

## Integration points

- **Registrations** — confirmation triggers `issueJoinLink`; the link is shown on
  the attendee's ticket/registration view.
- **Notifications** — join links delivered via the existing sms.ir/Pakett
  abstraction ([`NOTIFICATIONS.md`](NOTIFICATIONS.md)).
- **Audit** — session creation and credential changes via `AuditLog::record()`.
- **Webhooks** — emit `webinar.session.created` / `webinar.recording.ready` on the
  existing webhook framework ([`WEBHOOKS.md`](WEBHOOKS.md)).

## Open questions

- Which provider ships first (drives the concrete adapter and auth model: OAuth
  vs. API key vs. JWT).
- Attendance granularity needed for analytics/certificates.
- Whether join links must be single-use.
- Time-zone handling for session scheduling.

## Acceptance for the scope itself

Architecture review confirms the `WebinarProvider` contract surface, the
registry/adapter pattern, the worker-driven recording retrieval, and the handoff
of `recording_ref` to the replay track — before any implementation task is opened.
