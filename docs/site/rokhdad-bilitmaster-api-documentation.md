# BilitMaster API Documentation

Source collection:

```text
/Users/ricksabchez/Downloads/Rokhdad.postman_collection.json
```

Base URL:

```text
https://api.bilitmaster.com/api
```

Generated from the `BilitMaster` folder in the Rokhdad Postman collection.

Important: Two endpoints in the source collection contained a `user` token/hash. This document redacts that value as `{user_token}`.

## Common Notes

- Most BilitMaster endpoints in the collection use `POST`, even when they appear to be read-only.
- The collection did not define request bodies for these `POST` requests.
- Public checks showed responses with `status: true`.

## Endpoint Index

| Method | Endpoint | Collection Name | Notes |
|---|---|---|---|
| `POST` | `/getHomeEvents` | `All event` | Homepage events and sliders |
| `POST` | `/getEvents` | `All event og` | General event list |
| `POST` | `/getInternationalEvents` | `International Events` | International events |
| `POST` | `/getMarketEvents` | `Market Events` | Market events |
| `POST` | `/getMonthCalendar` | `Month Calendar` | Monthly calendar |
| `POST` | `/getInitial` | `Category` | Categories, states, site info |
| `POST` | `/pageGetEvent?id={id}` | `Single evnt` | Event detail |
| `POST` | `/getCities` | discovered from site JS | States/cities list |
| `POST` | `/getBanks` | discovered from site JS | Bank list |
| `POST` | `/getInfo` | discovered from site JS | Site/about info |
| `POST` | `/getTacs` | discovered from site JS | Terms and conditions |
| `POST` | `/getEventListSliders` | discovered from site JS | Event listing sliders |
| `POST` | `/getMarketListSliders` | discovered from site JS | Market listing sliders |
| `POST` | `/getCategoryLabel` | discovered from site JS | Category label |
| `POST` | `/getEventSliderDate` | discovered from site JS | Slider date options |
| `POST` | `/searchAll` | discovered from site JS | Global search suggestions |
| `POST` | `/searchEventName` | discovered from site JS | Event search suggestions |
| `POST` | `/searchMarket` | discovered from site JS | Market search suggestions |
| `POST` | `/getMarketEvent/{id}` | discovered from site JS | Market event detail |
| `POST` | `/getMarketOwnerEvents/{owner_id}` | discovered from site JS | Market owner events |
| `POST` | `/user/getEventCustomers/{event_id}?user={user_token}` | `Details event` | Sensitive user-scoped data |
| `POST` | `/user/getEvents?user={user_token}` | `all event submit` | Sensitive user-scoped data |

## POST /getHomeEvents

Returns homepage event groups, sliders, top events, new events, and market events.

```http
POST https://api.bilitmaster.com/api/getHomeEvents
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getHomeEvents'
```

### Response Shape

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
            "image_mode": "upload",
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

| Field | Type | Description |
|---|---|---|
| `id` | integer | Event ID. |
| `name` | string | Event name. |
| `state_id` | string/integer | State/country identifier or display value. |
| `city_id` | string/integer | City identifier or display value. |
| `image` | string/null | Image URL. |
| `image_mode` | string | Image source/mode. |
| `status` | string | Event status, e.g. `published`. |
| `dateString` | string | Persian display date. |
| `stateString` | string | Display state/country. |
| `cityString` | string | Display city. |
| `locationString` | string | Combined display location. |
| `priceString` | string | Price formatted as string. |
| `link` | string | Public event URL. |

## POST /getEvents

Returns general events.

```http
POST https://api.bilitmaster.com/api/getEvents
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getEvents'
```

### Notes

- No query parameters or body were stored in the collection.
- Exact filters and pagination behavior are unknown.

## POST /getInternationalEvents

Returns international events.

```http
POST https://api.bilitmaster.com/api/getInternationalEvents
```

## POST /getMarketEvents

Returns market events.

```http
POST https://api.bilitmaster.com/api/getMarketEvents
```

