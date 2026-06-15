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

## Categories

Categories provide the public event taxonomy.

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Stable internal ID |
| `parent_id` | integer | No | Optional parent category |
| `name` | string | Yes | Display label |
| `slug` | string | Yes | Unique public URL key |
| `description` | string | No | Optional SEO/admin description |
| `is_active` | boolean | Yes | Hidden from public UI when false |
| `sort_order` | integer | Yes | Manual ordering |

## Cities

Cities provide location filtering for public events.

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Stable internal ID |
| `name` | string | Yes | Display label |
| `slug` | string | Yes | Unique public URL key |
| `province` | string | No | Province/state |
| `country_code` | string | Yes | ISO-3166 alpha-2, defaults to `IR` |
| `latitude` | decimal | No | Optional map coordinate |
| `longitude` | decimal | No | Optional map coordinate |
| `is_active` | boolean | Yes | Hidden from public UI when false |
| `sort_order` | integer | Yes | Manual ordering |

## Organizers

Organizers own or publish events.

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Stable internal ID |
| `city_id` | integer | No | Optional home city |
| `name` | string | Yes | Display label |
| `slug` | string | Yes | Unique public URL key |
| `description` | string | No | Public/admin description |
| `website_url` | string | No | Official URL |
| `email` | string | No | Contact email |
| `phone_e164` | string | No | Contact phone |
| `social_links` | object | No | Provider keyed social URLs |
| `is_active` | boolean | Yes | Hidden from public UI when false |

## People

People represent speakers, hosts, instructors, and organizer team members.

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Stable internal ID |
| `full_name` | string | Yes | Display name |
| `slug` | string | Yes | Unique public URL key |
| `title` | string | No | Short professional title |
| `bio` | string | No | Public biography |
| `website_url` | string | No | Official URL |
| `email` | string | No | Contact email |
| `phone_e164` | string | No | Contact phone |
| `social_links` | object | No | Provider keyed social URLs |
| `is_active` | boolean | Yes | Hidden from public UI when false |

People can be linked to organizers through `organizer_person.role_title`.

## Events

Events are canonical public records after manual creation or future source normalization.

| Field | Type | Required | Notes |
|---|---|---:|---|
| `id` | integer | Yes | Stable internal ID |
| `category_id` | integer | No | Optional primary public taxonomy |
| `city_id` | integer | No | Optional location filter |
| `organizer_id` | integer | No | Optional owning/publishing organizer |
| `title` | string | Yes | Public event title |
| `slug` | string | Yes | Unique public URL key |
| `summary` | string | No | Short listing text |
| `description` | string | No | Long detail content |
| `starts_at` | datetime | No | Canonical start time |
| `ends_at` | datetime | No | Canonical end time |
| `timezone` | string | Yes | Defaults to `Asia/Tehran` |
| `event_type` | string | Yes | `in_person`, `online`, or `hybrid` |
| `status` | string | Yes | `draft`, `published`, `cancelled`, or future moderation states |
| `venue_name` | string | No | Physical venue name |
| `venue_address` | string | No | Physical venue address |
| `latitude` | decimal | No | Optional map coordinate |
| `longitude` | decimal | No | Optional map coordinate |
| `online_url` | string | No | Public online join or landing URL |
| `canonical_url` | string | No | Preferred canonical public URL |
| `metadata` | object | No | Non-authoritative extra event attributes |
| `is_featured` | boolean | Yes | Manual promotion flag |

Events can be linked to speakers, hosts, or instructors through `event_person.role_title` and `event_person.sort_order`.
