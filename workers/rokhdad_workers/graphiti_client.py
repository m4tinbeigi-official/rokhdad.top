from __future__ import annotations

import os
from typing import Any
from graphiti_core import Graphiti
from rokhdad_workers.settings import (
    neo4j_uri_from_env,
    neo4j_user_from_env,
    neo4j_password_from_env,
)


def get_graphiti_client() -> Graphiti:
    """
    Initialize and return a Graphiti client using environment settings.
    Requires OPENAI_API_KEY to be set in environment variables if the default LLM is used.
    """
    uri = neo4j_uri_from_env()
    user = neo4j_user_from_env()
    password = neo4j_password_from_env() or ""

    return Graphiti(
        uri=uri,
        user=user,
        password=password,
    )


def format_event_as_text(event: dict[str, Any]) -> str:
    """
    Formats normalized event dictionary into a descriptive text episode
    that Graphiti can ingest to build nodes and relationships.
    """
    title = event.get("title", "Unnamed Event")
    source = event.get("source_key", "unknown")
    start = event.get("starts_at", "unknown time")

    parts = [
        f"Event titled '{title}' is sourced from '{source}'.",
        f"It starts at {start}."
    ]

    if organizer := event.get("organizer"):
        if isinstance(organizer, dict) and organizer.get("name"):
            parts.append(f"The organizer is '{organizer['name']}'.")
            if organizer.get("url"):
                parts.append(f"Organizer website is {organizer['url']}.")

    if location := event.get("location"):
        if isinstance(location, dict):
            city = location.get("city_name")
            venue = location.get("venue_name")
            addr = location.get("venue_address")
            loc_desc = []
            if venue:
                loc_desc.append(f"venue '{venue}'")
            if addr:
                loc_desc.append(f"address '{addr}'")
            if city:
                loc_desc.append(f"city '{city}'")
            if loc_desc:
                parts.append(f"The event takes place at {', '.join(loc_desc)}.")

    if people := event.get("people"):
        for person in people:
            if isinstance(person, dict) and person.get("name"):
                role = person.get("role_title") or "speaker/guest"
                parts.append(f"'{person['name']}' participates as a '{role}'.")

    if summary := event.get("summary"):
        parts.append(f"Event summary: {summary}")

    return " ".join(parts)
