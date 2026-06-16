import assert from 'node:assert/strict'
import test from 'node:test'
import {
  buildEventFilterSearch,
  createDefaultEventFilters,
  getEventLocation,
  loadHomepageEvents,
  normalizeHomepageEvent,
  readEventFiltersFromSearch,
} from './homepage.js'

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

test('loadHomepageEvents forwards advanced filter query fields', async () => {
  const calls = []
  const api = {
    listEvents: async (query) => {
      calls.push(query)
      return { data: [], meta: { total: 0 } }
    },
  }

  await loadHomepageEvents(api, {
    q: 'Laravel',
    category: 'technology',
    city: 'tehran',
    event_type: 'online',
    source: 'evand',
    start_date: '2026-06-01',
    end_date: '2026-06-30',
  })

  assert.deepEqual(calls[0], {
    per_page: 6,
    q: 'Laravel',
    category: 'technology',
    city: 'tehran',
    event_type: 'online',
    source: 'evand',
    start_date: '2026-06-01',
    end_date: '2026-06-30',
  })
})

test('event filter URL helpers parse and build shareable query strings', () => {
  assert.deepEqual(readEventFiltersFromSearch('?q=Laravel&city=tehran&ignored=value'), {
    ...createDefaultEventFilters(),
    q: 'Laravel',
    city: 'tehran',
  })

  assert.equal(buildEventFilterSearch({
    ...createDefaultEventFilters(),
    q: ' Laravel ',
    category: 'technology',
    city: '',
    event_type: 'online',
    source: 'evand',
    start_date: '2026-06-01',
    end_date: '2026-06-30',
  }), 'q=Laravel&category=technology&event_type=online&source=evand&start_date=2026-06-01&end_date=2026-06-30')
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
  assert.equal(event.source, null)
})

test('normalizeHomepageEvent maps source attributions', () => {
  const event = normalizeHomepageEvent({
    id: 15,
    title: 'External Event',
    slug: 'external-event',
    role_title: 'Speaker',
    source_attributions: [
      {
        source_key: 'evand',
        external_url: 'https://evand.com/e/123',
      },
    ],
  })

  assert.deepEqual(event.source, {
    key: 'evand',
    label: 'ایوند',
    url: 'https://evand.com/e/123',
  })
  assert.equal(event.role_title, 'Speaker')
})

test('getEventLocation prefers online label for online events', () => {
  assert.equal(getEventLocation({
    event_type: 'online',
    city: { name: 'تهران' },
    venue_name: 'سالن',
  }), 'آنلاین')
})
