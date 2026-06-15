from __future__ import annotations

import argparse
import base64
import hashlib
import json
import sys
import uuid
from dataclasses import asdict, dataclass, field
from datetime import UTC, datetime
from pathlib import Path
from typing import Any, Iterable
from urllib.request import Request, urlopen

from rokhdad_workers.cli import run_worker


@dataclass(frozen=True)
class ImageDownloadJob:
    canonical_key: str
    image_url: str
    source_url: str | None = None
    image_role: str = "cover"
    requested_at: str = field(default_factory=lambda: datetime.now(UTC).isoformat())
    job_id: str = field(default_factory=lambda: str(uuid.uuid4()))

    def __post_init__(self) -> None:
        if not self.canonical_key.strip():
            raise ValueError("canonical_key is required")
        if not self.image_url.strip():
            raise ValueError("image_url is required")

    @classmethod
    def from_dict(cls, payload: dict[str, Any]) -> ImageDownloadJob:
        return cls(
            canonical_key=str(payload.get("canonical_key", "")),
            image_url=str(payload.get("image_url", "")),
            source_url=payload.get("source_url"),
            image_role=str(payload.get("image_role") or "cover"),
            requested_at=str(payload.get("requested_at") or datetime.now(UTC).isoformat()),
            job_id=str(payload.get("job_id") or uuid.uuid4()),
        )


@dataclass(frozen=True)
class ImageDownloadResult:
    canonical_key: str
    image_role: str
    storage_path: str
    content_type: str
    byte_size: int
    sha256: str
    downloaded_at: str = field(default_factory=lambda: datetime.now(UTC).isoformat())

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


def download_image(job: ImageDownloadJob, storage_root: Path) -> ImageDownloadResult:
    content, content_type = fetch_image(job.image_url)
    extension = extension_for_content_type(content_type) or extension_from_url(job.image_url) or ".bin"
    filename = f"{safe_filename(job.canonical_key)}-{safe_filename(job.image_role)}{extension}"
    target_dir = storage_root / "events" / safe_filename(job.canonical_key)
    target_dir.mkdir(parents=True, exist_ok=True)
    target_path = target_dir / filename
    target_path.write_bytes(content)

    return ImageDownloadResult(
        canonical_key=job.canonical_key,
        image_role=job.image_role,
        storage_path=str(target_path),
        content_type=content_type,
        byte_size=len(content),
        sha256=hashlib.sha256(content).hexdigest(),
    )


def fetch_image(url: str) -> tuple[bytes, str]:
    if url.startswith("data:"):
        return fetch_data_url(url)

    request = Request(url, headers={"User-Agent": "rokhdad-worker/1.0"})
    with urlopen(request, timeout=30) as response:
        content_type = response.headers.get_content_type()
        return response.read(), content_type


def fetch_data_url(url: str) -> tuple[bytes, str]:
    header, encoded = url.split(",", 1)
    content_type = header[5:].split(";", 1)[0] or "application/octet-stream"
    if ";base64" in header:
        return base64.b64decode(encoded), content_type
    return encoded.encode("utf-8"), content_type


def extension_for_content_type(content_type: str) -> str | None:
    return {
        "image/jpeg": ".jpg",
        "image/png": ".png",
        "image/webp": ".webp",
        "image/gif": ".gif",
    }.get(content_type)


def extension_from_url(url: str) -> str | None:
    suffix = Path(url.split("?", 1)[0]).suffix.lower()
    return suffix if suffix in {".jpg", ".jpeg", ".png", ".webp", ".gif"} else None


def safe_filename(value: str) -> str:
    return "".join(char if char.isalnum() or char in {"-", "_"} else "-" for char in value).strip("-") or "image"


def load_image_job_fixture(path: Path) -> ImageDownloadJob:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("Image fixture must be a JSON object.")
    return ImageDownloadJob.from_dict(payload)


def main(argv: Iterable[str] | None = None) -> int:
    args = list(argv) if argv is not None else sys.argv[1:]
    if "--fixture" not in args:
        return run_worker("images", args)

    parser = argparse.ArgumentParser(prog="rokhdad-image-worker")
    parser.add_argument("--fixture", type=Path, required=True)
    parser.add_argument("--output-dir", type=Path, required=True)
    parsed = parser.parse_args(args)

    result = download_image(load_image_job_fixture(parsed.fixture), parsed.output_dir)
    print(json.dumps(result.to_dict(), ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
