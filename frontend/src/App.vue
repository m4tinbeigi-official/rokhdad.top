<script setup>
import { computed, onMounted, ref } from 'vue'
import { ApiError, NetworkError, createRokhdadApi, getApiBaseUrl } from './api/client.js'
import {
  buildEventFilterSearch,
  createDefaultEventFilters,
  formatEventDate,
  getEventLocation,
  loadHomepageEvents,
  normalizeHomepageEvent,
  readEventFiltersFromSearch,
} from './events/homepage.js'
import { loadCategoryDirectory, loadCityDirectory } from './lookups/directory.js'

const apiBaseUrl = getApiBaseUrl()
const api = createRokhdadApi()
const currentPath = window.location.pathname
const events = ref([])
const meta = ref(null)
const isLoading = ref(true)
const error = ref(null)
const hasEvents = computed(() => events.value.length > 0)
const directoryItems = ref([])
const directoryLoading = ref(false)
const directoryError = ref(null)
const detailItem = ref(null)
const registrationFeedback = ref(null)
const isRegistering = ref(false)
const filterOptions = ref({
  categories: [],
  cities: [],
})
const filtersLoading = ref(false)
const eventFilters = ref(readEventFiltersFromSearch(window.location.search))
const hasActiveFilters = computed(() => Object.values(eventFilters.value).some(Boolean))

const pageKind = computed(() => {
  if (currentPath === '/categories') {
    return 'categories'
  }
  if (currentPath === '/cities') {
    return 'cities'
  }
  if (currentPath.startsWith('/categories/')) {
    return 'category-detail'
  }
  if (currentPath.startsWith('/cities/')) {
    return 'city-detail'
  }
  if (currentPath.startsWith('/events/')) {
    return 'event-detail'
  }
  if (currentPath.startsWith('/organizers/')) {
    return 'organizer-detail'
  }
  if (currentPath.startsWith('/people/')) {
    return 'person-detail'
  }
  return 'home'
})

const currentSlug = computed(() => {
  if (pageKind.value === 'category-detail') {
    return currentPath.substring('/categories/'.length)
  }
  if (pageKind.value === 'city-detail') {
    return currentPath.substring('/cities/'.length)
  }
  if (pageKind.value === 'event-detail') {
    return currentPath.substring('/events/'.length)
  }
  if (pageKind.value === 'organizer-detail') {
    return currentPath.substring('/organizers/'.length)
  }
  if (pageKind.value === 'person-detail') {
    return currentPath.substring('/people/'.length)
  }
  return null
})

const directoryTitle = computed(() => pageKind.value === 'cities' ? 'شهرها' : 'دسته بندی ها')
const directoryDescription = computed(() => (
  pageKind.value === 'cities'
    ? 'شهرهای فعال برای مرور و فیلتر رویدادهای آینده.'
    : 'دسته بندی های فعال برای کشف سریع تر رویدادها.'
))

onMounted(() => {
  if (pageKind.value === 'home') {
    fetchEvents()
    fetchFilterOptions()
  } else if (pageKind.value === 'categories' || pageKind.value === 'cities') {
    fetchDirectory()
  } else if (
    pageKind.value === 'category-detail' ||
    pageKind.value === 'city-detail' ||
    pageKind.value === 'event-detail' ||
    pageKind.value === 'organizer-detail' ||
    pageKind.value === 'person-detail'
  ) {
    fetchDetail()
  }
})

async function fetchEvents() {
  isLoading.value = true
  error.value = null
  syncFilterUrl()

  try {
    const result = await loadHomepageEvents(api, eventFilters.value)
    events.value = result.events
    meta.value = result.meta
  } catch (caught) {
    error.value = getErrorMessage(caught)
    events.value = []
    meta.value = null
  } finally {
    isLoading.value = false
  }
}

async function fetchFilterOptions() {
  filtersLoading.value = true

  try {
    const [categories, cities] = await Promise.all([
      loadCategoryDirectory(api),
      loadCityDirectory(api),
    ])
    filterOptions.value = { categories, cities }
  } catch {
    filterOptions.value = { categories: [], cities: [] }
  } finally {
    filtersLoading.value = false
  }
}

function resetFilters() {
  eventFilters.value = createDefaultEventFilters()
  fetchEvents()
}

function syncFilterUrl() {
  const search = buildEventFilterSearch(eventFilters.value)
  const nextUrl = search ? `${currentPath}?${search}` : currentPath

  if (`${window.location.pathname}${window.location.search}` !== nextUrl) {
    window.history.replaceState({}, '', nextUrl)
  }
}

async function fetchDirectory() {
  directoryLoading.value = true
  directoryError.value = null

  try {
    directoryItems.value = pageKind.value === 'cities'
      ? await loadCityDirectory(api)
      : await loadCategoryDirectory(api)
  } catch (caught) {
    directoryError.value = getErrorMessage(caught)
    directoryItems.value = []
  } finally {
    directoryLoading.value = false
  }
}

