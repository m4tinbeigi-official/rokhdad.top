from __future__ import annotations

import os
from urllib.parse import quote


def redis_url_from_env() -> str | None:
    if redis_url := os.getenv("REDIS_URL"):
        return redis_url

    host = os.getenv("REDIS_HOST")
    if not host:
        return None

    port = os.getenv("REDIS_PORT", "6379")
    database = os.getenv("REDIS_DB", "0")
    password = os.getenv("REDIS_PASSWORD")
    auth = f":{quote(password)}@" if password else ""

    return f"redis://{auth}{host}:{port}/{database}"
