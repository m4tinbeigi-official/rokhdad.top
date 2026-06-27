import io
import json
import logging
import unittest

from rokhdad_workers.logging import configure_logging, get_logger, level_from_env
from rokhdad_workers.queue import QueueResult


class StructuredLoggingTest(unittest.TestCase):
    def setUp(self) -> None:
        self.stream = io.StringIO()
        configure_logging(level=logging.DEBUG, stream=self.stream)

    def tearDown(self) -> None:
        # Remove handlers we installed so other tests are unaffected.
        root = logging.getLogger()
        for handler in list(root.handlers):
            if getattr(handler, "_rokhdad_json", False):
                root.removeHandler(handler)

    def _lines(self) -> list[dict]:
        return [json.loads(line) for line in self.stream.getvalue().splitlines() if line.strip()]

    def test_emits_standard_envelope(self) -> None:
        log = get_logger("worker.ingestion")
        log.info("queue.started", "consumer up", correlation_id="job-1", queue="rokhdad:ingestion")

        line = self._lines()[0]
        self.assertEqual("info", line["level"])
        self.assertEqual("worker.ingestion", line["service"])
        self.assertEqual("queue.started", line["event"])
        self.assertEqual("consumer up", line["message"])
        self.assertEqual("job-1", line["correlation_id"])
        self.assertEqual("rokhdad:ingestion", line["queue"])
        self.assertIn("ts", line)

    def test_levels_are_filtered(self) -> None:
        configure_logging(level=logging.WARNING, stream=self.stream)
        log = get_logger("worker.test")
        log.info("ignored.event", "should not appear")
        log.error("kept.event", "should appear")

        events = [line["event"] for line in self._lines()]
        self.assertNotIn("ignored.event", events)
        self.assertIn("kept.event", events)

    def test_domain_fields_cannot_clobber_envelope(self) -> None:
        log = get_logger("worker.test")
        # Passing a reserved key as a domain field must not overwrite the envelope.
        log.info("evt", "msg", level="HACKED", service="HACKED")

        line = self._lines()[0]
        self.assertEqual("info", line["level"])
        self.assertEqual("worker.test", line["service"])

    def test_level_from_env(self) -> None:
        import os

        os.environ["LOG_LEVEL"] = "error"
        try:
            self.assertEqual(logging.ERROR, level_from_env())
        finally:
            del os.environ["LOG_LEVEL"]

        self.assertEqual(logging.INFO, level_from_env())

    def test_log_queue_result_maps_status_to_level(self) -> None:
        from rokhdad_workers.cli import log_queue_result

        log = get_logger("worker.cli")
        log_queue_result(log, QueueResult(status="failed", queue="q", job_id="j1", job_type="t", error="boom"))
        log_queue_result(log, QueueResult(status="processed", queue="q", job_id="j2", job_type="t"))

        lines = {line["event"]: line for line in self._lines()}
        self.assertEqual("error", lines["queue.job.failed"]["level"])
        self.assertEqual("j1", lines["queue.job.failed"]["correlation_id"])
        self.assertEqual("info", lines["queue.job.processed"]["level"])


if __name__ == "__main__":
    unittest.main()
