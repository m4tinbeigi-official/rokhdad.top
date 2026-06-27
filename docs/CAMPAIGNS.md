# Organizer Campaigns

Campaigns let organizers send email/SMS messages to the attendees of their events. The feature is driven by `CampaignController` and the `Campaign` model, and sends through the shared notification layer.

## Endpoints (auth, organizer-scoped)

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/v1/me/campaigns` | GET | List the organizer's campaigns (latest 20) plus available channels and audiences. |
| `/api/v1/me/campaigns` | POST | Create a draft campaign. |
| `/api/v1/me/campaigns/{id}/simulate` | POST | Run a send simulation against the resolved audience. |

All queries are scoped to the organizers linked to the authenticated user (`user->organizers()`), so an organizer can never target another organizer's events.

## Create Payload

```json
{
  "organizer_id": 1,
  "event_id": 12,
  "name": "Reminder blast",
  "channel": "email",            // email | sms
  "audience_type": "confirmed_only",
  "subject": "See you Friday",   // optional
  "message": "..."
}
```

- `organizer_id` must be one the user owns.
- `event_id` is optional; when present it must resolve to an event owned by that organizer.
- `audience_type`: `all_registrations`, `confirmed_only`, or `pending_only`.

New campaigns are created with `status=draft`.

## Audiences

The audience is derived from the target event's registrations filtered by `audience_type` (all, confirmed, or pending). Recipients are the registered users; the simulation endpoint resolves this set and reports who would receive the message without committing a live blast.

## `campaigns` Table

The current campaigns table (`2026_06_20_..._create_campaigns_table.php`) has: `organizer_id`, `event_id` (nullable), `name`, `channel` (default `email`), `audience_type` (default `all_registrations`), `status` (default `draft`), `subject`, `message`, `recipients_count`, `sent_count`, `last_sent_at`, `metadata` (JSON).

> An earlier multi-table design (`campaigns` + `campaign_messages` + `campaign_analytics`) also exists from `2026_06_15_..._create_campaigns_tables.php` and backs the `CampaignMessage` / `CampaignAnalytics` models for per-message scheduling and metric tracking (`sent`, `opened`, `clicked`, `converted`).

## Delivery

Sends go through `NotificationService` (`sendSms` / `sendEmail`), so every message is logged in `notification_logs`. See [`NOTIFICATIONS.md`](NOTIFICATIONS.md).
