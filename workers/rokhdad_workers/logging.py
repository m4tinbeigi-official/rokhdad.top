"""Structured JSON logging for Rokhdad workers.

Implements the standard log envelope defined in ``docs/LOGGING.md``: every line
is a single JSON object with ``ts``, ``level``, ``service``, ``event``,
``message`` and an optional ``correlation_id``, plus arbitrary domain fields.

Usage::

    from rokhdad_workers.logging import configure_logging, get_logger

    configure_logging()
    log = get_logger("worker.ingestion")
    log.info("queue.started", "consumer up", queue="rokhdad:ingestion")
    log.error("queue.job.failed", "handler raised", correlation_id=job.id,
              job_type=job.type, attempts=job.attempts, error=str(exc))
"""

from __future__ import annotations

import json
import logging
import os
import sys
from datetime import datetime, timezone
from typing import Any

_LEVELS = {
    "debug": logging.DEBUG,
    "info": logging.INFO,
    "warning": logging.WARNING,
    "error": logging.ERROR,
    "critical": logging.CRITICAL,
}

# Reserved envelope keys that must not be overwritten by domain fields.
_RESERVED = {"ts", "level", "service", "event", "message", "correlation_id"}


class JsonLogFormatter(logging.Formatter):
    """Render a log record as a single-line JSON envelope."""

    def format(self, record: logging.LogRecord) -> str:
        envelope: dict[str, Any] = {
            "ts": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
            "level": record.levelname.lower(),
            "service": getattr(record, "service", record.name),
            "event": getattr(record, "event", record.name),
            "message": record.getMessage(),
        }

        correlation_id = getattr(record, "correlation_id", None)
        if correlation_id is not None:
            envelope["correlation_id"] = correlation_id

        fields = getattr(record, "fields", None)
        if isinstance(fields, dict):
            for key, value in fields.items():
                if key not in _RESERVED:
                    envelope[key] = value

        return json.dumps(envelope, sort_keys=True, default=str)


def level_from_env(default: str = "info") -> int:
    """Resolve the numeric log level from the ``LOG_LEVEL`` environment var."""
    name = os.getenv("LOG_LEVEL", default).strip().lower()
    return _LEVELS.get(name, logging.INFO)


def configure_logging(level: int | None = None, stream: Any = None) -> None:
    """Install the JSON formatter on the root logger.

    Idempotent: replaces any handlers previously installed by this function so
    repeated calls (e.g. in tests) do not duplicate output.
    """
    root = logging.getLogger()
    root.setLevel(level if level is not None else level_from_env())

    # Drop handlers we previously installed so output is not duplicated.
    for handler in list(root.handlers):
        if getattr(handler, "_rokhdad_json", False):
            root.removeHandler(handler)

    handler = logging.StreamHandler(stream or sys.stderr)
    handler.setFormatter(JsonLogFormatter())
    handler._rokhdad_json = True  # type: ignore[attr-defined]
    root.addHandler(handler)


class StructuredLogger:
    """Thin wrapper that emits the standard envelope with domain fields."""

    def __init__(self, service: str) -> None:
        self.service = service
        self._logger = logging.getLogger(service)

    def _log(
        self,
        levelno: int,
        event: str,
        message: str,
        correlation_id: str | None,
        fields: dict[str, Any],
    ) -> None:
        if not self._logger.isEnabledFor(levelno):
            return

        self._logger.log(
            levelno,
            message,
            extra={
                "service": self.service,
                "event": event,
                "correlation_id": correlation_id,
                "fields": fields,
            },
        )

    def debug(self, event: str, message: str = "", *, correlation_id: str | None = None, **fields: Any) -> None:
        self._log(logging.DEBUG, event, message, correlation_id, fields)

    def info(self, event: str, message: str = "", *, correlation_id: str | None = None, **fields: Any) -> None:
        self._log(logging.INFO, event, message, correlation_id, fields)

    def warning(self, event: str, message: str = "", *, correlation_id: str | None = None, **fields: Any) -> None:
        self._log(logging.WARNING, event, message, correlation_id, fields)

    def error(self, event: str, message: str = "", *, correlation_id: str | None = None, **fields: Any) -> None:
        self._log(logging.ERROR, event, message, correlation_id, fields)

    def critical(self, event: str, message: str = "", *, correlation_id: str | None = None, **fields: Any) -> None:
        self._log(logging.CRITICAL, event, message, correlation_id, fields)


def get_logger(service: str) -> StructuredLogger:
    """Return a :class:`StructuredLogger` bound to ``service`` (e.g. ``worker.ingestion``)."""
    return StructuredLogger(service)
