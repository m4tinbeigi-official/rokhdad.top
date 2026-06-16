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

BASE_URL = "https://api.eseminar.tv/api/v1"
DEFAULT_PER_PAGE = 50
DEFAULT_MAX_PAGES = 200
DEFAULT_DELAY = 0.5

# All public listing endpoints to harvest
LISTING_ENDPOINTS = [
    "/webinars",
    "/latest_webinars",
    "/special_webinars",
    "/video_webinars",
    "/free_webinars",
]


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


def _extract_external_id(record: dict[str, Any]) -> str | None:
    for field in ("id", "webinar_id", "event_id"):
        value = record.get(field)
        if value is not None:
            return str(value)
    return None


class EseminarRawCollector:
    source_key = "eseminar"

    def __init__(
        self,
        fixture_path: Path | None = None,
        api_url: str | None = None,
        per_page: int = DEFAULT_PER_PAGE,
        max_pages: int = DEFAULT_MAX_PAGES,
        page_delay: float = DEFAULT_DELAY,
        fetch_all_endpoints: bool = True,
    ) -> None:
        self.fixture_path = fixture_path
        self.api_url = api_url
        self.per_page = per_page
        self.max_pages = max_pages
        self.page_delay = page_delay
        self.fetch_all_endpoints = fetch_all_endpoints

    def collect(self, limit: int | None = None) -> list[RawEventPayload]:
        records = self._load_records(limit=limit)
        fetched_at = datetime.now(UTC).isoformat()
        seen_ids: set[str] = set()
        payloads: list[RawEventPayload] = []

        for record in records:
            ext_id = _extract_external_id(record)
            if not ext_id or ext_id in seen_ids:
                continue
            seen_ids.add(ext_id)
            payloads.append(RawEventPayload(
                source_key=self.source_key,
                external_id=ext_id,
                fetched_at=fetched_at,
                payload=record,
            ))

        return payloads

    def _load_records(self, limit: int | None = None) -> list[dict[str, Any]]:
        if self.fixture_path is not None:
            return self._load_from_fixture(limit)
        return self._fetch_all(limit)

    def _load_from_fixture(self, limit: int | None) -> list[dict[str, Any]]:
        with self.fixture_path.open("r", encoding="utf-8") as handle:
            raw = json.load(handle)
        records = raw.get("data", raw.get("webinars", raw)) if isinstance(raw, dict) else raw
        if not isinstance(records, list):
            raise ValueError("Eseminar fixture must be a list or object with a 'data'/'webinars' list.")
        records = [r for r in records if isinstance(r, dict)]
        return records[:limit] if limit is not None else records

    def _fetch_all(self, limit: int | None) -> list[dict[str, Any]]:
        all_records: list[dict[str, Any]] = []

        if self.api_url:
            # Single endpoint mode
            all_records.extend(self._fetch_paginated(self.api_url, limit))
        elif self.fetch_all_endpoints:
            # Harvest all public endpoints
            for endpoint in LISTING_ENDPOINTS:
                url = f"{BASE_URL}{endpoint}"
                fetched = self._fetch_paginated(url, limit=None)
                all_records.extend(fetched)
                if self.page_delay > 0:
                    time.sleep(self.page_delay)
        else:
            all_records.extend(self._fetch_paginated(f"{BASE_URL}/webinars", limit))

        return all_records[:limit] if limit is not None else all_records

    def _fetch_paginated(self, base_url: str, limit: int | None) -> list[dict[str, Any]]:
        records: list[dict[str, Any]] = []
        page = 1
        supports_pagination = True

        while page <= self.max_pages:
            if supports_pagination:
                url = f"{base_url}?page={page}&per_page={self.per_page}"
            else:
                url = base_url

            try:
                data = _http_get(url)
            except (HTTPError, URLError, json.JSONDecodeError) as exc:
                print(
                    json.dumps({"warning": f"eseminar {url} failed: {exc}"}),
                    file=sys.stderr, flush=True,
                )
                break

            items = data.get("data", [])
            if isinstance(items, dict):
                # Sometimes data is a dict with nested list
                items = list(items.values()) if items else []

            if not isinstance(items, list) or not items:
                break

            records.extend(r for r in items if isinstance(r, dict))

            # Check if this endpoint supports pagination
            pagination = data.get("pagination")
            if not pagination or not isinstance(pagination, dict):
                # Endpoint doesn't paginate (e.g. /latest_webinars, /free_webinars)
                supports_pagination = False
                break

            total = pagination.get("total", 0)
            per_page = pagination.get("per_page", self.per_page)
            total_pages = (total + per_page - 1) // per_page if total and per_page else 1

            print(
                json.dumps({
                    "source": "eseminar",
                    "endpoint": base_url,
                    "page": page,
                    "total_pages": total_pages,
                    "fetched_so_far": len(records),
                }),
                file=sys.stderr, flush=True,
            )

            if limit is not None and len(records) >= limit:
                break
            if page >= total_pages:
                break

            page += 1
            if self.page_delay > 0:
                time.sleep(self.page_delay)

        return records


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-eseminar-collector")
    parser.add_argument("--fixture", type=Path)
    parser.add_argument("--api-url")
    parser.add_argument("--limit", type=int)
    parser.add_argument("--per-page", type=int, default=DEFAULT_PER_PAGE)
    parser.add_argument("--max-pages", type=int, default=DEFAULT_MAX_PAGES)
    parser.add_argument("--page-delay", type=float, default=DEFAULT_DELAY)
    parser.add_argument("--no-all-endpoints", action="store_true")
    args = parser.parse_args(list(argv) if argv is not None else None)

    collector = EseminarRawCollector(
        fixture_path=args.fixture,
        api_url=args.api_url,
        per_page=args.per_page,
        max_pages=args.max_pages,
        page_delay=args.page_delay,
        fetch_all_endpoints=not args.no_all_endpoints,
    )
    payloads = collector.collect(limit=args.limit)

    print(json.dumps([asdict(payload) for payload in payloads], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
