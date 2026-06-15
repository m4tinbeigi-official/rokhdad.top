import json
import subprocess
import sys
import unittest
from pathlib import Path

from rokhdad_workers.enrichment import ENRICHMENT_JOB_TYPE, EnrichmentJob, build_enrichment_job, load_job_fixture
from rokhdad_workers.queue import QueueJob


FIXTURE = Path(__file__).parent / "fixtures" / "enrichment_job.json"


class EnrichmentJobTest(unittest.TestCase):
    def test_loads_fixture_and_converts_to_queue_job(self) -> None:
        enrichment_job = load_job_fixture(FIXTURE)
        queue_job = enrichment_job.to_queue_job()

        self.assertEqual("enrich-fixture-101", queue_job.id)
        self.assertEqual(ENRICHMENT_JOB_TYPE, queue_job.type)
        self.assertEqual("evand:101", queue_job.payload["canonical_key"])
        self.assertEqual(["seo", "summary", "category"], list(queue_job.payload["targets"]))

    def test_rehydrates_from_queue_job(self) -> None:
        queue_job = QueueJob(
            id="job-1",
            type=ENRICHMENT_JOB_TYPE,
            payload={
                "canonical_key": "evand:101",
                "targets": ["seo"],
                "normalized_event": {"canonical_key": "evand:101", "title": "AI Product Management"},
            },
        )

        enrichment_job = EnrichmentJob.from_queue_job(queue_job)

        self.assertEqual("job-1", enrichment_job.job_id)
        self.assertEqual(("seo",), enrichment_job.targets)

    def test_builds_job_from_normalized_event(self) -> None:
        enrichment_job = build_enrichment_job(
            {
                "canonical_key": "eseminar:web-201",
                "source_key": "eseminar",
                "title": "Marketing Analytics Webinar",
            },
            targets=["summary", "people"],
            priority=2,
        )

        self.assertEqual(("eseminar",), enrichment_job.source_keys)
        self.assertEqual(2, enrichment_job.priority)

    def test_rejects_unknown_target(self) -> None:
        with self.assertRaises(ValueError):
            EnrichmentJob(
                canonical_key="evand:101",
                targets=("unknown",),
                normalized_event={"canonical_key": "evand:101"},
            )

    def test_cli_outputs_queue_contract(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.enrichment", "--fixture", str(FIXTURE)],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual("rokhdad:jobs:enrichment", payload["queue"])
        self.assertEqual(ENRICHMENT_JOB_TYPE, payload["job"]["type"])
        self.assertEqual("evand:101", payload["job"]["payload"]["canonical_key"])


if __name__ == "__main__":
    unittest.main()
