from __future__ import annotations

import argparse
import json
import os
import signal
import sys
import time
from dataclasses import asdict, dataclass
from datetime import datetime, timezone
from typing import Any, Iterable

from rokhdad_workers import __version__
from rokhdad_workers.logging import configure_logging, get_logger
from rokhdad_workers.queue import QueueConsumer, QueueJob, QueueResult, connect_redis


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
        timestamp=datetime.now(timezone.utc).isoformat(),
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
    parser.add_argument("--simulate-failure", action="store_true", help="Raise a handler error after locking a job.")
    args = parser.parse_args(list(argv) if argv is not None else None)

    configure_logging()
    log = get_logger(f"worker.{service}")

    if args.smoke:
        emit_status(service)
        log.info("worker.smoke", "worker smoke check", queue=args.queue)
        return 0

    if args.once:
        if args.queue:
            redis_client = connect_redis()
            if redis_client is None:
                emit_status(service, "redis_unconfigured", {"queue": args.queue})
                return 2 if args.require_redis else 0

            result = QueueConsumer(redis_client, args.queue, handler=build_handler(args.simulate_failure)).consume_once(timeout=max(args.timeout, 1))
            emit_status(service, result.status, result.to_dict())
            log_queue_result(log, result)
            return 0 if result.status in {"processed", "empty", "retrying"} else 1

        emit_status(service, "idle")
        return 0

    stopped = False

    def handle_stop(signum: int, frame: object) -> None:
        nonlocal stopped
        stopped = True

    signal.signal(signal.SIGTERM, handle_stop)
    signal.signal(signal.SIGINT, handle_stop)

    emit_status(service, "started", {"queue": args.queue} if args.queue else None)
    log.info("worker.started", "worker loop started", queue=args.queue)

    while not stopped:
        if args.queue:
            redis_client = connect_redis()
            if redis_client is None:
                emit_status(service, "redis_unconfigured", {"queue": args.queue})
                log.critical("worker.redis_unconfigured", "Redis is not configured", queue=args.queue)
                return 2 if args.require_redis else 0

            result = QueueConsumer(redis_client, args.queue, handler=build_handler(args.simulate_failure)).consume_once(timeout=max(args.timeout, 1))
            emit_status(service, result.status, result.to_dict())
            log_queue_result(log, result)
            continue

        time.sleep(max(args.interval, 1))

    emit_status(service, "stopped")
    log.info("worker.stopped", "worker loop stopped", queue=args.queue)
    return 0


# Map queue result statuses to the standard log levels from docs/LOGGING.md.
_RESULT_LEVELS = {
    "processed": "info",
    "empty": "debug",
    "retrying": "warning",
    "locked": "warning",
    "failed": "error",
}


def log_queue_result(log, result: QueueResult) -> None:
    """Emit a structured log line for a queue consume result."""
    level = _RESULT_LEVELS.get(result.status, "info")
    getattr(log, level)(
        f"queue.job.{result.status}",
        f"queue job {result.status}",
        correlation_id=result.job_id,
        queue=result.queue,
        job_type=result.job_type,
        error=result.error,
    )


def main(argv: Iterable[str] | None = None) -> int:
    return run_worker("worker", argv)


def build_handler(simulate_failure: bool):
    if not simulate_failure:
        return None

    def fail(job: QueueJob) -> None:
        raise RuntimeError(f"simulated failure for {job.id}")

    return fail


if __name__ == "__main__":
    sys.exit(main())
