# Frontend image for rokhdad.top (Vue 3 + Vite)
# Builds the SPA and serves it with `vite preview` on port 3000,
# matching the existing deployment (container listens on :3000).
FROM node:20-alpine

WORKDIR /app

# Install dependencies first for better layer caching
COPY package*.json ./
RUN npm install

# Build the app
COPY . .
RUN npm run build

EXPOSE 3000

# vite.config.js preview is configured for host 0.0.0.0:3000 and allowedHosts rokhdad.top
CMD ["npm", "run", "preview"]
