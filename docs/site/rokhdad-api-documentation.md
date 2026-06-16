# Rokhdad Postman Collection API Documentation

این سند از فایل Postman Collection زیر استخراج شده است:

```text
/Users/ricksabchez/Downloads/Rokhdad.postman_collection.json
```

نام Collection: `Rokhdad`

تاریخ تبدیل و بررسی: 2026-06-15

## خلاصه

این collection شامل وب‌سرویس‌های چند سایت است:

| سرویس | Base URL | تعداد request در collection | توضیح |
|---|---|---:|---|
| Eseminar | `https://api.eseminar.tv/api/v1` | 11 | لیست وبینارها، جزئیات وبینار، اسلایدرها، میزبان‌های برتر |
| BilitMaster | `https://api.bilitmaster.com/api` | 8 | لیست رویدادها، جزئیات رویداد، تقویم، دسته‌بندی‌ها |
| Evand | `https://api.evand.com` | 1 | لیست رویدادهای ایوند |
| Pegah Retargeting CDN | `https://ma-cdn.pegah.tech` | 1 | فایل JSON تبلیغات/retargeting |

نکته: خود collection هیچ response نمونه‌ای ذخیره نکرده بود. برای endpointهای عمومی، چند response زنده به صورت محدود بررسی شد. endpointهایی که دارای پارامتر `user=...` بودند به دلیل احتمال وجود داده کاربری فراخوانی محتوایی نشدند و در این سند با مقدار ریدکت‌شده نمایش داده شده‌اند.

## ملاحظات امنیتی

در collection اصلی دو endpoint زیر شامل مقدار `user` هستند:

```text
https://api.bilitmaster.com/api/user/getEventCustomers/419?user=...
https://api.bilitmaster.com/api/user/getEvents?user=...
```

این مقدار احتمالاً token یا hash کاربر است. در این داکیومنت با `{user_token}` جایگزین شده و نباید در مستندات عمومی منتشر شود.

## Endpoint Index

| سرویس | Method | Endpoint | نام در Collection |
|---|---|---|---|
| Eseminar | `GET` | `/user/saved_webinars` | `saved_webinars` |
| Eseminar | `GET` | `/webinar/{slug}` | `Single event` |
| Eseminar | `GET` | `/webinars` | `All event` |
| Eseminar | `GET` | `/webinars` | `By category` |
| Pegah CDN | `GET` | `/v1/retargeting/19246/advertiser.json` | advertiser JSON |
| Eseminar | `GET` | `/special_webinars` | `special webinars` |
| Eseminar | `GET` | `/video_webinars` | `video webinars` |
| Eseminar | `GET` | `/free_webinars` | `free webinars` |
| Eseminar | `GET` | `/latest_webinars` | `latest webinars` |
| Eseminar | `GET` | `/best_hosts` | `best hosts` |
| Eseminar | `GET` | `/sliders` | `sliders` |
| BilitMaster | `POST` | `/api/user/getEventCustomers/{event_id}` | `Details event` |
| BilitMaster | `POST` | `/api/user/getEvents` | `all event submit` |
| BilitMaster | `POST` | `/api/pageGetEvent` | `Single evnt` |
| BilitMaster | `POST` | `/api/getHomeEvents` | `All event` |
| BilitMaster | `POST` | `/api/getEvents` | `All event og` |
| BilitMaster | `POST` | `/api/getInternationalEvents` | `International Events` |
| BilitMaster | `POST` | `/api/getMarketEvents` | `Market Events` |
| BilitMaster | `POST` | `/api/getMonthCalendar` | `Month Calendar` |
| BilitMaster | `POST` | `/api/getInitial` | `Category` |
| Evand | `GET` | `/events` | `All events` |

# Eseminar API

Base URL:

```text
https://api.eseminar.tv/api/v1
```

## Common Response Pattern

بیشتر پاسخ‌های Eseminar ساختار زیر را دارند:

```json
{
  "data": [],
  "status": "success"
}
```

در endpointهای لیستی مثل `/webinars` فیلدهای metadata بیشتری هم وجود دارد:

```json
{
  "description": "...",
  "body": null,
  "pageTitle": null,
  "faq": null,
  "index": true,
  "data": [],
  "pagination": {},
  "status": "success"
}
```

