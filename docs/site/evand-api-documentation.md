# Evand Public API Documentation

این سند بر اساس بررسی عملی endpointهای عمومی `https://api.evand.com` تهیه شده است. مستند رسمی عمومی برای این API پیدا نشد؛ بنابراین موارد زیر از پاسخ زنده API، لینک‌های داخل پاسخ‌ها (`_links`)، sitemap سایت ایوند و رفتار مشاهده‌شده endpointها استخراج شده‌اند.

تاریخ بررسی: 2026-06-15

## Base URL

```text
https://api.evand.com
```

## وضعیت کلی API

اکثر endpointهای عمومی بدون احراز هویت پاسخ می‌دهند. پاسخ‌ها معمولاً JSON هستند و برای لیست‌های بزرگ از ساختار صفحه‌بندی استفاده می‌شود.

Headerهای مشاهده‌شده:

| Header | توضیح |
|---|---|
| `Content-Type: application/json` | نوع پاسخ |
| `X-RateLimit-Limit` | سقف درخواست مشاهده‌شده، مثل `200` |
| `X-RateLimit-Remaining` | تعداد درخواست باقی‌مانده |
| `Set-Cookie: laravel_session` | session cookie سمت Laravel |

## Endpointهای بررسی‌شده

| Method | Endpoint | وضعیت | توضیح |
|---|---|---:|---|
| `GET` | `/events` | تایید شد | لیست کامل رویدادها |
| `GET` | `/events/{slug}` | تایید شد | جزئیات کامل یک رویداد |
| `GET` | `/events/{slug}/tickets` | تایید شد | بلیت‌های یک رویداد |
| `GET` | `/events/{slug}/showtimes` | تایید شد | سانس‌ها/زمان‌بندی رویداد |
| `GET` | `/v2/events` | تایید شد | لیست سبک‌تر رویدادها، مناسب UI |
| `GET` | `/v2/events/{slug}` | تایید شد | جزئیات خلاصه‌تر رویداد |
| `GET` | `/organizations` | تایید شد | لیست برگزارکننده‌ها |
| `GET` | `/organizations/{slug}` | تایید شد | جزئیات برگزارکننده |
| `GET` | `/categories` | تایید شد | لیست دسته‌بندی‌ها |
| `GET` | `/cities` | تایید شد | لیست شهرها |
| `GET` | `/tags` | تایید شد | لیست تگ‌ها |
| `GET` | `/types` | تایید شد | لیست نوع رویدادها |

Endpointهای بررسی‌شده اما نامعتبر یا نامطمئن:

| Method | Endpoint | نتیجه |
|---|---|---|
| `GET` | `/event-types` | `404` |
| `GET` | `/cities/{id}` | `404` |
| `GET` | `/categories/{id}` | `404` |
| `GET` | `/events/{slug}/comments` | `404` |
| `GET` | `/events/{slug}/sessions` | `404` |
| `GET` | `/v2/events/{slug}/tickets` | `404` |
| `GET` | `/` | `500` |
| `POST` | `/events/{slug}/cancellation` | فقط در `_links` دیده شد؛ احتمالاً نیازمند احراز هویت است |

## ساختارهای مشترک

### Envelope برای لیست‌های صفحه‌بندی‌شده

```json
{
  "data": [],
  "meta": {
    "pagination": {
      "total": 428,
      "count": 10,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 43,
      "links": {
        "next": "https://api.evand.com/events?page=2"
      }
    }
  }
}
```

نکته: در بعضی پاسخ‌ها وقتی لینک قبلی/بعدی وجود ندارد، `links` به جای object به شکل آرایه خالی `[]` برمی‌گردد.

### Envelope برای جزئیات

```json
{
  "data": {}
}
```

### Pagination Fields

| Field | Type | توضیح |
|---|---|---|
| `total` | integer | کل رکوردهای مطابق query |
| `count` | integer | تعداد رکورد برگشتی در صفحه فعلی |
| `per_page` | integer | تعداد آیتم در هر صفحه |
| `current_page` | integer | شماره صفحه فعلی |
| `total_pages` | integer | تعداد کل صفحات |
| `links.previous` | string | لینک صفحه قبل، در صورت وجود |
| `links.next` | string | لینک صفحه بعد، در صورت وجود |

## GET /events

لیست رویدادها را با جزئیات کامل برمی‌گرداند.

```http
GET https://api.evand.com/events
```

### Query Parameters

| Name | Type | Required | وضعیت | توضیح |
|---|---|---:|---:|---|
| `page` | integer | No | تایید شد | شماره صفحه، پیش‌فرض `1` |
| `per_page` | integer | No | تایید شد | تعداد آیتم در هر صفحه، پیش‌فرض `10` |
| `id` | integer | No | تایید شد | فیلتر مستقیم بر اساس شناسه رویداد؛ برای ذخیره/به‌روزرسانی رویدادهای شناخته‌شده با ID قابل استفاده است |
| `q` | string | No | تایید شد | جستجوی متنی |
| `city_id` | integer | No | تایید شد | فیلتر بر اساس شهر |
| `category_id` | integer | No | تایید شد | فیلتر بر اساس دسته‌بندی |
| `online` | string | No | تایید شد | مقدار مشاهده‌شده: `yes` یا `no` |
| `type_id` | integer | No | استنباطی | از فیلد رویداد استنباط شده؛ روی `/v2/events` پاسخ می‌دهد |
| `status` | string | No | استنباطی | مثل `accepted` |
| `timing_status` | string | No | استنباطی | مثل `future` یا `past` |

