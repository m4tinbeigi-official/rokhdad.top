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


def mongodb_uri_from_env() -> str | None:
    if mongodb_uri := os.getenv("MONGODB_URI"):
        return mongodb_uri

    username = os.getenv("MONGO_INITDB_ROOT_USERNAME")
    password = os.getenv("MONGO_INITDB_ROOT_PASSWORD")
    host = os.getenv("MONGODB_HOST", "mongodb")
    port = os.getenv("MONGODB_PORT", "27017")
    database = os.getenv("MONGODB_DATABASE", "rokhdad")

    if username and password:
        return f"mongodb://{quote(username)}:{quote(password)}@{host}:{port}/{database}?authSource=admin"

    return None


def mongodb_database_from_env() -> str:
    return os.getenv("MONGODB_DATABASE", "rokhdad")
