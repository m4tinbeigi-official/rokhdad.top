from __future__ import annotations

import argparse
import json
import sys
import time
from dataclasses import asdict, dataclass
from datetime import UTC, datetime
from pathlib import Path
from typing import Any, Iterable
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

BASE_URL = "https://api.evand.com"
DEFAULT_PER_PAGE = 50
DEFAULT_MAX_PAGES = 200
DEFAULT_DELAY = 0.5  # seconds between pages


@dataclass(frozen=True)
class RawEventPayload:
    source_key: str
    external_id: str
    fetched_at: str
    payload: dict[str, Any]


def _http_get(url: str, timeout: int = 30) -> dict[str, Any]:
    req = Request(url, headers={"Accept": "application/json", "User-Agent": "rokhdad-worker/1.0"})
    with urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


class EvandRawCollector:
    source_key = "evand"

    def __init__(
        self,
        fixture_path: Path | None = None,
        api_url: str | None = None,
        per_page: int = DEFAULT_PER_PAGE,
        max_pages: int = DEFAULT_MAX_PAGES,
        page_delay: float = DEFAULT_DELAY,
        extra_params: dict[str, str] | None = None,
    ) -> None:
        self.fixture_path = fixture_path
        self.api_url = api_url or f"{BASE_URL}/events"
        self.per_page = per_page
        self.max_pages = max_pages
        self.page_delay = page_delay
        self.extra_params = extra_params or {}

    def collect(self, limit: int | None = None) -> list[RawEventPayload]:
        records = self._load_records(limit=limit)
        fetched_at = datetime.now(UTC).isoformat()

        return [
            RawEventPayload(
                source_key=self.source_key,
                external_id=str(record.get("id") or record.get("event_id", "")),
                fetched_at=fetched_at,
                payload=record,
            )
            for record in records
            if record.get("id") or record.get("event_id")
        ]

    def _load_records(self, limit: int | None = None) -> list[dict[str, Any]]:
        if self.fixture_path is not None:
            return self._load_from_fixture(limit)
        return self._fetch_all_pages(limit)

    def _load_from_fixture(self, limit: int | None) -> list[dict[str, Any]]:
        with self.fixture_path.open("r", encoding="utf-8") as handle:
            raw = json.load(handle)
        records = raw.get("data", raw) if isinstance(raw, dict) else raw
        if not isinstance(records, list):
            raise ValueError("Evand fixture must be a list or object with a 'data' list.")
        records = [r for r in records if isinstance(r, dict)]
        return records[:limit] if limit is not None else records

    def _fetch_all_pages(self, limit: int | None) -> list[dict[str, Any]]:
        all_records: list[dict[str, Any]] = []
        page = 1

        while page <= self.max_pages:
            url = self._build_url(page)
            try:
                data = _http_get(url)
            except (HTTPError, URLError, json.JSONDecodeError) as exc:
                print(json.dumps({"warning": f"evand page {page} failed: {exc}"}), file=sys.stderr, flush=True)
                break

            records = data.get("data", [])
            if not isinstance(records, list) or not records:
                break

            all_records.extend(r for r in records if isinstance(r, dict))

            # Check pagination
            pagination = data.get("meta", {}).get("pagination", {})
            total_pages = pagination.get("total_pages", 1)
            current_page = pagination.get("current_page", page)

            print(
                json.dumps({
                    "source": "evand",
                    "page": current_page,
                    "total_pages": total_pages,
                    "fetched_so_far": len(all_records),
                }),
                file=sys.stderr,
                flush=True,
            )

            if limit is not None and len(all_records) >= limit:
                break

            if current_page >= total_pages:
                break

            page += 1
            if self.page_delay > 0:
                time.sleep(self.page_delay)

        return all_records[:limit] if limit is not None else all_records

    def _build_url(self, page: int) -> str:
        params: dict[str, str] = {
            "page": str(page),
            "per_page": str(self.per_page),
            **self.extra_params,
        }
        query = "&".join(f"{k}={v}" for k, v in params.items())
        base = self.api_url.split("?")[0]
        return f"{base}?{query}"


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-evand-collector")
    parser.add_argument("--fixture", type=Path)
    parser.add_argument("--api-url", default=f"{BASE_URL}/events")
    parser.add_argument("--limit", type=int)
    parser.add_argument("--per-page", type=int, default=DEFAULT_PER_PAGE)
    parser.add_argument("--max-pages", type=int, default=DEFAULT_MAX_PAGES)
    parser.add_argument("--page-delay", type=float, default=DEFAULT_DELAY)
    args = parser.parse_args(list(argv) if argv is not None else None)

    collector = EvandRawCollector(
        fixture_path=args.fixture,
        api_url=args.api_url,
        per_page=args.per_page,
        max_pages=args.max_pages,
        page_delay=args.page_delay,
    )
    payloads = collector.collect(limit=args.limit)

    print(json.dumps([asdict(payload) for payload in payloads], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
