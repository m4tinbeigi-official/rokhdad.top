import json
import subprocess
import sys
import unittest

from rokhdad_workers.normalization import (
    NormalizedEvent,
    NormalizedLocation,
    NormalizedOrganizerRef,
    NormalizedPersonRef,
    schema_document,
)


class NormalizationSchemaTest(unittest.TestCase):
    def test_normalized_event_serializes_canonical_shape(self) -> None:
        event = NormalizedEvent(
            source_key="evand",
            external_id="101",
            title="AI Product Management",
            starts_at="2026-07-01T09:00:00+03:30",
            ends_at="2026-07-01T12:00:00+03:30",
            event_type="hybrid",
            canonical_url="https://evand.com/events/101",
            location=NormalizedLocation(city_name="Tehran", venue_name="Test Hall"),
            organizer=NormalizedOrganizerRef(name="Evand Test Organizer"),
            people=(NormalizedPersonRef(name="Test Speaker", role_title="speaker"),),
            metadata={"source_title": "AI Product Management"},
            raw_snapshot_id="snapshot-1",
        )

        payload = event.to_dict()

        self.assertEqual("evand:101", payload["canonical_key"])
        self.assertEqual(1, payload["schema_version"])
        self.assertEqual("Tehran", payload["location"]["city_name"])
        self.assertEqual("Evand Test Organizer", payload["organizer"]["name"])
        self.assertEqual("speaker", payload["people"][0]["role_title"])

    def test_normalized_event_from_dict_rehydrates_nested_refs(self) -> None:
        event = NormalizedEvent.from_dict({
            "source_key": "eseminar",
            "external_id": "web-201",
            "title": "Marketing Analytics Webinar",
            "starts_at": "2026-07-10T10:00:00Z",
            "event_type": "online",
            "online_url": "https://eseminar.tv/webinar/web-201",
            "organizer": {"name": "Eseminar"},
            "people": [{"name": "Eseminar Instructor", "role_title": "teacher"}],
        })

        self.assertEqual("2026-07-10T10:00:00+00:00", event.starts_at)
        self.assertEqual("Eseminar", event.organizer.name)
        self.assertEqual("teacher", event.people[0].role_title)

    def test_normalized_event_rejects_missing_required_fields(self) -> None:
        with self.assertRaises(ValueError):
            NormalizedEvent(source_key="evand", external_id="101", title="")

    def test_normalized_event_rejects_unknown_event_type(self) -> None:
        with self.assertRaises(ValueError):
            NormalizedEvent(source_key="evand", external_id="101", title="Test", event_type="virtual")

    def test_schema_document_is_cli_visible(self) -> None:
        result = subprocess.run(
            [sys.executable, "-m", "rokhdad_workers.normalization", "--schema"],
            check=True,
            capture_output=True,
            text=True,
        )

        payload = json.loads(result.stdout)

        self.assertEqual(schema_document()["schema"], payload["schema"])
        self.assertIn("title", payload["required"])


if __name__ == "__main__":
    unittest.main()
