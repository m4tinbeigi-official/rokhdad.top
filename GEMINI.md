# Gemini Project Context

@./AGENTS.md

Keep context small. Root context is only a map; subsystem details live in
`backend/GEMINI.md`, `frontend/GEMINI.md`, and `workers/GEMINI.md` and should be
loaded just in time when those paths are touched.
