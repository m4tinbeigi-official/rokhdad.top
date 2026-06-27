from __future__ import annotations

import os
import unittest
from rokhdad_workers.graphiti_client import format_event_as_text, get_graphiti_client


class TestGraphitiIntegration(unittest.TestCase):
    def test_format_event_as_text_basic(self) -> None:
        event = {
            "source_key": "evand",
            "external_id": "12345",
            "title": "Introduction to AI",
            "starts_at": "2026-07-01T18:00:00+03:30",
            "summary": "A basic intro session to artificial intelligence.",
            "organizer": {
                "name": "Tehran AI Guild",
                "url": "https://aiguild.ir"
            },
            "location": {
                "city_name": "Tehran",
                "venue_name": "Guild Hall",
                "venue_address": "Main St. 4"
            },
            "people": [
                {
                    "name": "Arash",
                    "role_title": "speaker"
                }
            ]
        }

        text = format_event_as_text(event)
        self.assertIn("Event titled 'Introduction to AI' is sourced from 'evand'.", text)
        self.assertIn("It starts at 2026-07-01T18:00:00+03:30.", text)
        self.assertIn("The organizer is 'Tehran AI Guild'.", text)
        self.assertIn("Organizer website is https://aiguild.ir.", text)
        self.assertIn("The event takes place at venue 'Guild Hall', address 'Main St. 4', city 'Tehran'.", text)
        self.assertIn("'Arash' participates as a 'speaker'.", text)
        self.assertIn("Event summary: A basic intro session to artificial intelligence.", text)

    def test_format_event_as_text_missing_fields(self) -> None:
        event = {
            "title": "Minimal Event",
        }
        text = format_event_as_text(event)
        self.assertEqual("Event titled 'Minimal Event' is sourced from 'unknown'. It starts at unknown time.", text.strip())

    def test_get_graphiti_client_initialization(self) -> None:
        # Verify it can initialize without error (should raise standard connection errors only when used, or fail to find graphiti)
        # Note: We won't call any async methods to avoid requiring a running Neo4j and OpenAI API keys.
        old_key = os.environ.get("OPENAI_API_KEY")
        os.environ["OPENAI_API_KEY"] = "dummy-key-for-testing"
        try:
            client = get_graphiti_client()
            self.assertIsNotNone(client)
        except ImportError as e:
            self.fail(f"Could not import graphiti dependency: {e}")
        finally:
            if old_key is not None:
                os.environ["OPENAI_API_KEY"] = old_key
            else:
                del os.environ["OPENAI_API_KEY"]


if __name__ == "__main__":
    unittest.main()
