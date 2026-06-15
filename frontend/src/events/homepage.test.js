import assert from 'node:assert/strict'
import test from 'node:test'
import { getEventLocation, loadHomepageEvents, normalizeHomepageEvent } from './homepage.js'

test('loadHomepageEvents requests published event listing and maps cards', async () => {
  const calls = []
  const api = {
    listEvents: async (query) => {
      calls.push(query)
      return {
        data: [
          {
            id: 10,
            title: 'Vue Conf Tehran',
            slug: 'vue-conf-tehran',
            summary: 'Frontend event',
            starts_at: '2026-06-20T08:30:00.000000Z',
            event_type: 'in_person',
            venue_name: 'سالن رویش',
            city: { name: 'تهران' },
            category: { name: 'فناوری' },
            organizer: { name: 'رخداد' },
          },
        ],
        meta: { total: 1 },
      }
    },
  }

  const result = await loadHomepageEvents(api, { city: 'tehran' })

  assert.deepEqual(calls, [{ per_page: 6, city: 'tehran' }])
  assert.equal(result.meta.total, 1)
  assert.equal(result.events[0].title, 'Vue Conf Tehran')
  assert.equal(result.events[0].location, 'تهران، سالن رویش')
  assert.equal(result.events[0].category, 'فناوری')
  assert.equal(result.events[0].href, '/events/vue-conf-tehran')
})

test('normalizeHomepageEvent provides safe fallbacks', () => {
  const event = normalizeHomepageEvent({
    id: 12,
    slug: null,
    starts_at: null,
    event_type: 'hybrid',
    city: null,
    organizer: null,
    category: null,
  })

  assert.equal(event.title, 'رویداد بدون عنوان')
  assert.equal(event.date, 'زمان اعلام می شود')
  assert.equal(event.location, 'مکان اعلام می شود')
  assert.equal(event.organizer, 'برگزارکننده نامشخص')
  assert.equal(event.category, 'عمومی')
  assert.equal(event.badge, 'ترکیبی')
  assert.equal(event.href, '#')
})

test('getEventLocation prefers online label for online events', () => {
  assert.equal(getEventLocation({
    event_type: 'online',
    city: { name: 'تهران' },
    venue_name: 'سالن',
  }), 'آنلاین')
})
