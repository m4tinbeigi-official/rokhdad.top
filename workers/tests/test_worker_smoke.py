import json
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


if __name__ == "__main__":
    unittest.main()
