# Ingestion Sources & Pipeline

This documents the external event sources and the normalization pipeline inside the Python worker package (`workers/rokhdad_workers/`). For the worker runtime, queue contract, Docker image, and smoke commands see [`WORKERS.md`](WORKERS.md); this file focuses on the source connectors and the data stages that turn raw payloads into canonical events.

## Sources

Each connector lives in `rokhdad_workers/sources/` and harvests public listing endpoints, writing raw payloads for downstream normalization. They use only the Python standard library (`urllib`) for HTTP, with a polite default delay between pages.

| Source | Module | Base URL | Notes |
|---|---|---|---|
| Evand | `sources/evand.py` | `https://api.evand.com` | Paginated (`DEFAULT_PER_PAGE=50`, up to 200 pages). Historical sync also runs server-side via the `evand:historical-sync` artisan command to walk organizers and bypass rate limits. |
| Eseminar | `sources/eseminar.py` | `https://api.eseminar.tv/api/v1` | Harvests a defined set of public listing endpoints; optional bearer token. |
| Bilitmaster | `sources/bilitmaster.py` | `https://api.bilitmaster.com/api` | Public listing endpoints (`/getHomeEvents`, `/getEvents`, …), no auth required. |

Source registration metadata (keys, URLs, auth type, rate limits, health) is also tracked in MariaDB `event_sources` — see [`DATA_MODEL.md`](DATA_MODEL.md).

## Normalizers

`rokhdad_workers/normalizers/` maps a source's raw shape into the canonical `NormalizedEvent` schema:

- `normalizers/evand.py`
- `normalizers/eseminar.py`

The canonical schema is defined in `normalization.py` via dataclasses: `NormalizedEvent`, `NormalizedLocation`, `NormalizedOrganizerRef`, `NormalizedPersonRef`. `validate_datetime()` enforces date fields and `schema_document()` exposes the JSON schema. Run `python -m rokhdad_workers.normalization --smoke` to validate.

## Pipeline Stages

1. **Ingestion** (`ingestion.py`) — pull raw payloads from a source and store them (MongoDB owns raw payloads and snapshots).
2. **Snapshots** (`snapshots.py`) — capture point-in-time source records for diffing and field history.
3. **Normalization** (`normalization.py` + `normalizers/`) — convert raw payloads to `NormalizedEvent` records.
4. **Deduplication** (`deduplication.py`) — `score_event_pair()` and `find_duplicate_candidates()` compute similarity (with `normalize_text()` cleanup) to merge events that appear across multiple sources, producing `DeduplicationCandidate` results.
5. **Field history** (`field_history.py`) — track how individual fields change over time so manual overrides/locks (MariaDB `event_field_overrides`/`event_field_locks`) are respected.
6. **Enrichment** (`enrichment.py`) — `build_enrichment_job()` assembles enrichment jobs; responses are stored for auditing.
7. **Images** (`images.py`) — download and resize event images (Pillow).

## Knowledge Graph (Graphiti)

`graphiti_client.py` + `graphiti_cli.py` initialize a Graphiti client over Neo4j (`NEO4J_URI`/user/password) for graph-based enrichment. The default LLM path requires `OPENAI_API_KEY`. This is optional infrastructure layered on top of the normalized event store.

## Queue & CLI

Workers consume Redis list jobs (`BLPOP`) per the contract in [`WORKERS.md`](WORKERS.md). The `cli.py` helpers (`build_status`, `run_worker`, `emit_status`) provide the `--smoke`, `--once`, and `--interval` flags and report whether Redis/MongoDB are configured. `queue.py` defines `QueueConsumer`, `QueueJob`, `QueueResult`, and `connect_redis()`.

## Environment

Connectors and the pipeline read source bases and credentials from environment/config (e.g. `EVAND_API_BASE`, `ESEMINAR_API_BASE`, `ESEMINAR_API_TOKEN`, `REDIS_URL`, `MONGODB_URI`, `NEO4J_URI`, `OPENAI_API_KEY`). See [`ENVIRONMENT.md`](ENVIRONMENT.md).
