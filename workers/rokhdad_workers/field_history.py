from __future__ import annotations

import argparse
import hashlib
import json
import sys
from dataclasses import asdict, dataclass
from datetime import UTC, datetime
from typing import Any, Iterable, Protocol

from rokhdad_workers.normalization import NormalizedEvent
from rokhdad_workers.settings import mongodb_database_from_env, mongodb_uri_from_env


FIELD_PATHS = (
    "title",
    "summary",
    "description",
    "starts_at",
    "ends_at",
    "event_type",
    "status",
    "canonical_url",
    "online_url",
    "category_name",
    "location.city_name",
    "location.venue_name",
    "location.venue_address",
    "organizer.name",
    "organizer.url",
)


class FieldHistoryCollection(Protocol):
    def update_one(self, filter: dict[str, Any], update: dict[str, Any], upsert: bool = False) -> Any:
        pass

    def find_one(self, query: dict[str, Any]) -> dict[str, Any] | None:
        pass


@dataclass(frozen=True)
class FieldHistoryEntry:
    canonical_key: str
    source_key: str
    external_id: str
    field_path: str
    value: Any
    value_hash: str
    observed_at: str
    raw_snapshot_id: str | None = None
    schema_version: int = 1
    document_type: str = "event_field_history"

    def to_document(self) -> dict[str, Any]:
        return asdict(self)


class FieldHistoryStore:
    def __init__(self, collection: FieldHistoryCollection) -> None:
        self.collection = collection

    def save_many(self, entries: Iterable[FieldHistoryEntry]) -> int:
        saved = 0
        for entry in entries:
            document = entry.to_document()
            self.collection.update_one(
                {
                    "canonical_key": entry.canonical_key,
                    "source_key": entry.source_key,
                    "external_id": entry.external_id,
                    "field_path": entry.field_path,
                    "value_hash": entry.value_hash,
                },
                {"$setOnInsert": document},
                upsert=True,
            )
            saved += 1
        return saved

    def find_latest(self, canonical_key: str, field_path: str, source_key: str) -> dict[str, Any] | None:
        return self.collection.find_one({
            "canonical_key": canonical_key,
            "field_path": field_path,
            "source_key": source_key,
        })


def build_field_history(event: NormalizedEvent, observed_at: str | None = None) -> list[FieldHistoryEntry]:
    observed = observed_at or datetime.now(UTC).isoformat()
    event_dict = event.to_dict()
    entries: list[FieldHistoryEntry] = []

    for field_path in FIELD_PATHS:
        value = value_at_path(event_dict, field_path)
        if value is None or value == "":
            continue

        entries.append(FieldHistoryEntry(
            canonical_key=event.canonical_key,
            source_key=event.source_key,
            external_id=event.external_id,
            field_path=field_path,
            value=value,
            value_hash=hash_value(value),
            observed_at=observed,
            raw_snapshot_id=event.raw_snapshot_id,
            schema_version=event.schema_version,
        ))

    return entries


def build_field_history_many(events: Iterable[NormalizedEvent], observed_at: str | None = None) -> list[FieldHistoryEntry]:
    entries: list[FieldHistoryEntry] = []
    for event in events:
        entries.extend(build_field_history(event, observed_at=observed_at))
    return entries


def value_at_path(payload: dict[str, Any], field_path: str) -> Any:
    current: Any = payload
    for part in field_path.split("."):
        if not isinstance(current, dict):
            return None
        current = current.get(part)
    return current


def hash_value(value: Any) -> str:
    encoded = json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode("utf-8")
    return hashlib.sha256(encoded).hexdigest()


def connect_field_history_store(collection_name: str = "event_field_history") -> FieldHistoryStore:
    mongodb_uri = mongodb_uri_from_env()
    if not mongodb_uri:
        raise RuntimeError("MongoDB is not configured.")

    from pymongo import MongoClient

    client = MongoClient(mongodb_uri)
    database = client[mongodb_database_from_env()]
    return FieldHistoryStore(database[collection_name])


def normalized_events_from_json(input_json: str) -> list[NormalizedEvent]:
    raw = json.loads(input_json)
    items = raw.get("events", raw) if isinstance(raw, dict) else raw
    if not isinstance(items, list):
        raise ValueError("--input-json must be a JSON list or object with an events list.")

    return [NormalizedEvent.from_dict(item) for item in items if isinstance(item, dict)]


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-field-history")
    parser.add_argument("--input-json", required=True, help="JSON list of normalized events.")
    parser.add_argument("--collection", default="event_field_history")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args(list(argv) if argv is not None else None)

    events = normalized_events_from_json(args.input_json)
    entries = build_field_history_many(events)
    documents = [entry.to_document() for entry in entries]

    if args.dry_run:
        print(json.dumps({"entries": documents, "stored": 0}, ensure_ascii=False, sort_keys=True), flush=True)
        return 0

    stored = connect_field_history_store(args.collection).save_many(entries)
    print(json.dumps({"entries": len(entries), "stored": stored}, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
