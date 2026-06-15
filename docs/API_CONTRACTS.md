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

## Email And Password Auth

Authentication uses Laravel Sanctum bearer tokens for API clients.

### Register

`POST /api/v1/auth/register`

Request:

```json
{
  "name": "Rokhdad User",
  "email": "user@example.com",
  "phone_e164": "+989121234567",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response `201`:

```json
{
  "user": {},
  "token": "1|plain-text-token",
  "token_type": "Bearer"
}
```

### Login

`POST /api/v1/auth/login`

Response `200` returns the same token shape as registration.

### Current User

`GET /api/v1/auth/me`

Requires `Authorization: Bearer <token>`.

### Logout

`POST /api/v1/auth/logout`

Deletes all active API tokens for the current user.

## Roles And Permissions

RBAC is role-based:

- `roles.name` is the stable role key, for example `admin`.
- `permissions.name` is the stable permission key, for example `users.manage`.
- Users receive roles through `role_user`.
- Roles receive permissions through `permission_role`.
- Permission checks should use `User::hasPermissionTo($permission)` so later admin policies can share one path.
