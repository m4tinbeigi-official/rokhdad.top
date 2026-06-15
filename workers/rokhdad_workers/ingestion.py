from __future__ import annotations

import sys
from typing import Iterable

from rokhdad_workers.cli import run_worker


def main(argv: Iterable[str] | None = None) -> int:
    return run_worker("ingestion", argv)


if __name__ == "__main__":
    sys.exit(main())
