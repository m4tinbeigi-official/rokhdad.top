# Evand API Documentation From Rokhdad Collection

Source collection:

```text
/Users/ricksabchez/Downloads/Rokhdad.postman_collection.json
```

Base URL:

```text
https://api.evand.com
```

The Rokhdad Postman collection contains one Evand endpoint:

```http
GET https://api.evand.com/events
```

A fuller Evand-only document was also created at:

```text
/Users/ricksabchez/Desktop/RickVPN/evand-api-documentation.md
```

Additional public endpoints were confirmed from live API checks and the Evand homepage/sitemap.

## Endpoint Index

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/events` | Detailed paginated event list |
| `GET` | `/events/{slug}` | Detailed event by slug |
| `GET` | `/events/{slug}/tickets` | Event tickets |
| `GET` | `/events/{slug}/showtimes` | Event showtimes |
| `GET` | `/v2/events` | Compact event list for UI cards |
| `GET` | `/v2/events/{slug}` | Compact event detail |
| `GET` | `/organizations` | Paginated organizations |
| `GET` | `/organizations/{slug}` | Organization detail |
| `GET` | `/categories` | Category reference list |
| `GET` | `/cities` | City reference list |
| `GET` | `/tags` | Paginated tags |
| `GET` | `/types` | Event type reference list |

## GET /events

Returns a paginated list of Evand events.

```http
GET https://api.evand.com/events
```

### Query Parameters

The Postman collection did not include query parameters, but live checks confirmed these common parameters:

| Name | Type | Required | Description |
|---|---|---:|---|
| `page` | integer | No | Page number. |
| `per_page` | integer | No | Page size. |
| `id` | integer | No | Filter by event ID. Useful for storing or updating known events one by one, for example `https://api.evand.com/events?id=14822567`. |
| `q` | string | No | Search query. |
| `city_id` | integer | No | Filter by city. |
| `category_id` | integer | No | Filter by category. |
| `online` | string | No | `yes` or `no`. |

### Example Request

```bash
curl 'https://api.evand.com/events?per_page=1'
```

Fetch a known event by ID:

```bash
curl 'https://api.evand.com/events?id=14822567'
```

In ingestion flows, all known Evand events can be stored or refreshed by iterating event IDs and calling `GET /events?id={event_id}`. The response still uses the paginated list envelope.

### Response Shape

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

## GET /v2/events

Returns a compact event list. This endpoint is used by Evand homepage cards and supports richer includes.

```http
GET https://api.evand.com/v2/events?sort=bestsellers&per_page=12&include=city,organization,prices
```

### Query Parameters

| Name | Type | Description |
|---|---|---|
| `page` | integer | Page number. |
| `per_page` | integer | Page size. |
| `include` | CSV string | Confirmed: `city,organization,prices`. |
| `sort` | string | Confirmed: `bestsellers`, `randomness`. |
| `partnership` | CSV string | Confirmed: `colleague,partner`. |
| `type_id` | integer | Accepted by endpoint; exact filtering behavior needs more verification. |
| `timing_status` | string | Accepted, e.g. `future`. |

