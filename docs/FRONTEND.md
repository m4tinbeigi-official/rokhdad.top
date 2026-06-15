# Rokhdad Frontend

Task: `P15-001`

The public frontend is a Vue 3 app built with Vite.

## Local Commands

```sh
cd frontend
npm install
npm test
npm run build
```

## API Client

The frontend API client lives in:

```text
frontend/src/api/client.js
```

It provides public endpoint helpers for events, categories, cities, organizers, and people. Tests use mocked `fetch` through Node's built-in test runner.

## Docker Image

```sh
docker build -f deploy/frontend.Dockerfile -t rokhdad/frontend:latest frontend
```

The image exposes port `3000` and serves the production build with Vite preview. Nginx proxies public traffic to `frontend:3000`.

## API Base URL

Default API base:

```text
/api/v1
```

Override at image build time:

```sh
docker build \
  --build-arg VITE_API_BASE_URL=https://rokhdad.top/api/v1 \
  -f deploy/frontend.Dockerfile \
  -t rokhdad/frontend:latest \
  frontend
```
