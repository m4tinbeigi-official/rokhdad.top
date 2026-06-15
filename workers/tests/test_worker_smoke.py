import json
import os
import subprocess
import sys
import unittest


class WorkerSmokeTest(unittest.TestCase):
    def test_ingestion_module_smoke_outputs_status(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.ingestion", "--smoke"],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual("ingestion", payload["service"])
        self.assertEqual("ok", payload["status"])
        self.assertIn("version", payload)

    def test_placeholder_once_mode_exits_successfully(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.normalization", "--once"],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual("normalization", payload["service"])
        self.assertEqual("idle", payload["status"])

    def test_once_mode_reports_missing_redis_without_queue_failure(self) -> None:
        env = os.environ.copy()
        for key in ["REDIS_URL", "REDIS_HOST", "REDIS_PORT", "REDIS_PASSWORD", "REDIS_DB"]:
            env.pop(key, None)

        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.images", "--once", "--queue", "rokhdad:test"],
            check=True,
            capture_output=True,
            env=env,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual("images", payload["service"])
        self.assertEqual("redis_unconfigured", payload["status"])
        self.assertEqual("rokhdad:test", payload["queue"])


if __name__ == "__main__":
    unittest.main()
