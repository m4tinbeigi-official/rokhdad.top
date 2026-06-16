# Eseminar API Documentation

Source collection:

```text
/Users/ricksabchez/Downloads/Rokhdad.postman_collection.json
```

Base URL:

```text
https://api.eseminar.tv/api/v1
```

Generated from the `Eseminar` folder in the Rokhdad Postman collection. The collection did not include saved example responses, so response shapes are based on live public checks where possible.

## Common Response Pattern

Most public endpoints return:

```json
{
  "data": [],
  "status": "success"
}
```

The `/webinars` listing includes additional page metadata:

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

## Endpoint Index

| Method | Endpoint | Collection Name |
|---|---|---|
| `GET` | `/user/saved_webinars` | `saved_webinars` |
| `GET` | `/webinar/{slug}` | `Single event` |
| `GET` | `/webinars` | `All event` |
| `GET` | `/webinars?category_id={category}` | `By category` |
| `GET` | `/special_webinars` | `special webinars` |
| `GET` | `/video_webinars` | `video webinars` |
| `GET` | `/free_webinars` | `free webinars` |
| `GET` | `/latest_webinars` | `latest webinars` |
| `GET` | `/best_hosts` | `best hosts` |
| `GET` | `/sliders` | `sliders` |
| `GET` | `/subjects` | discovered from site bundle |
| `GET` | `/webinars-sliders` | discovered from site bundle |
| `GET` | `/hosts` | discovered from site bundle |
| `GET` | `/teachers` | discovered from site bundle |

## GET /webinars

Returns a paginated/listed set of webinars.

```http
GET https://api.eseminar.tv/api/v1/webinars?page=1&per_page=15
```

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `page` | integer | No | Page number. |
| `per_page` | integer | No | Number of webinars per page. |
| `category_id` | string | No | Category slug or ID. The collection uses a Persian category slug. |

### Example Requests

```bash
curl 'https://api.eseminar.tv/api/v1/webinars?page=1&per_page=15'
```

```bash
curl 'https://api.eseminar.tv/api/v1/webinars?category_id=بازاریابی-محتوا&page=1&per_page=15'
```

### Response Shape

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

### Webinar Card Fields

| Field | Type | Description |
|---|---|---|
| `id` | integer | Webinar ID. |
| `title` | string | Webinar title. |
| `slug` | string | Webinar slug. |
| `cover` | string/null | Cover image URL. |
| `speaker` | object/null | Speaker summary. |
| `speaker.name` | string | Speaker name. |
| `speaker.slug` | string | Speaker slug. |
| `speaker.avatar` | string/null | Speaker avatar URL. |
| `subjects` | array | Categories/topics. |
| `start_at` | string | Start time, present on some endpoints. |
| `end_at` | string | End time, present on some endpoints. |
| `price` | integer/null | Price. |
| `currency` | string | Currency, e.g. `IRR`. |
| `tickets` | array | Ticket list, present on some endpoints. |

## GET /webinar/{slug}

Returns detailed data for one webinar.

```http
GET https://api.eseminar.tv/api/v1/webinar/{slug}
```

### Example Request

```bash
curl 'https://api.eseminar.tv/api/v1/webinar/درآمد-دلاری-با-دراپ-شیپینگ-و-هوش-مصنوعی-بدون-سرمایه'
```

### Response Shape

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

## GET /user/saved_webinars

Returns saved webinars for the current user.

```http
GET https://api.eseminar.tv/api/v1/user/saved_webinars?per_page=5
```

### Notes

- This endpoint is under `/user` and may require authentication/session for complete results.
- No auth header or token was stored in the collection.

## GET /special_webinars

Returns special webinars.

```http
GET https://api.eseminar.tv/api/v1/special_webinars?utm={"type":"specialwebinars"}
```

## GET /video_webinars

Returns video webinars.

```http
GET https://api.eseminar.tv/api/v1/video_webinars
```

## GET /free_webinars

Returns free webinars.

```http
GET https://api.eseminar.tv/api/v1/free_webinars
```

## GET /latest_webinars

Returns latest webinars.

```http
GET https://api.eseminar.tv/api/v1/latest_webinars
```

### Response Shape

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
      "excerpt": "تولید محتوای بیشتر...",
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

Returns top hosts.

```http
GET https://api.eseminar.tv/api/v1/best_hosts
```

### Response Shape

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

## GET /sliders

Returns homepage sliders.

```http
GET https://api.eseminar.tv/api/v1/sliders
```

### Response Shape

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

## GET /subjects

Returns webinar subject/category taxonomy.

```http
GET https://api.eseminar.tv/api/v1/subjects
```

### Response Shape

```json
{
  "data": {
    "cats": [
      {
        "id": 30,
        "name": "مالی و سرمایه‌گذاری",
        "lang": "fa",
        "slug": "مالی-و-سرمایه-گذاری",
        "icon": "wallet-3-line",
        "parent": null,
        "children": [
          {
            "id": 68,
            "name": "ارزهای دیجیتال",
            "icon": null,
            "lang": "fa",
            "slug": "ارزهای-دیجیتال"
          }
        ]
      }
    ]
  },
  "status": "success"
}
```

## GET /webinars-sliders

Returns slider items for the webinars listing page.

```http
GET https://api.eseminar.tv/api/v1/webinars-sliders
```

### Response Shape