async function fetchDetail() {
  isLoading.value = true
  error.value = null
  detailItem.value = null
  registrationFeedback.value = null
  events.value = []

  try {
    const slug = currentSlug.value
    if (pageKind.value === 'category-detail') {
      const categoriesPayload = await api.listCategories()
      const categories = Array.isArray(categoriesPayload?.data) ? categoriesPayload.data : []
      const matched = categories.find(c => c.slug === slug)
      if (!matched) {
        throw new ApiError('دسته بندی مورد نظر یافت نشد.', { status: 404 })
      }
      detailItem.value = {
        title: matched.name,
        description: matched.description || 'رویدادهای این دسته بندی در زیر نمایش داده شده اند.'
      }
      
      const eventsResult = await loadHomepageEvents(api, { category: slug })
      events.value = eventsResult.events
      meta.value = eventsResult.meta
    } else if (pageKind.value === 'city-detail') {
      const citiesPayload = await api.listCities()
      const cities = Array.isArray(citiesPayload?.data) ? citiesPayload.data : []
      const matched = cities.find(c => c.slug === slug)
      if (!matched) {
        throw new ApiError('شهر مورد نظر یافت نشد.', { status: 404 })
      }
      detailItem.value = {
        title: matched.name,
        description: matched.province ? `استان ${matched.province}` : 'رویدادهای این شهر در زیر نمایش داده شده اند.'
      }
      
      const eventsResult = await loadHomepageEvents(api, { city: slug })
      events.value = eventsResult.events
      meta.value = eventsResult.meta
    } else if (pageKind.value === 'event-detail') {
      const eventPayload = await api.getEvent(slug)
      const rawEvent = eventPayload?.data
      if (!rawEvent) {
        throw new ApiError('رویداد مورد نظر یافت نشد.', { status: 404 })
      }
      detailItem.value = {
        id: rawEvent.id,
        title: rawEvent.title,
        description: rawEvent.description || 'توضیحات بیشتری برای این رویداد ثبت نشده است.',
        summary: rawEvent.summary || 'خلاصه ای برای این رویداد ثبت نشده است.',
        badge: { in_person: 'حضوری', online: 'آنلاین', hybrid: 'ترکیبی' }[rawEvent.event_type] || 'رویداد',
        date: formatEventDate(rawEvent.starts_at),
        endDate: rawEvent.ends_at ? formatEventDate(rawEvent.ends_at) : null,
        location: getEventLocation(rawEvent),
        venue_name: rawEvent.venue_name,
        venue_address: rawEvent.venue_address,
        online_url: rawEvent.online_url,
        canonical_url: rawEvent.canonical_url,
        is_internal: rawEvent.is_internal,
        registration_open: rawEvent.registration_open,
        registration_instructions: rawEvent.registration_instructions,
        category: rawEvent.category,
        city: rawEvent.city,
        organizer: rawEvent.organizer,
        people: rawEvent.people || [],
        source: rawEvent.source_attributions?.[0] ? {
          key: rawEvent.source_attributions[0].source_key,
          label: { evand: 'ایوند', eseminar: 'ایسمینار', bilitmaster: 'بلیط‌مستر' }[rawEvent.source_attributions[0].source_key] || rawEvent.source_attributions[0].source_key,
          url: rawEvent.source_attributions[0].external_url || '#',
        } : null,
      }
    } else if (pageKind.value === 'organizer-detail') {
      const orgPayload = await api.getOrganizer(slug)
      const rawOrg = orgPayload?.data
      if (!rawOrg) {
        throw new ApiError('برگزارکننده مورد نظر یافت نشد.', { status: 404 })
      }
      detailItem.value = {
        name: rawOrg.name,
        description: rawOrg.description || 'توضیحاتی ثبت نشده است.',
        website_url: rawOrg.website_url,
        social_links: rawOrg.social_links || {},
        city: rawOrg.city,
        people: rawOrg.people || [],
      }
      events.value = (rawOrg.events || []).map(normalizeHomepageEvent)
      meta.value = { total: events.value.length }
    } else if (pageKind.value === 'person-detail') {
      const personPayload = await api.getPerson(slug)
      const rawPerson = personPayload?.data
      if (!rawPerson) {
        throw new ApiError('شخص مورد نظر یافت نشد.', { status: 404 })
      }
      detailItem.value = {
        full_name: rawPerson.full_name,
        title: rawPerson.title || 'کارشناس / سخنران',
        bio: rawPerson.bio || 'بیوگرافی ثبت نشده است.',
        website_url: rawPerson.website_url,
        social_links: rawPerson.social_links || {},
        organizers: rawPerson.organizers || [],
      }
      events.value = (rawPerson.events || []).map(normalizeHomepageEvent)
      meta.value = { total: events.value.length }
    }
  } catch (caught) {
    error.value = getErrorMessage(caught)
  } finally {
    isLoading.value = false
  }
}

