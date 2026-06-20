export class ApiError extends Error {
  constructor(message, { status, payload, url } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
    this.url = url
  }
}

export class NetworkError extends Error {
  constructor(message, { cause, url } = {}) {
    super(message)
    this.name = 'NetworkError'
    this.cause = cause
    this.url = url
  }
}

export class ApiValidationError extends ApiError {
  constructor(message, options = {}) {
    super(message, options)
    this.name = 'ApiValidationError'
    this.errors = options.payload?.errors || {}
  }
}

const defaultHeaders = {
  Accept: 'application/json',
}

export function createApiClient({
  baseUrl = '/api/v1',
  fetchImpl = globalThis.fetch,
  timeoutMs = 10000,
} = {}) {
  if (typeof fetchImpl !== 'function') {
    throw new TypeError('createApiClient requires a fetch implementation')
  }

  const normalizedBaseUrl = String(baseUrl).replace(/\/+$/, '')

  async function request(path, { query, signal, headers, ...init } = {}) {
    const url = buildUrl(normalizedBaseUrl, path, query)
    const controller = new AbortController()
    const timeout = setTimeout(() => controller.abort(), timeoutMs)

    if (signal) {
      signal.addEventListener('abort', () => controller.abort(), { once: true })
    }

    try {
      const response = await fetchImpl(url, {
        ...init,
        headers: {
          ...defaultHeaders,
          ...headers,
        },
        signal: controller.signal,
      })
      const payload = await parsePayload(response)

      if (!response.ok) {
        const message = payload?.message || `Request failed with status ${response.status}`
        const ErrorClass = response.status === 422 ? ApiValidationError : ApiError
        throw new ErrorClass(message, {
          status: response.status,
          payload,
          url,
        })
      }

      return payload
    } catch (error) {
      if (error instanceof ApiError) {
        throw error
      }

      const message = error?.name === 'AbortError' ? 'Request timed out' : 'Network request failed'
      throw new NetworkError(message, { cause: error, url })
    } finally {
      clearTimeout(timeout)
    }
  }

  return {
    request,
    listEvents: (query) => request('/events', { query }),
    getEvent: (slug) => request(`/events/${encodeURIComponent(slug)}`),
    listCategories: () => request('/categories'),
    listCities: () => request('/cities'),
    listOrganizers: (query) => request('/organizers', { query }),
    getOrganizer: (slug) => request(`/organizers/${encodeURIComponent(slug)}`),
    listPeople: (query) => request('/people', { query }),
    getPerson: (slug) => request(`/people/${encodeURIComponent(slug)}`),
    getOrganizerDashboard: (token) => request('/me/organizer-dashboard', {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    }),
    listCampaigns: (token) => request('/me/campaigns', {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    }),
    createCampaign: (payload, token) => request('/me/campaigns', {
      method: 'POST',
      headers: {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    }),
    simulateCampaign: (campaignId, token) => request(`/me/campaigns/${encodeURIComponent(campaignId)}/simulate`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    }),
    exportEventAttendees: (eventId, token) => request(`/me/events/${encodeURIComponent(eventId)}/attendees/export`, {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    }),
    importEventAttendees: (eventId, file, token) => {
      const formData = new FormData()
      formData.append('file', file)

      return request(`/me/events/${encodeURIComponent(eventId)}/attendees/import`, {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : undefined,
        body: formData,
      })
    },
  }
}

export function getApiBaseUrl() {
  return import.meta.env.VITE_API_BASE_URL || '/api/v1'
}

export function createRokhdadApi(options = {}) {
  return createApiClient({
    baseUrl: getApiBaseUrl(),
    ...options,
  })
}

function buildUrl(baseUrl, path, query) {
  const normalizedPath = `/${String(path).replace(/^\/+/, '')}`
  const url = `${baseUrl}${normalizedPath}`

  if (!query || Object.keys(query).length === 0) {
    return url
  }

  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') {
      continue
    }

    if (Array.isArray(value)) {
      value.forEach((item) => params.append(key, String(item)))
      continue
    }

    params.set(key, String(value))
  }

  const queryString = params.toString()
  return queryString ? `${url}?${queryString}` : url
}

async function parsePayload(response) {
  if (response.status === 204) {
    return null
  }

  const text = await response.text()
  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return { message: text }
  }
}