## GET /webinars

لیست وبینارها را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/webinars?page=1&per_page=15
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `page` | integer | No | شماره صفحه |
| `per_page` | integer | No | تعداد آیتم در صفحه |
| `category_id` | string | No | slug یا شناسه دسته‌بندی؛ در collection مقدار slug فارسی دیده شد |

### Example Requests

```bash
curl 'https://api.eseminar.tv/api/v1/webinars?page=1&per_page=15'
```

```bash
curl 'https://api.eseminar.tv/api/v1/webinars?category_id=بازاریابی-محتوا&page=1&per_page=15'
```

### Observed Response Shape

```json
{
  "description": "با یک کلیک حرفه‌ای شو...",
  "body": null,
  "pageTitle": null,
  "faq": null,
  "index": true,
  "data": [
    {
      "id": 177881,
      "title": "وبینار سلامت ابرو و مژه",
      "slug": "سلامت-ابرو-و-مژه",
      "cover": "https://s3.eseminar.tv/upload/webinar/thumb/example.webp",
      "speaker": {
        "name": "الهام عطاردی",
        "slug": "الهام-عطاردی",
        "avatar": "https://s3.eseminar.tv/upload/teacher/thumb/example.webp"
      },
      "subjects": []
    }
  ],
  "pagination": {},
  "status": "success"
}
```

### Webinar List Item Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه وبینار |
| `title` | string | عنوان وبینار |
| `slug` | string | slug وبینار |
| `cover` | string/null | تصویر وبینار |
| `speaker` | object/null | اطلاعات سخنران |
| `speaker.name` | string | نام سخنران |
| `speaker.slug` | string | slug سخنران |
| `speaker.avatar` | string/null | تصویر سخنران |
| `subjects` | array | دسته‌بندی‌ها/موضوعات |
| `start_at` | string | زمان شروع؛ در برخی endpointها وجود دارد |
| `end_at` | string | زمان پایان؛ در برخی endpointها وجود دارد |
| `price` | integer/null | قیمت |
| `currency` | string | واحد پول، مثل `IRR` |
| `tickets` | array | اطلاعات بلیت‌ها، در برخی endpointها |

## GET /webinar/{slug}

جزئیات یک وبینار را بر اساس slug برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/webinar/{slug}
```

### Example Request

```bash
curl 'https://api.eseminar.tv/api/v1/webinar/درآمد-دلاری-با-دراپ-شیپینگ-و-هوش-مصنوعی-بدون-سرمایه'
```

### Observed Response Shape

```json
{
  "data": {
    "crawl": false,
    "login_link": null,
    "webinar": {
      "id": 120893,
      "title": "وبینار بررسی 10 نمونه موفق حال حاظر دراپ شیپینگ...",
      "slug": "درآمد-دلاری-با-دراپ-شیپینگ-و-هوش-مصنوعی-بدون-سرمایه",
      "lang": "fa",
      "start_at": "2023-10-13T14:30:00.000000Z",
      "end_at": "2023-10-13T15:30:00.000000Z",
      "description": "بررسی قدم به قدم...",
      "private": 0,
      "cover": "https://s3.eseminar.tv/c453019/upload/webinar/example.jpg",
      "body": "<p>...</p>"
    }
  },
  "status": "success"
}
```

### Detail Fields

| Field | Type | توضیح |
|---|---|---|
| `data.crawl` | boolean | وضعیت crawl |
| `data.login_link` | string/null | لینک ورود، اگر لازم باشد |
| `data.webinar.id` | integer | شناسه وبینار |
| `data.webinar.title` | string | عنوان |
| `data.webinar.slug` | string | slug |
| `data.webinar.lang` | string | زبان، مثل `fa` |
| `data.webinar.start_at` | string | زمان شروع |
| `data.webinar.end_at` | string | زمان پایان |
| `data.webinar.description` | string/null | توضیح کوتاه |
| `data.webinar.body` | string/null | محتوای HTML |
| `data.webinar.private` | integer/boolean | خصوصی بودن؛ مقدار عددی مشاهده شد |

## GET /user/saved_webinars

لیست وبینارهای ذخیره‌شده کاربر را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/user/saved_webinars?per_page=5
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `per_page` | integer | No | تعداد آیتم در صفحه |

### Notes

- این endpoint در مسیر `user` قرار دارد و احتمالاً برای نتیجه کامل نیازمند session/auth است.
- در collection header یا token مشخصی برای آن ثبت نشده بود.

## GET /special_webinars

لیست وبینارهای ویژه را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/special_webinars?utm={"type":"specialwebinars"}
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `utm` | JSON string | No | metadata کمپین؛ در collection به صورت URL encoded آمده است |

## GET /video_webinars

لیست وبینارهای ویدیویی را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/video_webinars
```

