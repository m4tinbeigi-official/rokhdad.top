import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from rokhdad_workers.images import download_image, load_image_job_fixture


FIXTURE = Path(__file__).parent / "fixtures" / "image_download_job.json"


class ImageDownloadWorkerTest(unittest.TestCase):
    def test_downloads_fixture_image_to_storage_path(self) -> None:
        job = load_image_job_fixture(FIXTURE)

        with tempfile.TemporaryDirectory() as temp_dir:
            result = download_image(job, Path(temp_dir))
            stored = Path(result.storage_path)

            self.assertTrue(stored.exists())
            self.assertEqual("image/png", result.content_type)
            self.assertEqual(stored.read_bytes()[:8], b"\x89PNG\r\n\x1a\n")
            self.assertEqual("evand:101", result.canonical_key)
            self.assertGreater(result.byte_size, 0)

    def test_cli_outputs_download_result(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            result = subprocess.run(
                [
                    sys.executable,
                    "-m",
                    "rokhdad_workers.images",
                    "--fixture",
                    str(FIXTURE),
                    "--output-dir",
                    temp_dir,
                ],
                check=True,
                capture_output=True,
                text=True,
            )

            payload = json.loads(result.stdout)

            self.assertEqual("evand:101", payload["canonical_key"])
            self.assertEqual("image/png", payload["content_type"])
            self.assertTrue(Path(payload["storage_path"]).exists())


if __name__ == "__main__":
    unittest.main()
