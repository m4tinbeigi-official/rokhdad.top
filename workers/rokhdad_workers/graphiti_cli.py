from __future__ import annotations

import argparse
import asyncio
import json
import sys
from pathlib import Path
from typing import Sequence

from rokhdad_workers.graphiti_client import get_graphiti_client, format_event_as_text


async def run_index_event(fixture_path: Path) -> int:
    try:
        content = fixture_path.read_text(encoding="utf-8")
        event_dict = json.loads(content)
        # Verify it can load as a normalized event
        if "payload" in event_dict and "normalized_event" in event_dict["payload"]:
            # It's an enrichment job fixture
            event = event_dict["payload"]["normalized_event"]
        else:
            # Direct normalized event
            event = event_dict

        text = format_event_as_text(event)
        print(f"Formatted event text for ingestion:\n{text}\n")

        client = get_graphiti_client()
        print("Connecting to Neo4j and indexing episode...")
        # Index in graphiti
        metadata = {
            "source_key": event.get("source_key"),
            "external_id": event.get("external_id"),
            "canonical_key": f"{event.get('source_key')}:{event.get('external_id')}",
            "type": "event_ingestion"
        }
        resp = await client.add_episode(text=text, metadata=metadata)
        print(f"Successfully added episode. Graphiti response: {resp}")
        return 0
    except Exception as e:
        print(f"Error indexing event: {e}", file=sys.stderr)
        return 1


async def run_search(query: str, limit: int) -> int:
    try:
        client = get_graphiti_client()
        print(f"Searching Graphiti knowledge graph for: '{query}'...")
        results = await client.search(query=query, num_results=limit)
        print(f"Found {len(results)} results:")
        for i, res in enumerate(results, 1):
            # Graphiti search results usually have node/relationship/fact info
            # Depending on version, it could be a search result object with a 'fact' attribute
            fact = getattr(res, "fact", str(res))
            print(f"{i}. Fact: {fact}")
        return 0
    except Exception as e:
        print(f"Error during search: {e}", file=sys.stderr)
        return 1


def main(argv: Sequence[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-graphiti-cli")
    subparsers = parser.add_subparsers(dest="command", required=True)

    index_parser = subparsers.add_parser("index-event", help="Index a normalized event fixture into Graphiti")
    index_parser.add_argument("--fixture", type=Path, required=True, help="Path to event JSON fixture")

    search_parser = subparsers.add_parser("search", help="Search the Graphiti knowledge graph")
    search_parser.add_argument("query", type=str, help="Search query string")
    search_parser.add_argument("--limit", type=int, default=5, help="Max results to return")

    args = parser.parse_args(argv if argv is not None else sys.argv[1:])

    if args.command == "index-event":
        return asyncio.run(run_index_event(args.fixture))
    elif args.command == "search":
        return asyncio.run(run_search(args.query, args.limit))

    return 0


if __name__ == "__main__":
    sys.exit(main())