### Example Request

```bash
curl 'https://api.evand.com/events?per_page=5&page=1&online=yes' \
  -H 'Accept: application/json'
```

برای دریافت مستقیم یک رویداد بر اساس شناسه نیز می‌توان از همین endpoint استفاده کرد:

```bash
curl 'https://api.evand.com/events?id=14822567' \
  -H 'Accept: application/json'
```

نکته ingestion: اگر شناسه رویدادها از قبل موجود باشد، ذخیره یا به‌روزرسانی همه رویدادها می‌تواند با پیمایش IDها و درخواست‌های `GET /events?id={event_id}` انجام شود. پاسخ همچنان در envelope لیستی `data` برمی‌گردد.

### Example Response

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
          "original": "https://static.evand.net/images/organizations/logos/original/ac3fe53f2ce710a2377c709832758ccc.png",
          "thumbnails": []
        }
      },
      "type_id": 6,
      "category_id": 165,
      "name": ">> انگلیسی را قورت بده!",
      "slug": "ورک-شاپ-انگلیسی-را-قورت-بده-2774845-1-1",
      "start_date": "2026-06-16T17:30:00+0330",
      "end_date": "2026-06-16T20:30:00+0330",
      "cover": {
        "original": "https://static.evand.net/images/events/covers/original/f3975e79a3ef44d46983b82f81f7fdfb.png",
        "thumbnails": [],
        "name": "f3975e79a3ef44d46983b82f81f7fdfb.png"
      },
      "address": "خیابان بهشتی، خیابان پاکستان، کوچه حکیمی، پلاک ۳۰، واحد دو",
      "latitude": 35.73300627,
      "longitude": 51.42119703,
      "status": "accepted",
      "private": "no",
      "online": "no",
      "published": "yes",
      "ended": false,
      "soldout": false,
      "timing_status": "future",
      "is_free": false,
      "cancelled": false,
      "duration": {
        "hours": 3
      },
      "created_at": "2026-06-08T17:08:12+0330",
      "updated_at": "2026-06-11T15:37:04+0330",
      "_links": {
        "self": {
          "href": "/events/ورک-شاپ-انگلیسی-را-قورت-بده-2774845-1-1"
        },
        "certificate": null,
        "cancel_event": {
          "verb": "post",
          "uri": "/events/ورک-شاپ-انگلیسی-را-قورت-بده-2774845-1-1/cancellation"
        },
        "user_ticket": null
      }
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

## GET /events/{slug}

جزئیات کامل یک رویداد را بر اساس slug برمی‌گرداند.

```http
GET https://api.evand.com/events/{slug}
```

### Example

```http
GET https://api.evand.com/events/ورکشاپ-تیم-بیزینس-کوچینگ-سوم-شخص
```

### Example Response

```json
{
  "data": {
    "id": 14822567,
    "city_id": 66,
    "organization_id": "qr798",
    "organization": {
      "id": "qr798",
      "name": "مرکز فروش اتاق رشد",
      "slug": "mollahoseyni-coach",
      "logo": {
        "original": "https://static.evand.net/images/organizations/logos/original/621085e832d5707edf3f616b9d3543de.png",
        "thumbnails": []
      }
    },
    "type_id": 6,
    "category_id": 215,
    "name": "ورک شاپ رشد بی بهانه",
    "slug": "ورکشاپ-تیم-بیزینس-کوچینگ-سوم-شخص",
    "start_date": "2023-06-25T09:00:00+0330",
    "end_date": "2023-06-25T19:00:00+0330",
    "address": "دهکده ورزشی چهارباغ",
    "latitude": 35.85693649,
    "longitude": 50.80723961,
    "status": "accepted",
    "private": "no",
    "online": "no",
    "published": "yes",
    "ended": true,
    "soldout": false,
    "timing_status": "past",
    "is_free": false,
    "deprecated": true,
    "duration": {
      "hours": 10
    },
    "created_at": "2023-05-27T12:55:13+0330",
    "updated_at": "2023-05-27T18:25:55+0330",
    "_links": {
      "self": {
        "href": "/events/ورکشاپ-تیم-بیزینس-کوچینگ-سوم-شخص"
      },
      "certificate": null,
      "cancel_event": {
        "verb": "post",
        "uri": "/events/ورکشاپ-تیم-بیزینس-کوچینگ-سوم-شخص/cancellation"
      },
      "user_ticket": null
    }
  }
}
```

