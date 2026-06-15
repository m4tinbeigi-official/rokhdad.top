import unittest

from rokhdad_workers.snapshots import SnapshotStore
from rokhdad_workers.sources.evand import RawEventPayload


class InsertResult:
    def __init__(self, inserted_id: str) -> None:
        self.inserted_id = inserted_id


class FakeCollection:
    def __init__(self) -> None:
        self.documents = []

    def insert_one(self, document):
        self.documents.append(document)
        return InsertResult(str(len(self.documents)))

    def find_one(self, query):
        for document in self.documents:
            if all(document.get(key) == value for key, value in query.items()):
                return document

        return None


class SnapshotStoreTest(unittest.TestCase):
    def test_saves_raw_payload_snapshot_documents(self) -> None:
        collection = FakeCollection()
        store = SnapshotStore(collection)
        payload = RawEventPayload(
            source_key="evand",
            external_id="101",
            fetched_at="2026-06-15T00:00:00+00:00",
            payload={"title": "AI Product Management"},
        )

        snapshot_ids = store.save_many([payload])
        found = store.find_by_external_id("evand", "101")

        self.assertEqual(["1"], snapshot_ids)
        self.assertIsNotNone(found)
        self.assertEqual("raw_event", found["snapshot_type"])
        self.assertEqual("AI Product Management", found["payload"]["title"])


if __name__ == "__main__":
    unittest.main()
