# Workers Agent Notes

Scope: Python ingestion, external source collectors, normalization,
deduplication, enrichment, image jobs, queues, snapshots, and Graphiti helpers.

- Entry points: `rokhdad_workers/cli.py` and `rokhdad_workers/graphiti_cli.py`.
- Source collectors: `rokhdad_workers/sources/`.
- Normalizers: `rokhdad_workers/normalizers/`.
- Shared pipeline modules: `ingestion.py`, `normalization.py`,
  `deduplication.py`, `enrichment.py`, `images.py`, `queue.py`, `snapshots.py`.
- Tests live in `tests/`.
- Do not read `.venv/`, `__pycache__/`, `*.egg-info/`, snapshots, logs, or large
  provider reference docs unless the task requires them.
- Prefer targeted `python -m pytest tests/test_name.py`.

Relevant docs: `docs/WORKERS.md`, `docs/INGESTION_SOURCES.md`, and provider
references in `docs/site/` only when needed.
