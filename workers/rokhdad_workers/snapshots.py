from __future__ import annotations

import argparse
import json
import sys
from dataclasses import asdict, is_dataclass
from typing import Any, Iterable, Protocol

from rokhdad_workers.settings import mongodb_database_from_env, mongodb_uri_from_env


class SnapshotCollection(Protocol):
    def insert_one(self, document: dict[str, Any]) -> Any:
        pass

    def find_one(self, query: dict[str, Any]) -> dict[str, Any] | None:
        pass


class SnapshotStore:
    def __init__(self, collection: SnapshotCollection) -> None:
        self.collection = collection

    def save_many(self, payloads: Iterable[Any]) -> list[str]:
        snapshot_ids: list[str] = []

        for payload in payloads:
            document = payload_to_document(payload)
            result = self.collection.insert_one(document)
            snapshot_ids.append(str(result.inserted_id))

        return snapshot_ids

    def find_by_external_id(self, source_key: str, external_id: str) -> dict[str, Any] | None:
        return self.collection.find_one({
            "source_key": source_key,
            "external_id": external_id,
        })


def payload_to_document(payload: Any) -> dict[str, Any]:
    document = asdict(payload) if is_dataclass(payload) else dict(payload)
    document["snapshot_type"] = "raw_event"

    return document


def connect_snapshot_store(collection_name: str = "raw_event_snapshots") -> SnapshotStore:
    mongodb_uri = mongodb_uri_from_env()
    if not mongodb_uri:
        raise RuntimeError("MongoDB is not configured.")

    from pymongo import MongoClient

    client = MongoClient(mongodb_uri)
    database = client[mongodb_database_from_env()]

    return SnapshotStore(database[collection_name])


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-snapshot-store")
    parser.add_argument("--input-json", required=True, help="JSON list of raw payload envelopes.")
    parser.add_argument("--collection", default="raw_event_snapshots")
    args = parser.parse_args(list(argv) if argv is not None else None)

    payloads = json.loads(args.input_json)
    if not isinstance(payloads, list):
        raise ValueError("--input-json must be a JSON list.")

    snapshot_ids = connect_snapshot_store(args.collection).save_many(payloads)
    print(json.dumps({"stored": len(snapshot_ids), "snapshot_ids": snapshot_ids}, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
