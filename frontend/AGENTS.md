# Frontend Agent Notes

Scope: Vue 3 + Vite Persian UI, PWA assets, and Capacitor Android wrapper.

- Start with `src/App.vue` for route/page behavior.
- API calls live in `src/api/client.js`.
- Feature helpers live under `src/events/`, `src/lookups/`, and
  `src/organizer/`.
- Tests are colocated as `*.test.js`.
- Do not read `node_modules/`, `dist/`, Android build output, or lockfiles for
  routine tasks.
- Preserve RTL/Persian UX. Keep copy natural for Iranian users.
- Prefer targeted `node --test path/to/file.test.js`; run `npm run build` when
  UI or Vite behavior changes.

Relevant docs: `docs/FRONTEND.md`, `docs/MOBILE_APP.md`,
`docs/API_CONTRACTS.md`, and the API/domain doc for the touched feature.
