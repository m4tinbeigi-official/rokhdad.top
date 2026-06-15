from __future__ import annotations

import argparse
import json
import sys
from dataclasses import asdict, is_dataclass
from pathlib import Path
from typing import Any, Iterable

from rokhdad_workers.normalization import (
    NormalizedEvent,
    NormalizedLocation,
    NormalizedOrganizerRef,
    NormalizedPersonRef,
)
from rokhdad_workers.sources.evand import EvandRawCollector


ONLINE_CITY_VALUES = frozenset({"online", "virtual", "webinar"})


def normalize_evand_payload(raw_payload: Any, raw_snapshot_id: str | None = None) -> NormalizedEvent:
    envelope = asdict(raw_payload) if is_dataclass(raw_payload) else dict(raw_payload)
    payload = envelope.get("payload") or {}
    if not isinstance(payload, dict):
        raise ValueError("Evand raw payload must contain a payload object.")

    external_id = str(envelope.get("external_id") or payload.get("id") or payload.get("event_id") or "")
    title = first_string(payload, "title", "name")
    source_url = first_string(payload, "url", "canonical_url", "event_url")
    starts_at = first_string(payload, "starts_at", "start_at", "start_date")
    ends_at = first_string(payload, "ends_at", "end_at", "end_date")
    city_name = first_string(payload, "city", "city_name")
    event_type = infer_event_type(payload, city_name)

    return NormalizedEvent(
        source_key=str(envelope.get("source_key") or "evand"),
        external_id=external_id,
        title=title,
        starts_at=starts_at,
        ends_at=ends_at,
        event_type=event_type,
        summary=first_string(payload, "summary", "subtitle"),
        description=first_string(payload, "description", "body"),
        canonical_url=source_url,
        online_url=first_string(payload, "online_url", "join_url") or (source_url if event_type == "online" else None),
        category_name=extract_category_name(payload),
        location=extract_location(payload, city_name, event_type),
        organizer=extract_organizer(payload),
        people=tuple(extract_people(payload)),
        metadata={
            "source": "evand",
            "source_status": payload.get("status"),
            "source_price": payload.get("price"),
        },
        raw_snapshot_id=raw_snapshot_id,
    )


def normalize_evand_payloads(raw_payloads: Iterable[Any]) -> list[NormalizedEvent]:
    return [normalize_evand_payload(payload) for payload in raw_payloads]


def first_string(payload: dict[str, Any], *keys: str) -> str | None:
    for key in keys:
        value = payload.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
        if isinstance(value, (int, float)):
            return str(value)
    return None


def infer_event_type(payload: dict[str, Any], city_name: str | None) -> str:
    if bool(payload.get("is_online")):
        return "online"

    if city_name and city_name.strip().lower() in ONLINE_CITY_VALUES:
        return "online"

    if first_string(payload, "online_url", "join_url"):
        return "hybrid"

    return "in_person"


def extract_location(payload: dict[str, Any], city_name: str | None, event_type: str) -> NormalizedLocation | None:
    if event_type == "online" and not first_string(payload, "venue_name", "venue", "address"):
        return NormalizedLocation(city_name="Online")

    venue = payload.get("venue")
    venue_name = venue.get("name") if isinstance(venue, dict) else first_string(payload, "venue_name", "venue")
    venue_address = venue.get("address") if isinstance(venue, dict) else first_string(payload, "venue_address", "address")

    return NormalizedLocation(
        city_name=city_name,
        venue_name=venue_name,
        venue_address=venue_address,
        latitude=number_or_none(payload.get("latitude") or payload.get("lat")),
        longitude=number_or_none(payload.get("longitude") or payload.get("lng")),
    )


def extract_organizer(payload: dict[str, Any]) -> NormalizedOrganizerRef | None:
    organizer = payload.get("organizer")
    if isinstance(organizer, dict):
        name = first_string(organizer, "name", "title")
        if name:
            return NormalizedOrganizerRef(name=name, url=first_string(organizer, "url", "website"))

    organizer_name = first_string(payload, "organizer_name")
    return NormalizedOrganizerRef(name=organizer_name) if organizer_name else None


def extract_people(payload: dict[str, Any]) -> list[NormalizedPersonRef]:
    people: list[NormalizedPersonRef] = []
    for key, role_title in (("speakers", "speaker"), ("teachers", "teacher"), ("hosts", "host")):
        values = payload.get(key)
        if not isinstance(values, list):
            continue
        for index, value in enumerate(values):
            name = value.get("name") if isinstance(value, dict) else value
            if isinstance(name, str) and name.strip():
                people.append(NormalizedPersonRef(name=name.strip(), role_title=role_title, sort_order=index))
    return people


def extract_category_name(payload: dict[str, Any]) -> str | None:
    category = payload.get("category")
    if isinstance(category, dict):
        return first_string(category, "name", "title")
    if isinstance(category, str):
        return category.strip() or None
    return first_string(payload, "category_name")


def number_or_none(value: Any) -> float | None:
    if value is None or value == "":
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-evand-normalizer")
    parser.add_argument("--fixture", type=Path, required=True)
    parser.add_argument("--limit", type=int)
    args = parser.parse_args(list(argv) if argv is not None else None)

    payloads = EvandRawCollector(fixture_path=args.fixture).collect(limit=args.limit)
    normalized = normalize_evand_payloads(payloads)

    print(json.dumps([event.to_dict() for event in normalized], ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
