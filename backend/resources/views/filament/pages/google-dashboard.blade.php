<x-filament-panels::page>
    <!-- Sync action and status banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-3">
            @if ($isUsingDemoData)
                <div class="inline-flex p-2 bg-amber-50 dark:bg-amber-950 text-amber-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">در حال استفاده از داده‌های آزمایشی (Demo)</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">برای دیدن اطلاعات واقعی، حساب گوگل خود را در بخش تنظیمات گوگل متصل کنید.</p>
                </div>
            @else
                <div class="inline-flex p-2 bg-emerald-50 dark:bg-emerald-950 text-emerald-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">متصل به حساب گوگل</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">آخرین زمان همگام‌سازی: {{ $lastSynced }}</p>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-filament::button wire:click="syncNow" color="amber" icon="heroicon-m-arrow-path" wire:loading.attr="disabled">
                <span wire:loading.remove>به‌روزرسانی داده‌ها</span>
                <span wire:loading>در حال به‌روزرسانی...</span>
            </x-filament::button>
        </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
        <!-- Analytics Pageviews -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">بازدید صفحات (۳۰ روز)</p>
            <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ number_format($totalPageviews) }}</h3>
        </div>

        <!-- Analytics Sessions -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">نشست‌ها (۳۰ روز)</p>
            <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ number_format($totalSessions) }}</h3>
        </div>

        <!-- Search Console Clicks -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">کلیک‌های سرچ (۳۰ روز)</p>
            <h3 class="text-2xl font-extrabold text-pink-600 dark:text-pink-400 mt-1">{{ number_format($totalClicks) }}</h3>
        </div>

        <!-- Search Console Impressions -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">نمایش در سرچ (۳۰ روز)</p>
            <h3 class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($totalImpressions) }}</h3>
        </div>

        <!-- Search Console CTR -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">میانگین CTR</p>
            <h3 class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">{{ $avgCtr }}%</h3>
        </div>

        <!-- Search Console Position -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">میانگین رتبه</p>
            <h3 class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ $avgPosition }}</h3>
        </div>
    </div>
</x-filament-panels::page>