### Event Field Descriptions

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه رویداد |
| `city_id` | integer | شناسه شهر؛ برای برخی رویدادهای آنلاین ممکن است `0` باشد |
| `organization_id` | string | شناسه برگزارکننده |
| `organization` | object/null | اطلاعات خلاصه برگزارکننده |
| `type_id` | integer | شناسه نوع رویداد؛ از `/types` قابل تطبیق است |
| `category_id` | integer | شناسه دسته‌بندی |
| `name` | string | عنوان رویداد |
| `slug` | string | slug رویداد |
| `start_date` | string | زمان شروع با offset، مثل `+0330` |
| `end_date` | string | زمان پایان با offset |
| `cover` | object/null | تصویر کاور |
| `website` | string/null | سایت رویداد |
| `address` | string/null | آدرس رویداد |
| `latitude` | number/null | عرض جغرافیایی |
| `longitude` | number/null | طول جغرافیایی |
| `status` | string | وضعیت تایید/انتشار؛ مقدار مشاهده‌شده: `accepted` |
| `private` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `online` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `published` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `ended` | boolean | آیا رویداد تمام شده است |
| `soldout` | boolean | آیا فروش تکمیل شده است |
| `timing_status` | string | وضعیت زمانی، مثل `future` یا `past` |
| `tickets_count` | integer | تعداد ticketها یا ticket typeها؛ معنی دقیق نیازمند تایید است |
| `tickets_sold_count` | integer | تعداد بلیت فروخته‌شده |
| `is_free` | boolean | رایگان بودن رویداد |
| `cancelled` | boolean | لغو شدن رویداد |
| `deprecated` | boolean | قدیمی/منسوخ بودن؛ در یک رویداد قدیمی `true` مشاهده شد |
| `duration.hours` | number | مدت رویداد بر حسب ساعت |
| `created_at` | string | زمان ایجاد |
| `updated_at` | string | زمان آخرین به‌روزرسانی |
| `_links.self.href` | string | مسیر نسبی جزئیات رویداد |
| `_links.cancel_event` | object/null | اکشن لغو؛ احتمالاً نیازمند احراز هویت |

## GET /events/{slug}/tickets

بلیت‌های یک رویداد را برمی‌گرداند.

```http
GET https://api.evand.com/events/{slug}/tickets
```

### Example Response

```json
{
  "data": [
    {
      "id": 327563,
      "event_id": 25021499,
      "showtime_id": 183198,
      "type": "normal",
      "title": "بلیت زودهنگام مخصوص 2نفر اول",
      "available_count": 2,
      "capacity": 3,
      "price": 150000,
      "commission_pay_by_user": 39600,
      "start_date": "2026-06-08T10:30:00+0330",
      "end_date": "2026-06-16T16:00:00+0330",
      "description": "90 درصد تخفیف",
      "min_price": 0,
      "max_price": 0,
      "min_count": 1,
      "max_count": 1,
      "active": "yes",
      "ordering": 0,
      "login_required": "no",
      "confirmation_on": "none",
      "_links": {
        "self": {
          "href": "/tickets/327563"
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 3,
      "count": 3,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 1,
      "links": []
    }
  }
}
```

### Ticket Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه بلیت |
| `event_id` | integer | شناسه رویداد |
| `showtime_id` | integer | شناسه سانس |
| `type` | string | نوع بلیت؛ مثل `normal` |
| `title` | string | عنوان بلیت |
| `available_count` | integer | تعداد باقی‌مانده |
| `capacity` | integer | ظرفیت کل |
| `price` | integer | قیمت بلیت |
| `commission_pay_by_user` | integer | کارمزد پرداختی توسط کاربر |
| `start_date` | string | شروع بازه فروش |
| `end_date` | string | پایان بازه فروش |
| `description` | string/null | توضیح بلیت |
| `min_count` | integer | حداقل تعداد قابل خرید |
| `max_count` | integer | حداکثر تعداد قابل خرید |
| `active` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `login_required` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `confirmation_on` | string | وضعیت تایید، مثل `none` |

## GET /events/{slug}/showtimes

سانس‌های یک رویداد را برمی‌گرداند.

```http
GET https://api.evand.com/events/{slug}/showtimes
```

### Example Response

```json
{
  "data": [
    {
      "id": 183198,
      "event_id": 25021499,
      "start_date": "2026-06-16T17:30:00+0330",
      "end_date": "2026-06-16T20:30:00+0330",
      "description": "",
      "label": "سانس یک",
      "has_purchasable_ticket": true
    }
  ],
  "meta": {
    "pagination": {
      "total": 1,
      "count": 1,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 1,
      "links": []
    }
  }
}
```

## GET /v2/events

نسخه سبک‌تر لیست رویدادها. برای نمایش کارت رویداد در UI مناسب‌تر است.

```http
GET https://api.evand.com/v2/events
```

### Query Parameters

| Name | Type | Required | وضعیت | توضیح |
|---|---|---:|---:|---|
| `page` | integer | No | تایید شد | شماره صفحه |
| `per_page` | integer | No | تایید شد | تعداد آیتم در صفحه |
| `include` | CSV string | No | تایید شد | مقدار مشاهده‌شده: `city,organization,prices` |
| `sort` | string | No | تایید شد | مقادیر مشاهده‌شده: `bestsellers`, `randomness` |
| `partnership` | CSV string | No | تایید شد | مقدار مشاهده‌شده: `colleague,partner` |
| `type_id` | integer | No | پاسخ داد | اثر دقیق فیلتر نیازمند تایید بیشتر |
| `timing_status` | string | No | پاسخ داد | مثل `future` |
| `is_free` | boolean/string | No | پاسخ داد | اثر دقیق نیازمند تایید بیشتر |
| `tag` | string | No | پاسخ داد | اثر دقیق نیازمند تایید بیشتر |

### Example Request

```bash
curl 'https://api.evand.com/v2/events?sort=bestsellers&per_page=2&include=city,organization,prices'
```

### Example Response