async function submitEventRegistration() {
  if (!detailItem.value?.slug && !currentSlug.value) {
    return
  }

  const token = window.localStorage.getItem('rokhdad_api_token')
  if (!token) {
    registrationFeedback.value = 'برای ثبت نام مستقیم، ابتدا وارد حساب کاربری شوید.'
    return
  }

  isRegistering.value = true
  registrationFeedback.value = null

  try {
    const payload = await api.request(`/events/${encodeURIComponent(currentSlug.value)}/registrations`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ quantity: 1 }),
    })
    registrationFeedback.value = payload?.data?.status === 'confirmed'
      ? 'ثبت نام شما با موفقیت تایید شد.'
      : 'درخواست ثبت نام شما ثبت شد و در انتظار بررسی است.'
  } catch (caught) {
    registrationFeedback.value = getErrorMessage(caught)
  } finally {
    isRegistering.value = false
  }
}

function getErrorMessage(caught) {
  if (caught instanceof NetworkError) {
    return 'ارتباط با سرور برقرار نشد. چند لحظه بعد دوباره تلاش کنید.'
  }

  if (caught instanceof ApiError) {
    if (caught.status === 404) {
      return 'داده یا مسیر مورد نظر یافت نشد.'
    }

    if (caught.status >= 500) {
      return 'سرور در حال حاضر پاسخ مناسبی نمی دهد.'
    }

    return caught.message || 'دریافت اطلاعات با خطا روبه رو شد.'
  }

  return 'خطای پیش بینی نشده رخ داد.'
}
</script>

