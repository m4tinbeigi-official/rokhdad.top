# Deploy Agent Notes

Scope: Docker Compose, Dockerfiles, bootstrap scripts, health checks, rollback,
and server deployment helpers.

- Start with `docker-compose.yml` for service topology.
- Scripts live under `scripts/`; keep shell changes small and portable.
- Do not read credentials or server notes unless explicitly requested and safe.
- Keep deployment docs in sync with changed commands, ports, volumes, or
  environment variables.
- Prefer static review for shell changes; run scripts only when the user asks or
  the environment is clearly safe.
