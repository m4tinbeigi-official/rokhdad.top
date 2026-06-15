import assert from 'node:assert/strict'
import test from 'node:test'
import { ApiError, ApiValidationError, NetworkError, createApiClient } from './client.js'

test('listEvents builds public events URL with query params and parses JSON', async () => {
  const calls = []
  const client = createApiClient({
    baseUrl: 'https://rokhdad.top/api/v1/',
    fetchImpl: async (url, init) => {
      calls.push({ url, init })
      return jsonResponse(200, {
        data: [{ id: 1, title: 'رویداد تست' }],
        meta: { total: 1 },
      })
    },
  })

  const payload = await client.listEvents({ city: 'tehran', per_page: 12, empty: '' })

  assert.equal(calls[0].url, 'https://rokhdad.top/api/v1/events?city=tehran&per_page=12')
  assert.equal(calls[0].init.headers.Accept, 'application/json')
  assert.equal(payload.data[0].title, 'رویداد تست')
})

test('detail methods encode slugs safely', async () => {
  const calls = []
  const client = createApiClient({
    baseUrl: '/api/v1',
    fetchImpl: async (url) => {
      calls.push(url)
      return jsonResponse(200, { data: { slug: 'vue workshop' } })
    },
  })

  await client.getEvent('vue workshop')

  assert.equal(calls[0], '/api/v1/events/vue%20workshop')
})

test('non-ok API responses throw ApiError with status and payload', async () => {
  const client = createApiClient({
    fetchImpl: async () => jsonResponse(404, { message: 'Not found' }),
  })

  await assert.rejects(() => client.getOrganizer('missing'), (error) => {
    assert.ok(error instanceof ApiError)
    assert.equal(error.status, 404)
    assert.equal(error.message, 'Not found')
    assert.equal(error.url, '/api/v1/organizers/missing')
    return true
  })
})

test('validation responses throw ApiValidationError with field errors', async () => {
  const client = createApiClient({
    fetchImpl: async () => jsonResponse(422, {
      message: 'Validation failed',
      errors: { query: ['Invalid query'] },
    }),
  })

  await assert.rejects(() => client.request('/events'), (error) => {
    assert.ok(error instanceof ApiValidationError)
    assert.deepEqual(error.errors, { query: ['Invalid query'] })
    return true
  })
})

test('network failures are normalized', async () => {
  const client = createApiClient({
    fetchImpl: async () => {
      throw new Error('socket closed')
    },
  })

  await assert.rejects(() => client.listCities(), (error) => {
    assert.ok(error instanceof NetworkError)
    assert.equal(error.message, 'Network request failed')
    assert.equal(error.url, '/api/v1/cities')
    return true
  })
})

function jsonResponse(status, payload) {
  return {
    ok: status >= 200 && status < 300,
    status,
    text: async () => JSON.stringify(payload),
  }
}
