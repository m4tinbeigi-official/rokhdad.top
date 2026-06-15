import json
import unittest

from rokhdad_workers.queue import QueueConsumer, QueueJob


class FakeRedis:
    def __init__(self, message: str | None) -> None:
        self.messages = [] if message is None else [message]
        self.locks: set[str] = set()

    def blpop(self, keys: list[str], timeout: int = 0):
        if not self.messages:
            return None

        return keys[0], self.messages.pop(0)

    def set(self, name: str, value: str, nx: bool = False, ex: int | None = None) -> bool | None:
        if nx and name in self.locks:
            return None

        self.locks.add(name)
        return True

    def delete(self, name: str) -> int:
        if name in self.locks:
            self.locks.remove(name)
            return 1

        return 0

    def rpush(self, name: str, value: str) -> int:
        self.messages.append(value)
        return len(self.messages)


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

    def test_consume_once_requeues_failed_job_until_max_attempts(self) -> None:
        client = FakeRedis(json.dumps({
            "id": "job-3",
            "type": "ingest.source",
            "payload": {"source": "evand"},
            "attempts": 0,
        }))

        def fail(job: QueueJob) -> None:
            raise RuntimeError("source timeout")

        result = QueueConsumer(client, "rokhdad:test", handler=fail, max_attempts=3).consume_once()
        retried = QueueJob.from_json(client.messages[0])

        self.assertEqual("retrying", result.status)
        self.assertEqual(1, retried.attempts)

    def test_consume_once_fails_after_max_attempts(self) -> None:
        client = FakeRedis(json.dumps({
            "id": "job-4",
            "type": "ingest.source",
            "payload": {"source": "evand"},
            "attempts": 2,
        }))

        def fail(job: QueueJob) -> None:
            raise RuntimeError("source timeout")

        result = QueueConsumer(client, "rokhdad:test", handler=fail, max_attempts=3).consume_once()

        self.assertEqual("failed", result.status)
        self.assertEqual([], client.messages)

    def test_consume_once_reports_locked_job(self) -> None:
        client = FakeRedis(json.dumps({
            "id": "job-5",
            "type": "ingest.source",
            "payload": {},
        }))
        client.locks.add("lock:rokhdad:test:job-5")

        result = QueueConsumer(client, "rokhdad:test").consume_once()

        self.assertEqual("locked", result.status)
        self.assertEqual("job-5", result.job_id)


if __name__ == "__main__":
    unittest.main()
