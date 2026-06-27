# Rokhdad Agent Instructions

Goal: keep AI context small and task-specific. Do not scan the whole repository
unless the user explicitly asks for a broad audit.

## Context Budget

- Start with this file and `docs/AI_CONTEXT.md`.
- Open only the files for the subsystem you are changing.
- Prefer summaries and targeted symbol reads over pasting whole files.
- Do not read dependencies, generated output, lockfiles, local databases, logs,
  or external API reference dumps unless the task explicitly requires them.
- If a task references a `Pxx-xxx` ID, open only the matching section in
  `docs/TASK_BOARD.md` and the relevant domain doc from `docs/INDEX.md`.

## Code Discovery

This project uses codebase-memory-mcp to maintain a code knowledge graph.
Prefer MCP graph tools over grep/glob/file-search for code discovery.

Priority:
1. `search_graph` to find functions, classes, routes, and variables.
2. `trace_path` to inspect callers/callees and data flow.
3. `get_code_snippet` to read specific source.
4. `query_graph` for complex patterns.
5. `get_architecture` for a high-level overview.

Fallback to `rg`/file reads only for string literals, config, docs, shell files,
or when the MCP graph is unavailable or stale.

## Project Shape

- `backend/`: Laravel 12 API, Filament admin, payments, auth, settlements,
  campaigns, webhooks, notifications, and database migrations.
- `frontend/`: Vue 3 + Vite Persian event UI and Capacitor Android wrapper.
- `workers/`: Python ingestion, normalization, deduplication, enrichment,
  image jobs, queue consumers, and Graphiti integration.
- `deploy/`: Docker Compose and server deployment scripts.
- `docs/`: planning and domain docs. Use `docs/INDEX.md` as the routing table.

## Task Discipline

- Keep changes scoped to one task/domain at a time.
- Do not rewrite unrelated code, docs, generated files, or user changes.
- For Persian UI/copy, keep language natural for Iranian users and preserve RTL
  behavior.
- Update docs only when behavior, API contracts, setup, or task status changes.
- Never expose or summarize secrets from `.env`, `.server-credentials`, server
  notes, local databases, or logs.

## Verification

- Backend: prefer targeted PHPUnit/Laravel tests under `backend/tests`.
- Frontend: prefer targeted Node tests under `frontend/src/*.test.js`; run a
  Vite build only when UI/build behavior changes.
- Workers: prefer targeted Python tests under `workers/tests`.
- If dependencies or services are unavailable, state what was not run and why.