<template>
  <div class="min-h-screen bg-canvas text-ink">
    <header class="border-b border-line bg-surface/95">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a class="text-2xl font-black text-brand-900" href="/" aria-label="رخداد">
          رخداد
        </a>
        <nav class="hidden items-center gap-2 text-sm font-bold text-muted md:flex" aria-label="ناوبری اصلی">
          <a class="rounded-md px-3 py-2 text-brand-800 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="/">
            رویدادها
          </a>
          <a class="rounded-md px-3 py-2 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="/categories">
            دسته بندی ها
          </a>
          <a class="rounded-md px-3 py-2 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="/cities">
            شهرها
          </a>
        </nav>
        <button class="rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
          ثبت رویداد
        </button>
      </div>
    </header>

    <main v-if="pageKind === 'home'" class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <section class="grid gap-5 rounded-lg border border-line bg-surface p-5 shadow-soft lg:grid-cols-[1fr_360px] lg:items-end lg:p-7">
        <div>
          <p class="mb-2 text-sm font-bold text-brand-700">کشف رویداد</p>
          <h1 class="max-w-3xl text-3xl font-black leading-tight text-ink sm:text-4xl">
            کشف رویدادهای فناوری، کسب وکار و آموزش در ایران
          </h1>
          <p class="mt-4 max-w-2xl text-base leading-8 text-muted">
            تازه ترین رویدادهای منتشرشده از API رخداد خوانده می شوند و این صفحه برای فیلترهای بعدی آماده است.
          </p>
        </div>

        <form class="grid gap-3 rounded-lg border border-line bg-canvas p-4" role="search" aria-label="جستجوی رویداد" @submit.prevent="fetchEvents">
          <label class="grid gap-1 text-sm font-bold text-ink">
            جستجو
            <input
              v-model.trim="eventFilters.q"
              class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
              type="search"
              placeholder="نام رویداد، شهر یا موضوع"
            />
          </label>
          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1 text-sm font-bold text-ink">
              شهر
              <select v-model="eventFilters.city" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option value="">همه شهرها</option>
                <option v-for="city in filterOptions.cities" :key="city.slug" :value="city.slug">
                  {{ city.title }}
                </option>
              </select>
            </label>
            <label class="grid gap-1 text-sm font-bold text-ink">
              نوع
              <select v-model="eventFilters.event_type" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option value="">همه</option>
                <option value="in_person">حضوری</option>
                <option value="online">آنلاین</option>
                <option value="hybrid">ترکیبی</option>
              </select>
            </label>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1 text-sm font-bold text-ink">
              دسته
              <select v-model="eventFilters.category" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option value="">همه دسته ها</option>
                <option v-for="category in filterOptions.categories" :key="category.slug" :value="category.slug">
                  {{ category.title }}
                </option>
              </select>
            </label>
            <label class="grid gap-1 text-sm font-bold text-ink">
              منبع
              <select v-model="eventFilters.source" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option value="">همه منابع</option>
                <option value="evand">ایوند</option>
                <option value="eseminar">ایسمینار</option>
                <option value="bilitmaster">بلیط مستر</option>
              </select>
            </label>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1 text-sm font-bold text-ink">
              از تاریخ
              <input v-model="eventFilters.start_date" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100" type="date" />
            </label>
            <label class="grid gap-1 text-sm font-bold text-ink">
              تا تاریخ
              <input v-model="eventFilters.end_date" class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100" type="date" />
            </label>
          </div>
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-bold text-muted">
              <template v-if="filtersLoading">در حال دریافت گزینه های فیلتر...</template>
              <template v-else-if="hasActiveFilters">فیلترها روی نتایج API اعمال می شوند.</template>
              <template v-else>بدون فیلتر فعال</template>
            </p>
            <div class="flex gap-2">
              <button
                v-if="hasActiveFilters"
                class="rounded-md border border-line bg-white px-3 py-2 text-sm font-bold text-muted hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                type="button"
                @click="resetFilters"
              >
                پاک کردن
              </button>
              <button
                class="rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                type="submit"
              >
                اعمال فیلتر
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="grid gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div>
            <h2 class="text-xl font-black text-ink">رویدادهای منتخب</h2>
            <p class="mt-1 text-sm text-muted">
              <template v-if="meta?.total">نمایش {{ events.length }} رویداد از {{ meta.total }} نتیجه</template>
              <template v-else>رویدادهای منتشرشده از API عمومی رخداد</template>
            </p>
          </div>
          <p class="rounded-md border border-line bg-surface px-3 py-2 text-xs font-bold text-muted" dir="ltr">
            API: {{ apiBaseUrl }}
          </p>
        </div>

        <div v-if="isLoading" class="grid gap-3 md:grid-cols-3" aria-live="polite" aria-label="در حال بارگذاری رویدادها">
          <article
            v-for="index in 3"
            :key="index"
            class="min-h-52 animate-pulse rounded-lg border border-line bg-surface p-4 shadow-soft"
          >
            <div class="mb-5 h-6 w-24 rounded-md bg-brand-50"></div>
            <div class="h-6 w-4/5 rounded-md bg-line"></div>
            <div class="mt-4 h-4 w-full rounded-md bg-line"></div>
            <div class="mt-2 h-4 w-2/3 rounded-md bg-line"></div>
          </article>
        </div>

        <div v-else-if="error" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
          <h3 class="text-base font-black text-ink">دریافت رویدادها ناموفق بود</h3>
          <p class="mt-2 text-sm leading-7 text-muted">{{ error }}</p>
          <button
            class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            type="button"
            @click="fetchEvents"
          >
            تلاش دوباره
          </button>
        </div>

        <div v-else-if="!hasEvents" class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm leading-7 text-muted">
          هنوز رویداد منتشرشده ای برای نمایش وجود ندارد.
        </div>

        <div v-else class="grid gap-3 md:grid-cols-3">
          <article
            v-for="event in events"
            :key="event.id || event.slug || event.title"
            class="rounded-lg border border-line bg-surface p-4 shadow-soft"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ event.badge }}</span>
              <span class="text-xs font-bold text-muted">{{ event.date }}</span>
            </div>
            <h3 class="text-lg font-black leading-8 text-ink">
              <a class="hover:text-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600" :href="event.href">
                {{ event.title }}
              </a>
            </h3>
            <p class="mt-2 line-clamp-3 text-sm leading-7 text-muted">{{ event.summary }}</p>

            <!-- External Source Badge (P16-003) -->
            <div v-if="event.source" class="mt-3 flex items-center gap-2">
              <span class="text-xs text-muted">منبع اصلی:</span>
              <a :href="event.source.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded bg-muted-100 px-2 py-0.5 text-xs font-bold text-brand-800 hover:bg-brand-50 hover:text-brand-900 border border-brand-200">
                {{ event.source.label }}
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </div>

            <dl class="mt-4 grid gap-2 text-sm text-muted">
              <div class="flex items-center justify-between gap-3">
                <dt>مکان</dt>
                <dd class="font-bold text-ink">{{ event.location }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt>برگزارکننده</dt>
                <dd class="font-bold text-ink">{{ event.organizer }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt>دسته</dt>
                <dd class="font-bold text-ink">{{ event.category }}</dd>
              </div>
            </dl>
          </article>
        </div>
      </section>
    </main>

    <main v-else-if="pageKind === 'categories' || pageKind === 'cities'" class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
      <section class="rounded-lg border border-line bg-surface p-5 shadow-soft lg:p-7">
        <p class="mb-2 text-sm font-bold text-brand-700">مرور سریع</p>
        <h1 class="text-3xl font-black leading-tight text-ink sm:text-4xl">{{ directoryTitle }}</h1>
        <p class="mt-4 max-w-2xl text-base leading-8 text-muted">{{ directoryDescription }}</p>
      </section>

      <section v-if="directoryLoading" class="grid gap-3 md:grid-cols-3" aria-live="polite" aria-label="در حال بارگذاری فهرست">
        <article v-for="index in 6" :key="index" class="min-h-36 animate-pulse rounded-lg border border-line bg-surface p-4 shadow-soft">
          <div class="h-6 w-2/3 rounded-md bg-line"></div>
          <div class="mt-4 h-4 w-full rounded-md bg-line"></div>
          <div class="mt-2 h-4 w-1/2 rounded-md bg-line"></div>
        </article>
      </section>

      <section v-else-if="directoryError" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
        <h2 class="text-base font-black text-ink">دریافت فهرست ناموفق بود</h2>
        <p class="mt-2 text-sm leading-7 text-muted">{{ directoryError }}</p>
        <button
          class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
          type="button"
          @click="fetchDirectory"
        >
          تلاش دوباره
        </button>
      </section>

      <section v-else-if="directoryItems.length === 0" class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm leading-7 text-muted">
        هنوز آیتم فعالی برای نمایش وجود ندارد.
      </section>

      <section v-else class="grid gap-3 md:grid-cols-3">
        <article
          v-for="item in directoryItems"
          :key="item.id || item.slug || item.title"
          class="rounded-lg border border-line bg-surface p-4 shadow-soft"
        >
          <div class="mb-4 flex items-center justify-between gap-3">
            <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ item.meta }}</span>
          </div>
          <h2 class="text-lg font-black leading-8 text-ink">
            <a class="hover:text-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600" :href="item.href">
              {{ item.title }}
            </a>
          </h2>
          <p class="mt-2 text-sm leading-7 text-muted">{{ item.description }}</p>
        </article>
      </section>
    </main>

    <main v-else-if="pageKind === 'category-detail' || pageKind === 'city-detail'" class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <section class="rounded-lg border border-line bg-surface p-5 shadow-soft lg:p-7">
        <p class="mb-2 text-sm font-bold text-brand-700">
          {{ pageKind === 'category-detail' ? 'رویدادهای دسته بندی' : 'رویدادهای شهر' }}
        </p>
        <h1 class="text-3xl font-black leading-tight text-ink sm:text-4xl">
          {{ detailItem?.title || 'در حال بارگذاری...' }}
        </h1>
        <p class="mt-4 max-w-2xl text-base leading-8 text-muted">
          {{ detailItem?.description || 'توضیحات این بخش در دسترس نیست.' }}
        </p>
      </section>

      <section class="grid gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div>
            <h2 class="text-xl font-black text-ink">رویدادها</h2>
            <p class="mt-1 text-sm text-muted">
              <template v-if="meta?.total">نمایش {{ events.length }} رویداد از {{ meta.total }} نتیجه</template>
              <template v-else>رویدادهای ثبت شده در این بخش</template>
            </p>
          </div>
        </div>

        <div v-if="isLoading" class="grid gap-3 md:grid-cols-3" aria-live="polite" aria-label="در حال بارگذاری رویدادها">
          <article v-for="index in 3" :key="index" class="min-h-52 animate-pulse rounded-lg border border-line bg-surface p-4 shadow-soft">
            <div class="mb-5 h-6 w-24 rounded-md bg-brand-50"></div>
            <div class="h-6 w-4/5 rounded-md bg-line"></div>
            <div class="mt-4 h-4 w-full rounded-md bg-line"></div>
            <div class="mt-2 h-4 w-2/3 rounded-md bg-line"></div>
          </article>
        </div>

        <div v-else-if="error" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
          <h3 class="text-base font-black text-ink">دریافت رویدادها ناموفق بود</h3>
          <p class="mt-2 text-sm leading-7 text-muted">{{ error }}</p>
          <button
            class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            type="button"
            @click="fetchDetail"
          >
            تلاش دوباره
          </button>
        </div>

        <div v-else-if="!hasEvents" class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm leading-7 text-muted">
          هیچ رویدادی در این بخش یافت نشد.
        </div>

        <div v-else class="grid gap-3 md:grid-cols-3">
          <article
            v-for="event in events"
            :key="event.id || event.slug || event.title"
            class="rounded-lg border border-line bg-surface p-4 shadow-soft"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ event.badge }}</span>
              <span class="text-xs font-bold text-muted">{{ event.date }}</span>
            </div>
            <h3 class="text-lg font-black leading-8 text-ink">
              <a class="hover:text-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600" :href="event.href">
                {{ event.title }}
              </a>
            </h3>
            <p class="mt-2 line-clamp-3 text-sm leading-7 text-muted">{{ event.summary }}</p>

            <!-- External Source Badge (P16-003) -->
            <div v-if="event.source" class="mt-3 flex items-center gap-2">
              <span class="text-xs text-muted">منبع اصلی:</span>
              <a :href="event.source.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded bg-muted-100 px-2 py-0.5 text-xs font-bold text-brand-800 hover:bg-brand-50 hover:text-brand-900 border border-brand-200">
                {{ event.source.label }}
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </div>

            <dl class="mt-4 grid gap-2 text-sm text-muted">
              <div class="flex items-center justify-between gap-3">
                <dt>مکان</dt>
                <dd class="font-bold text-ink">{{ event.location }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt>برگزارکننده</dt>
                <dd class="font-bold text-ink">{{ event.organizer }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt>دسته</dt>
                <dd class="font-bold text-ink">{{ event.category }}</dd>
              </div>
            </dl>
          </article>
        </div>
      </section>
    </main>

    <!-- Event Detail Page (P17-001) -->
    <main v-else-if="pageKind === 'event-detail'" class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <div v-if="isLoading" class="min-h-96 flex items-center justify-center">
        <div class="text-brand-500 font-bold animate-pulse text-lg">در حال بارگذاری جزئیات رویداد...</div>
      </div>
      <div v-else-if="error" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
        <h3 class="text-base font-black text-ink">خطا در دریافت اطلاعات رویداد</h3>
        <p class="mt-2 text-sm leading-7 text-muted">{{ error }}</p>
        <button
          class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2"
          type="button"
          @click="fetchDetail"
        >
          تلاش دوباره
        </button>
      </div>
      <div v-else-if="detailItem" class="grid gap-8 lg:grid-cols-[2fr_1fr]">
        <!-- Left Column: Details -->
        <article class="grid gap-6 rounded-lg border border-line bg-surface p-5 shadow-soft lg:p-7">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
            <div>
              <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ detailItem.badge }}</span>
            </div>
            <div class="text-xs font-bold text-muted" dir="ltr">شروع: {{ detailItem.date }}</div>
          </div>
          
          <h1 class="text-3xl font-black leading-tight text-ink">{{ detailItem.title }}</h1>
          <p class="text-base leading-8 text-muted border-r-4 border-brand-500 pr-4 italic">{{ detailItem.summary }}</p>
          
          <div class="prose max-w-none text-ink leading-8 whitespace-pre-line mt-4">
            {{ detailItem.description }}
          </div>

          <!-- Speakers/People Section (P17-003) -->
          <section v-if="detailItem.people.length > 0" class="mt-8 border-t border-line pt-6">
            <h2 class="text-xl font-black text-ink mb-4">سخنرانان و ارائه‌دهندگان</h2>
            <div class="grid gap-4 sm:grid-cols-2">
              <a v-for="person in detailItem.people" :key="person.id" :href="`/people/${person.slug}`" class="flex items-center gap-3 rounded-lg border border-line p-3 hover:border-brand-500 transition">
                <div class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-800 font-bold">
                  {{ person.full_name.charAt(0) }}
                </div>
                <div>
                  <h3 class="text-sm font-bold text-ink hover:text-brand-800">{{ person.full_name }}</h3>
                  <p class="text-xs text-muted">{{ person.role_title || 'سخنران' }}</p>
                </div>
              </a>
            </div>
          </section>
        </article>

        <!-- Right Column: Sidebar Info & Actions -->
        <aside class="grid gap-6 self-start">
          <!-- Information Card -->
          <div class="rounded-lg border border-line bg-surface p-5 shadow-soft">
            <h2 class="text-lg font-black text-ink mb-4 border-b border-line pb-2">اطلاعات برگزاری</h2>
            <dl class="grid gap-4 text-sm">
              <div>
                <dt class="font-bold text-muted">مکان</dt>
                <dd class="mt-1 text-ink font-bold">{{ detailItem.location }}</dd>
                <dd v-if="detailItem.venue_address" class="mt-1 text-xs text-muted">{{ detailItem.venue_address }}</dd>
              </div>
              <div>
                <dt class="font-bold text-muted">زمان شروع</dt>
                <dd class="mt-1 text-ink font-bold">{{ detailItem.date }}</dd>
              </div>
              <div v-if="detailItem.endDate">
                <dt class="font-bold text-muted">زمان پایان</dt>
                <dd class="mt-1 text-ink font-bold">{{ detailItem.endDate }}</dd>
              </div>
              <div v-if="detailItem.category">
                <dt class="font-bold text-muted">دسته‌بندی</dt>
                <dd class="mt-1">
                  <a :href="`/categories/${detailItem.category.slug}`" class="text-brand-700 hover:text-brand-900 font-bold">
                    {{ detailItem.category.name }}
                  </a>
                </dd>
              </div>
              <div v-if="detailItem.city">
                <dt class="font-bold text-muted">شهر</dt>
                <dd class="mt-1">
                  <a :href="`/cities/${detailItem.city.slug}`" class="text-brand-700 hover:text-brand-900 font-bold">
                    {{ detailItem.city.name }}
                  </a>
                </dd>
              </div>
              <div v-if="detailItem.organizer">
                <dt class="font-bold text-muted">برگزارکننده</dt>
                <dd class="mt-1">
                  <a :href="`/organizers/${detailItem.organizer.slug}`" class="text-brand-700 hover:text-brand-900 font-bold">
                    {{ detailItem.organizer.name }}
                  </a>
                </dd>
              </div>
            </dl>

            <!-- Call To Action -->
            <div class="mt-6 border-t border-line pt-4 grid gap-3">
              <a v-if="detailItem.source" :href="detailItem.source.url" target="_blank" rel="noopener noreferrer" class="rounded-md bg-brand-700 px-4 py-3 text-center text-sm font-bold text-white shadow-sm hover:bg-brand-800 transition">
                ثبت‌نام در {{ detailItem.source.label }}
              </a>
              <button
                v-else
                class="rounded-md bg-brand-700 px-4 py-3 text-center text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:bg-muted"
                type="button"
                :disabled="isRegistering || !detailItem.is_internal || !detailItem.registration_open"
                @click="submitEventRegistration"
              >
                ثبت‌نام مستقیم رویداد
              </button>
              <p v-if="detailItem.registration_instructions" class="text-xs leading-6 text-muted">
                {{ detailItem.registration_instructions }}
              </p>
              <p v-if="registrationFeedback" class="rounded-md border border-line bg-canvas px-3 py-2 text-xs font-bold leading-6 text-muted">
                {{ registrationFeedback }}
              </p>
              
              <a v-if="detailItem.canonical_url" :href="detailItem.canonical_url" target="_blank" rel="noopener noreferrer" class="text-center text-xs text-brand-600 hover:underline">
                وب‌سایت اصلی رویداد
              </a>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <!-- Organizer Public Page (P17-002) -->
    <main v-else-if="pageKind === 'organizer-detail'" class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <div v-if="isLoading" class="min-h-96 flex items-center justify-center">
        <div class="text-brand-500 font-bold animate-pulse text-lg">در حال بارگذاری پروفایل برگزارکننده...</div>
      </div>
      <div v-else-if="error" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
        <h3 class="text-base font-black text-ink">خطا در دریافت اطلاعات برگزارکننده</h3>
        <p class="mt-2 text-sm leading-7 text-muted">{{ error }}</p>
        <button
          class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2"
          type="button"
          @click="fetchDetail"
        >
          تلاش دوباره
        </button>
      </div>
      <div v-else-if="detailItem" class="grid gap-8 lg:grid-cols-[1fr_2fr]">
        <!-- Left: Organizer details -->
        <aside class="grid gap-6 self-start">
          <div class="rounded-lg border border-line bg-surface p-5 shadow-soft">
            <div class="h-16 w-16 rounded-full bg-brand-500 text-white font-black text-2xl flex items-center justify-center mb-4 mx-auto">
              {{ detailItem.name.charAt(0) }}
            </div>
            <h1 class="text-2xl font-black text-ink text-center mb-2">{{ detailItem.name }}</h1>
            <p v-if="detailItem.city" class="text-xs text-muted text-center mb-4">مستقر در {{ detailItem.city.name }}</p>
            
            <p class="text-sm leading-7 text-muted text-center border-t border-line pt-4 mb-4">
              {{ detailItem.description }}
            </p>

            <div class="grid gap-2 text-center text-sm border-t border-line pt-4">
              <a v-if="detailItem.website_url" :href="detailItem.website_url" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline font-bold">
                وب‌سایت برگزارکننده
              </a>
              <div v-if="Object.keys(detailItem.social_links).length > 0" class="flex justify-center gap-3 mt-2">
                <a v-for="(link, key) in detailItem.social_links" :key="key" :href="link" target="_blank" rel="noopener noreferrer" class="text-muted hover:text-brand-500 capitalize text-xs">
                  {{ key }}
                </a>
              </div>
            </div>
          </div>

          <!-- Organizer Team Members -->
          <div v-if="detailItem.people.length > 0" class="rounded-lg border border-line bg-surface p-5 shadow-soft">
            <h2 class="text-lg font-black text-ink mb-4 border-b border-line pb-2">اعضای برگزارکننده</h2>
            <div class="grid gap-3">
              <a v-for="person in detailItem.people" :key="person.id" :href="`/people/${person.slug}`" class="flex items-center gap-2 hover:text-brand-700 transition">
                <div class="h-6 w-6 rounded-full bg-brand-100 flex items-center justify-center text-[10px] font-bold text-brand-800">
                  {{ person.full_name.charAt(0) }}
                </div>
                <div class="text-sm">
                  <span class="font-bold text-ink hover:text-brand-800">{{ person.full_name }}</span>
                  <span class="text-xs text-muted pr-1">({{ person.role_title || 'عضو' }})</span>
                </div>
              </a>
            </div>
          </div>
        </aside>

        <!-- Right: Organizer events -->
        <section class="grid gap-4">
          <h2 class="text-xl font-black text-ink">رویدادهای برگزارکننده</h2>
          <div v-if="!hasEvents" class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm text-muted">
            هیچ رویداد فعالی برای این برگزارکننده یافت نشد.
          </div>
          <div v-else class="grid gap-4 md:grid-cols-2">
            <article v-for="event in events" :key="event.id" class="rounded-lg border border-line bg-surface p-4 shadow-soft">
              <div class="mb-4 flex items-center justify-between gap-3">
                <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ event.badge }}</span>
                <span class="text-xs font-bold text-muted">{{ event.date }}</span>
              </div>
              <h3 class="text-lg font-black leading-8 text-ink">
                <a class="hover:text-brand-800" :href="event.href">{{ event.title }}</a>
              </h3>
              <p class="mt-2 line-clamp-2 text-sm leading-7 text-muted">{{ event.summary }}</p>

              <!-- External Source Badge -->
              <div v-if="event.source" class="mt-3 flex items-center gap-2">
                <span class="text-xs text-muted">منبع اصلی:</span>
                <a :href="event.source.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded bg-muted-100 px-2 py-0.5 text-xs font-bold text-brand-800 hover:bg-brand-50 hover:text-brand-900 border border-brand-200">
                  {{ event.source.label }}
                </a>
              </div>

              <dl class="mt-4 grid gap-2 text-sm text-muted">
                <div class="flex items-center justify-between gap-3">
                  <dt>مکان</dt>
                  <dd class="font-bold text-ink">{{ event.location }}</dd>
                </div>
              </dl>
            </article>
          </div>
        </section>
      </div>
    </main>

    <!-- Person Public Page (P17-003) -->
    <main v-else-if="pageKind === 'person-detail'" class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <div v-if="isLoading" class="min-h-96 flex items-center justify-center">
        <div class="text-brand-500 font-bold animate-pulse text-lg">در حال بارگذاری پروفایل شخص...</div>
      </div>
      <div v-else-if="error" class="rounded-lg border border-line bg-surface p-5 shadow-soft" role="alert">
        <h3 class="text-base font-black text-ink">خطا در دریافت اطلاعات کارشناس</h3>
        <p class="mt-2 text-sm leading-7 text-muted">{{ error }}</p>
        <button
          class="mt-4 rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2"
          type="button"
          @click="fetchDetail"
        >
          تلاش دوباره
        </button>
      </div>
      <div v-else-if="detailItem" class="grid gap-8 lg:grid-cols-[1fr_2fr]">
        <!-- Left: Person Details -->
        <aside class="grid gap-6 self-start">
          <div class="rounded-lg border border-line bg-surface p-5 shadow-soft">
            <div class="h-16 w-16 rounded-full bg-brand-500 text-white font-black text-2xl flex items-center justify-center mb-4 mx-auto">
              {{ detailItem.full_name.charAt(0) }}
            </div>
            <h1 class="text-2xl font-black text-ink text-center mb-2">{{ detailItem.full_name }}</h1>
            <p class="text-sm text-brand-700 font-bold text-center mb-4">{{ detailItem.title }}</p>
            
            <p class="text-sm leading-7 text-muted text-center border-t border-line pt-4 mb-4">
              {{ detailItem.bio }}
            </p>

            <div class="grid gap-2 text-center text-sm border-t border-line pt-4">
              <a v-if="detailItem.website_url" :href="detailItem.website_url" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline font-bold">
                وب‌سایت شخصی
              </a>
              <div v-if="Object.keys(detailItem.social_links).length > 0" class="flex justify-center gap-3 mt-2">
                <a v-for="(link, key) in detailItem.social_links" :key="key" :href="link" target="_blank" rel="noopener noreferrer" class="text-muted hover:text-brand-500 capitalize text-xs">
                  {{ key }}
                </a>
              </div>
            </div>
          </div>

          <!-- Associated Organizers -->
          <div v-if="detailItem.organizers.length > 0" class="rounded-lg border border-line bg-surface p-5 shadow-soft">
            <h2 class="text-lg font-black text-ink mb-4 border-b border-line pb-2">برگزارکنندگان مرتبط</h2>
            <div class="grid gap-3">
              <a v-for="org in detailItem.organizers" :key="org.id" :href="`/organizers/${org.slug}`" class="flex items-center gap-2 hover:text-brand-700 transition">
                <div class="h-6 w-6 rounded bg-brand-100 flex items-center justify-center text-[10px] font-bold text-brand-800">
                  {{ org.name.charAt(0) }}
                </div>
                <div class="text-sm">
                  <span class="font-bold text-ink hover:text-brand-800">{{ org.name }}</span>
                  <span v-if="org.role_title" class="text-xs text-muted pr-1">({{ org.role_title }})</span>
                </div>
              </a>
            </div>
          </div>
        </aside>

        <!-- Right: Person Events -->
        <section class="grid gap-4">
          <h2 class="text-xl font-black text-ink">رویدادها و سخنرانی‌ها</h2>
          <div v-if="!hasEvents" class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm text-muted">
            هیچ رویدادی برای این شخص یافت نشد.
          </div>
          <div v-else class="grid gap-4 md:grid-cols-2">
            <article v-for="event in events" :key="event.id" class="rounded-lg border border-line bg-surface p-4 shadow-soft">
              <div class="mb-4 flex items-center justify-between gap-3">
                <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ event.badge }}</span>
                <span class="text-xs font-bold text-muted">{{ event.date }}</span>
              </div>
              <h3 class="text-lg font-black leading-8 text-ink">
                <a class="hover:text-brand-800" :href="event.href">{{ event.title }}</a>
              </h3>
              <p class="mt-2 line-clamp-2 text-sm leading-7 text-muted">{{ event.summary }}</p>

              <!-- External Source Badge -->
              <div v-if="event.source" class="mt-3 flex items-center gap-2">
                <span class="text-xs text-muted">منبع اصلی:</span>
                <a :href="event.source.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded bg-muted-100 px-2 py-0.5 text-xs font-bold text-brand-800 hover:bg-brand-50 hover:text-brand-900 border border-brand-200">
                  {{ event.source.label }}
                </a>
              </div>

              <dl class="mt-4 grid gap-2 text-sm text-muted">
                <div class="flex items-center justify-between gap-3">
                  <dt>نقش در رویداد</dt>
                  <dd class="font-bold text-ink">{{ event.role_title || 'سخنران / مدرس' }}</dd>
                </div>
              </dl>
            </article>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
