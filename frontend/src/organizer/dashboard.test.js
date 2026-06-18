import assert from 'node:assert/strict'
import test from 'node:test'
import { loadOrganizerDashboard, normalizeOrganizerDashboard } from './dashboard.js'

test('loadOrganizerDashboard passes auth token and normalizes metrics', async () => {
  const calls = []
  const api = {
    getOrganizerDashboard: async (token) => {
      calls.push(token)
      return {
        data: {
          summary: {
            organizers_count: 1,
            events_count: 2,
            registrations_count: 12,
            confirmed_registrations_count: 8,
            tickets_count: 8,
            revenue_total: 4500000,
            currency: 'IRR',
          },
          organizers: [{ id: 3, name: 'رخداد', slug: 'rokhdad', role: 'owner', events_count: 2 }],
          events: [{ id: 10, title: 'رویداد تست', slug: 'test-event', registrations_count: 12 }],
        },
      }
    },
  }

  const dashboard = await loadOrganizerDashboard(api, 'token-123')

  assert.deepEqual(calls, ['token-123'])
  assert.equal(dashboard.summary.revenue_total, 4500000)
  assert.equal(dashboard.organizers[0].href, '/organizers/rokhdad')
  assert.equal(dashboard.events[0].href, '/events/test-event')
})

test('normalizeOrganizerDashboard provides empty-state fallbacks', () => {
  const dashboard = normalizeOrganizerDashboard()

  assert.equal(dashboard.summary.organizers_count, 0)
  assert.deepEqual(dashboard.organizers, [])
  assert.deepEqual(dashboard.events, [])
})