```json
{
  "data": [
    {
      "id": 25021486,
      "name": "همایش ادبیات نهایی_علیرضا جعفری",
      "slug": "همایش-ادبیات-نهایی-علیرضا-جعفری-398315",
      "city_name": "مشهد",
      "online": "no",
      "cover": {
        "original": "https://static.evand.net/images/events/covers/original/1a795413d370df2406e03a254c782a6c.png",
        "thumbnails": [],
        "name": "1a795413d370df2406e03a254c782a6c.png"
      },
      "start_date": "2026-06-22T16:00:00+0330",
      "end_date": "2026-06-23T20:00:00+0330",
      "organization": {
        "data": {
          "name": "بنیاد علمی آموزشی دکترشیخ",
          "slug": "mohammadyoosefsheykh-id-gmail-com-3134278",
          "logo": {
            "original": "https://static.evand.net/images/organizations/logos/original/d46032728238ccbf91ec22481e2532af.png",
            "thumbnails": []
          }
        }
      },
      "ended": false,
      "soldout": false,
      "partnership": {
        "status": null
      },
      "is_liked": false,
      "has_file": false,
      "minimum_ticket_price": 58000,
      "maximum_ticket_price": 1580000
    }
  ],
  "meta": {
    "pagination": {
      "total": 428,
      "count": 2,
      "per_page": 2,
      "current_page": 1,
      "total_pages": 214,
      "links": {
        "next": "https://api.evand.com/v2/events?sort=bestsellers&per_page=2&include=city%2Corganization%2Cprices&page=2"
      }
    }
  }
}
```

## GET /v2/events/{slug}

جزئیات خلاصه یک رویداد را برمی‌گرداند.

```http
GET https://api.evand.com/v2/events/{slug}
```

### Example Response

```json
{
  "data": {
    "id": 25021499,
    "address": "خیابان بهشتی، خیابان پاکستان، کوچه حکیمی، پلاک ۳۰، واحد دو",
    "name": ">> انگلیسی را قورت بده!",
    "cover": "https://static.evand.net/images/events/covers/original/f3975e79a3ef44d46983b82f81f7fdfb.png",
    "slug": "ورک-شاپ-انگلیسی-را-قورت-بده-2774845-1-1",
    "start_date": "2026-06-16T17:30:00+0330",
    "end_date": "2026-06-16T20:30:00+0330",
    "latitude": 35.73300627,
    "longitude": 51.42119703,
    "city": {
      "name": "تهران"
    },
    "organization": {
      "name": "اندیشکده رویش",
      "logo": "https://static.evand.net/images/organizations/logos/original/ac3fe53f2ce710a2377c709832758ccc.png",
      "socials": {
        "tel": "02122919522"
      }
    }
  }
}
```

## GET /organizations

لیست برگزارکننده‌ها را به صورت صفحه‌بندی‌شده برمی‌گرداند.

```http
GET https://api.evand.com/organizations
```

### Query Parameters

| Name | Type | Required | وضعیت | توضیح |
|---|---|---:|---:|---|
| `page` | integer | No | تایید شد | شماره صفحه |
| `per_page` | integer | No | تایید شد | تعداد آیتم در صفحه |
| `sort` | string | No | دیده‌شده | در HTML سایت مقادیر `bestsellers` و `partnerships` دیده شد؛ نیازمند بررسی بیشتر |

### Example Request

```bash
curl 'https://api.evand.com/organizations?per_page=2&page=1'
```

### Example Response

```json
{
  "data": [
    {
      "id": "92ze8",
      "name": "atie.eskandari85@gmail.com",
      "slug": "atie-eskandari85-gmail-com-136695",
      "logo": {
        "original": "https://static.evand.net/images/organizations/logos/original/5aeea353a18adb00a15ee175d35d0f7c.png",
        "thumbnails": []
      },
      "cover": null,
      "description": "<p>...</p>",
      "striped_description": "آخرین باری که با تمام وجود...",
      "socials": {
        "tel": "09358105253"
      },
      "show_admins": "no",
      "followers_count": 0,
      "events_count": 0,
      "is_followed": false,
      "user_role": null,
      "partnership": {
        "status": null
      },
      "active_events_count": 0,
      "recommended": {
        "number_of_recommended": 0,
        "percent_of_recommended": 0
      },
      "_links": {
        "self": {
          "href": "/organizations/atie-eskandari85-gmail-com-136695"
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 43087,
      "count": 2,
      "per_page": 2,
      "current_page": 1,
      "total_pages": 21544,
      "links": {
        "next": "https://api.evand.com/organizations?per_page=2&page=2"
      }
    }
  }
}
```

## GET /organizations/{slug}

جزئیات یک برگزارکننده را بر اساس slug برمی‌گرداند.

```http
GET https://api.evand.com/organizations/{slug}
```

### Example

```http
GET https://api.evand.com/organizations/داود-میرعلایی-294475101
```

### Example Response

```json
{
  "data": {
    "id": "rrjo1",
    "name": "داود میرعلایی",
    "slug": "داود-میرعلایی-294475101",
    "logo": null,
    "cover": null,
    "description": "<p>مکتب کارآفرینی اصغری</p>",
    "striped_description": "مکتب کارآفرینی اصغری",
    "socials": {
      "tel": "09193600941"
    },
    "show_admins": "no",
    "followers_count": 9,
    "events_count": 1,
    "is_followed": false,
    "user_role": null,
    "partnership": {
      "status": null
    },
    "active_events_count": 0,
    "recommended": {
      "number_of_recommended": 0,
      "percent_of_recommended": 0
    },
    "_links": {
      "self": {
        "href": "/organizations/داود-میرعلایی-294475101"
      }
    }
  }
}
```

