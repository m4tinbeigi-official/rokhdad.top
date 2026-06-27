# Mobile App (Capacitor Android)

The same Vue frontend is packaged as a native Android app using [Capacitor](https://capacitorjs.com). The web build in `frontend/dist` is wrapped as the app's web layer — there is no separate mobile codebase.

## Configuration

`frontend/capacitor.config.json`:

```json
{
  "appId": "top.rokhdad.app",
  "appName": "رخداد",
  "webDir": "dist"
}
```

- **appId:** `top.rokhdad.app`
- **appName:** `رخداد`
- **webDir:** `dist` (the Vite production build output)

Capacitor packages are dev dependencies: `@capacitor/core`, `@capacitor/cli`, `@capacitor/android`. The native Android project is in `frontend/android/`.

## Build Commands

From `frontend/`:

```bash
npm run android:sync   # build the web app then `cap sync android`
npm run android:open   # open the Android project in Android Studio
```

`android:sync` runs `npm run build` (Vite) first, so `dist/` is always current before syncing into the native shell. Producing a release APK/AAB is then done from Android Studio (or Gradle) against `frontend/android/`.

## Notes

- The app talks to the same API base as the web app (`/api/v1`); see [`FRONTEND.md`](FRONTEND.md) for the API client.
- Because the mobile app reuses the web build, any frontend change ships to mobile after a rebuild + `cap sync`.
- The UI is RTL/Persian-first (matching `appName` `رخداد`).
