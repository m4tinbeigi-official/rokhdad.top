from __future__ import annotations

import json
import sys
from dataclasses import asdict, dataclass, field
from datetime import datetime
from typing import Any, Iterable

from rokhdad_workers.cli import run_worker


EVENT_TYPES = frozenset({"in_person", "online", "hybrid"})
EVENT_STATUSES = frozenset({"draft", "published", "cancelled"})
SCHEMA_VERSION = 1


@dataclass(frozen=True)
class NormalizedPersonRef:
    name: str
    role_title: str | None = None
    sort_order: int = 0

    def __post_init__(self) -> None:
        if not self.name.strip():
            raise ValueError("person name is required")


@dataclass(frozen=True)
class NormalizedOrganizerRef:
    name: str
    url: str | None = None

    def __post_init__(self) -> None:
        if not self.name.strip():
            raise ValueError("organizer name is required")


@dataclass(frozen=True)
class NormalizedLocation:
    city_name: str | None = None
    venue_name: str | None = None
    venue_address: str | None = None
    latitude: float | None = None
    longitude: float | None = None


@dataclass(frozen=True)
class NormalizedEvent:
    source_key: str
    external_id: str
    title: str
    starts_at: str | None = None
    ends_at: str | None = None
    timezone: str = "Asia/Tehran"
    event_type: str = "in_person"
    status: str = "draft"
    summary: str | None = None
    description: str | None = None
    canonical_url: str | None = None
    online_url: str | None = None
    category_name: str | None = None
    location: NormalizedLocation | None = None
    organizer: NormalizedOrganizerRef | None = None
    people: tuple[NormalizedPersonRef, ...] = ()
    metadata: dict[str, Any] = field(default_factory=dict)
    raw_snapshot_id: str | None = None
    schema_version: int = SCHEMA_VERSION

    def __post_init__(self) -> None:
        if not self.source_key.strip():
            raise ValueError("source_key is required")
        if not self.external_id.strip():
            raise ValueError("external_id is required")
        if not self.title.strip():
            raise ValueError("title is required")
        if self.event_type not in EVENT_TYPES:
            raise ValueError(f"event_type must be one of {sorted(EVENT_TYPES)}")
        if self.status not in EVENT_STATUSES:
            raise ValueError(f"status must be one of {sorted(EVENT_STATUSES)}")

        object.__setattr__(self, "starts_at", validate_datetime(self.starts_at, "starts_at"))
        object.__setattr__(self, "ends_at", validate_datetime(self.ends_at, "ends_at"))
        object.__setattr__(self, "people", tuple(self.people))

    @property
    def canonical_key(self) -> str:
        return f"{self.source_key}:{self.external_id}"

    def to_dict(self) -> dict[str, Any]:
        return asdict(self) | {"canonical_key": self.canonical_key}

    def to_json(self) -> str:
        return json.dumps(self.to_dict(), ensure_ascii=False, sort_keys=True)

    @classmethod
    def from_dict(cls, payload: dict[str, Any]) -> NormalizedEvent:
        location = payload.get("location")
        organizer = payload.get("organizer")
        people = payload.get("people") or ()

        return cls(
            source_key=str(payload.get("source_key", "")),
            external_id=str(payload.get("external_id", "")),
            title=str(payload.get("title", "")),
            starts_at=payload.get("starts_at"),
            ends_at=payload.get("ends_at"),
            timezone=payload.get("timezone", "Asia/Tehran"),
            event_type=payload.get("event_type", "in_person"),
            status=payload.get("status", "draft"),
            summary=payload.get("summary"),
            description=payload.get("description"),
            canonical_url=payload.get("canonical_url"),
            online_url=payload.get("online_url"),
            category_name=payload.get("category_name"),
            location=NormalizedLocation(**location) if isinstance(location, dict) else location,
            organizer=NormalizedOrganizerRef(**organizer) if isinstance(organizer, dict) else organizer,
            people=tuple(NormalizedPersonRef(**person) if isinstance(person, dict) else person for person in people),
            metadata=payload.get("metadata") or {},
            raw_snapshot_id=payload.get("raw_snapshot_id"),
            schema_version=int(payload.get("schema_version", SCHEMA_VERSION)),
        )


def validate_datetime(value: str | None, field_name: str) -> str | None:
    if value is None:
        return None

    candidate = value.replace("Z", "+00:00")
    try:
        datetime.fromisoformat(candidate)
    except ValueError as exc:
        raise ValueError(f"{field_name} must be an ISO-8601 datetime") from exc

    return candidate


def schema_document() -> dict[str, Any]:
    return {
        "schema": "rokhdad.normalized_event",
        "version": SCHEMA_VERSION,
        "required": ["source_key", "external_id", "title"],
        "event_types": sorted(EVENT_TYPES),
        "statuses": sorted(EVENT_STATUSES),
        "fields": {
            "source_key": "External source key, for example evand or eseminar.",
            "external_id": "Stable event id from the source.",
            "title": "Canonical event title.",
            "starts_at": "ISO-8601 start datetime with timezone when known.",
            "ends_at": "ISO-8601 end datetime with timezone when known.",
            "timezone": "IANA timezone name; defaults to Asia/Tehran.",
            "event_type": "One of in_person, online, hybrid.",
            "status": "One of draft, published, cancelled.",
            "canonical_url": "Source or Rokhdad canonical event URL.",
            "online_url": "Online attendance URL when the event is online or hybrid.",
            "category_name": "Source-derived category label before category matching.",
            "location": "Normalized city, venue, address, and optional coordinates.",
            "organizer": "Normalized organizer reference before organizer matching.",
            "people": "Speaker, teacher, host, or related person references.",
            "metadata": "Source-specific normalized facts not yet modeled relationally.",
            "raw_snapshot_id": "MongoDB raw snapshot id used as provenance.",
        },
    }


def main(argv: Iterable[str] | None = None) -> int:
    args = list(argv) if argv is not None else sys.argv[1:]
    if args == ["--schema"]:
        print(json.dumps(schema_document(), ensure_ascii=False, sort_keys=True), flush=True)
        return 0

    return run_worker("normalization", args)


if __name__ == "__main__":
    sys.exit(main())
