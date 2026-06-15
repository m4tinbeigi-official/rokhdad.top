from __future__ import annotations

import argparse
import json
import sys
import uuid
from dataclasses import asdict, dataclass, field
from datetime import UTC, datetime
from pathlib import Path
from typing import Any, Iterable

from rokhdad_workers.queue import QueueJob


ENRICHMENT_JOB_TYPE = "enrich.event"
DEFAULT_ENRICHMENT_QUEUE = "rokhdad:jobs:enrichment"
SUPPORTED_TARGETS = frozenset({"seo", "summary", "category", "city", "people", "image_prompt"})


@dataclass(frozen=True)
class EnrichmentJob:
    canonical_key: str
    targets: tuple[str, ...]
    normalized_event: dict[str, Any]
    source_keys: tuple[str, ...] = ()
    priority: int = 0
    requested_at: str = field(default_factory=lambda: datetime.now(UTC).isoformat())
    context: dict[str, Any] = field(default_factory=dict)
    job_id: str = field(default_factory=lambda: str(uuid.uuid4()))

    def __post_init__(self) -> None:
        if not self.canonical_key.strip():
            raise ValueError("canonical_key is required")
        if not self.targets:
            raise ValueError("at least one enrichment target is required")
        unknown_targets = sorted(set(self.targets) - SUPPORTED_TARGETS)
        if unknown_targets:
            raise ValueError(f"unsupported enrichment targets: {unknown_targets}")
        if not isinstance(self.normalized_event, dict) or not self.normalized_event:
            raise ValueError("normalized_event must be a non-empty object")

        object.__setattr__(self, "targets", tuple(self.targets))
        object.__setattr__(self, "source_keys", tuple(self.source_keys))

    def to_payload(self) -> dict[str, Any]:
        return asdict(self)

    def to_queue_job(self) -> QueueJob:
        return QueueJob(id=self.job_id, type=ENRICHMENT_JOB_TYPE, payload=self.to_payload())

    @classmethod
    def from_payload(cls, payload: dict[str, Any]) -> EnrichmentJob:
        return cls(
            canonical_key=str(payload.get("canonical_key", "")),
            targets=tuple(payload.get("targets") or ()),
            normalized_event=payload.get("normalized_event") or {},
            source_keys=tuple(payload.get("source_keys") or ()),
            priority=int(payload.get("priority") or 0),
            requested_at=str(payload.get("requested_at") or datetime.now(UTC).isoformat()),
            context=payload.get("context") or {},
            job_id=str(payload.get("job_id") or uuid.uuid4()),
        )

    @classmethod
    def from_queue_job(cls, job: QueueJob) -> EnrichmentJob:
        if job.type != ENRICHMENT_JOB_TYPE:
            raise ValueError(f"Queue job type must be {ENRICHMENT_JOB_TYPE}.")

        payload = dict(job.payload)
        payload.setdefault("job_id", job.id)
        return cls.from_payload(payload)


def build_enrichment_job(
    normalized_event: dict[str, Any],
    targets: Iterable[str],
    priority: int = 0,
    context: dict[str, Any] | None = None,
) -> EnrichmentJob:
    canonical_key = str(normalized_event.get("canonical_key") or "")
    source_key = normalized_event.get("source_key")

    return EnrichmentJob(
        canonical_key=canonical_key,
        targets=tuple(targets),
        normalized_event=normalized_event,
        source_keys=(str(source_key),) if source_key else (),
        priority=priority,
        context=context or {},
    )


def load_job_fixture(path: Path) -> EnrichmentJob:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("Enrichment fixture must be a JSON object.")

    if payload.get("type") == ENRICHMENT_JOB_TYPE and isinstance(payload.get("payload"), dict):
        return EnrichmentJob.from_queue_job(QueueJob.from_json(json.dumps(payload)))

    return EnrichmentJob.from_payload(payload)


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-enrichment")
    parser.add_argument("--fixture", type=Path, required=True)
    args = parser.parse_args(list(argv) if argv is not None else None)

    enrichment_job = load_job_fixture(args.fixture)
    queue_job = enrichment_job.to_queue_job()

    print(json.dumps({
        "queue": DEFAULT_ENRICHMENT_QUEUE,
        "job": json.loads(queue_job.to_json()),
    }, ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
