import json
import subprocess
import sys
import unittest

from rokhdad_workers.field_history import FieldHistoryStore, build_field_history, hash_value
from rokhdad_workers.normalization import NormalizedEvent, NormalizedLocation, NormalizedOrganizerRef


class FakeCollection:
    def __init__(self) -> None:
        self.documents = []

    def update_one(self, filter, update, upsert=False):
        for document in self.documents:
            if all(document.get(key) == value for key, value in filter.items()):
                return None

        if upsert:
            self.documents.append(update["$setOnInsert"])
        return None

    def find_one(self, query):
        for document in self.documents:
            if all(document.get(key) == value for key, value in query.items()):
                return document
        return None


class FieldHistoryTest(unittest.TestCase):
    def test_builds_field_history_entries_from_normalized_event(self) -> None:
        event = NormalizedEvent(
            source_key="evand",
            external_id="101",
            title="AI Product Management",
            starts_at="2026-07-01T09:00:00+03:30",
            event_type="in_person",
            canonical_url="https://evand.com/events/101",
            location=NormalizedLocation(city_name="Tehran"),
            organizer=NormalizedOrganizerRef(name="Evand Test Organizer"),
            raw_snapshot_id="snapshot-1",
        )

        entries = build_field_history(event, observed_at="2026-06-15T00:00:00+00:00")
        by_path = {entry.field_path: entry for entry in entries}

        self.assertEqual("AI Product Management", by_path["title"].value)
        self.assertEqual("Tehran", by_path["location.city_name"].value)
        self.assertEqual("snapshot-1", by_path["title"].raw_snapshot_id)
        self.assertEqual(hash_value("AI Product Management"), by_path["title"].value_hash)

    def test_store_upserts_history_documents(self) -> None:
        collection = FakeCollection()
        store = FieldHistoryStore(collection)
        event = NormalizedEvent(source_key="evand", external_id="101", title="AI Product Management")
        entries = build_field_history(event, observed_at="2026-06-15T00:00:00+00:00")

        first_count = store.save_many(entries)
        second_count = store.save_many(entries)
        found = store.find_latest("evand:101", "title", "evand")

        self.assertEqual(first_count, second_count)
        self.assertEqual(len(entries), len(collection.documents))
        self.assertIsNotNone(found)
        self.assertEqual("event_field_history", found["document_type"])

    def test_cli_dry_run_outputs_history_entries(self) -> None:
        input_json = json.dumps([{
            "source_key": "evand",
            "external_id": "101",
            "title": "AI Product Management",
            "starts_at": "2026-07-01T09:00:00+03:30",
        }])

        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.field_history", "--input-json", input_json, "--dry-run"],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)
        field_paths = {entry["field_path"] for entry in payload["entries"]}

        self.assertEqual(0, payload["stored"])
        self.assertIn("title", field_paths)
        self.assertIn("starts_at", field_paths)


if __name__ == "__main__":
    unittest.main()
