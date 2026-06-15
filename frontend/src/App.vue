<script setup>
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1'
</script>

<template>
  <main class="app-shell">
    <section class="intro">
      <p class="eyebrow">Rokhdad MVP</p>
      <h1>رخداد</h1>
      <p class="lead">
        اسکلت frontend عمومی رخداد آماده است و در تسک‌های بعدی به API رویدادها، دسته‌بندی‌ها و پروفایل‌ها وصل می‌شود.
      </p>
      <dl class="status-grid" aria-label="وضعیت اتصال">
        <div>
          <dt>Framework</dt>
          <dd>Vue 3 + Vite</dd>
        </div>
        <div>
          <dt>API Base</dt>
          <dd dir="ltr">{{ apiBaseUrl }}</dd>
        </div>
      </dl>
    </section>
  </main>
</template>
