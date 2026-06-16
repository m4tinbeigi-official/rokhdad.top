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

BASE_URL = "https://api.bilitmaster.com/api"
DEFAULT_DELAY = 0.5

# All public listing endpoints (no auth required)
PUBLIC_LISTING_ENDPOINTS = [
    "/getHomeEvents",
    "/getEvents",
    "/getInternationalEvents",
    "/getMarketEvents",
]

# Keys that may carry event lists inside the response
HOME_EVENT_KEYS = ("top_events", "new_events", "new_markets")


@dataclass(frozen=True)
class RawEventPayload:
    source_key: str
    external_id: str
    fetched_at: str
    payload: dict[str, Any]


def _http_post(url: str, body: dict[str, Any] | None = None, timeout: int = 30) -> dict[str, Any]:
    data = json.dumps(body or {}).encode("utf-8")
    req = Request(
        url,
        data=data,
        headers={
            "Accept": "application/json",
            "Content-Type": "application/json",
            "User-Agent": "rokhdad-worker/1.0",
        },
        method="POST",
    )
    with urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


def _extract_external_id(record: dict[str, Any]) -> str | None:
    for field in ("id", "event_id"):
        value = record.get(field)
        if value is not None:
            return str(value)
    return None


def _extract_events_from_home_response(data: dict[str, Any]) -> list[dict[str, Any]]:
    """
    /getHomeEvents wraps events in nested keys like top_events.items[].events[]
    """
    results: list[dict[str, Any]] = []

    for section_key in HOME_EVENT_KEYS:
        section = data.get(section_key, {})
        if not isinstance(section, dict):
            continue

        items = section.get("items", [])
        if not isinstance(items, list):
            continue

        for item in items:
            if not isinstance(item, dict):
                continue
            events = item.get("events", [])
            if isinstance(events, list):
                results.extend(e for e in events if isinstance(e, dict))

    # Also check top-level "data" key
    top_data = data.get("data", [])
    if isinstance(top_data, list):
        results.extend(e for e in top_data if isinstance(e, dict))

    return results


def _extract_events_from_response(endpoint: str, data: dict[str, Any]) -> list[dict[str, Any]]:
    if endpoint == "/getHomeEvents":
        return _extract_events_from_home_response(data)

    # Standard endpoints: data key is a list
    records = data.get("data", data.get("events", []))
    if isinstance(records, list):
        return [r for r in records if isinstance(r, dict)]

    return []


class BilitmasterRawCollector:
    source_key = "bilitmaster"

    def __init__(
        self,
        fixture_path: Path | None = None,
        page_delay: float = DEFAULT_DELAY,
    ) -> None:
        self.fixture_path = fixture_path
        self.page_delay = page_delay

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
        records = raw.get("data", raw) if isinstance(raw, dict) else raw
        if not isinstance(records, list):
            raise ValueError("BilitMaster fixture must be a list or object with a 'data' list.")
        records = [r for r in records if isinstance(r, dict)]
        return records[:limit] if limit is not None else records

    def _fetch_all(self, limit: int | None) -> list[dict[str, Any]]:
        all_records: list[dict[str, Any]] = []

        for endpoint in PUBLIC_LISTING_ENDPOINTS:
            url = f"{BASE_URL}{endpoint}"
            try:
                data = _http_post(url)
            except (HTTPError, URLError, json.JSONDecodeError) as exc:
                print(
                    json.dumps({"warning": f"bilitmaster {endpoint} failed: {exc}"}),
                    file=sys.stderr, flush=True,
                )
                if self.page_delay > 0:
                    time.sleep(self.page_delay)
                continue

            records = _extract_events_from_response(endpoint, data)
            all_records.extend(records)

            print(
                json.dumps({
                    "source": "bilitmaster",
                    "endpoint": endpoint,
                    "fetched": len(records),
                    "total_so_far": len(all_records),
                }),
                file=sys.stderr, flush=True,
            )

            if limit is not None and len(all_records) >= limit:
                break

            if self.page_delay > 0:
                time.sleep(self.page_delay)

        return all_records[:limit] if limit is not None else all_records

    def fetch_event_detail(self, event_id: int | str) -> dict[str, Any] | None:
        """Fetch full detail for a single event via /pageGetEvent."""
        url = f"{BASE_URL}/pageGetEvent?id={event_id}"
        try:
            data = _http_post(url)
            if data.get("status"):
                return data
        except (HTTPError, URLError, json.JSONDecodeError) as exc:
            print(
                json.dumps({"warning": f"bilitmaster detail {event_id} failed: {exc}"}),
                file=sys.stderr, flush=True,
            )
        return None

    def fetch_initial(self) -> dict[str, Any] | None:
        """Fetch categories, states, and site info."""
        url = f"{BASE_URL}/getInitial"
        try:
            return _http_post(url)
        except (HTTPError, URLError, json.JSONDecodeError):
            return None


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-bilitmaster-collector")
    parser.add_argument("--fixture", type=Path)
    parser.add_argument("--limit", type=int)
    parser.add_argument("--page-delay", type=float, default=DEFAULT_DELAY)
    args = parser.parse_args(list(argv) if argv is not None else None)

    collector = BilitmasterRawCollector(
        fixture_path=args.fixture,
        page_delay=args.page_delay,
    )
    payloads = collector.collect(limit=args.limit)

    print(json.dumps([asdict(payload) for payload in payloads], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
