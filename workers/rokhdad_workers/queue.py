from __future__ import annotations

import json
import uuid
from dataclasses import asdict, dataclass
from typing import Any, Callable, Protocol

from rokhdad_workers.settings import redis_url_from_env


class QueueClient(Protocol):
    def blpop(self, keys: list[str], timeout: int = 0) -> tuple[bytes | str, bytes | str] | None:
        pass

    def set(self, name: str, value: str, nx: bool = False, ex: int | None = None) -> bool | None:
        pass

    def delete(self, name: str) -> int:
        pass

    def rpush(self, name: str, value: str) -> int:
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

    def to_json(self) -> str:
        return json.dumps(asdict(self), sort_keys=True)


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
    def __init__(
        self,
        client: QueueClient,
        queue_name: str,
        handler: Callable[[QueueJob], None] | None = None,
        lock_ttl: int = 300,
        max_attempts: int = 3,
    ) -> None:
        self.client = client
        self.queue_name = queue_name
        self.handler = handler
        self.lock_ttl = lock_ttl
        self.max_attempts = max_attempts

    def consume_once(self, timeout: int = 5) -> QueueResult:
        item = self.client.blpop([self.queue_name], timeout=timeout)
        if item is None:
            return QueueResult(status="empty", queue=self.queue_name)

        _, message = item

        try:
            job = QueueJob.from_json(message)
        except (TypeError, ValueError, json.JSONDecodeError) as exc:
            return QueueResult(status="failed", queue=self.queue_name, error=str(exc))

        lock_key = f"lock:{self.queue_name}:{job.id}"
        if not self.client.set(lock_key, "1", nx=True, ex=self.lock_ttl):
            return QueueResult(status="locked", queue=self.queue_name, job_id=job.id, job_type=job.type)

        try:
            if self.handler is not None:
                self.handler(job)
        except Exception as exc:
            next_attempt = job.attempts + 1
            if next_attempt < self.max_attempts:
                self.client.rpush(
                    self.queue_name,
                    QueueJob(
                        id=job.id,
                        type=job.type,
                        payload=job.payload,
                        attempts=next_attempt,
                    ).to_json(),
                )

                return QueueResult(
                    status="retrying",
                    queue=self.queue_name,
                    job_id=job.id,
                    job_type=job.type,
                    error=str(exc),
                )

            return QueueResult(
                status="failed",
                queue=self.queue_name,
                job_id=job.id,
                job_type=job.type,
                error=str(exc),
            )
        finally:
            self.client.delete(lock_key)

        return QueueResult(status="processed", queue=self.queue_name, job_id=job.id, job_type=job.type)


def connect_redis() -> QueueClient | None:
    redis_url = redis_url_from_env()
    if not redis_url:
        return None

    from redis import Redis

    return Redis.from_url(redis_url, decode_responses=False)