### Organization Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | string | شناسه برگزارکننده |
| `name` | string | نام نمایشی |
| `slug` | string | slug برگزارکننده |
| `logo` | object/null | لوگو |
| `cover` | object/null | تصویر کاور |
| `description` | string/null | توضیح HTML |
| `striped_description` | string/null | توضیح پاک‌سازی‌شده/متنی |
| `socials` | object/null | اطلاعات تماس یا شبکه‌های اجتماعی؛ مثل `tel` |
| `show_admins` | string | مقدار مشاهده‌شده: `yes` یا `no` |
| `followers_count` | integer | تعداد دنبال‌کننده |
| `events_count` | integer | تعداد کل رویدادها |
| `active_events_count` | integer | تعداد رویدادهای فعال |
| `is_followed` | boolean | دنبال شدن توسط کاربر فعلی |
| `user_role` | string/null | نقش کاربر فعلی در صورت احراز هویت |
| `partnership.status` | string/null | وضعیت همکاری |
| `recommended` | object | آمار پیشنهادشدن |
| `_links.self.href` | string | مسیر نسبی برگزارکننده |

## GET /categories

لیست دسته‌بندی‌ها را برمی‌گرداند. صفحه‌بندی مشاهده نشد.

```http
GET https://api.evand.com/categories
```

### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "parent_id": 0,
      "title": "تکنولوژی",
      "slug": "تکنولوژی",
      "description": "دفعه‌ی بعد که می‌خواستید...",
      "seo": {
        "title": "دوره ها، کارگاه ها، همایش ها و کنفرانس های تکنولوژی در ایوند",
        "description": "دسته بندی «رویداد های تکنولوژی» ایوند..."
      },
      "ordering": 1,
      "is_followed": false,
      "events_count": 0
    }
  ]
}
```

## GET /cities

لیست شهرها را برمی‌گرداند. صفحه‌بندی مشاهده نشد.

```http
GET https://api.evand.com/cities
```

### Example Response

```json
{
  "data": [
    {
      "id": 139,
      "name": "آبادان",
      "slug": "آبادان",
      "latitude": 30.347296,
      "longitude": 48.2934004,
      "events_count": 0
    },
    {
      "id": 87,
      "name": "تهران",
      "slug": "تهران",
      "latitude": 35.696111,
      "longitude": 51.423056,
      "events_count": 0
    }
  ]
}
```

### City Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه شهر |
| `name` | string | نام شهر |
| `slug` | string | slug شهر |
| `latitude` | number/null | عرض جغرافیایی |
| `longitude` | number/null | طول جغرافیایی |
| `events_count` | integer | تعداد رویدادهای مرتبط |

## GET /tags

لیست تگ‌ها را به صورت صفحه‌بندی‌شده برمی‌گرداند.

```http
GET https://api.evand.com/tags
```

### Query Parameters

| Name | Type | Required | توضیح |
|---|---|---:|---|
| `page` | integer | No | شماره صفحه، پیش‌فرض `1` |
| `per_page` | integer | No | تعداد تگ در هر صفحه، پیش‌فرض `10` |

### Example Request

```bash
curl 'https://api.evand.com/tags?per_page=3&page=2' \
  -H 'Accept: application/json'
```

### Example Response

```json
{
  "data": [
    {
      "id": 1384543,
      "name": "سه_شنبه_دیزاین"
    },
    {
      "id": 1384524,
      "name": "آلن_تورینگ"
    },
    {
      "id": 1384523,
      "name": "کارگاهمذهبی"
    }
  ],
  "meta": {
    "pagination": {
      "total": 103524,
      "count": 3,
      "per_page": 3,
      "current_page": 2,
      "total_pages": 34508,
      "links": {
        "previous": "https://api.evand.com/tags?per_page=3&page=1",
        "next": "https://api.evand.com/tags?per_page=3&page=3"
      }
    }
  }
}
```

### Tag Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه تگ |
| `name` | string | نام تگ |

## GET /types

لیست ثابت نوع‌های رویداد را برمی‌گرداند. این endpoint صفحه‌بندی ندارد.

```http
GET https://api.evand.com/types
```

### Example Request

```bash
curl 'https://api.evand.com/types' \
  -H 'Accept: application/json'
```

### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "title": "اکسپو",
      "slug": "اکسپو",
      "ordering": 1
    },
    {
      "id": 6,
      "title": "کارگاه",
      "slug": "کارگاه",
      "ordering": 6
    }
  ]
}
```

### Event Type Fields

| Field | Type | توضیح |
|---|---|---|
| `id` | integer | شناسه نوع؛ با `type_id` در رویدادها مرتبط است |
| `title` | string | عنوان نوع رویداد |
| `slug` | string | slug نوع |
| `ordering` | integer | ترتیب نمایش |

### Observed Type IDs

| ID | Title |
|---:|---|
| 1 | اکسپو |
| 2 | جشن |
| 3 | جلسه |
| 4 | دورهمی |
| 5 | سمینار |
| 6 | کارگاه |
| 7 | کنسرت |
| 8 | کنفرانس |
| 9 | سفر |
| 10 | رقابت |
| 11 | غیره |
| 12 | رویداد |
| 13 | دوره |
| 15 | همایش |
| 16 | کنگره |
| 17 | تئاتر |

