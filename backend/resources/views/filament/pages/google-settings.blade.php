<x-filament-panels::page>
    @if (session()->has('google_success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            <span class="font-medium">موفقیت:</span> {{ session('google_success') }}
        </div>
    @endif

    @if (session()->has('google_error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            <span class="font-medium">خطا:</span> {{ session('google_error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <!-- Status Card -->
        <div class="md:col-span-1 p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">وضعیت اتصال به گوگل</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    سرویس‌های آنالیتیکس و سرچ کنسول برای واکشی اطلاعات به اجازه دسترسی نیاز دارند.
                </p>

                <div class="flex items-center gap-3 mb-6">
                    @if ($isConnected)
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">متصل به حساب گوگل</span>
                    @else
                        <span class="h-3 w-3 rounded-full bg-red-500"></span>
                        <span class="text-red-500 font-medium">قطع ارتباط</span>
                    @endif
                </div>
            </div>

            <div>
                @if ($isConnected)
                    <button type="button" wire:click="disconnectGoogle" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        قطع اتصال گوگل
                    </button>
                @else
                    <button type="button" wire:click="connectGoogle" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                        <svg class="w-5 h-5 me-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114-3.41 0-6.19-2.78-6.19-6.19s2.78-6.19 6.19-6.19c1.602 0 3.018.611 4.1 1.62l3.073-3.073C19.296 2.915 15.996 1.5 12.24 1.5 6.033 1.5 1 6.533 1 12.74s5.033 11.24 11.24 11.24c6.476 0 11.24-4.555 11.24-11.24 0-.797-.08-1.571-.23-2.315H12.24z"/>
                        </svg>
                        اتصال به حساب گوگل
                    </button>
                @endif
            </div>
        </div>

        <!-- Form Card -->
        <div class="md:col-span-2 p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <form wire:submit="save">
                {{ $this->form }}

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button type="submit" color="amber">
                        ذخیره تنظیمات
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