## POST /pageGetEvent

Returns detail for one event.

```http
POST https://api.bilitmaster.com/api/pageGetEvent?id=458
```

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `id` | integer | Yes | Event ID. |

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/pageGetEvent?id=458'
```

### Response Shape

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

| Field | Type | Description |
|---|---|---|
| `status` | boolean | Request success. |
| `user` | object | Organizer/owner data. |
| `user.id` | integer | Internal organizer ID. |
| `user.user_id` | integer | User ID. |
| `user.name` | string | Organizer name. |
| `user.image` | string/null | Organizer image. |
| `user.description` | string/null | HTML description. |
| `info` | object | Event detail object. |
| `tabs` | array | Content tabs. |
| `related_events` | array | Related events. |
| `st` | any | Unknown. |

## POST /getMonthCalendar

Returns monthly calendar data.

```http
POST https://api.bilitmaster.com/api/getMonthCalendar
```

### Notes

- No body or date query was present in the collection.
- The server may use a default month, or the actual client may send a body not captured in this collection.

## POST /getInitial

Returns initial site data: categories, states, sliders, and site info.

```http
POST https://api.bilitmaster.com/api/getInitial
```

### Example Request

```bash
curl -X POST 'https://api.bilitmaster.com/api/getInitial'
```

### Response Shape

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

| Field | Type | Description |
|---|---|---|
| `event_categories` | array | Event categories. |
| `all_event_categories` | array | All event categories. |
| `market_categories` | array | Market categories. |
| `all_market_categories` | array | All market categories. |
| `event_states` | array | Event states/locations. |
| `market_states` | array | Market states/locations. |
| `all_states` | array | All states. |
| `event_sliders` | array/object | Event sliders. |
| `market_sliders` | array/object | Market sliders. |
| `site_info` | object | Contact and support links. |

## POST /getCities

Returns states/cities used by the site.

```http
POST https://api.bilitmaster.com/api/getCities
```

```json
{
  "status": true,
  "items": [
    {
      "id": 1,
      "parent_id": 0,
      "name": "آذربایجان شرقی",
      "event_number": 0,
      "market_event_number": 0
    }
  ]
}
```

## POST /getBanks

Returns bank reference data.

```http
POST https://api.bilitmaster.com/api/getBanks
```

```json
{
  "status": true,
  "items": [
    {
      "id": 21,
      "name": "آینده"
    }
  ]
}
```

## POST /getInfo

Returns site informational pages/content such as about text.

```http
POST https://api.bilitmaster.com/api/getInfo
```

```json
{
  "status": true,
  "info": {
    "id": 1,
    "about_title": "درباره ما",
    "about_description": "درباره بلیت مستر بیشتر بدانید",
    "about_content": "<p>...</p>"
  }
}
```

## POST /getTacs

Returns terms and conditions content.

```http
POST https://api.bilitmaster.com/api/getTacs
```

```json
{
  "status": true,
  "items": [
    {
      "id": 1,
      "title": "قوانین و مقررات وبسایت برای خریداران بلیت رویداد",
      "order_id": 0,
      "content": []
    }
  ]
}
```

## POST /getEventListSliders

Returns sliders for event listing pages.

```http
POST https://api.bilitmaster.com/api/getEventListSliders
```

```json
{
  "status": true,
  "items": []
}
```

## POST /getMarketListSliders

Returns sliders for market listing pages.

```http
POST https://api.bilitmaster.com/api/getMarketListSliders
```

```json
{
  "status": true,
  "items": []
}
```

## POST /getCategoryLabel

Returns the display label used for a category/owner field.

```http
POST https://api.bilitmaster.com/api/getCategoryLabel
```

```json
{
  "status": true,
  "label": "نام برگزار کننده"
}
```

## POST /getEventSliderDate

Returns date options for event sliders/filtering.

```http
POST https://api.bilitmaster.com/api/getEventSliderDate
```

```json
{
  "status": true,
  "items": [
    {
      "date": "1405/3/26",
      "name": "سه شنبه"
    }
  ]
}
```

## POST /searchAll

Returns global search suggestions across events, event owners, markets, and market owners.

```http
POST https://api.bilitmaster.com/api/searchAll
```

```json
{
  "status": true,
  "events": [
    {
      "id": 734,
      "name": "کنسرت گوریلاز در استانبول"
    }
  ],
  "event_owners": [
    {
      "id": 1,
      "name": "رضا ایمانی"
    }
  ],
  "markets": [],
  "market_owners": [
    {
      "id": 3,
      "name": "سینما کوروش",
      "banner": "1591095916_1581.png",
      "deleted": 0
    }
  ]
}
```

## POST /searchEventName

Returns event and owner search suggestions.

```http
POST https://api.bilitmaster.com/api/searchEventName
```

```json
{
  "status": true,
  "owners": [
    {
      "id": 1,
      "name": "رضا ایمانی"
    }
  ],
  "events": [
    {
      "id": 734,
      "name": "کنسرت گوریلاز در استانبول"
    }
  ]
}
```

## POST /searchMarket

Returns market event and owner search suggestions.

```http
POST https://api.bilitmaster.com/api/searchMarket
```

```json
{
  "status": true,
  "events": [],
  "owners": [
    {
      "id": 3,
      "name": "سینما کوروش",
      "banner": "1591095916_1581.png",
      "deleted": 0
    }
  ]
}
```

## POST /getMarketEvent/{id}

Returns market event detail.

```http
POST https://api.bilitmaster.com/api/getMarketEvent/{id}
```

```json
{
  "status": true,
  "tickets": [],
  "event": {
    "name": "کنسرت چارتار",
    "image": "https://static.bilitmaster.com/media/480/market_null.jpg",
    "start_date": "1590690600",
    "state_id": 106,
    "city_id": 112,
    "location_id": 6,
    "location_name": "سالن همایش برج میلاد",
    "owner_id": 1,
    "city_name": "تهران - تهران",
    "start_dateString": "1399/3/8 ساعت 23:00"
  },
  "market_rule": "<ul>...</ul>"
}
```

## POST /getMarketOwnerEvents/{owner_id}

Returns market events for an owner.

```http
POST https://api.bilitmaster.com/api/getMarketOwnerEvents/{owner_id}
```

```json
{
  "status": true,
  "items": [],
  "owner": {
    "name": "چارتار"
  }
}
```

## Discovered But Not Publicly Confirmed

The site JavaScript references many `user/...` endpoints for dashboard, payments, ticketing, event creation, and customer management. They are intentionally not expanded as public API documentation because they are authenticated and often write/change data.

| Pattern | Notes |
|---|---|
| `user/createFullEvent` | Event creation flow. |
| `user/getEvent/{id}` | User-owned event detail. |
| `user/getEventCustomers/{event_id}` | Sensitive customer data. |
| `user/paymentOrderEvent` | Payment action. |
| `user/deleteEvent` | Destructive action. |

`POST /getMarketTicketConfig` was discovered but returned `500` when called without context, so it is not documented as a confirmed public endpoint.

## POST /user/getEventCustomers/{event_id}

Returns customers/registrations for a user's event.

```http
POST https://api.bilitmaster.com/api/user/getEventCustomers/{event_id}?user={user_token}
```

### Path Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `event_id` | integer | Yes | Event ID. The collection used `419`. |

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `user` | string | Yes | User token/hash. Redacted in this document. |

### Security Notes

- This endpoint likely returns customer personal data.
- Do not publish real `user` token/hash values.
- This endpoint was not live-tested for content.

## POST /user/getEvents

Returns user-scoped events.

```http
POST https://api.bilitmaster.com/api/user/getEvents?user={user_token}
```

### Query Parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| `user` | string | Yes | User token/hash. Redacted in this document. |

## JSON Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://rokhdad.local/schemas/bilitmaster.json",
  "title": "BilitMaster API Schemas",
  "$defs": {
    "EventCard": {
      "type": "object",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "string" },
        "state_id": { "type": ["string", "integer", "null"] },
        "city_id": { "type": ["string", "integer", "null"] },
        "image": { "type": ["string", "null"] },
        "image_mode": { "type": ["string", "null"] },
        "status": { "type": ["string", "null"] },
        "dateString": { "type": ["string", "null"] },
        "locationString": { "type": ["string", "null"] },
        "priceString": { "type": ["string", "null"] },
        "link": { "type": ["string", "null"] }
      },
      "additionalProperties": true
    },
    "InitialResponse": {
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
    }
  }
}
```

