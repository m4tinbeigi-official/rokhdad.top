# Phase 2 Scope — Advanced AI Matching (P32-003)

> Status: **scope document** (phase 2, not scheduled). Defines the problem,
> guardrails, and a staged approach for AI-driven event matching. Blocked until
> the MVP is complete and there is enough event + behavior data to make matching
> meaningful. This builds on the existing AI dedup/enrichment work, not a new AI
> stack.

## Goal

Recommend the right events to the right users — and surface non-obvious
relationships between events, organizers, people, and topics — using AI ranking
on top of the canonical event data, going beyond the rule-based personalization
already shipped (`saved filters`, preferences, personalized homepage API).

## Why it matters

Discovery quality is the core of an aggregation product. Rule-based filters get a
user to a category; matching gets them to the *specific* event they didn't know
to search for. Better matching increases registration conversion for organizers
and return visits for attendees, and it makes the aggregated catalog more
valuable than any single source.

## In scope

- **Semantic event matching**: embed event content (title, description, category,
  people, location) and rank candidates by similarity to a user's history,
  saved filters, and stated preferences.
- **"More like this"**: event-to-event similarity on the detail page.
- **Cold-start handling**: fall back to the existing rule-based personalized
  ranking when a user has little history, blending in as signal accumulates.
- **Explainability**: every recommendation carries a short reason ("because you
  saved X" / "same speaker as Y") so the UI can justify it and so results are
  auditable.
- Reuse of the existing AI service configuration and the worker pipeline that
  already does enrichment/deduplication.

## Out of scope (this phase)

- A real-time online learning system; phase 2 is batch/near-real-time scoring.
- Cross-user social graph features.
- Paid placement / sponsored ranking (must be designed separately and clearly
  labeled if ever added).
- Replacing the deterministic search/filter API — matching augments, never hides,
  explicit user intent.

## Shape (sketch, not final)

- An embedding step in the worker pipeline ([`WORKERS.md`](WORKERS.md),
  [`INGESTION_SOURCES.md`](INGESTION_SOURCES.md)) producing per-event vectors,
  stored alongside canonical events (vector column or sidecar store).
- A ranking service combining vector similarity with rule-based signals
  (recency, locality, category affinity, saved-filter overlap).
- A recommendations API extending the personalized-homepage endpoint with a
  `reason` per item.

## Guardrails

- **Intent first.** When a user searches or filters, those constraints are hard
  filters; AI only orders within them.
- **No dark patterns.** Reasons are honest; no fabricated scarcity or fake
  "popularity".
- **Privacy.** Matching uses on-platform behavior only; no inference of sensitive
  attributes. Inputs and model/config changes are recorded via `AuditLog::record()`.
- **Fallback safety.** If the model/service is unavailable, ranking degrades to
  the existing deterministic personalization, never to an empty page.

## Metrics

- Registration conversion from recommended vs. browsed events.
- Click-through on "more like this".
- Coverage (share of catalog ever recommended) to avoid popularity collapse.

## Open questions

- Vector store choice (in-DB vs. dedicated) and refresh cadence.
- How much history before AI ranking overrides rule-based ordering.
- Offline evaluation harness and guard metrics before exposure.
- Re-embedding cost on event edits.

## Acceptance for the scope itself

Product review confirms the augment-not-replace stance, the explainability and
privacy guardrails, the fallback behavior, and the success metrics — before any
implementation task is opened.