## JSON Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://api.evand.com/schemas/public-api.json",
  "title": "Evand Public API Schemas",
  "$defs": {
    "Pagination": {
      "type": "object",
      "properties": {
        "total": { "type": "integer" },
        "count": { "type": "integer" },
        "per_page": { "type": "integer" },
        "current_page": { "type": "integer" },
        "total_pages": { "type": "integer" },
        "links": {
          "oneOf": [
            {
              "type": "object",
              "properties": {
                "previous": { "type": "string" },
                "next": { "type": "string" }
              }
            },
            {
              "type": "array",
              "maxItems": 0
            }
          ]
        }
      },
      "additionalProperties": true
    },
    "ImageAsset": {
      "type": ["object", "null"],
      "properties": {
        "original": { "type": "string" },
        "thumbnails": { "type": "array" },
        "name": { "type": ["string", "null"] }
      },
      "additionalProperties": true
    },
    "Event": {
      "type": "object",
      "required": ["id", "name", "slug", "start_date", "end_date"],
      "properties": {
        "id": { "type": "integer" },
        "city_id": { "type": "integer" },
        "organization_id": { "type": "string" },
        "type_id": { "type": "integer" },
        "category_id": { "type": "integer" },
        "name": { "type": "string" },
        "slug": { "type": "string" },
        "start_date": { "type": "string" },
        "end_date": { "type": "string" },
        "cover": { "$ref": "#/$defs/ImageAsset" },
        "website": { "type": ["string", "null"] },
        "address": { "type": ["string", "null"] },
        "latitude": { "type": ["number", "null"] },
        "longitude": { "type": ["number", "null"] },
        "status": { "type": "string" },
        "private": { "type": "string", "enum": ["yes", "no"] },
        "online": { "type": "string", "enum": ["yes", "no"] },
        "published": { "type": "string", "enum": ["yes", "no"] },
        "ended": { "type": "boolean" },
        "soldout": { "type": "boolean" },
        "timing_status": { "type": "string" },
        "is_free": { "type": "boolean" },
        "cancelled": { "type": "boolean" },
        "deprecated": { "type": "boolean" },
        "created_at": { "type": "string" },
        "updated_at": { "type": "string" }
      },
      "additionalProperties": true
    },
    "Organization": {
      "type": "object",
      "properties": {
        "id": { "type": "string" },
        "name": { "type": "string" },
        "slug": { "type": "string" },
        "logo": { "$ref": "#/$defs/ImageAsset" },
        "cover": { "$ref": "#/$defs/ImageAsset" },
        "description": { "type": ["string", "null"] },
        "striped_description": { "type": ["string", "null"] },
        "socials": { "type": ["object", "null"] },
        "show_admins": { "type": "string", "enum": ["yes", "no"] },
        "followers_count": { "type": "integer" },
        "events_count": { "type": "integer" },
        "active_events_count": { "type": "integer" },
        "is_followed": { "type": "boolean" },
        "user_role": { "type": ["string", "null"] }
      },
      "additionalProperties": true
    },
    "City": {
      "type": "object",
      "required": ["id", "name", "slug"],
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "string" },
        "slug": { "type": "string" },
        "latitude": { "type": ["number", "null"] },
        "longitude": { "type": ["number", "null"] },
        "events_count": { "type": "integer" }
      },
      "additionalProperties": true
    },
    "Tag": {
      "type": "object",
      "required": ["id", "name"],
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "string" }
      }
    },
    "EventType": {
      "type": "object",
      "required": ["id", "title", "slug", "ordering"],
      "properties": {
        "id": { "type": "integer" },
        "title": { "type": "string" },
        "slug": { "type": "string" },
        "ordering": { "type": "integer" }
      }
    }
  }
}
```

## OpenAPI 3.1 YAML

```yaml
openapi: 3.1.0
info:
  title: Evand Public API
  version: 0.3.0
  description: Inferred documentation for public Evand API endpoints based on observed responses.
servers:
  - url: https://api.evand.com