## GET /free_webinars

لیست وبینارهای رایگان را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/free_webinars
```

## GET /latest_webinars

لیست جدیدترین وبینارها را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/latest_webinars
```

### Observed Response Shape

```json
{
  "data": [
    {
      "id": 177848,
      "title": "وبینار پایان پادشاهی محتوا...",
      "slug": "پایان-پادشاهی-محتوا-گذار-استراتژیک-از-تولید-محتوا-به-توسعه-محصول",
      "cover": "https://s3.eseminar.tv/c453019/upload/webinar/thumb/example.jpg",
      "speaker": {
        "name": "معین زمانی",
        "slug": "معین-زمانی",
        "avatar": "https://s3.eseminar.tv/c453019/upload/teacher/thumb/example.jpg"
      },
      "start_at": "2026-06-16 19:00:00",
      "end_at": "2026-06-16 20:30:00",
      "can_buy": true,
      "buy_video": false,
      "excerpt": "🚨 تولید محتوای بیشتر...",
      "bookmarked": false,
      "purchased": false,
      "price": 0,
      "currency": "IRR",
      "symbol": "تومان",
      "tickets": []
    }
  ],
  "status": "success"
}
```

## GET /best_hosts

لیست میزبان‌های برتر را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/best_hosts
```

### Observed Response Shape

```json
{
  "data": [
    {
      "name": "تیم آموزشی هجوم",
      "slug": "تیم-آموزشی-هجوم",
      "field": null,
      "about": "حرفه ای ترین تیم مشاوره کنکور",
      "img": "https://s3.eseminar.tv/c453019/upload/host/example.jpg",
      "stars": "4.9",
      "followers": 958
    }
  ],
  "status": "success"
}
```

### Host Fields

| Field | Type | توضیح |
|---|---|---|
| `name` | string | نام میزبان |
| `slug` | string | slug میزبان |
| `field` | string/null | حوزه فعالیت |
| `about` | string/null | توضیح کوتاه |
| `img` | string/null | تصویر |
| `stars` | string/number | امتیاز |
| `followers` | integer | تعداد دنبال‌کنندگان |

## GET /sliders

اسلایدرهای صفحه اصلی ایسمینار را برمی‌گرداند.

```http
GET https://api.eseminar.tv/api/v1/sliders
```

### Observed Response Shape

```json
{
  "data": [
    {
      "cover": "https://s3.eseminar.tv/upload/homepage-slider/example.jpg",
      "link": "https://esmn.ir/3pg",
      "webinar": {
        "id": 177755,
        "title": "وبینار نقشه راه آیلتس برای نمره بالای ۷",
        "slug": "وبینار-نقشه-راه-آیلتس-برای-نمره-بالای-۷-دور-دوم",
        "speaker": {},
        "start_at": "2026-06-15 19:00:00",
        "end_at": "2026-06-15 20:30:00",
        "price": 0,
        "currency": "IRR",
        "tickets": []
      }
    }
  ],
  "status": "success"
}
```

# Pegah Retargeting CDN

## GET /v1/retargeting/19246/advertiser.json

فایل JSON مربوط به advertiser/retargeting.

```http
GET https://ma-cdn.pegah.tech/v1/retargeting/19246/advertiser.json
```

### Notes

- این endpoint روی CDN است و بخشی از API اصلی Eseminar نیست.
- احتمالاً برای تنظیمات تبلیغات یا retargeting استفاده می‌شود.

# BilitMaster API

Base URL:

```text
https://api.bilitmaster.com/api
```

نکته: بسیاری از endpointهای BilitMaster با وجود ماهیت خواندنی، با متد `POST` فراخوانی می‌شوند و در collection بدنه‌ای برای آن‌ها ثبت نشده است.

## Common Response Pattern

```json
{
  "status": true
}
```

در endpointهای لیستی، داده‌ها معمولاً در کلیدهایی مثل `top_events`, `new_events`, `items`, `info`, `event_categories` و `all_states` قرار می‌گیرند.

## POST /getHomeEvents

اطلاعات رویدادهای صفحه اصلی، اسلایدرها، رویدادهای جدید و رویدادهای برتر را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getHomeEvents
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getHomeEvents'
```

