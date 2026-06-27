# Authentication & OTP

Authentication is handled by `AuthController` and backed by Laravel Sanctum personal access tokens. Two paths exist: email/password and phone OTP. Both return a Bearer token used for all authenticated API calls.

## Token Model

Successful `register`, `login`, and `otp/verify` (for an existing user) issue a Sanctum token via `createToken('api')`. The plaintext token is returned once as `token` with `token_type: "Bearer"`. Clients send it as `Authorization: Bearer <token>` to reach routes behind `auth:sanctum`. `logout` deletes all of the current user's tokens.

## Email / Password

| Endpoint | Method | Body | Notes |
|---|---|---|---|
| `/api/v1/auth/register` | POST | `name`, `email`, `password` (confirmed, min 8), optional `phone_e164` | Creates an `active` user with `locale=fa`, `timezone=Asia/Tehran`; returns user + token (201). |
| `/api/v1/auth/login` | POST | `email`, `password` | Verifies hash and `status=active`; updates `last_login_at`; returns user + token. |
| `/api/v1/auth/me` | GET | — | Returns current user payload. Requires Bearer token. |
| `/api/v1/auth/logout` | POST | — | Revokes all tokens for the user. |

`phone_e164` must match `^\+[1-9]\d{7,14}$` (E.164) and is unique. The serialized user payload never includes the password, remember token, or OTP fields.

## Phone OTP

OTP is stored in the `otp_codes` table. The plaintext code is never persisted: only a masked preview (`code`, e.g. `12****`) and a bcrypt hash (`code_hash`) are saved.

### Request OTP — `POST /api/v1/auth/otp/request`

Body: `phone_e164` (E.164), optional `purpose` (`login` or `verify`, default `verify`).

Flow: any prior unused OTP for the same phone+purpose is invalidated, a fresh 6-digit code is generated, hashed, and stored with a 5-minute expiry, then sent through `NotificationService::sendOtp()` using the sms.ir template `SMSIR_TEMPLATE_ID_OTP`. Response returns `expires_at`.

### Verify OTP — `POST /api/v1/auth/otp/verify`

Body: `phone_e164`, `code` (6 digits), optional `purpose`.

The latest unused, unexpired OTP for the phone+purpose is selected. Verification fails if the code is missing, has `attempts >= 5`, or the hash does not match — each failure increments `attempts`. On success the OTP is marked used; if a user exists with that phone and is not yet phone-verified, `phone_verified_at` is set. If a matching user exists, a token is returned (login); otherwise `user`/`token` are null (phone verified but no account).

## Security Notes

- OTP lifetime is 5 minutes; maximum 5 verification attempts per code.
- Codes are bcrypt-hashed at rest; only a 2-char masked preview is stored in cleartext.
- Rate limiting and abuse protection should be enforced at the gateway/Nginx and Laravel throttle layers.

See [`API_CONTRACTS.md`](API_CONTRACTS.md) for the user payload shape and [`NOTIFICATIONS.md`](NOTIFICATIONS.md) for OTP delivery.