paths:
  /events:
    get:
      summary: List detailed events
      operationId: listEvents
      parameters:
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
        - name: q
          in: query
          schema:
            type: string
        - name: id
          in: query
          description: Filter by event ID. Useful for fetching/storing known events one by one.
          schema:
            type: integer
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
          description: Paginated event list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/EventListResponse"

  /events/{slug}:
    get:
      summary: Get detailed event by slug
      operationId: getEvent
      parameters:
        - $ref: "#/components/parameters/Slug"
      responses:
        "200":
          description: Event detail
          content:
            application/json:
              schema:
                type: object
                required: [data]
                properties:
                  data:
                    $ref: "#/components/schemas/Event"

  /events/{slug}/tickets:
    get:
      summary: List event tickets
      operationId: listEventTickets
      parameters:
        - $ref: "#/components/parameters/Slug"
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
      responses:
        "200":
          description: Paginated ticket list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/TicketListResponse"

  /events/{slug}/showtimes:
    get:
      summary: List event showtimes
      operationId: listEventShowtimes
      parameters:
        - $ref: "#/components/parameters/Slug"
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
      responses:
        "200":
          description: Paginated showtime list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/ShowtimeListResponse"

  /v2/events:
    get:
      summary: List compact events
      operationId: listV2Events
      parameters:
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
        - name: include
          in: query
          schema:
            type: string
          examples:
            default:
              value: city,organization,prices
        - name: sort
          in: query
          schema:
            type: string
            enum: [bestsellers, randomness]
        - name: partnership
          in: query
          schema:
            type: string
          examples:
            default:
              value: colleague,partner
        - name: type_id
          in: query
          schema:
            type: integer
        - name: timing_status
          in: query
          schema:
            type: string
        - name: is_free
          in: query
          schema:
            type: string
        - name: tag
          in: query
          schema:
            type: string
      responses:
        "200":
          description: Paginated compact event list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/V2EventListResponse"

  /v2/events/{slug}:
    get:
      summary: Get compact event by slug
      operationId: getV2Event
      parameters:
        - $ref: "#/components/parameters/Slug"
      responses:
        "200":
          description: Compact event detail
          content:
            application/json:
              schema:
                type: object
                required: [data]
                properties:
                  data:
                    $ref: "#/components/schemas/V2EventDetail"

  /organizations:
    get:
      summary: List organizations
      operationId: listOrganizations
      parameters:
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
        - name: sort
          in: query
          schema:
            type: string
            enum: [bestsellers, partnerships]
      responses:
        "200":
          description: Paginated organization list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/OrganizationListResponse"

  /organizations/{slug}:
    get:
      summary: Get organization by slug
      operationId: getOrganization
      parameters:
        - $ref: "#/components/parameters/Slug"
      responses:
        "200":
          description: Organization detail
          content:
            application/json:
              schema:
                type: object
                required: [data]
                properties:
                  data:
                    $ref: "#/components/schemas/Organization"

  /categories:
    get:
      summary: List categories
      operationId: listCategories
      responses:
        "200":
          description: Category list
          content:
            application/json:
              schema:
                type: object
                required: [data]
                properties:
                  data:
                    type: array
                    items:
                      $ref: "#/components/schemas/Category"

  /cities:
    get:
      summary: List cities
      operationId: listCities
      responses:
        "200":
          description: City list
          content:
            application/json:
              schema:
                type: object
                required: [data]
                properties:
                  data:
                    type: array
                    items:
                      $ref: "#/components/schemas/City"

  /tags:
    get:
      summary: List tags
      operationId: listTags
      parameters:
        - $ref: "#/components/parameters/Page"
        - $ref: "#/components/parameters/PerPage"
      responses:
        "200":
          description: Paginated tag list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/TagListResponse"

  /types:
    get:
      summary: List event types
      operationId: listEventTypes
      responses:
        "200":
          description: Event type list
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/EventTypeListResponse"