## OpenAPI 3.1 YAML

```yaml
openapi: 3.1.0
info:
  title: BilitMaster API
  version: 0.1.0
servers:
  - url: https://api.bilitmaster.com/api
paths:
  /getHomeEvents:
    post:
      summary: Get homepage events
      responses:
        "200": { description: Homepage events }
  /getEvents:
    post:
      summary: Get events
      responses:
        "200": { description: Events }
  /getInternationalEvents:
    post:
      summary: Get international events
      responses:
        "200": { description: International events }
  /getMarketEvents:
    post:
      summary: Get market events
      responses:
        "200": { description: Market events }
  /pageGetEvent:
    post:
      summary: Get event detail
      parameters:
        - name: id
          in: query
          required: true
          schema: { type: integer }
      responses:
        "200": { description: Event detail }
  /getMonthCalendar:
    post:
      summary: Get month calendar
      responses:
        "200": { description: Month calendar }
  /getInitial:
    post:
      summary: Get initial site data
      responses:
        "200": { description: Initial data }
  /getCities:
    post:
      summary: Get cities and states
      responses:
        "200": { description: Cities and states }
  /getBanks:
    post:
      summary: Get banks
      responses:
        "200": { description: Bank list }
  /getInfo:
    post:
      summary: Get site info
      responses:
        "200": { description: Site info }
  /getTacs:
    post:
      summary: Get terms and conditions
      responses:
        "200": { description: Terms and conditions }
  /getEventListSliders:
    post:
      summary: Get event list sliders
      responses:
        "200": { description: Event list sliders }
  /getMarketListSliders:
    post:
      summary: Get market list sliders
      responses:
        "200": { description: Market list sliders }
  /getCategoryLabel:
    post:
      summary: Get category label
      responses:
        "200": { description: Category label }
  /getEventSliderDate:
    post:
      summary: Get event slider dates
      responses:
        "200": { description: Event slider dates }
  /searchAll:
    post:
      summary: Search all public entities
      responses:
        "200": { description: Search suggestions }
  /searchEventName:
    post:
      summary: Search event names
      responses:
        "200": { description: Event search suggestions }
  /searchMarket:
    post:
      summary: Search market events
      responses:
        "200": { description: Market search suggestions }
  /getMarketEvent/{id}:
    post:
      summary: Get market event detail
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        "200": { description: Market event detail }
  /getMarketOwnerEvents/{owner_id}:
    post:
      summary: Get market owner events
      parameters:
        - name: owner_id
          in: path
          required: true
          schema: { type: integer }
      responses:
        "200": { description: Market owner events }
  /user/getEventCustomers/{event_id}:
    post:
      summary: Get event customers
      description: Sensitive user-scoped endpoint.
      parameters:
        - name: event_id
          in: path
          required: true
          schema: { type: integer }
        - name: user
          in: query
          required: true
          schema: { type: string }
      responses:
        "200": { description: Event customers }
  /user/getEvents:
    post:
      summary: Get user events
      description: Sensitive user-scoped endpoint.
      parameters:
        - name: user
          in: query
          required: true
          schema: { type: string }
      responses:
        "200": { description: User events }
```

## Unknowns

- The actual request body for several POST endpoints may exist in production but was not captured in the collection.
- The exact filters and pagination for list endpoints are unknown.
- User-scoped endpoints were not content-tested to avoid exposing sensitive data.
