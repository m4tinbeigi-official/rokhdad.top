# Rokhdad Environment And Secret Inventory

Do not commit real secrets. Production secret files live only on the Ubuntu server.

## App

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `APP_ENV` | No | Yes | `production` on server |
| `APP_KEY` | Yes | Yes | Laravel app key |
| `APP_URL` | No | Yes | `https://rokhdad.top` |
| `API_BASE_URL` | No | Yes | Public API base for frontend |
| `ADMIN_URL` | No | Yes | `https://rokhdad.top/admin` |

## MariaDB

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `MARIADB_DATABASE` | No | Yes | Canonical DB |
| `MARIADB_USER` | No | Yes | App DB user |
| `MARIADB_PASSWORD` | Yes | Yes | Strong password |
| `MARIADB_ROOT_PASSWORD` | Yes | Yes | Server only |
| `DATABASE_URL` | Yes | Yes | Backend connection string |

## MongoDB

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `MONGO_INITDB_ROOT_USERNAME` | Yes | Yes | Server only |
| `MONGO_INITDB_ROOT_PASSWORD` | Yes | Yes | Server only |
| `MONGODB_DATABASE` | No | Yes | Raw data DB |
| `MONGODB_URI` | Yes | Yes | Backend/worker connection string |

## Redis

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `REDIS_PASSWORD` | Yes | Yes | Required in production |
| `REDIS_URL` | Yes | Yes | Backend/worker connection string |

## Domain And SSL

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `DOMAIN` | No | Yes | `rokhdad.top` |
| `LETSENCRYPT_EMAIL` | No | Yes | Owner email for certificate notices |

## SMS

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `SMSIR_API_KEY` | Yes | Yes | sms.ir API key |
| `SMSIR_TEMPLATE_ID_OTP` | No | Yes | OTP template ID |
| `SMSIR_SENDER` | No | No | If account requires sender |

## Email

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `PAKETT_API_KEY` | Yes | Should | Pakett API key |
| `MAIL_FROM_ADDRESS` | No | Yes | Transactional sender |
| `MAIL_FROM_NAME` | No | Yes | `رخداد` |

## Payments

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `ZARINPAL_MERCHANT_ID` | Yes | MVP | ZarinPal merchant ID |
| `ZARINPAL_SANDBOX` | No | Yes | `true` or `false` |
| `ZIBAL_MERCHANT_ID` | Yes | Later | Zibal merchant ID |
| `PAYMENT_CALLBACK_BASE_URL` | No | Yes | `https://rokhdad.top/api/v1/payments/callback` |

## Source Providers

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `EVAND_API_KEY` | Yes | If available | Can be replaced by source key registry |
| `ESEMINAR_API_KEY` | Yes | If available | Can be replaced by source key registry |

## Security

| Variable | Secret | Required | Notes |
|---|---:|---:|---|
| `SESSION_DOMAIN` | No | Yes | `rokhdad.top` |
| `CORS_ALLOWED_ORIGINS` | No | Yes | Production origin list |
| `RATE_LIMIT_DEFAULT` | No | Yes | Default API rate limit |
| `JWT_SECRET` | Yes | If JWT used | Prefer Laravel Sanctum unless changed |

