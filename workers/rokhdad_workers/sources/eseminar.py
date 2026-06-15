from __future__ import annotations

import argparse
import json
import sys
from dataclasses import asdict, dataclass
from datetime import UTC, datetime
from pathlib import Path
from typing import Any, Iterable
from urllib.request import urlopen


@dataclass(frozen=True)
class RawEventPayload:
    source_key: str
    external_id: str
    fetched_at: str
    payload: dict[str, Any]


class EseminarRawCollector:
    source_key = "eseminar"

    def __init__(self, fixture_path: Path | None = None, api_url: str | None = None) -> None:
        self.fixture_path = fixture_path
        self.api_url = api_url

    def collect(self, limit: int | None = None) -> list[RawEventPayload]:
        records = self._load_records()
        selected = records[:limit] if limit is not None else records
        fetched_at = datetime.now(UTC).isoformat()

        return [
            RawEventPayload(
                source_key=self.source_key,
                external_id=str(record.get("id") or record.get("webinar_id") or record.get("event_id")),
                fetched_at=fetched_at,
                payload=record,
            )
            for record in selected
        ]

    def _load_records(self) -> list[dict[str, Any]]:
        if self.fixture_path is not None:
            with self.fixture_path.open("r", encoding="utf-8") as handle:
                raw = json.load(handle)
        elif self.api_url is not None:
            with urlopen(self.api_url, timeout=20) as response:
                raw = json.load(response)
        else:
            raise ValueError("Either fixture_path or api_url is required.")

        records = raw.get("data", raw.get("webinars", raw)) if isinstance(raw, dict) else raw
        if not isinstance(records, list):
            raise ValueError("Eseminar raw response must be a list or an object with a data/webinars list.")

        return [record for record in records if isinstance(record, dict)]


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-eseminar-collector")
    parser.add_argument("--fixture", type=Path)
    parser.add_argument("--api-url")
    parser.add_argument("--limit", type=int)
    args = parser.parse_args(list(argv) if argv is not None else None)

    collector = EseminarRawCollector(fixture_path=args.fixture, api_url=args.api_url)
    payloads = collector.collect(limit=args.limit)

    print(json.dumps([asdict(payload) for payload in payloads], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