components:
  parameters:
    Page:
      name: page
      in: query
      schema:
        type: integer
        minimum: 1
        default: 1
    PerPage:
      name: per_page
      in: query
      schema:
        type: integer
        minimum: 1
        default: 10
    Slug:
      name: slug
      in: path
      required: true
      schema:
        type: string

  schemas:
    Pagination:
      type: object
      properties:
        total:
          type: integer
        count:
          type: integer
        per_page:
          type: integer
        current_page:
          type: integer
        total_pages:
          type: integer
        links:
          oneOf:
            - type: object
              properties:
                previous:
                  type: string
                next:
                  type: string
            - type: array
              maxItems: 0

    PaginatedMeta:
      type: object
      properties:
        pagination:
          $ref: "#/components/schemas/Pagination"

    ImageAsset:
      type:
        - object
        - "null"
      properties:
        original:
          type: string
        thumbnails:
          type: array
          items: {}
        name:
          type:
            - string
            - "null"
      additionalProperties: true

    EventListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/Event"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    Event:
      type: object
      additionalProperties: true
      properties:
        id:
          type: integer
        city_id:
          type: integer
        organization_id:
          type: string
        organization:
          $ref: "#/components/schemas/EventOrganization"
        type_id:
          type: integer
        category_id:
          type: integer
        name:
          type: string
        slug:
          type: string
        start_date:
          type: string
        end_date:
          type: string
        cover:
          $ref: "#/components/schemas/ImageAsset"
        website:
          type: [string, "null"]
        address:
          type: [string, "null"]
        latitude:
          type: [number, "null"]
        longitude:
          type: [number, "null"]
        status:
          type: string
        private:
          type: string
          enum: [yes, no]
        online:
          type: string
          enum: [yes, no]
        published:
          type: string
          enum: [yes, no]
        ended:
          type: boolean
        soldout:
          type: boolean
        timing_status:
          type: string
        is_free:
          type: boolean
        cancelled:
          type: boolean
        deprecated:
          type: boolean

    EventOrganization:
      type:
        - object
        - "null"
      properties:
        id:
          type: string
        name:
          type: string
        slug:
          type: string
        logo:
          $ref: "#/components/schemas/ImageAsset"
      additionalProperties: true

    TicketListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/Ticket"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    Ticket:
      type: object
      additionalProperties: true
      properties:
        id:
          type: integer
        event_id:
          type: integer
        showtime_id:
          type: integer
        type:
          type: string
        title:
          type: string
        available_count:
          type: integer
        capacity:
          type: integer
        price:
          type: integer
        commission_pay_by_user:
          type: integer
        start_date:
          type: string
        end_date:
          type: string
        min_count:
          type: integer
        max_count:
          type: integer
        active:
          type: string
          enum: [yes, no]

    ShowtimeListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/Showtime"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    Showtime:
      type: object
      properties:
        id:
          type: integer
        event_id:
          type: integer
        start_date:
          type: string
        end_date:
          type: string
        description:
          type: string
        label:
          type: string
        has_purchasable_ticket:
          type: boolean

    V2EventListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/V2Event"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    V2Event:
      type: object
      additionalProperties: true
      properties:
        id:
          type: integer
        name:
          type: string
        slug:
          type: string
        city_name:
          type: string
        online:
          type: string
          enum: [yes, no]
        cover:
          $ref: "#/components/schemas/ImageAsset"
        start_date:
          type: string
        end_date:
          type: string
        organization:
          type: object
        ended:
          type: boolean
        soldout:
          type: boolean
        minimum_ticket_price:
          type: integer
        maximum_ticket_price:
          type: integer

    V2EventDetail:
      type: object
      additionalProperties: true
      properties:
        id:
          type: integer
        address:
          type: [string, "null"]
        name:
          type: string
        cover:
          type: string
        slug:
          type: string
        start_date:
          type: string
        end_date:
          type: string
        latitude:
          type: number
        longitude:
          type: number
        city:
          type: object
        organization:
          type: object

    OrganizationListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/Organization"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    Organization:
      type: object
      additionalProperties: true
      properties:
        id:
          type: string
        name:
          type: string
        slug:
          type: string
        logo:
          $ref: "#/components/schemas/ImageAsset"
        cover:
          $ref: "#/components/schemas/ImageAsset"
        description:
          type: [string, "null"]
        striped_description:
          type: [string, "null"]
        socials:
          type: [object, "null"]
        show_admins:
          type: string
          enum: [yes, no]
        followers_count:
          type: integer
        events_count:
          type: integer
        active_events_count:
          type: integer
        is_followed:
          type: boolean
        user_role:
          type: [string, "null"]

    Category:
      type: object
      additionalProperties: true
      properties:
        id:
          type: integer
        parent_id:
          type: integer
        title:
          type: string
        slug:
          type: string
        description:
          type: string
        seo:
          type: object
        ordering:
          type: integer
        is_followed:
          type: boolean
        events_count:
          type: integer

    City:
      type: object
      required: [id, name, slug]
      properties:
        id:
          type: integer
        name:
          type: string
        slug:
          type: string
        latitude:
          type: [number, "null"]
        longitude:
          type: [number, "null"]
        events_count:
          type: integer

    TagListResponse:
      type: object
      required: [data, meta]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/Tag"
        meta:
          $ref: "#/components/schemas/PaginatedMeta"

    Tag:
      type: object
      required: [id, name]
      properties:
        id:
          type: integer
        name:
          type: string

    EventTypeListResponse:
      type: object
      required: [data]
      properties:
        data:
          type: array
          items:
            $ref: "#/components/schemas/EventType"

    EventType:
      type: object
      required: [id, title, slug, ordering]
      properties:
        id:
          type: integer
        title:
          type: string
        slug:
          type: string
        ordering:
          type: integer
```

## Unknowns and Assumptions

- این API ظاهراً مستند رسمی عمومی ندارد؛ همه موارد بالا از مشاهده عملی استخراج شده‌اند.
- احراز هویت برای endpointهای read عمومی لازم نبود، اما فیلدهایی مثل `is_liked`, `is_followed`, `user_role`, `has_event_signed_up` احتمالاً در حالت authenticated معنی کامل‌تری دارند.
- `POST /events/{slug}/cancellation` در `_links` دیده شد ولی تست نشد، چون action تغییردهنده است و احتمالاً نیاز به مجوز دارد.
- بعضی query parameterها پاسخ می‌دهند اما اثر دقیقشان قطعی نیست؛ مثل `is_free` و `tag` در `/v2/events`.
- برای `organizations` مقادیر `sort=bestsellers` و `sort=partnerships` در HTML سایت دیده شد، اما در تست مستقیم برخی درخواست‌ها کند یا بی‌پاسخ شدند.
- بعضی فیلدهای boolean به شکل string هستند؛ مثل `online: "yes"`, `private: "no"`, `published: "yes"`.
- فرمت تاریخ‌ها شبیه ISO 8601 است اما offset به شکل `+0330` می‌آید، نه `+03:30`.
- `links` در pagination گاهی object و گاهی array خالی است. کلاینت باید هر دو حالت را تحمل کند.
- `GET /cities/{id}` و `GET /categories/{id}` وجود نداشتند یا عمومی نبودند.
- `GET /categories`، `GET /cities` و `GET /types` بدون pagination برگشتند.
- `GET /tags`، `GET /events`، `GET /v2/events` و `GET /organizations` صفحه‌بندی دارند.

## منابع بررسی

- `https://api.evand.com/events`
- `https://api.evand.com/events/{slug}`
- `https://api.evand.com/events/{slug}/tickets`
- `https://api.evand.com/events/{slug}/showtimes`
- `https://api.evand.com/v2/events`
- `https://api.evand.com/v2/events/{slug}`
- `https://api.evand.com/organizations`
- `https://api.evand.com/organizations/{slug}`
- `https://api.evand.com/categories`
- `https://api.evand.com/cities`
- `https://api.evand.com/tags`
- `https://api.evand.com/types`
- `https://evand.com/sitemap.xml`
- `https://evand.com`
