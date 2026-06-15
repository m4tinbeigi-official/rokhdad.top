from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import dataclass
from difflib import SequenceMatcher
from pathlib import Path
from typing import Iterable

from rokhdad_workers.normalization import NormalizedEvent


DUPLICATE_THRESHOLD = 80
POSSIBLE_DUPLICATE_THRESHOLD = 65


@dataclass(frozen=True)
class DeduplicationScore:
    score: int
    reasons: tuple[str, ...]

    @property
    def is_duplicate(self) -> bool:
        return self.score >= DUPLICATE_THRESHOLD

    @property
    def is_possible_duplicate(self) -> bool:
        return self.score >= POSSIBLE_DUPLICATE_THRESHOLD

    def to_dict(self) -> dict[str, object]:
        return {
            "score": self.score,
            "reasons": list(self.reasons),
            "is_duplicate": self.is_duplicate,
            "is_possible_duplicate": self.is_possible_duplicate,
        }


@dataclass(frozen=True)
class DeduplicationCandidate:
    canonical_key: str
    score: DeduplicationScore

    def to_dict(self) -> dict[str, object]:
        return {
            "canonical_key": self.canonical_key,
            **self.score.to_dict(),
        }


def score_event_pair(left: NormalizedEvent, right: NormalizedEvent) -> DeduplicationScore:
    if left.canonical_key == right.canonical_key:
        return DeduplicationScore(score=100, reasons=("same_source_event",))

    score = 0
    reasons: list[str] = []

    title_similarity = SequenceMatcher(None, normalize_text(left.title), normalize_text(right.title)).ratio()
    if title_similarity >= 0.95:
        score += 45
        reasons.append("title_exact_or_near_exact")
    elif title_similarity >= 0.82:
        score += 35
        reasons.append("title_similar")
    elif title_similarity >= 0.70:
        score += 20
        reasons.append("title_weak_match")

    if left.starts_at and right.starts_at:
        if left.starts_at == right.starts_at:
            score += 30
            reasons.append("starts_at_exact")
        elif left.starts_at[:10] == right.starts_at[:10]:
            score += 15
            reasons.append("same_start_date")

    left_city = normalize_text(left.location.city_name) if left.location and left.location.city_name else ""
    right_city = normalize_text(right.location.city_name) if right.location and right.location.city_name else ""
    if left_city and right_city and left_city == right_city:
        score += 10
        reasons.append("city_match")

    left_organizer = normalize_text(left.organizer.name) if left.organizer else ""
    right_organizer = normalize_text(right.organizer.name) if right.organizer else ""
    if left_organizer and right_organizer and left_organizer == right_organizer:
        score += 10
        reasons.append("organizer_match")

    if left.canonical_url and right.canonical_url and left.canonical_url == right.canonical_url:
        score += 15
        reasons.append("canonical_url_match")

    return DeduplicationScore(score=min(score, 100), reasons=tuple(reasons))


def find_duplicate_candidates(
    target: NormalizedEvent,
    candidates: Iterable[NormalizedEvent],
    threshold: int = POSSIBLE_DUPLICATE_THRESHOLD,
) -> list[DeduplicationCandidate]:
    results = [
        DeduplicationCandidate(candidate.canonical_key, score_event_pair(target, candidate))
        for candidate in candidates
        if candidate.canonical_key != target.canonical_key
    ]

    return sorted(
        (candidate for candidate in results if candidate.score.score >= threshold),
        key=lambda candidate: candidate.score.score,
        reverse=True,
    )


def normalize_text(value: str) -> str:
    lowered = value.casefold()
    return re.sub(r"\s+", " ", re.sub(r"[^\w\s]+", " ", lowered)).strip()


def load_normalized_events(path: Path) -> list[NormalizedEvent]:
    raw = json.loads(path.read_text(encoding="utf-8"))
    items = raw.get("events", raw) if isinstance(raw, dict) else raw
    if not isinstance(items, list):
        raise ValueError("Deduplication fixture must be a list or object with an events list.")

    return [NormalizedEvent.from_dict(item) for item in items if isinstance(item, dict)]


def main(argv: Iterable[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="rokhdad-deduplication")
    parser.add_argument("--fixture", type=Path, required=True)
    parser.add_argument("--target-index", type=int, default=0)
    parser.add_argument("--threshold", type=int, default=POSSIBLE_DUPLICATE_THRESHOLD)
    args = parser.parse_args(list(argv) if argv is not None else None)

    events = load_normalized_events(args.fixture)
    target = events[args.target_index]
    candidates = events[:args.target_index] + events[args.target_index + 1 :]
    result = {
        "target": target.canonical_key,
        "candidates": [candidate.to_dict() for candidate in find_duplicate_candidates(target, candidates, args.threshold)],
    }

    print(json.dumps(result, ensure_ascii=False, sort_keys=True), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