### Observed Response Shape

```json
{
  "status": true,
  "top_events": {
    "status": true,
    "items": [
      {
        "id": 0,
        "name": "همه موارد",
        "events": [
          {
            "id": 765,
            "name": "کنسرت تیستو در استانبول",
            "state_id": "ترکیه",
            "city_id": "استانبول",
            "image": "https://static.bilitmaster.com/media/480/event/example.png",
            "status": "published",
            "dateString": "1405/5/17 ساعت 21:30",
            "stateString": "ترکیه",
            "cityString": "استانبول",
            "locationString": "ترکیه - استانبول",
            "priceString": "10200000",
            "link": "https://bilitmaster.com/event/765"
          }
        ]
      }
    ]
  },
  "new_events": {},
  "new_markets": {},
  "sliders": {},
  "timer_sliders": {}
}
```

### Event Card Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه رویداد |
| `name` | string | نام رویداد |
| `state_id` | string/integer | استان یا کشور |
| `city_id` | string/integer | شهر |
| `image` | string/null | تصویر |
| `image_mode` | string | نوع تصویر |
| `status` | string | وضعیت انتشار، مثل `published` |
| `dateString` | string | تاریخ شمسی نمایشی |
| `stateString` | string | نام استان/کشور |
| `cityString` | string | نام شهر |
| `locationString` | string | موقعیت نمایشی |
| `priceString` | string | قیمت به صورت string |
| `link` | string | لینک صفحه رویداد |

## POST /getEvents

لیست عمومی رویدادها را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getEvents
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getEvents'
```

### Notes

- در collection پارامتر query یا body ثبت نشده است.
- احتمالاً برای لیست کامل رویدادها استفاده می‌شود.

## POST /getInternationalEvents

لیست رویدادهای بین‌المللی را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getInternationalEvents
```

## POST /getMarketEvents

لیست market events یا رویدادهای بازار/بلیت را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getMarketEvents
```

## POST /pageGetEvent

جزئیات یک رویداد را بر اساس `id` برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/pageGetEvent?id=458
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `id` | integer | Yes | شناسه رویداد |

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/pageGetEvent?id=458'
```

### Observed Response Shape

```json
{
  "status": true,
  "user": {
    "id": 2020,
    "user_id": 1762,
    "name": "آکادمی ایرانسل",
    "image": "https://static.bilitmaster.com/media/480/user/example.png",
    "description": "<p dir=\"rtl\">...</p>"
  },
  "info": {},
  "tabs": [],
  "related_events": [],
  "st": null
}
```

### Detail Response Fields

| Field | Type | توضیح |
|---|---|---|
| `status` | boolean | موفقیت درخواست |
| `user` | object | اطلاعات برگزارکننده/مالک رویداد |
| `user.id` | integer | شناسه داخلی |
| `user.user_id` | integer | شناسه کاربر |
| `user.name` | string | نام برگزارکننده |
| `user.image` | string/null | تصویر برگزارکننده |
| `user.description` | string/null | توضیح HTML |
| `info` | object | اطلاعات اصلی رویداد |
| `tabs` | array | تب‌ها/بخش‌های محتوایی |
| `related_events` | array | رویدادهای مرتبط |

## POST /getMonthCalendar

تقویم ماهانه رویدادها را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getMonthCalendar
```

### Notes

- در collection هیچ body یا query ثبت نشده است.
- احتمالاً تاریخ/ماه پیش‌فرض سمت سرور استفاده می‌شود یا در نسخه‌های دیگر body می‌پذیرد.

## POST /getInitial

اطلاعات اولیه سایت شامل دسته‌بندی‌ها، استان‌ها، اسلایدرها و اطلاعات تماس را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/getInitial
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getInitial'
```

