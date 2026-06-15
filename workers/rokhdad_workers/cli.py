from __future__ import annotations

import argparse
import json
import os
import signal
import sys
import time
from dataclasses import asdict, dataclass
from datetime import UTC, datetime
from typing import Any, Iterable

from rokhdad_workers import __version__
from rokhdad_workers.queue import QueueConsumer, connect_redis


@dataclass(frozen=True)
class WorkerStatus:
    service: str
    version: str
    status: str
    timestamp: str
    redis_configured: bool
    mongodb_configured: bool


def build_status(service: str, status: str = "ok") -> WorkerStatus:
    return WorkerStatus(
        service=service,
        version=__version__,
        status=status,
        timestamp=datetime.now(UTC).isoformat(),
        redis_configured=bool(os.getenv("REDIS_URL") or os.getenv("REDIS_HOST")),
        mongodb_configured=bool(os.getenv("MONGODB_URI") or os.getenv("MONGO_INITDB_ROOT_USERNAME")),
    )


def emit_status(service: str, status: str = "ok", extra: dict[str, Any] | None = None) -> None:
    payload = asdict(build_status(service, status))
    if extra:
        payload.update(extra)

    print(json.dumps(payload, sort_keys=True), flush=True)


def run_worker(service: str, argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog=f"rokhdad-worker-{service}")
    parser.add_argument("--smoke", action="store_true", help="Print worker status and exit.")
    parser.add_argument("--once", action="store_true", help="Run one queue cycle or placeholder cycle and exit.")
    parser.add_argument("--interval", type=int, default=60, help="Idle loop interval in seconds.")
    parser.add_argument("--queue", default=None, help="Redis list name to consume.")
    parser.add_argument("--timeout", type=int, default=5, help="Queue pop timeout in seconds.")
    parser.add_argument("--require-redis", action="store_true", help="Fail when Redis is not configured.")
    args = parser.parse_args(list(argv) if argv is not None else None)

    if args.smoke:
        emit_status(service)
        return 0

    if args.once:
        if args.queue:
            redis_client = connect_redis()
            if redis_client is None:
                emit_status(service, "redis_unconfigured", {"queue": args.queue})
                return 2 if args.require_redis else 0

            result = QueueConsumer(redis_client, args.queue).consume_once(timeout=max(args.timeout, 1))
            emit_status(service, result.status, result.to_dict())
            return 0 if result.status in {"processed", "empty"} else 1

        emit_status(service, "idle")
        return 0

    stopped = False

    def handle_stop(signum: int, frame: object) -> None:
        nonlocal stopped
        stopped = True

    signal.signal(signal.SIGTERM, handle_stop)
    signal.signal(signal.SIGINT, handle_stop)

    emit_status(service, "started", {"queue": args.queue} if args.queue else None)

    while not stopped:
        if args.queue:
            redis_client = connect_redis()
            if redis_client is None:
                emit_status(service, "redis_unconfigured", {"queue": args.queue})
                return 2 if args.require_redis else 0

            result = QueueConsumer(redis_client, args.queue).consume_once(timeout=max(args.timeout, 1))
            emit_status(service, result.status, result.to_dict())
            continue

        time.sleep(max(args.interval, 1))

    emit_status(service, "stopped")
    return 0


def main(argv: Iterable[str] | None = None) -> int:
    return run_worker("worker", argv)


if __name__ == "__main__":
    sys.exit(main())
