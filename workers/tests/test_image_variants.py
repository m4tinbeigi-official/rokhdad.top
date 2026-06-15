import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from PIL import Image

from rokhdad_workers.images import create_image_variants


FIXTURE = Path(__file__).parent / "fixtures" / "image_download_job.json"


class ImageVariantsTest(unittest.TestCase):
    def test_creates_resized_webp_variants_with_expected_dimensions(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            original = Path(temp_dir) / "original.png"
            Image.new("RGB", (400, 200), color=(255, 0, 0)).save(original)

            variants = create_image_variants(original, widths=(200, 100))

            self.assertEqual(2, len(variants))
            self.assertEqual((200, 100), (variants[0].width, variants[0].height))
            self.assertEqual((100, 50), (variants[1].width, variants[1].height))
            self.assertTrue(Path(variants[0].storage_path).exists())
            self.assertEqual("image/webp", variants[0].content_type)

    def test_cli_can_download_and_create_variant(self) -> None:
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
                    "--variants",
                    "1",
                ],
                check=True,
                capture_output=True,
                text=True,
            )

            payload = json.loads(result.stdout)

            self.assertEqual(1, len(payload["variants"]))
            self.assertEqual("w1", payload["variants"][0]["variant"])
            self.assertEqual(1, payload["variants"][0]["width"])


if __name__ == "__main__":
    unittest.main()
