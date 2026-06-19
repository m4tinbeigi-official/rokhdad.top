export async function loadOrganizerDashboard(api, token) {
  const payload = await api.getOrganizerDashboard(token)
  return normalizeOrganizerDashboard(payload?.data)
}

export function normalizeOrganizerDashboard(data = {}) {
  return {
    summary: {
      organizers_count: Number(data.summary?.organizers_count || 0),
      events_count: Number(data.summary?.events_count || 0),
      registrations_count: Number(data.summary?.registrations_count || 0),
      confirmed_registrations_count: Number(data.summary?.confirmed_registrations_count || 0),
      tickets_count: Number(data.summary?.tickets_count || 0),
      revenue_total: Number(data.summary?.revenue_total || 0),
      currency: data.summary?.currency || 'IRR',
      conversion_rate: Number(data.summary?.conversion_rate || 0),
      avg_revenue_per_registration: Number(data.summary?.avg_revenue_per_registration || 0),
    },
    analytics: normalizeAnalytics(data.analytics),
    organizers: Array.isArray(data.organizers) ? data.organizers.map(normalizeOrganizer) : [],
    events: Array.isArray(data.events) ? data.events.map(normalizeOrganizerEvent) : [],
  }
}

function normalizeAnalytics(analytics = {}) {
  return {
    registration_funnel: {
      pending: Number(analytics.registration_funnel?.pending || 0),
      confirmed: Number(analytics.registration_funnel?.confirmed || 0),
      cancelled: Number(analytics.registration_funnel?.cancelled || 0),
    },
    registrations_timeline: Array.isArray(analytics.registrations_timeline)
      ? analytics.registrations_timeline.map((point) => ({
        date: point.date || '',
        registrations_count: Number(point.registrations_count || 0),
      }))
      : [],
    event_type_breakdown: Array.isArray(analytics.event_type_breakdown)
      ? analytics.event_type_breakdown.map((item) => ({
        event_type: item.event_type || 'in_person',
        events_count: Number(item.events_count || 0),
        registrations_count: Number(item.registrations_count || 0),
        revenue_total: Number(item.revenue_total || 0),
      }))
      : [],
    top_events: Array.isArray(analytics.top_events)
      ? analytics.top_events.map((event) => ({
        id: event.id,
        title: event.title || 'رویداد بدون عنوان',
        slug: event.slug || '',
        registrations_count: Number(event.registrations_count || 0),
        confirmed_registrations_count: Number(event.confirmed_registrations_count || 0),
        revenue_total: Number(event.revenue_total || 0),
        href: event.slug ? `/events/${event.slug}` : '#',
      }))
      : [],
  }
}

function normalizeOrganizer(organizer = {}) {
  return {
    id: organizer.id,
    name: organizer.name || 'برگزارکننده بدون نام',
    slug: organizer.slug || '',
    role: organizer.role || 'member',
    events_count: Number(organizer.events_count || 0),
    href: organizer.slug ? `/organizers/${organizer.slug}` : '#',
  }
}

function normalizeOrganizerEvent(event = {}) {
  return {
    id: event.id,
    title: event.title || 'رویداد بدون عنوان',
    slug: event.slug || '',
    status: event.status || 'draft',
    is_internal: Boolean(event.is_internal),
    starts_at: event.starts_at || null,
    event_type: event.event_type || 'in_person',
    capacity: event.capacity,
    registration_open: Boolean(event.registration_open),
    registrations_count: Number(event.registrations_count || 0),
    confirmed_registrations_count: Number(event.confirmed_registrations_count || 0),
    tickets_count: Number(event.tickets_count || 0),
    revenue_total: Number(event.revenue_total || 0),
    attendees_export_href: event.id ? `/api/v1/me/events/${event.id}/attendees/export` : '#',
    organizer: event.organizer?.name || 'برگزارکننده نامشخص',
    city: event.city?.name || 'شهر نامشخص',
    category: event.category?.name || 'عمومی',
    href: event.slug ? `/events/${event.slug}` : '#',
  }
}
