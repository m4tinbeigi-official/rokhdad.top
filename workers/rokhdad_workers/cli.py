from __future__ import annotations

import argparse
import json
import os
import signal
import sys
import time
from dataclasses import asdict, dataclass
from datetime import UTC, datetime
from typing import Iterable

from rokhdad_workers import __version__


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


def emit_status(service: str, status: str = "ok") -> None:
    print(json.dumps(asdict(build_status(service, status)), sort_keys=True), flush=True)


def run_worker(service: str, argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog=f"rokhdad-worker-{service}")
    parser.add_argument("--smoke", action="store_true", help="Print worker status and exit.")
    parser.add_argument("--once", action="store_true", help="Run one placeholder cycle and exit.")
    parser.add_argument("--interval", type=int, default=60, help="Idle loop interval in seconds.")
    args = parser.parse_args(list(argv) if argv is not None else None)

    if args.smoke:
        emit_status(service)
        return 0

    if args.once:
        emit_status(service, "idle")
        return 0

    stopped = False

    def handle_stop(signum: int, frame: object) -> None:
        nonlocal stopped
        stopped = True

    signal.signal(signal.SIGTERM, handle_stop)
    signal.signal(signal.SIGINT, handle_stop)

    emit_status(service, "started")

    while not stopped:
        time.sleep(max(args.interval, 1))

    emit_status(service, "stopped")
    return 0


def main(argv: Iterable[str] | None = None) -> int:
    return run_worker("worker", argv)


if __name__ == "__main__":
    sys.exit(main())
