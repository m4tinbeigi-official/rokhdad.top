# Centralized Logging Plan

This is the logging standard for every Rokhdad runtime — Laravel backend, Python
workers, and the Nginx edge. It exists so that, in production, an operator can
answer "what happened, where, and in what order" from a single place without
shelling into individual containers.

## Principles

- **Log to stdout/stderr, not files.** Every service writes structured lines to
  standard streams. Docker captures them; the host log driver ships them onward.
  Services must not manage their own log files, rotation, or disk paths.
- **One event per line, JSON-encoded.** Each line is a self-contained JSON object
  so it can be parsed, filtered, and indexed without multiline reassembly.
- **No secrets in logs.** Never log API keys, tokens, passwords, OTP codes, full
  card/bank data, or raw `.env` values. Redact identifiers to the minimum needed
  to debug (e.g. last 4 digits, internal IDs instead of phone numbers).
- **Correlation over verbosity.** Prefer a few well-keyed fields that let you
  join records (request id, job id, organizer id) over large free-text dumps.

## Standard Envelope

Every log line, in every runtime, carries at least these fields:

| Field | Type | Meaning |
|---|---|---|
| `ts` | string | ISO-8601 UTC timestamp (e.g. `2026-06-27T10:30:00Z`). |
| `level` | string | `debug`, `info`, `warning`, `error`, `critical`. |
| `service` | string | Emitter, e.g. `backend`, `worker.ingestion`, `nginx`. |
| `event` | string | Short stable event key, e.g. `payment.verified`, `queue.job.failed`. |
| `message` | string | Human-readable summary. |
| `correlation_id` | string | Request id (backend) or job id (worker) for joining lines. |

Domain fields are added alongside the envelope, never nested under a generic
`data` blob, so they stay queryable: `organizer_id`, `event_id`, `payment_id`,
`source_key`, `attempts`, `duration_ms`, `status_code`.

## Levels

- `debug` — developer detail; off in production by default.
- `info` — normal lifecycle events (service started, job processed, payment paid).
- `warning` — recoverable/expected-bad situations (job retrying, source blocked,
  payout rejected).
- `error` — a unit of work failed and was not recovered.
- `critical` — the service cannot continue (lost DB/Redis, config missing).

Production default level is `info`; set per service via the `LOG_LEVEL`
environment variable.

## Per-Runtime Implementation

### Backend (Laravel)

- Channel: the `stderr` / JSON-formatted stack defined in `config/logging.php`,
  driven by `LOG_CHANNEL=stack` and `LOG_LEVEL`.
- Correlation id: a middleware assigns a request id (incoming
  `X-Request-Id` header if present, otherwise a generated UUID), echoes it back on
  the response, and pushes it into the log context so every line in that request
  carries `correlation_id`.
- HTTP-level request logging already lives in the `request_logs` table and its
  Filament viewer; that is the per-request audit surface. Application logs
  (payments, settlement, webhooks) go to stdout with the envelope above.

### Workers (Python)

- Use `rokhdad_workers.logging.configure_logging()` once at process start and
  `get_logger(service)` to obtain a logger. See [WORKERS.md](WORKERS.md) and
  `workers/rokhdad_workers/logging.py`.
- The worker logger emits the standard JSON envelope. The job id from
  `QueueJob.id` is used as `correlation_id`; `job_type`, `attempts`, `queue`, and
  `duration_ms` are attached as domain fields.
- `LOG_LEVEL` controls verbosity; default `info`.

### Nginx (edge)

- Access and error logs go to stdout/stderr (the container default). Access logs
  use a JSON `log_format` so they share the envelope shape (`ts`, `status_code`,
  `duration_ms`, request path, upstream).

## Collection & Retention

- The Docker Compose stack uses the host log driver (`json-file` with size +
  file rotation caps, or `journald`) so a single misbehaving container cannot
  fill the disk. See [DOCKER_INFRASTRUCTURE.md](DOCKER_INFRASTRUCTURE.md).
- Retention target: **14 days** hot for application/access logs, longer only for
  records that already live in a database (request logs, audit logs).
- Audit-grade events (admin actions, money movement) are *also* persisted to the
  database (see [ADMIN_PANEL.md](ADMIN_PANEL.md) audit log), independent of the
  rolling stdout logs, because they must outlive log rotation.

## What Belongs Where

- **stdout JSON logs** — operational/debug timeline, short retention.
- **`request_logs` table** — per outbound/inbound HTTP request, queryable in admin.
- **`audit_logs` table** — durable record of *who did what* (auth, payouts,
  permission and source changes); never rotated away.
