# Notifications (SMS & Email)

Outbound messaging is centralized in `App\Notifications\NotificationService`, which delegates to provider classes and records every send in the `notification_logs` table.

## Providers

| Channel | Provider | Class | Config keys |
|---|---|---|---|
| SMS | sms.ir | `SmsIrProvider` | `SMSIR_API_KEY`, `SMSIR_LINE_NUMBER` (default `30007732`), `SMSIR_TEMPLATE_ID_OTP` |
| Email | Pakett | `PakettProvider` | `PAKETT_API_KEY`, `PAKETT_FROM_EMAIL`, `PAKETT_FROM_NAME` |

`SmsIrProvider` calls the sms.ir v1 API (`https://api.sms.ir/v1`) with an `x-api-key` header:
- `sendVerify(mobile, templateId, parameters)` → `POST /send/verify` (template-based OTP / FastSend).
- `sendBulk(mobiles, message, lineNumber?)` → bulk plain-text SMS over the configured line number.

## Service API

`NotificationService` exposes three methods, each creating a `NotificationLog` first (`status=queued`) and updating it to `sent`/`failed` based on the provider response:

| Method | Purpose |
|---|---|
| `sendOtp(mobile, templateId, parameters, userId?)` | Template OTP via sms.ir; success when response `status == 1`. |
| `sendSms(mobiles, message, type, userId?)` | Bulk SMS; success when response `status == 1`. |
| `sendEmail(toEmail, toName, subject, templateId, variables, type, userId?)` | Transactional email via Pakett; success when the response has an `id` or `status == sent`. |

Provider exceptions are caught and logged as `failed` with the error message stored in `provider_response` — sends never throw out of the service.

## `notification_logs` Table

| Column | Notes |
|---|---|
| `user_id` | Nullable recipient user. |
| `channel` | `sms` or `email`. |
| `provider` | `sms.ir` or `pakett`. |
| `recipient` | Phone number(s) or email. |
| `type` | `otp`, `registration_confirm`, `reminder`, `general`, … |
| `message` | Body or subject. |
| `status` | `queued`, `sent`, `failed`. |
| `provider_message_id` | Provider's message id when available. |
| `provider_response` | Raw provider JSON (audit trail). |
| `sent_at` | Set on success. |

## Usage Sites

- **OTP** — `AuthController` calls `sendOtp()` during phone verification/login (see [`AUTHENTICATION.md`](AUTHENTICATION.md)).
- **Campaigns** — organizer campaigns send through the same service for email/SMS blasts (see [`CAMPAIGNS.md`](CAMPAIGNS.md)).
