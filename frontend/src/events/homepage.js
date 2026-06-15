const eventTypeLabels = {
  in_person: 'حضوری',
  online: 'آنلاین',
  hybrid: 'ترکیبی',
}

export async function loadHomepageEvents(api, query = {}) {
  const payload = await api.listEvents({
    per_page: 6,
    ...query,
  })

  const events = Array.isArray(payload?.data) ? payload.data : []

  return {
    events: events.map(normalizeHomepageEvent),
    meta: payload?.meta || null,
  }
}

export function normalizeHomepageEvent(event) {
  return {
    id: event.id,
    title: event.title || 'رویداد بدون عنوان',
    slug: event.slug,
    summary: event.summary || 'توضیحات کوتاه برای این رویداد هنوز ثبت نشده است.',
    date: formatEventDate(event.starts_at),
    location: getEventLocation(event),
    organizer: event.organizer?.name || 'برگزارکننده نامشخص',
    category: event.category?.name || 'عمومی',
    badge: eventTypeLabels[event.event_type] || 'رویداد',
    href: event.slug ? `/events/${event.slug}` : '#',
  }
}

export function getEventLocation(event) {
  if (event.event_type === 'online') {
    return 'آنلاین'
  }

  if (event.city?.name && event.venue_name) {
    return `${event.city.name}، ${event.venue_name}`
  }

  return event.city?.name || event.venue_name || 'مکان اعلام می شود'
}

export function formatEventDate(value) {
  if (!value) {
    return 'زمان اعلام می شود'
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return 'زمان نامعتبر'
  }

  return new Intl.DateTimeFormat('fa-IR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}
