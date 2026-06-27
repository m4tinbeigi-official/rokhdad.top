<template>
  <section class="relative overflow-hidden rounded-3xl border border-white/20 bg-linear-to-br from-brand-900 via-brand-700 to-brand-500 shadow-2xl transition-default">
    <!-- Decorative background elements -->
    <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0djI2aDJWMzRoLTIyek0wIDM0djI2aDJWMzRIMHptMC0zNGgydjI2SDBWMHptMzYgMGgydjI2aC0yVjB6Ii8+PC9nPjwvZz48L3N2Zz4=')]" class=""></div>
    <div class="pointer-events-none absolute -right-48 -top-48 h-96 w-96 rounded-full bg-brand-400/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-48 -left-48 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative grid gap-10 p-6 sm:p-10 lg:grid-cols-[1fr_420px] lg:items-center lg:p-12">
      <div class="text-white z-10">
        <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white shadow-sm backdrop-blur-md">
          <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span></span>
          رخداد · گردآوری خودکار از ایوند و ایسمینار
        </span>
        <h1 class="mt-2 max-w-3xl text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
          دنیای رویدادها، <br />
          <span class="text-transparent bg-clip-text bg-gradient-to-l from-white to-brand-200">در یک نگاه</span>
        </h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/90">
          رویدادهای حضوری و وبینارهای آنلاین هر ساعت به‌صورت خودکار گردآوری می‌شوند. جستجو کن، فیلتر کن و مستقیم به منبع اصلی برو.
        </p>
        <dl class="mt-8 flex flex-wrap gap-8 text-white">
          <div class="flex flex-col">
            <dt class="text-sm font-bold text-brand-100">رویدادهای این صفحه</dt>
            <dd class="mt-1 text-3xl font-black">{{ meta?.total ? new Intl.NumberFormat('fa-IR').format(meta.total) : '—' }}</dd>
          </div>
          <div class="flex flex-col">
            <dt class="text-sm font-bold text-brand-100">منابع متصل</dt>
            <dd class="mt-1 text-3xl font-black">۲</dd>
          </div>
        </dl>
      </div>
      <form class="relative z-10 grid gap-4 rounded-2xl border border-white/20 bg-white/10 p-6 shadow-xl backdrop-blur-xl" role="search" aria-label="جستجوی رویداد" @submit.prevent="onSubmit">
        <h2 class="text-lg font-black text-white">جستجوی هوشمند</h2>
        <div class="relative">
          <input v-model.trim="localQuery" class="w-full rounded-xl border-0 bg-white/90 px-4 py-3.5 pr-11 text-base text-ink shadow-inner outline-none transition focus:bg-white focus:ring-4 focus:ring-brand-400/30 placeholder:text-muted/60" type="search" placeholder="نام رویداد، شهر یا موضوع..." />
          <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <select v-model="filters.city" class="rounded-xl border-0 bg-white/90 px-3 py-3 text-sm text-ink outline-none transition focus:bg-white focus:ring-4 focus:ring-brand-400/30"><option value="">همه شهرها</option><option v-for="city in filterOptions.cities" :key="city.slug" :value="city.slug">{{ city.title }}</option></select>
          <select v-model="filters.event_type" class="rounded-xl border-0 bg-white/90 px-3 py-3 text-sm text-ink outline-none transition focus:bg-white focus:ring-4 focus:ring-brand-400/30"><option value="">همه انواع</option><option value="in_person">حضوری</option><option value="online">آنلاین</option><option value="hybrid">ترکیبی</option></select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <select v-model="filters.category" class="rounded-xl border-0 bg-white/90 px-3 py-3 text-sm text-ink outline-none transition focus:bg-white focus:ring-4 focus:ring-brand-400/30"><option value="">همه دسته‌ها</option><option v-for="category in filterOptions.categories" :key="category.slug" :value="category.slug">{{ category.title }}</option></select>
          <select v-model="filters.source" class="rounded-xl border-0 bg-white/90 px-3 py-3 text-sm text-ink outline-none transition focus:bg-white focus:ring-4 focus:ring-brand-400/30"><option value="">همه منابع</option><option value="evand">ایوند</option><option value="eseminar">ایسمینار</option></select>
        </div>
        <div class="mt-2 flex items-center justify-between">
          <button v-if="hasActiveFilters" class="rounded-xl px-4 py-2.5 text-sm font-bold text-white/80 transition hover:bg-white/10 hover:text-white" type="button" @click="resetFilters">پاک کردن</button>
          <div v-else></div>
          <button class="rounded-xl bg-white px-6 py-2.5 text-sm font-bold text-brand-800 shadow-md transition hover:bg-brand-50 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-white/40" type="submit">اعمال فیلتر</button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  meta: Object,
  eventFilters: Object,
  filterOptions: Object,
  hasActiveFilters: Boolean,
});

const emit = defineEmits(['reset-filters', 'fetch-events', 'update-filters']);

import { ref, watch } from 'vue';
const localQuery = ref(props.eventFilters?.q || '');
const filters = ref({
  city: props.eventFilters?.city || '',
  event_type: props.eventFilters?.event_type || '',
  category: props.eventFilters?.category || '',
  source: props.eventFilters?.source || '',
});

watch(localQuery, (val) => {
  emit('update-filters', { ...props.eventFilters, q: val });
});
watch(filters, (val) => {
  emit('update-filters', { ...props.eventFilters, ...val });
}, { deep: true });

function resetFilters() {
  emit('reset-filters');
}

function onSubmit() {
  emit('fetch-events');
}
</script>

<style scoped>
/* No additional styles */
</style>
