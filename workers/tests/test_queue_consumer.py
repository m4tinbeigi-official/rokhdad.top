import json
import unittest

from rokhdad_workers.queue import QueueConsumer, QueueJob


class FakeRedis:
    def __init__(self, message: str | None) -> None:
        self.message = message

    def blpop(self, keys: list[str], timeout: int = 0):
        if self.message is None:
            return None

        return keys[0], self.message


class QueueConsumerTest(unittest.TestCase):
    def test_queue_job_decodes_contract(self) -> None:
        job = QueueJob.from_json(json.dumps({
            "id": "job-1",
            "type": "ingest.source",
            "payload": {"source": "evand"},
            "attempts": 2,
        }))

        self.assertEqual("job-1", job.id)
        self.assertEqual("ingest.source", job.type)
        self.assertEqual("evand", job.payload["source"])
        self.assertEqual(2, job.attempts)

    def test_consume_once_processes_valid_message(self) -> None:
        consumer = QueueConsumer(FakeRedis(json.dumps({
            "id": "job-2",
            "type": "normalize.event",
            "payload": {},
        })), "rokhdad:test")

        result = consumer.consume_once()

        self.assertEqual("processed", result.status)
        self.assertEqual("job-2", result.job_id)
        self.assertEqual("normalize.event", result.job_type)

    def test_consume_once_reports_empty_queue(self) -> None:
        result = QueueConsumer(FakeRedis(None), "rokhdad:test").consume_once()

        self.assertEqual("empty", result.status)

    def test_consume_once_reports_invalid_message(self) -> None:
        result = QueueConsumer(FakeRedis("not-json"), "rokhdad:test").consume_once()

        self.assertEqual("failed", result.status)
        self.assertIsNotNone(result.error)


if __name__ == "__main__":
    unittest.main()
