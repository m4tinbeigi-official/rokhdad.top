FROM python:3.12-alpine

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

COPY pyproject.toml /app/pyproject.toml
COPY rokhdad_workers /app/rokhdad_workers

RUN pip install --no-cache-dir .

CMD ["python", "-m", "rokhdad_workers.cli", "--smoke"]
