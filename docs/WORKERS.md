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
