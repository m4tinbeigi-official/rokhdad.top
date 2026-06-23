# گردآوری خودکار رویدادها (ایوند + ایسمینار)

این سند تغییرات افزوده‌شده برای گردآوری ساعتی رویدادها از ایوند و ایسمینار و نمایش آن‌ها روی صفحه اصلی rokhdad.top را توضیح می‌دهد.

## ۱) ایمپورت منابع

دو دستور آرتیسان برای واکشی داده از API منابع وجود دارد (`backend/routes/console.php`):

```bash
php artisan evand:import        # رویدادهای ایوند از https://api.evand.com
php artisan eseminar:import     # وبینارهای ایسمینار (آدرس از .env خوانده می‌شود)
```

گزینه‌ها: `--pages=` (تعداد صفحه) و `--per-page=` (پیش‌فرض ۵۰).

هر دو دستور داده را به جدول‌های اصلی `events`، `organizers` و `event_source_attributions` با `source_key` مربوطه (`evand` / `eseminar`) upsert می‌کنند، پس اجرای دوباره تکراری ایجاد نمی‌کند.

## ۲) زمان‌بندی ساعتی

هر دو دستور در انتهای `console.php` با اسکجولر لاراول هر ساعت ثبت شده‌اند
(`->hourly()->withoutOverlapping()->runInBackground()`).

برای فعال‌شدن، یک کرون روی سرور لازم است:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

بررسی زمان‌بندی: `php artisan schedule:list`

## ۳) تنظیمات ایسمینار (.env)

برخلاف ایوند، ایسمینار API عمومی مستندی ندارد. مقادیر زیر را در `backend/.env` تنظیم کنید
(کلیدها در `config/services.php` تعریف شده‌اند):

```env
ESEMINAR_API_BASE=https://eseminar.tv/api/v1
ESEMINAR_EVENTS_PATH=/events
ESEMINAR_API_TOKEN=            # اگر احراز هویت لازم است
ESEMINAR_SITE_URL=https://eseminar.tv
```

ایمپورتر ایسمینار مدافعانه نوشته شده و چند شکل رایج پاسخ (`data` / `events` / `results` / آرایه‌ی ساده)
و نام‌فیلدهای جایگزین (`title`/`name`، `start_date`/`starts_at`، `cover`/`image`/`thumbnail` و ...) را پشتیبانی می‌کند.
پس از مشخص‌شدن endpoint و ساختار واقعی، در صورت نیاز نگاشت فیلدها را دقیق‌تر می‌کنیم.

## ۴) صفحه اصلی (frontend)

- `frontend/src/App.vue` — هیروی گرادیانت با جستجو و آمار، چیپ‌های دسته‌بندی، تب‌های منبع (همه/ایوند/ایسمینار)،
  بخش «رویدادهای ویژه» و گرید کارت‌ها با تصویر کاور و نشان منبع.
- `frontend/src/events/homepage.js` — افزودن `cover`, `city`, `type`, `isFeatured` به مدل کارت.
- بک‌اند: `cover_url` اکنون در پاسخ فهرست `/api/v1/events` برمی‌گردد.

اجرای فرانت‌اند:

```bash
cd frontend
npm install
npm run dev      # آدرس API از VITE_API_BASE_URL خوانده می‌شود (پیش‌فرض /api/v1)
```

## اجرای دستی اولین گردآوری

```bash
cd backend
php artisan evand:import --pages=2
php artisan eseminar:import --pages=2
```
