import json
import subprocess
import sys
import unittest
from pathlib import Path

from rokhdad_workers.deduplication import (
    DUPLICATE_THRESHOLD,
    find_duplicate_candidates,
    load_normalized_events,
    score_event_pair,
)


FIXTURE = Path(__file__).parent / "fixtures" / "normalized_duplicates.json"


class DeduplicationTest(unittest.TestCase):
    def test_scores_strong_duplicate_from_fixture(self) -> None:
        events = load_normalized_events(FIXTURE)
        score = score_event_pair(events[0], events[1])

        self.assertGreaterEqual(score.score, DUPLICATE_THRESHOLD)
        self.assertTrue(score.is_duplicate)
        self.assertIn("starts_at_exact", score.reasons)
        self.assertIn("city_match", score.reasons)

    def test_scores_unrelated_event_below_possible_duplicate_threshold(self) -> None:
        events = load_normalized_events(FIXTURE)
        score = score_event_pair(events[0], events[2])

        self.assertLess(score.score, 65)
        self.assertFalse(score.is_possible_duplicate)

    def test_find_duplicate_candidates_sorts_above_threshold(self) -> None:
        events = load_normalized_events(FIXTURE)
        candidates = find_duplicate_candidates(events[0], events[1:])

        self.assertEqual(1, len(candidates))
        self.assertEqual("manual:dup-101", candidates[0].canonical_key)

    def test_cli_outputs_duplicate_candidates(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.deduplication", "--fixture", str(FIXTURE)],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual("evand:101", payload["target"])
        self.assertEqual("manual:dup-101", payload["candidates"][0]["canonical_key"])
        self.assertTrue(payload["candidates"][0]["is_duplicate"])


if __name__ == "__main__":
    unittest.main()
