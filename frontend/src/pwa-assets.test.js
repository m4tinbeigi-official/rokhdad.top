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
})
