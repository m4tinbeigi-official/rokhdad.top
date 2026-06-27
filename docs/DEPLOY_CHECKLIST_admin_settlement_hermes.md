# چک‌لیست اجرا — پنل ادمین + تسویه + Hermes

این تغییرات در این نشست انجام شدند: افزودن Resource برای همه‌ی مدل‌های بدون‌مدیریت،
زنده‌سازی کامل زیرسیستم تسویه (Settlement + Payout)، و تثبیت ماژول Hermes.
موارد زیر را روی سرور انجام بده.

## ۱) استقرار کد و وابستگی‌ها

- [ ] گرفتن/مرج آخرین تغییرات روی سرور (`git pull` یا روال دیپلوی خودت).
- [ ] `cd backend && composer dump-autoload -o` (کلاس‌های جدید: Payout, HermesError, SettlementService و ~۲۶ Resource).

## ۲) دیتابیس

- [ ] `php artisan migrate --force`
  - مایگریشن جدید: `2026_07_02_000000_create_settlement_tables` (جدول‌های `settlement_ledgers` و `payouts`).
  - `hermes_errors` از قبل بود؛ اگر هنوز اجرا نشده، همین‌جا ساخته می‌شود.
- [ ] (اختیاری) بکاپ دیتابیس قبل از migrate.

## ۳) کش و کشف Resourceها

- [ ] `php artisan config:clear && php artisan route:clear`
- [ ] `php artisan filament:optimize-clear` (تا Resourceهای جدید در `/admin` دیده شوند)
- [ ] در پروداکشن در انتها: `php artisan optimize` / `filament:optimize`

## ۴) تست‌ها

- [ ] `php artisan test --filter='SettlementLedgerTest|HermesServiceTest'`
- [ ] `php artisan test` (کل سوییت — اطمینان از نشکستن چیزی)
- [ ] میان‌بر: `bash scripts/verify_changes.sh` همه‌ی مراحل ۱ تا ۴ را یک‌جا اجرا می‌کند.

## ۵) دسترسی ادمین و بررسی پنل

- [ ] در صورت نیاز: `php artisan app:setup-admin-user`
- [ ] ورود به `/admin` و بررسی دیده‌شدن گروه‌های جدید منو:
      «فروش و مالی»، «محتوا»، «کمپین و اطلاع‌رسانی»، «دسترسی و کاربران»،
      «کیفیت داده و منابع»، «وب‌هوک»، «سیستم».
- [ ] تأیید اینکه کاربر غیرادمین به این Resourceها (به‌خصوص Roles/Permissions و Hermes) دسترسی ندارد.

## ۶) تنظیمات Hermes (ابزار توسعه)

- [ ] تصمیم: Hermes فقط ابزار توسعه است. در پروداکشن `HERMES_ENABLED` را تنظیم نکن یا `false` بگذار
      (پیش‌فرض فقط در `APP_ENV=local` روشن است).
- [ ] اگر می‌خواهی فعال باشد: در `.env` مقداردهی کن:
      `HERMES_ENABLED=true`، `HERMES_ENDPOINT=...`، `HERMES_API_KEY=...`
- [ ] اطمینان از وجود خطوط `HERMES_ENDPOINT=` و `HERMES_API_KEY=` در `.env`
      (صفحه‌ی ادمین Hermes این مقادیر را در `.env` می‌نویسد).

## ۷) دود-تست تسویه (مهم — جریان پول)

- [ ] یک پرداخت آزمایشی/سندباکس انجام بده و در DB چک کن دو ردیف در `settlement_ledgers`
      ساخته شده: یک `credit` (مبلغ ناخالص) و یک `debit` (کارمزد ۱۰٪).
- [ ] در پنل، یک `Payout` بساز و با اکشن «تکمیل» تأییدش کن؛ چک کن یک ردیف `debit`
      از نوع `payout` در دفتر ثبت شد و مانده درست شد.
- [ ] تصمیم نرخ کارمزد: الان **۱۰٪ ثابت** است (در `SettlementService::PLATFORM_FEE_RATE`).
      اگر باید قابل‌تنظیم (config/per-organizer) باشد، به من بگو تا تغییرش بدهم.

## ۸) پاک‌سازی و موارد باز (اختیاری)

- [ ] دو Resource موازی برای Event وجود دارد (`EventResource.php` در ریشه و `Events/EventResource.php`).
      این از قبل بوده؛ بهتر است یکی شود تا منو دوبار «رویدادها» نشان ندهد.
- [ ] دو طراحی موازی کمپین (`campaigns` تنها در برابر `campaigns`+`campaign_messages`+`campaign_analytics`)
      هنوز هم‌زمان وجود دارد؛ در فرصت مناسب یکی شود.
- [ ] `syncProject()` واقعی برای `hermes:sync` نیاز به مشخصات API سرور Hermes دارد؛
      اگر endpoint ingest داری بده تا پیاده‌اش کنم (الان فقط چک اتصال است).

## ۹) نهایی

- [ ] کامیت و دیپلوی.
- [ ] خروجی هر مرحله‌ای که قرمز شد را برای من بفرست تا رفع کنم.