```json
{
  "data": [
    {
      "cover": "https://s3.eseminar.tv/upload/webinars-slider/example.jpg",
      "link": "https://esmn.ir/3ph",
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

## GET /hosts

Returns paginated host profiles.

```http
GET https://api.eseminar.tv/api/v1/hosts
```

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `q` | string | No | Search term. Confirmed with `hosts?q=python`. |

### Response Shape

```json
{
  "hosts": [
    {
      "id": 10692,
      "name": "شرکت تحقق رویای مزرعه من ایرانیان",
      "slug": "شرکت-تحقق-رویای-مزرعه-من-ایرانیان",
      "email": null,
      "about": null,
      "image": "https://s3.eseminar.tv/c453019/upload/host/example.jpg",
      "thumb": "https://s3.eseminar.tv/c453019/upload/host/thumb/example.jpg",
      "total_star": "8",
      "followers_count": 14,
      "webinars_count": 1,
      "top_subjects": []
    }
  ],
  "pagination": {
    "total": 0,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  },
  "additional_data": {}
}
```

## GET /teachers

Returns paginated teacher profiles.

```http
GET https://api.eseminar.tv/api/v1/teachers
```

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `q` | string | No | Search term. Confirmed with `teachers?q=python`. |

### Response Shape

```json
{
  "teachers": [
    {
      "id": 18243,
      "name": "مهندس امیر حسین مقیمی",
      "slug": "مهندس-امیر-حسین-مقیمی",
      "email": null,
      "field": "مدیر عامل مزرعه من",
      "about": null,
      "image": "https://s3.eseminar.tv/c453019/upload/teacher/example.jpg",
      "thumb": "https://s3.eseminar.tv/c453019/upload/teacher/thumb/example.jpg",
      "total_star": "8",
      "followers_count": 30,
      "webinars_count": 2,
      "top_subjects": [],
      "is_followed": null
    }
  ],
  "pagination": {
    "total": 0,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  },
  "additional_data": {}
}
```

## Discovered But Not Publicly Confirmed

The Nuxt bundle also references these route/query patterns. They should not be treated as stable API contracts until tested with the correct app context:

| Pattern | Observed Result |
|---|---|
| `/webinars?type=free` | Returned `500` in a direct API call. |
| `/webinars?type=pro` | Returned `500` in a direct API call. |
| `/webinars?status=processed` | Returned `500` in a direct API call. |
| `/webinars?tag_id={id}` | Returned API JSON error when no webinar matched. |
| `/user/wishlists/webinars` | Redirected to login. |

## JSON Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://rokhdad.local/schemas/eseminar.json",
  "title": "Eseminar API Schemas",
  "$defs": {
    "StatusResponse": {
      "type": "object",
      "properties": {
        "status": { "type": "string" },
        "data": {}
      },
      "additionalProperties": true
    },
    "WebinarCard": {
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
        "subjects": { "type": "array" },
        "start_at": { "type": ["string", "null"] },
        "end_at": { "type": ["string", "null"] },
        "price": { "type": ["integer", "number", "null"] },
        "currency": { "type": ["string", "null"] },
        "tickets": { "type": "array" }
      },
      "additionalProperties": true
    },
    "Host": {
      "type": "object",
      "properties": {
        "name": { "type": "string" },
        "slug": { "type": "string" },
        "field": { "type": ["string", "null"] },
        "about": { "type": ["string", "null"] },
        "img": { "type": ["string", "null"] },
        "stars": { "type": ["string", "number"] },
        "followers": { "type": "integer" }
      },
      "additionalProperties": true
    }
  }
}
```

## OpenAPI 3.1 YAML

```yaml
openapi: 3.1.0
info:
  title: Eseminar API
  version: 0.1.0
servers:
  - url: https://api.eseminar.tv/api/v1
paths:
  /webinars:
    get:
      summary: List webinars
      parameters:
        - name: page
          in: query
          schema: { type: integer }
        - name: per_page
          in: query
          schema: { type: integer }
        - name: category_id
          in: query
          schema: { type: string }
      responses:
        "200":
          description: Webinar list
  /webinar/{slug}:
    get:
      summary: Get webinar by slug
      parameters:
        - name: slug
          in: path
          required: true
          schema: { type: string }
      responses:
        "200":
          description: Webinar detail
  /user/saved_webinars:
    get:
      summary: List saved webinars
      parameters:
        - name: per_page
          in: query
          schema: { type: integer }
      responses:
        "200":
          description: Saved webinars
  /special_webinars:
    get:
      summary: List special webinars
      responses:
        "200":
          description: Special webinars
  /video_webinars:
    get:
      summary: List video webinars
      responses:
        "200":
          description: Video webinars
  /free_webinars:
    get:
      summary: List free webinars
      responses:
        "200":
          description: Free webinars
  /latest_webinars:
    get:
      summary: List latest webinars
      responses:
        "200":
          description: Latest webinars
  /best_hosts:
    get:
      summary: List best hosts
      responses:
        "200":
          description: Best hosts
  /sliders:
    get:
      summary: List homepage sliders
      responses:
        "200":
          description: Sliders
  /subjects:
    get:
      summary: List webinar subjects
      responses:
        "200":
          description: Subject taxonomy
  /webinars-sliders:
    get:
      summary: List webinar page sliders
      responses:
        "200":
          description: Webinar listing sliders
  /hosts:
    get:
      summary: List hosts
      parameters:
        - name: q
          in: query
          schema: { type: string }
      responses:
        "200":
          description: Host list
  /teachers:
    get:
      summary: List teachers
      parameters:
        - name: q
          in: query
          schema: { type: string }
      responses:
        "200":
          description: Teacher list
```

## Unknowns

- Saved response examples were not present in the Postman collection.
- Authentication behavior for `/user/saved_webinars` is unknown.
- Exact pagination object for `/webinars` needs a full response sample.
