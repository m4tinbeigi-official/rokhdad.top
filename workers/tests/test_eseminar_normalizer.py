import json
import subprocess
import sys
import unittest
from pathlib import Path

from rokhdad_workers.normalizers.eseminar import normalize_eseminar_payloads
from rokhdad_workers.sources.eseminar import EseminarRawCollector


FIXTURE = Path(__file__).parent / "fixtures" / "eseminar_events.json"


class EseminarNormalizerTest(unittest.TestCase):
    def test_normalizes_eseminar_fixture_payloads(self) -> None:
        raw_payloads = EseminarRawCollector(fixture_path=FIXTURE).collect()
        normalized = normalize_eseminar_payloads(raw_payloads)

        self.assertEqual(2, len(normalized))
        self.assertEqual("eseminar:web-201", normalized[0].canonical_key)
        self.assertEqual("Marketing Analytics Webinar", normalized[0].title)
        self.assertEqual("2026-07-10T10:00:00+03:30", normalized[0].starts_at)
        self.assertEqual("2026-07-10T11:30:00+03:30", normalized[0].ends_at)
        self.assertEqual("online", normalized[0].event_type)
        self.assertEqual("https://eseminar.tv/webinar/web-201", normalized[0].online_url)
        self.assertEqual("Eseminar Instructor", normalized[0].people[0].name)
        self.assertEqual("teacher", normalized[0].people[0].role_title)

    def test_duration_drives_end_datetime(self) -> None:
        raw_payloads = EseminarRawCollector(fixture_path=FIXTURE).collect()
        normalized = normalize_eseminar_payloads(raw_payloads)

        self.assertEqual("2026-07-12T16:00:00+03:30", normalized[1].ends_at)
        self.assertEqual(120, normalized[1].metadata["duration_minutes"])

    def test_cli_outputs_normalized_events(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.normalizers.eseminar", "--fixture", str(FIXTURE), "--limit", "1"],
            check=True,
            capture_output=True,
            text=True,
        )

        payloads = json.loads(result.stdout)

        self.assertEqual(1, len(payloads))
        self.assertEqual("eseminar:web-201", payloads[0]["canonical_key"])
        self.assertEqual("eseminar", payloads[0]["metadata"]["source"])


if __name__ == "__main__":
    unittest.main()
