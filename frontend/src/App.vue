<script setup>
import { computed, onMounted, ref } from 'vue'
import { ApiError, NetworkError, createRokhdadApi, getApiBaseUrl } from './api/client.js'
import { loadHomepageEvents } from './events/homepage.js'
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
const pageKind = computed(() => {
  if (currentPath === '/categories') {
    return 'categories'
  }

  if (currentPath === '/cities') {
    return 'cities'
  }

  return 'home'
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
    return
  }

  fetchDirectory()
})

async function fetchEvents() {
  isLoading.value = true
  error.value = null

  try {
    const result = await loadHomepageEvents(api)
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

function getErrorMessage(caught) {
  if (caught instanceof NetworkError) {
    return 'ارتباط با سرور برقرار نشد. چند لحظه بعد دوباره تلاش کنید.'
  }

  if (caught instanceof ApiError) {
    if (caught.status === 404) {
      return 'مسیر دریافت داده در دسترس نیست.'
    }

    if (caught.status >= 500) {
      return 'سرور در حال حاضر پاسخ مناسبی نمی دهد.'
    }

    return caught.message || 'دریافت رویدادها با خطا روبه رو شد.'
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

        <form class="grid gap-3 rounded-lg border border-line bg-canvas p-4" role="search" aria-label="جستجوی رویداد">
          <label class="grid gap-1 text-sm font-bold text-ink">
            جستجو
            <input class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100" type="search" placeholder="نام رویداد، شهر یا موضوع" />
          </label>
          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1 text-sm font-bold text-ink">
              شهر
              <select class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option>همه شهرها</option>
                <option>تهران</option>
                <option>شیراز</option>
              </select>
            </label>
            <label class="grid gap-1 text-sm font-bold text-ink">
              نوع
              <select class="rounded-md border border-line bg-white px-3 py-2 text-base outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <option>همه</option>
                <option>حضوری</option>
                <option>آنلاین</option>
              </select>
            </label>
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

    <main v-else class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
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
  </div>
</template>
