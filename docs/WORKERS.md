# Rokhdad Workers

Python workers live in `workers/` and build into `rokhdad/worker:latest` with:

```bash
docker build -f deploy/worker.Dockerfile -t rokhdad/worker:latest workers
```

Smoke commands:

```bash
python -m rokhdad_workers.ingestion --smoke
python -m rokhdad_workers.normalization --smoke
python -m rokhdad_workers.images --smoke
```

Queue smoke command:

```bash
python -m rokhdad_workers.ingestion --once --queue rokhdad:jobs:ingestion --require-redis
```

Queue messages are JSON objects:

```json
{
  "id": "job-1",
  "type": "ingest.source",
  "payload": {
    "source": "evand"
  },
  "attempts": 0
}
```

The P8-002 consumer contract validates and consumes Redis list messages with `BLPOP`. It currently acknowledges valid messages by consuming them and reporting `processed`; task-specific handlers will be added by ingestion, normalization, and image tasks.

Retry and lock behavior:

- A job gets a Redis lock key `lock:<queue>:<job-id>` before handling.
- Handler failures requeue the same job with `attempts + 1` until `max_attempts`.
- After the final attempt, the worker reports `failed` and does not requeue.
- `--simulate-failure` exercises retry behavior for smoke tests.

Evand raw collection smoke command:

```bash
python -m rokhdad_workers.sources.evand --fixture workers/tests/fixtures/evand_events.json --limit 1
```

P10-001 collects raw Evand event payloads into a source-keyed envelope. MongoDB persistence is intentionally left for P10-003.

Eseminar raw collection smoke command:

```bash
python -m rokhdad_workers.sources.eseminar --fixture workers/tests/fixtures/eseminar_events.json --limit 1
```

P10-002 collects raw Eseminar webinar payloads into the same source-keyed envelope. MongoDB persistence is intentionally left for P10-003.

Snapshot storage command:

```bash
python -m rokhdad_workers.snapshots --input-json '[{"source_key":"evand","external_id":"101","fetched_at":"2026-06-15T00:00:00+00:00","payload":{}}]'
```

P10-003 stores raw event envelopes in MongoDB collection `raw_event_snapshots` with `snapshot_type=raw_event`.

Normalization schema command:

```bash
python -m rokhdad_workers.normalization --schema
```

P11-001 defines the normalized event DTO contract used by source-specific mappers before canonical database writes. The schema keeps source provenance (`source_key`, `external_id`, `raw_snapshot_id`), canonical event fields, location, organizer, people, and metadata.

Evand normalization smoke command:

```bash
python -m rokhdad_workers.normalizers.evand --fixture workers/tests/fixtures/evand_events.json --limit 1
```

P11-002 maps Evand raw event envelopes into the normalized event DTO, including canonical key, start/end datetimes, event type, location, organizer, source URL, and source metadata.

Eseminar normalization smoke command:

```bash
python -m rokhdad_workers.normalizers.eseminar --fixture workers/tests/fixtures/eseminar_events.json --limit 1
```

P11-003 maps Eseminar raw webinar envelopes into the normalized event DTO as online events, deriving `ends_at` from `start_date + duration_minutes` when needed and preserving teacher metadata as people references.

Deduplication scoring smoke command:

```bash
python -m rokhdad_workers.deduplication --fixture workers/tests/fixtures/normalized_duplicates.json
```

P11-004 scores normalized event pairs using title similarity, start datetime, city, organizer, and canonical URL signals. Scores at or above 80 are treated as duplicates; scores at or above 65 are possible duplicates for review.

Field history dry-run command:

```bash
python -m rokhdad_workers.field_history --input-json '[{"source_key":"evand","external_id":"101","title":"AI Product Management"}]' --dry-run
```

P12-001 builds field-level source history documents from normalized events and upserts them into MongoDB collection `event_field_history`. Each document stores canonical key, source provenance, field path, observed value, value hash, observed timestamp, schema version, and optional raw snapshot id.

Enrichment job contract smoke command:

```bash
python -m rokhdad_workers.enrichment --fixture workers/tests/fixtures/enrichment_job.json
```

P12-003 defines queue job type `enrich.event` for enrichment work. The payload carries the canonical key, requested enrichment targets, normalized event snapshot, source keys, priority, requested timestamp, and optional context. Default queue: `rokhdad:jobs:enrichment`.

Image download smoke command:

```bash
python -m rokhdad_workers.images --fixture workers/tests/fixtures/image_download_job.json --output-dir /tmp/rokhdad-images
```

P13-001 defines an image download worker contract and downloader. It fetches an image URL, writes it under an event-keyed storage path, and reports content type, byte size, SHA-256 hash, and downloaded path.

Image variant smoke command:

```bash
python -m rokhdad_workers.images --fixture workers/tests/fixtures/image_download_job.json --output-dir /tmp/rokhdad-images --variants 320,640
```

P13-002 adds WebP resize variants that preserve aspect ratio and report variant name, dimensions, content type, byte size, SHA-256 hash, and storage path.

Image moderation metadata smoke command:

```bash
python -m rokhdad_workers.images --fixture workers/tests/fixtures/image_download_job.json --output-dir /tmp/rokhdad-images --moderation-metadata
```

P13-003 records moderation-oriented image metadata: dimensions, detected content type, byte size, SHA-256 hash, review flags, and `needs_review`. Current automatic flags include unsupported content type and too-small dimensions.
