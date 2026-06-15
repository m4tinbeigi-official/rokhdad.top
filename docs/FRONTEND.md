# Rokhdad Frontend

Task: `P15-001`

The public frontend is a Vue 3 app built with Vite.

## Local Commands

```sh
cd frontend
npm install
npm run build
```

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
