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
            conversion_rate: 66.7,
            avg_revenue_per_registration: 375000,
          },
          analytics: {
            registration_funnel: { pending: 2, confirmed: 8, cancelled: 1 },
            registrations_timeline: [{ date: '2026-06-19', registrations_count: 3 }],
            event_type_breakdown: [{ event_type: 'in_person', events_count: 1, registrations_count: 8, revenue_total: 3000000 }],
            top_events: [{ id: 10, title: 'رویداد تست', slug: 'test-event', registrations_count: 12, confirmed_registrations_count: 8, revenue_total: 4500000 }],
          },
          organizers: [{ id: 3, name: 'رخداد', slug: 'rokhdad', role: 'owner', events_count: 2 }],
          events: [{ id: 10, title: 'رویداد تست', slug: 'test-event', is_internal: true, registrations_count: 12 }],
        },
      }
    },
  }

  const dashboard = await loadOrganizerDashboard(api, 'token-123')

  assert.deepEqual(calls, ['token-123'])
  assert.equal(dashboard.summary.revenue_total, 4500000)
  assert.equal(dashboard.summary.conversion_rate, 66.7)
  assert.equal(dashboard.organizers[0].href, '/organizers/rokhdad')
  assert.equal(dashboard.events[0].href, '/events/test-event')
  assert.equal(dashboard.events[0].is_internal, true)
  assert.equal(dashboard.events[0].attendees_export_href, '/api/v1/me/events/10/attendees/export')
  assert.equal(dashboard.analytics.top_events[0].href, '/events/test-event')
})

test('normalizeOrganizerDashboard provides empty-state fallbacks', () => {
  const dashboard = normalizeOrganizerDashboard()

  assert.equal(dashboard.summary.organizers_count, 0)
  assert.deepEqual(dashboard.organizers, [])
  assert.deepEqual(dashboard.events, [])
  assert.equal(dashboard.analytics.registration_funnel.confirmed, 0)
})
