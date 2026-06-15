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

Runtime modules currently expose placeholder loops. P8-002 will replace the idle loop with the Redis queue consumer contract.