### Observed Response Shape

```json
{
  "status": true,
  "event_categories": [
    {
      "id": 1,
      "type": "event",
      "name": "کسب و کار",
      "label": null,
      "event_number": 0,
      "order_id": 1
    }
  ],
  "event_states": [
    {
      "id": 1,
      "parent_id": 0,
      "name": "آذربایجان شرقی",
      "event_number": 0,
      "market_event_number": 0
    }
  ],
  "market_categories": [],
  "market_states": [],
  "site_info": {
    "contact_phone": "021-91070264",
    "contact_time": "هر روز هفته از ساعت 9 تا 18 پاسخگوی شما هستیم"
  }
}
```

### Initial Data Fields

| Field | Type | توضیح |
|---|---|---|
| `event_categories` | array | دسته‌بندی‌های رویداد |
| `all_event_categories` | array | همه دسته‌بندی‌های رویداد |
| `market_categories` | array | دسته‌بندی‌های market |
| `all_market_categories` | array | همه دسته‌بندی‌های market |
| `event_states` | array | استان‌ها/موقعیت‌های رویداد |
| `market_states` | array | استان‌ها/موقعیت‌های market |
| `all_states` | array | همه استان‌ها |
| `event_sliders` | array/object | اسلایدرهای رویداد |
| `market_sliders` | array/object | اسلایدرهای market |
| `site_info` | object | اطلاعات تماس و لینک‌های سایت |

## POST /api/user/getEventCustomers/{event_id}

لیست مشتریان/ثبت‌نام‌کنندگان یک رویداد کاربر را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/user/getEventCustomers/{event_id}?user={user_token}
```

### Path Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `event_id` | integer | Yes | شناسه رویداد؛ در collection مقدار `419` دیده شد |

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `user` | string | Yes | token/hash کاربر؛ در سند ریدکت شده است |

### Security Notes

- این endpoint احتمالاً داده شخصی مشتریان را برمی‌گرداند.
- مقدار `user` نباید در مستندات عمومی یا repository منتشر شود.
- این endpoint در بررسی زنده فراخوانی محتوایی نشد.

## POST /api/user/getEvents

لیست رویدادهای ثبت‌شده/مدیریتی کاربر را برمی‌گرداند.

```http
POST https://api.bilitmaster.com/api/user/getEvents?user={user_token}
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `user` | string | Yes | token/hash کاربر؛ در سند ریدکت شده است |

### Security Notes

- این endpoint احتمالاً scoped به حساب کاربری است.
- در collection body ثبت نشده است.
- این endpoint در بررسی زنده فراخوانی محتوایی نشد.

# Evand API

Base URL:

```text
https://api.evand.com
```

## GET /events

لیست رویدادهای ایوند را برمی‌گرداند.

```http
GET https://api.evand.com/events
```

### Query Parameters

در collection پارامتری ثبت نشده بود، اما در بررسی عملی پارامترهای زیر برای ایوند مشاهده شده‌اند:

| Name | Type | توضیح |
|---|---|---|
| `page` | integer | شماره صفحه |
| `per_page` | integer | تعداد آیتم در صفحه |
| `q` | string | جستجو |
| `city_id` | integer | فیلتر شهر |
| `category_id` | integer | فیلتر دسته |
| `online` | string | `yes` یا `no` |

### Example Request

```bash
curl 'https://api.evand.com/events?per_page=1'
```

### Observed Response Shape

```json
{
  "data": [
    {
      "id": 25021499,
      "city_id": 87,
      "organization_id": "4489j",
      "organization": {
        "id": "4489j",
        "name": "اندیشکده رویش",
        "slug": "اندیشکده-رویش-43095901",
        "logo": {
          "original": "https://static.evand.net/images/organizations/logos/original/example.png",
          "thumbnails": []
        }
      },
      "type_id": 6,
      "category_id": 165,
      "name": ">> انگلیسی را قورت بده!",
      "slug": "ورک-شاپ-انگلیسی-را-قورت-بده-2774845-1-1",
      "start_date": "2026-06-16T17:30:00+0330",
      "end_date": "2026-06-16T20:30:00+0330",
      "online": "no",
      "status": "accepted",
      "published": "yes",
      "ended": false,
      "soldout": false,
      "timing_status": "future"
    }
  ],
  "meta": {
    "pagination": {
      "total": 428,
      "count": 1,
      "per_page": 1,
      "current_page": 1,
      "total_pages": 428,
      "links": {
        "next": "https://api.evand.com/events?per_page=1&page=2"
      }
    }
  }
}
```

