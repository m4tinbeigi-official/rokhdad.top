from __future__ import annotations

import argparse
import json
import sys
from dataclasses import asdict, is_dataclass
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any, Iterable

from rokhdad_workers.normalization import NormalizedEvent, NormalizedPersonRef
from rokhdad_workers.sources.eseminar import EseminarRawCollector


def normalize_eseminar_payload(raw_payload: Any, raw_snapshot_id: str | None = None) -> NormalizedEvent:
    envelope = asdict(raw_payload) if is_dataclass(raw_payload) else dict(raw_payload)
    payload = envelope.get("payload") or {}
    if not isinstance(payload, dict):
        raise ValueError("Eseminar raw payload must contain a payload object.")

    external_id = str(envelope.get("external_id") or payload.get("webinar_id") or payload.get("id") or "")
    starts_at = first_string(payload, "start_date", "starts_at", "start_at")
    ends_at = first_string(payload, "end_date", "ends_at", "end_at") or calculate_end_datetime(
        starts_at,
        payload.get("duration_minutes"),
    )
    source_url = first_string(payload, "url", "canonical_url", "webinar_url")

    return NormalizedEvent(
        source_key=str(envelope.get("source_key") or "eseminar"),
        external_id=external_id,
        title=first_string(payload, "title", "name"),
        starts_at=starts_at,
        ends_at=ends_at,
        event_type="online",
        summary=first_string(payload, "summary", "subtitle"),
        description=first_string(payload, "description", "body"),
        canonical_url=source_url,
        online_url=first_string(payload, "online_url", "join_url") or source_url,
        category_name=extract_category_name(payload),
        people=tuple(extract_people(payload)),
        metadata={
            "source": "eseminar",
            "duration_minutes": payload.get("duration_minutes"),
            "source_status": payload.get("status"),
            "source_price": payload.get("price"),
        },
        raw_snapshot_id=raw_snapshot_id,
    )


def normalize_eseminar_payloads(raw_payloads: Iterable[Any]) -> list[NormalizedEvent]:
    return [normalize_eseminar_payload(payload) for payload in raw_payloads]


def first_string(payload: dict[str, Any], *keys: str) -> str | None:
    for key in keys:
        value = payload.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
        if isinstance(value, (int, float)):
            return str(value)
    return None


def calculate_end_datetime(starts_at: str | None, duration_minutes: Any) -> str | None:
    if not starts_at or duration_minutes in (None, ""):
        return None

    try:
        minutes = int(duration_minutes)
        start = datetime.fromisoformat(starts_at.replace("Z", "+00:00"))
    except (TypeError, ValueError):
        return None

    return (start + timedelta(minutes=minutes)).isoformat()


def extract_people(payload: dict[str, Any]) -> list[NormalizedPersonRef]:
    people: list[NormalizedPersonRef] = []
    teacher = first_string(payload, "teacher", "instructor", "speaker")
    if teacher:
        people.append(NormalizedPersonRef(name=teacher, role_title="teacher", sort_order=0))

    speakers = payload.get("speakers")
    if isinstance(speakers, list):
        start_index = len(people)
        for index, value in enumerate(speakers):
            name = value.get("name") if isinstance(value, dict) else value
            if isinstance(name, str) and name.strip():
                people.append(NormalizedPersonRef(name=name.strip(), role_title="speaker", sort_order=start_index + index))

    return people


def extract_category_name(payload: dict[str, Any]) -> str | None:
    category = payload.get("category")
    if isinstance(category, dict):
        return first_string(category, "name", "title")
    if isinstance(category, str):
        return category.strip() or None
    return first_string(payload, "category_name")


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-eseminar-normalizer")
    parser.add_argument("--fixture", type=Path, required=True)
    parser.add_argument("--limit", type=int)
    args = parser.parse_args(list(argv) if argv is not None else None)

    payloads = EseminarRawCollector(fixture_path=args.fixture).collect(limit=args.limit)
    normalized = normalize_eseminar_payloads(payloads)

    print(json.dumps([event.to_dict() for event in normalized], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
