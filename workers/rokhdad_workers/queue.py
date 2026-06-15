from __future__ import annotations

import json
import uuid
from dataclasses import asdict, dataclass
from typing import Any, Protocol

from rokhdad_workers.settings import redis_url_from_env


class QueueClient(Protocol):
    def blpop(self, keys: list[str], timeout: int = 0) -> tuple[bytes | str, bytes | str] | None:
        pass


@dataclass(frozen=True)
class QueueJob:
    id: str
    type: str
    payload: dict[str, Any]
    attempts: int = 0

    @classmethod
    def from_json(cls, message: bytes | str) -> "QueueJob":
        raw_message = message.decode("utf-8") if isinstance(message, bytes) else message
        raw = json.loads(raw_message)

        if not isinstance(raw, dict):
            raise ValueError("Queue job must be a JSON object.")

        payload = raw.get("payload", {})
        if not isinstance(payload, dict):
            raise ValueError("Queue job payload must be a JSON object.")

        return cls(
            id=str(raw.get("id") or uuid.uuid4()),
            type=str(raw.get("type") or "unknown"),
            payload=payload,
            attempts=int(raw.get("attempts") or 0),
        )


@dataclass(frozen=True)
class QueueResult:
    status: str
    queue: str
    job_id: str | None = None
    job_type: str | None = None
    error: str | None = None

    def to_dict(self) -> dict[str, Any]:
        return {key: value for key, value in asdict(self).items() if value is not None}


class QueueConsumer:
    def __init__(self, client: QueueClient, queue_name: str) -> None:
        self.client = client
        self.queue_name = queue_name

    def consume_once(self, timeout: int = 5) -> QueueResult:
        item = self.client.blpop([self.queue_name], timeout=timeout)
        if item is None:
            return QueueResult(status="empty", queue=self.queue_name)

        _, message = item

        try:
            job = QueueJob.from_json(message)
        except (TypeError, ValueError, json.JSONDecodeError) as exc:
            return QueueResult(status="failed", queue=self.queue_name, error=str(exc))

        return QueueResult(
            status="processed",
            queue=self.queue_name,
            job_id=job.id,
            job_type=job.type,
        )


def connect_redis() -> QueueClient | None:
    redis_url = redis_url_from_env()
    if not redis_url:
        return None

    from redis import Redis

    return Redis.from_url(redis_url, decode_responses=False)
