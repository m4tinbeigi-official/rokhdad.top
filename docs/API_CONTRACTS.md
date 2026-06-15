# Rokhdad API Contracts

## User Identity

User identity is the base record for authentication, phone verification, roles, admin access, and future personalization.

### Canonical Fields

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Internal stable identifier |
| `name` | string | Yes | Display name |
| `email` | string | Yes | Unique email for email/password auth |
| `email_verified_at` | datetime | No | Set by email verification flow |
| `phone_e164` | string | No | Unique E.164 phone number, e.g. `+989121234567` |
| `phone_verified_at` | datetime | No | Set only after OTP verification |
| `status` | string | Yes | `active`, `disabled`, or future moderation states |
| `locale` | string | Yes | Defaults to `fa` |
| `timezone` | string | Yes | Defaults to `Asia/Tehran` |
| `last_login_at` | datetime | No | Updated by future auth flow |

### Public API Shape

```json
{
  "id": 1,
  "name": "Rokhdad User",
  "email": "user@example.com",
  "email_verified_at": null,
  "phone_e164": "+989121234567",
  "phone_verified_at": null,
  "status": "active",
  "locale": "fa",
  "timezone": "Asia/Tehran",
  "last_login_at": null
}
```

Passwords, remember tokens, OTP codes, and internal security metadata must never be serialized in public API responses.
