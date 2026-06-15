import json
import subprocess
import sys
import unittest
from pathlib import Path

from rokhdad_workers.normalizers.evand import normalize_evand_payloads
from rokhdad_workers.sources.evand import EvandRawCollector


FIXTURE = Path(__file__).parent / "fixtures" / "evand_events.json"


class EvandNormalizerTest(unittest.TestCase):
    def test_normalizes_evand_fixture_payloads(self) -> None:
        raw_payloads = EvandRawCollector(fixture_path=FIXTURE).collect()
        normalized = normalize_evand_payloads(raw_payloads)

        self.assertEqual(2, len(normalized))
        self.assertEqual("evand:101", normalized[0].canonical_key)
        self.assertEqual("AI Product Management", normalized[0].title)
        self.assertEqual("2026-07-01T09:00:00+03:30", normalized[0].starts_at)
        self.assertEqual("in_person", normalized[0].event_type)
        self.assertEqual("Tehran", normalized[0].location.city_name)
        self.assertEqual("Evand Test Organizer", normalized[0].organizer.name)
        self.assertEqual("https://evand.com/events/101", normalized[0].canonical_url)

    def test_normalizes_online_evand_event(self) -> None:
        raw_payloads = EvandRawCollector(fixture_path=FIXTURE).collect()
        normalized = normalize_evand_payloads(raw_payloads)

        self.assertEqual("evand:102", normalized[1].canonical_key)
        self.assertEqual("online", normalized[1].event_type)
        self.assertEqual("Online", normalized[1].location.city_name)
        self.assertEqual("https://evand.com/events/102", normalized[1].online_url)

    def test_cli_outputs_normalized_events(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.normalizers.evand", "--fixture", str(FIXTURE), "--limit", "1"],
            check=True,
            capture_output=True,
            text=True,
        )

        payloads = json.loads(result.stdout)

        self.assertEqual(1, len(payloads))
        self.assertEqual("evand:101", payloads[0]["canonical_key"])
        self.assertEqual("evand", payloads[0]["metadata"]["source"])


if __name__ == "__main__":
    unittest.main()