برای مستند کامل‌تر Evand، فایل جداگانه زیر هم ساخته شده است:

```text
/Users/ricksabchez/Desktop/RickVPN/evand-api-documentation.md
```

# JSON Schema خلاصه

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://rokhdad.local/schemas/postman-derived-api.json",
  "title": "Rokhdad Postman Collection Derived Schemas",
  "$defs": {
    "EseminarStatusResponse": {
      "type": "object",
      "properties": {
        "status": {
          "type": "string"
        },
        "data": {}
      },
      "additionalProperties": true
    },
    "EseminarWebinarCard": {
      "type": "object",
      "properties": {
        "id": { "type": "integer" },
        "title": { "type": "string" },
        "slug": { "type": "string" },
        "cover": { "type": ["string", "null"] },
        "speaker": {
          "type": ["object", "null"],
          "properties": {
            "name": { "type": "string" },
            "slug": { "type": "string" },
            "avatar": { "type": ["string", "null"] }
          },
          "additionalProperties": true
        },
        "start_at": { "type": ["string", "null"] },
        "end_at": { "type": ["string", "null"] },
        "price": { "type": ["integer", "number", "null"] },
        "currency": { "type": ["string", "null"] },
        "tickets": { "type": "array" }
      },
      "additionalProperties": true
    },
    "BilitMasterEventCard": {
      "type": "object",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "string" },
        "state_id": { "type": ["string", "integer", "null"] },
        "city_id": { "type": ["string", "integer", "null"] },
        "image": { "type": ["string", "null"] },
        "status": { "type": ["string", "null"] },
        "dateString": { "type": ["string", "null"] },
        "locationString": { "type": ["string", "null"] },
        "priceString": { "type": ["string", "null"] },
        "link": { "type": ["string", "null"] }
      },
      "additionalProperties": true
    },
    "BilitMasterInitialResponse": {
      "type": "object",
      "properties": {
        "status": { "type": "boolean" },
        "event_categories": { "type": "array" },
        "event_states": { "type": "array" },
        "market_categories": { "type": "array" },
        "market_states": { "type": "array" },
        "site_info": { "type": "object" }
      },
      "additionalProperties": true
    },
    "EvandPaginatedEvents": {
      "type": "object",
      "properties": {
        "data": { "type": "array" },
        "meta": {
          "type": "object",
          "properties": {
            "pagination": {
              "type": "object",
              "properties": {
                "total": { "type": "integer" },
                "count": { "type": "integer" },
                "per_page": { "type": "integer" },
                "current_page": { "type": "integer" },
                "total_pages": { "type": "integer" },
                "links": {}
              }
            }
          }
        }
      },
      "additionalProperties": true
    }
  }
}
```

# OpenAPI 3.1 YAML خلاصه

```yaml
openapi: 3.1.0
info:
  title: Rokhdad Multi-Site API Collection
  version: 0.1.0
  description: API documentation derived from Rokhdad.postman_collection.json.
servers:
  - url: https://api.eseminar.tv/api/v1
    description: Eseminar
  - url: https://api.bilitmaster.com/api
    description: BilitMaster
  - url: https://api.evand.com
    description: Evand
  - url: https://ma-cdn.pegah.tech
    description: Pegah Retargeting CDN

