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


@dataclass(frozen=True)
class ImageVariantResult:
    variant: str
    storage_path: str
    width: int
    height: int
    content_type: str
    byte_size: int
    sha256: str

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


@dataclass(frozen=True)
class ImageModerationMetadata:
    storage_path: str
    content_type: str
    width: int
    height: int
    byte_size: int
    sha256: str
    flags: tuple[str, ...]
    reviewed_at: str = field(default_factory=lambda: datetime.now(UTC).isoformat())

    @property
    def needs_review(self) -> bool:
        return bool(self.flags)

    def to_dict(self) -> dict[str, Any]:
        return {**asdict(self), "needs_review": self.needs_review}


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


def create_image_variants(original_path: Path, widths: Iterable[int], output_dir: Path | None = None) -> list[ImageVariantResult]:
    from PIL import Image

    target_dir = output_dir or original_path.parent / "variants"
    target_dir.mkdir(parents=True, exist_ok=True)
    variants: list[ImageVariantResult] = []

    with Image.open(original_path) as image:
        source_width, source_height = image.size
        for requested_width in widths:
            if requested_width <= 0:
                raise ValueError("variant widths must be positive integers")

            width = min(requested_width, source_width)
            height = max(1, round(source_height * (width / source_width)))
            variant = image.copy()
            if (width, height) != image.size:
                variant = variant.resize((width, height), Image.Resampling.LANCZOS)

            variant_name = f"w{width}"
            target_path = target_dir / f"{original_path.stem}-{variant_name}.webp"
            variant.save(target_path, format="WEBP", quality=85)
            content = target_path.read_bytes()
            variants.append(ImageVariantResult(
                variant=variant_name,
                storage_path=str(target_path),
                width=width,
                height=height,
                content_type="image/webp",
                byte_size=len(content),
                sha256=hashlib.sha256(content).hexdigest(),
            ))

    return variants


def analyze_image_metadata(
    image_path: Path,
    min_width: int = 320,
    min_height: int = 180,
    allowed_content_types: Iterable[str] = ("image/jpeg", "image/png", "image/webp"),
) -> ImageModerationMetadata:
    from PIL import Image

    with Image.open(image_path) as image:
        width, height = image.size
        content_type = Image.MIME.get(image.format or "", "application/octet-stream")

    content = image_path.read_bytes()
    flags: list[str] = []
    allowed = set(allowed_content_types)

    if content_type not in allowed:
        flags.append("unsupported_content_type")
    if width < min_width or height < min_height:
        flags.append("too_small")

    return ImageModerationMetadata(
        storage_path=str(image_path),
        content_type=content_type,
        width=width,
        height=height,
        byte_size=len(content),
        sha256=hashlib.sha256(content).hexdigest(),
        flags=tuple(flags),
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
    parser.add_argument("--variants", help="Comma-separated variant widths, for example 320,640.")
    parser.add_argument("--moderation-metadata", action="store_true")
    parsed = parser.parse_args(args)

    result = download_image(load_image_job_fixture(parsed.fixture), parsed.output_dir)
    variants = []
    if parsed.variants:
        widths = [int(width.strip()) for width in parsed.variants.split(",") if width.strip()]
        variants = [variant.to_dict() for variant in create_image_variants(Path(result.storage_path), widths)]

    moderation_metadata = None
    if parsed.moderation_metadata:
        moderation_metadata = analyze_image_metadata(Path(result.storage_path)).to_dict()

    print(
        json.dumps(
            {**result.to_dict(), "variants": variants, "moderation_metadata": moderation_metadata},
            ensure_ascii=False,
            sort_keys=True,
        ),
        flush=True,
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
