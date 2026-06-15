<script setup>
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1'

const featuredEvents = [
  {
    title: 'همایش محصول و هوش مصنوعی',
    date: 'سه شنبه، ۲۶ خرداد',
    location: 'تهران، سالن رویش',
    organizer: 'انجمن وایب کدینگ ایران',
    badge: 'حضوری',
  },
  {
    title: 'وبینار ساخت MVP با Vue',
    date: 'چهارشنبه، ۲۷ خرداد',
    location: 'آنلاین',
    organizer: 'رخداد',
    badge: 'آنلاین',
  },
  {
    title: 'کارگاه رشد رویدادهای محلی',
    date: 'جمعه، ۳۰ خرداد',
    location: 'شیراز، خانه نوآوری',
    organizer: 'تیم اکوسیستم جنوب',
    badge: 'کارگاه',
  },
]
</script>

<template>
  <div class="min-h-screen bg-canvas text-ink">
    <header class="border-b border-line bg-surface/95">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a class="text-2xl font-black text-brand-900" href="/" aria-label="رخداد">
          رخداد
        </a>
        <nav class="hidden items-center gap-2 text-sm font-bold text-muted md:flex" aria-label="ناوبری اصلی">
          <a class="rounded-md px-3 py-2 text-brand-800 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="#">
            رویدادها
          </a>
          <a class="rounded-md px-3 py-2 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="#">
            دسته بندی ها
          </a>
          <a class="rounded-md px-3 py-2 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" href="#">
            برگزارکننده ها
          </a>
        </nav>
        <button class="rounded-md bg-brand-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
          ثبت رویداد
        </button>
      </div>
    </header>

    <main class="mx-auto grid max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
      <section class="grid gap-5 rounded-lg border border-line bg-surface p-5 shadow-soft lg:grid-cols-[1fr_360px] lg:items-end lg:p-7">
        <div>
          <p class="mb-2 text-sm font-bold text-brand-700">پایه طراحی عمومی</p>
          <h1 class="max-w-3xl text-3xl font-black leading-tight text-ink sm:text-4xl">
            کشف رویدادهای فناوری، کسب وکار و آموزش در ایران
          </h1>
          <p class="mt-4 max-w-2xl text-base leading-8 text-muted">
            این shell پایه با Vue، Vite و Tailwind آماده شده و در تسک های بعدی به API عمومی رخداد متصل می شود.
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
            <p class="mt-1 text-sm text-muted">ساختار card و spacing پایه برای صفحات بعدی.</p>
          </div>
          <p class="rounded-md border border-line bg-surface px-3 py-2 text-xs font-bold text-muted" dir="ltr">
            API: {{ apiBaseUrl }}
          </p>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
          <article
            v-for="event in featuredEvents"
            :key="event.title"
            class="rounded-lg border border-line bg-surface p-4 shadow-soft"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-black text-brand-800">{{ event.badge }}</span>
              <span class="text-xs font-bold text-muted">{{ event.date }}</span>
            </div>
            <h3 class="text-lg font-black leading-8 text-ink">{{ event.title }}</h3>
            <dl class="mt-4 grid gap-2 text-sm text-muted">
              <div class="flex items-center justify-between gap-3">
                <dt>مکان</dt>
                <dd class="font-bold text-ink">{{ event.location }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt>برگزارکننده</dt>
                <dd class="font-bold text-ink">{{ event.organizer }}</dd>
              </div>
            </dl>
          </article>
        </div>
      </section>

      <section class="rounded-lg border border-dashed border-line bg-surface p-5 text-sm leading-7 text-muted">
        حالت خالی، loading و خطا در همین سیستم طراحی با borderهای ۸px، رنگ های برند و focus ring مشترک پیاده می شوند.
      </section>
    </main>
  </div>
</template>
