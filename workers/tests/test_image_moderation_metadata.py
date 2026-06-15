import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from PIL import Image

from rokhdad_workers.images import analyze_image_metadata


FIXTURE = Path(__file__).parent / "fixtures" / "image_download_job.json"


class ImageModerationMetadataTest(unittest.TestCase):
    def test_analyzes_image_dimensions_and_flags_small_images(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            original = Path(temp_dir) / "small.png"
            Image.new("RGB", (100, 50), color=(255, 0, 0)).save(original)

            metadata = analyze_image_metadata(original, min_width=320, min_height=180)

            self.assertEqual(100, metadata.width)
            self.assertEqual(50, metadata.height)
            self.assertEqual("image/png", metadata.content_type)
            self.assertTrue(metadata.needs_review)
            self.assertIn("too_small", metadata.flags)

    def test_accepts_large_allowed_images_without_flags(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            original = Path(temp_dir) / "large.png"
            Image.new("RGB", (640, 360), color=(255, 0, 0)).save(original)

            metadata = analyze_image_metadata(original, min_width=320, min_height=180)

            self.assertFalse(metadata.needs_review)
            self.assertEqual((), metadata.flags)

    def test_cli_can_include_moderation_metadata(self) -> None:
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
                    "--moderation-metadata",
                ],
                check=True,
                capture_output=True,
                text=True,
            )

            payload = json.loads(result.stdout)

            self.assertIsNotNone(payload["moderation_metadata"])
            self.assertEqual("image/png", payload["moderation_metadata"]["content_type"])
            self.assertTrue(payload["moderation_metadata"]["needs_review"])


if __name__ == "__main__":
    unittest.main()
