import json
import subprocess
import sys
import unittest
from pathlib import Path

from rokhdad_workers.sources.eseminar import EseminarRawCollector


FIXTURE = Path(__file__).parent / "fixtures" / "eseminar_events.json"


class EseminarCollectorTest(unittest.TestCase):
    def test_collects_raw_payloads_from_fixture(self) -> None:
        payloads = EseminarRawCollector(fixture_path=FIXTURE).collect()

        self.assertEqual(2, len(payloads))
        self.assertEqual("eseminar", payloads[0].source_key)
        self.assertEqual("web-201", payloads[0].external_id)
        self.assertEqual("Marketing Analytics Webinar", payloads[0].payload["title"])

    def test_cli_outputs_raw_payload_envelope(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.sources.eseminar", "--fixture", str(FIXTURE), "--limit", "1"],
            check=True,
            capture_output=True,
            text=True,
        )

        payloads = json.loads(result.stdout)

        self.assertEqual(1, len(payloads))
        self.assertEqual("eseminar", payloads[0]["source_key"])
        self.assertEqual("web-201", payloads[0]["external_id"])


if __name__ == "__main__":
    unittest.main()