### Response Shape

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
        "original": "https://static.evand.net/images/events/covers/original/example.png",
        "thumbnails": [],
        "name": "example.png"
      },
      "start_date": "2026-06-22T16:00:00+0330",
      "end_date": "2026-06-23T20:00:00+0330",
      "organization": {
        "data": {
          "name": "بنیاد علمی آموزشی دکترشیخ",
          "slug": "mohammadyoosefsheykh-id-gmail-com-3134278",
          "logo": {}
        }
      },
      "ended": false,
      "soldout": false,
      "minimum_ticket_price": 58000,
      "maximum_ticket_price": 1580000
    }
  ],
  "meta": {
    "pagination": {}
  }
}
```

## GET /events/{slug}/tickets

Returns ticket types for an event.

```http
GET https://api.evand.com/events/{slug}/tickets
```

## GET /events/{slug}/showtimes

Returns event showtimes.

```http
GET https://api.evand.com/events/{slug}/showtimes
```

## GET /organizations

Returns paginated organizations.

```http
GET https://api.evand.com/organizations?per_page=10&page=1
```

### Query Parameters

| Name | Type | Description |
|---|---|---|
| `page` | integer | Page number. |
| `per_page` | integer | Page size. |
| `sort` | string | Seen in homepage usage: `bestsellers`, `partnerships`. |

## GET /organizations/{slug}

Returns organization detail.

```http
GET https://api.evand.com/organizations/{slug}
```

## GET /categories

Returns category references. No pagination observed.

```http
GET https://api.evand.com/categories
```

## GET /cities

Returns city references. No pagination observed.

```http
GET https://api.evand.com/cities
```

## GET /tags

Returns paginated tags.

```http
GET https://api.evand.com/tags?per_page=10&page=1
```

## GET /types

Returns event type references.

```http
GET https://api.evand.com/types
```

## Field Descriptions

| Field | Type | Description |
|---|---|---|
| `data` | array | Event records. |
| `data[].id` | integer | Event ID. |
| `data[].city_id` | integer | City ID. |
| `data[].organization_id` | string | Organization ID. |
| `data[].organization` | object/null | Organization summary. |
| `data[].type_id` | integer | Event type ID. |
| `data[].category_id` | integer | Category ID. |
| `data[].name` | string | Event name. |
| `data[].slug` | string | Event slug. |
| `data[].start_date` | string | Start date/time. |
| `data[].end_date` | string | End date/time. |
| `data[].online` | string | `yes` or `no`. |
| `data[].status` | string | Event status. |
| `data[].published` | string | `yes` or `no`. |
| `data[].ended` | boolean | Whether the event has ended. |
| `data[].soldout` | boolean | Whether the event is sold out. |
| `data[].timing_status` | string | Timing status, e.g. `future`. |
| `meta.pagination` | object | Pagination metadata. |

## JSON Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://rokhdad.local/schemas/evand-events.json",
  "title": "Evand Events Response",
  "type": "object",
  "properties": {
    "data": {
      "type": "array",
      "items": {
        "type": "object",
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
          "online": { "type": "string", "enum": ["yes", "no"] },
          "status": { "type": "string" },
          "published": { "type": "string", "enum": ["yes", "no"] },
          "ended": { "type": "boolean" },
          "soldout": { "type": "boolean" },
          "timing_status": { "type": "string" }
        },
        "additionalProperties": true
      }
    },
    "meta": {
      "type": "object",
      "properties": {
        "pagination": {
          "type": "object",
          "additionalProperties": true
        }
      }
    }
  },
  "additionalProperties": true
}
```

## OpenAPI 3.1 YAML

```yaml
openapi: 3.1.0
info:
  title: Evand API From Rokhdad Collection
  version: 0.1.0
servers:
  - url: https://api.evand.com
paths:
  /events:
    get:
      summary: List events
      parameters:
        - name: page
          in: query
          schema: { type: integer }
        - name: per_page
          in: query
          schema: { type: integer }
        - name: q
          in: query
          schema: { type: string }
        - name: id
          in: query
          description: Filter by event ID. Useful for fetching/storing known events one by one.
          schema: { type: integer }
        - name: city_id
          in: query
          schema: { type: integer }
        - name: category_id
          in: query
          schema: { type: integer }
        - name: online
          in: query
          schema:
            type: string
            enum: [yes, no]
      responses:
        "200":
          description: Event list
  /v2/events:
    get:
      summary: List compact events
      parameters:
        - name: page
          in: query
          schema: { type: integer }
        - name: per_page
          in: query
          schema: { type: integer }
        - name: include
          in: query
          schema: { type: string }
        - name: sort
          in: query
          schema:
            type: string
            enum: [bestsellers, randomness]
        - name: partnership
          in: query
          schema: { type: string }
      responses:
        "200":
          description: Compact event list
  /events/{slug}/tickets:
    get:
      summary: List event tickets
      parameters:
        - name: slug
          in: path
          required: true
          schema: { type: string }
      responses:
        "200":
          description: Event tickets
  /events/{slug}/showtimes:
    get:
      summary: List event showtimes
      parameters:
        - name: slug
          in: path
          required: true
          schema: { type: string }
      responses:
        "200":
          description: Event showtimes
  /organizations:
    get:
      summary: List organizations
      parameters:
        - name: page
          in: query
          schema: { type: integer }
        - name: per_page
          in: query
          schema: { type: integer }
      responses:
        "200":
          description: Organization list
  /organizations/{slug}:
    get:
      summary: Get organization
      parameters:
        - name: slug
          in: path
          required: true
          schema: { type: string }
      responses:
        "200":
          description: Organization detail
  /categories:
    get:
      summary: List categories
      responses:
        "200":
          description: Category list
  /cities:
    get:
      summary: List cities
      responses:
        "200":
          description: City list
  /tags:
    get:
      summary: List tags
      parameters:
        - name: page
          in: query
          schema: { type: integer }
        - name: per_page
          in: query
          schema: { type: integer }
      responses:
        "200":
          description: Tag list
  /types:
    get:
      summary: List event types
      responses:
        "200":
          description: Event type list
```

## Unknowns

- The Rokhdad collection only included `/events`; the fuller Evand docs are in `evand-api-documentation.md`.
- Some Evand fields use string booleans such as `yes` and `no`.
- Pagination `links` may be either an object or an empty array.
