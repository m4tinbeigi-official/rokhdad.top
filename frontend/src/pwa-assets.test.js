import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

test('PWA manifest exposes installable app metadata', async () => {
  const manifest = JSON.parse(await readFile(new URL('../public/manifest.webmanifest', import.meta.url), 'utf8'))

  assert.equal(manifest.name, 'رخداد')
  assert.equal(manifest.display, 'standalone')
  assert.equal(manifest.start_url, '/')
  assert.equal(manifest.icons[0].purpose, 'any maskable')
})

test('service worker caches shell without intercepting API requests', async () => {
  const serviceWorker = await readFile(new URL('../public/sw.js', import.meta.url), 'utf8')

  assert.match(serviceWorker, /CACHE_NAME/)
  assert.equal(serviceWorker.includes("url.pathname.startsWith('/api/')"), true)
  assert.equal(serviceWorker.includes('/offline.html'), true)
  assert.equal(serviceWorker.includes("request.mode === 'navigate'"), true)
})

test('offline fallback page is available for service worker navigation fallback', async () => {
  const offlinePage = await readFile(new URL('../public/offline.html', import.meta.url), 'utf8')

  assert.match(offlinePage, /رخداد/)
  assert.match(offlinePage, /اتصال اینترنت برقرار نیست/)
})

test('Capacitor Android shell points at the Vite production build', async () => {
  const capacitorConfig = JSON.parse(await readFile(new URL('../capacitor.config.json', import.meta.url), 'utf8'))
  const androidManifest = await readFile(new URL('../android/app/src/main/AndroidManifest.xml', import.meta.url), 'utf8')

  assert.equal(capacitorConfig.appId, 'top.rokhdad.app')
  assert.equal(capacitorConfig.appName, 'Rokhdad')
  assert.equal(capacitorConfig.webDir, 'dist')
  assert.match(androidManifest, /android:supportsRtl="true"/)
  assert.match(androidManifest, /android.permission.INTERNET/)
})