paths:
  /webinars:
    get:
      summary: List Eseminar webinars
      servers:
        - url: https://api.eseminar.tv/api/v1
      parameters:
        - name: page
          in: query
          schema:
            type: integer
        - name: per_page
          in: query
          schema:
            type: integer
        - name: category_id
          in: query
          schema:
            type: string
      responses:
        "200":
          description: Webinar list

  /webinar/{slug}:
    get:
      summary: Get Eseminar webinar
      servers:
        - url: https://api.eseminar.tv/api/v1
      parameters:
        - name: slug
          in: path
          required: true
          schema:
            type: string
      responses:
        "200":
          description: Webinar detail

  /latest_webinars:
    get:
      summary: List latest Eseminar webinars
      servers:
        - url: https://api.eseminar.tv/api/v1
      responses:
        "200":
          description: Latest webinars

  /best_hosts:
    get:
      summary: List best Eseminar hosts
      servers:
        - url: https://api.eseminar.tv/api/v1
      responses:
        "200":
          description: Best hosts

  /sliders:
    get:
      summary: List Eseminar homepage sliders
      servers:
        - url: https://api.eseminar.tv/api/v1
      responses:
        "200":
          description: Sliders

  /getHomeEvents:
    post:
      summary: Get BilitMaster homepage events
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: Homepage events

  /getEvents:
    post:
      summary: Get BilitMaster events
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: Events

  /getInternationalEvents:
    post:
      summary: Get BilitMaster international events
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: International events

  /getMarketEvents:
    post:
      summary: Get BilitMaster market events
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: Market events

  /pageGetEvent:
    post:
      summary: Get BilitMaster event detail
      servers:
        - url: https://api.bilitmaster.com/api
      parameters:
        - name: id
          in: query
          required: true
          schema:
            type: integer
      responses:
        "200":
          description: Event detail

  /getMonthCalendar:
    post:
      summary: Get BilitMaster month calendar
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: Month calendar

  /getInitial:
    post:
      summary: Get BilitMaster initial data
      servers:
        - url: https://api.bilitmaster.com/api
      responses:
        "200":
          description: Initial data

  /user/getEventCustomers/{event_id}:
    post:
      summary: Get BilitMaster event customers
      description: Requires user token/hash in query parameter. Sensitive endpoint.
      servers:
        - url: https://api.bilitmaster.com/api
      parameters:
        - name: event_id
          in: path
          required: true
          schema:
            type: integer
        - name: user
          in: query
          required: true
          schema:
            type: string
      responses:
        "200":
          description: Event customers

  /user/getEvents:
    post:
      summary: Get BilitMaster user events
      description: Requires user token/hash in query parameter. Sensitive endpoint.
      servers:
        - url: https://api.bilitmaster.com/api
      parameters:
        - name: user
          in: query
          required: true
          schema:
            type: string
      responses:
        "200":
          description: User events

  /events:
    get:
      summary: List Evand events
      servers:
        - url: https://api.evand.com
      parameters:
        - name: page
          in: query
          schema:
            type: integer
        - name: per_page
          in: query
          schema:
            type: integer
        - name: q
          in: query
          schema:
            type: string
        - name: city_id
          in: query
          schema:
            type: integer
        - name: category_id
          in: query
          schema:
            type: integer
        - name: online
          in: query
          schema:
            type: string
            enum: [yes, no]
      responses:
        "200":
          description: Evand events

  /v1/retargeting/19246/advertiser.json:
    get:
      summary: Get Pegah advertiser retargeting JSON
      servers:
        - url: https://ma-cdn.pegah.tech
      responses:
        "200":
          description: Advertiser JSON
```

# Assumptions and Unknowns

- Postman Collection هیچ `response` ذخیره‌شده‌ای نداشت؛ response shapes با چند درخواست زنده و محدود تکمیل شدند.
- برای endpointهای sensitive مربوط به BilitMaster `user` token ریدکت شد.
- متدهای `POST` در BilitMaster طبق collection مستند شدند، حتی اگر عملیاتشان خواندنی باشد.
- بسیاری از endpointهای BilitMaster بدون body ثبت شده‌اند؛ ممکن است در کلاینت واقعی body یا header اضافه ارسال شود.
- اثر دقیق بعضی endpointها مانند `/getMonthCalendar`, `/getInternationalEvents` و `/getMarketEvents` نیازمند نمونه‌های بیشتر است.
- تاریخ‌های BilitMaster در برخی فیلدها به صورت شمسی display string هستند؛ تاریخ‌های Eseminar غالباً میلادی/UTC یا string محلی‌اند.
- این سند برای reverse documentation و مصرف فنی داخلی مناسب است، نه الزاماً مستند رسمی عمومی سرویس‌ها.
