<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventSourceAttribution;
use App\Models\Organizer;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (! function_exists('evandOrganizerPayload')) {
    function evandOrganizerPayload(array $organization, ?City $city = null): array
    {
    $name = $organization['name'] ?? 'برگزارکننده ایوند';
    $externalId = (string) ($organization['id'] ?? '');
    $slug = (string) ($organization['slug'] ?? ($externalId ?: Str::slug($name)));
    $socials = $organization['socials'] ?? $organization['social_links'] ?? null;

    return [
        'source_key' => 'evand',
        'external_id' => $externalId ?: null,
        'city_id' => $city?->id,
        'name' => $name,
        'slug' => 'evand-'.$slug,
        'description' => isset($organization['description']) ? strip_tags((string) $organization['description']) : null,
        'website_url' => $slug ? "https://evand.com/organizations/{$slug}" : null,
        'logo_url' => $organization['logo']['original'] ?? null,
        'cover_url' => $organization['cover']['original'] ?? null,
        'social_links' => is_array($socials) ? $socials : null,
        'metadata' => [
            'source' => 'evand',
            'evand' => [
                'id' => $organization['id'] ?? null,
                'slug' => $organization['slug'] ?? null,
                'raw_keys' => array_values(array_unique(array_keys($organization))),
            ],
        ],
        'is_active' => true,
    ];
    }
}

if (! function_exists('upsertEvandOrganizer')) {
    function upsertEvandOrganizer(array $organization, ?City $city = null): Organizer
    {
    $payload = evandOrganizerPayload($organization, $city);
    $existing = Organizer::query()
        ->when($payload['external_id'], fn ($query) => $query->where(function ($inner) use ($payload) {
            $inner->where([
                'source_key' => 'evand',
                'external_id' => $payload['external_id'],
            ])->orWhere('slug', $payload['slug']);
        }), fn ($query) => $query->where('slug', $payload['slug']))
        ->first();

    if ($existing) {
        $existing->update($payload);

        return $existing;
    }

    return Organizer::query()->create($payload);
    }
}

if (! function_exists('fetchEvandOrganization')) {
    function fetchEvandOrganization(string $slug): ?array
    {
    if ($slug === '') {
        return null;
    }

    $response = Http::timeout(20)->retry(2, 500)->get("https://api.evand.com/organizations/{$slug}");

    if (! $response->successful()) {
        return null;
    }

    $payload = $response->json('data');

    return is_array($payload) ? $payload : null;
    }
}

Artisan::command('evand:import {--pages=} {--per-page=50}', function () {
    $pagesOption = $this->option('pages');
    $pages = $pagesOption !== null ? max(1, (int) $pagesOption) : null;
    $perPage = min(max(1, (int) $this->option('per-page')), 100);
    $imported = 0;
    $baseUrl = 'https://api.evand.com';

    $categoryMap = collect(Http::timeout(20)->get("{$baseUrl}/categories")->json('data') ?? [])
        ->mapWithKeys(fn (array $category) => [(string) ($category['id'] ?? '') => $category])
        ->filter(fn ($category, string $id) => $id !== '');

    $cityMap = collect(Http::timeout(20)->get("{$baseUrl}/cities")->json('data') ?? [])
        ->mapWithKeys(fn (array $city) => [(string) ($city['id'] ?? '') => $city])
        ->filter(fn ($city, string $id) => $id !== '');

    $page = 1;
    $totalPages = 1;

    do {
        $response = Http::timeout(30)->retry(2, 500)->get("{$baseUrl}/events", [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            $this->error("Evand page {$page} failed with HTTP {$response->status()}.");
            break;
        }

        $pagination = $response->json('meta.pagination') ?? [];
        if ($pages === null) {
            $totalPages = $pagination['total_pages'] ?? 1;
        } else {
            $totalPages = $pages;
        }

        $events = $response->json('data') ?? [];
        if (empty($events)) {
            break;
        }

        foreach ($events as $raw) {
            if (($raw['published'] ?? null) === 'no' || ($raw['cancelled'] ?? false) === true) {
                continue;
            }

            $category = null;
            $rawCategory = $categoryMap->get((string) ($raw['category_id'] ?? ''));
            if ($rawCategory) {
                $categoryName = $rawCategory['name'] ?? $rawCategory['title'] ?? 'ایوند';
                $category = Category::query()->updateOrCreate(
                    ['slug' => 'evand-'.$raw['category_id']],
                    ['name' => $categoryName, 'description' => $rawCategory['description'] ?? null, 'is_active' => true],
                );
            }

            $city = null;
            $rawCity = $cityMap->get((string) ($raw['city_id'] ?? ''));
            if ($rawCity) {
                $cityName = $rawCity['name'] ?? $rawCity['title'] ?? 'ایران';
                $city = City::query()->updateOrCreate(
                    ['slug' => 'evand-'.$raw['city_id']],
                    ['name' => $cityName, 'province' => $rawCity['province'] ?? null, 'country_code' => 'IR', 'is_active' => true],
                );
            }

            $organization = $raw['organization'] ?? [];
            $detailedOrganization = isset($organization['slug'])
                ? fetchEvandOrganization((string) $organization['slug'])
                : null;
            $organizer = upsertEvandOrganizer($detailedOrganization ?: $organization, $city);

            $externalId = (string) $raw['id'];
            $evandSlug = (string) ($raw['slug'] ?? $externalId);
            $eventSlug = 'evand-'.$externalId;
            $externalUrl = "https://evand.com/events/{$evandSlug}";
            $eventType = (($raw['online'] ?? 'no') === 'yes') ? 'online' : 'in_person';

            $event = Event::query()->updateOrCreate(
                ['slug' => $eventSlug],
                [
                    'category_id' => $category?->id,
                    'city_id' => $city?->id,
                    'organizer_id' => $organizer->id,
                    'title' => $raw['name'] ?? 'رویداد ایوند',
                    'summary' => Str::limit(strip_tags((string) ($raw['email_description'] ?? $raw['refund_policy'] ?? '')), 240),
                    'description' => $raw['email_description'] ?? $raw['refund_policy'] ?? null,
                    'starts_at' => $raw['start_date'] ?? null,
                    'ends_at' => $raw['end_date'] ?? null,
                    'timezone' => 'Asia/Tehran',
                    'event_type' => $eventType,
                    'status' => 'published',
                    'venue_name' => $eventType === 'online' ? null : ($raw['address'] ? Str::limit((string) $raw['address'], 250, '') : null),
                    'venue_address' => $raw['address'] ?? null,
                    'latitude' => $raw['latitude'] ?? null,
                    'longitude' => $raw['longitude'] ?? null,
                    'online_url' => $eventType === 'online' ? $externalUrl : null,
                    'canonical_url' => $externalUrl,
                    'metadata' => [
                        'source' => 'evand',
                        'evand' => [
                            'id' => $raw['id'] ?? null,
                            'slug' => $raw['slug'] ?? null,
                            'cover' => $raw['cover']['original'] ?? null,
                            'is_free' => $raw['is_free'] ?? null,
                            'soldout' => $raw['soldout'] ?? null,
                            'timing_status' => $raw['timing_status'] ?? null,
                        ],
                    ],
                    'is_featured' => (bool) ($raw['is_trended_event'] ?? false),
                ],
            );

            EventSourceAttribution::query()->updateOrCreate(
                ['source_key' => 'evand', 'external_id' => $externalId],
                [
                    'event_id' => $event->id,
                    'external_url' => $externalUrl,
                    'payload_hash' => hash('sha256', json_encode($raw, JSON_UNESCAPED_UNICODE)),
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'last_synced_at' => now(),
                    'sync_status' => 'synced',
                    'confidence_score' => 1,
                    'metadata' => ['source_slug' => $evandSlug],
                ],
            );

            $imported++;
        }

        $page++;
    } while ($page <= $totalPages);

    $this->info("Imported {$imported} Evand events.");
})->purpose('Import public Evand events into canonical Rokhdad event tables');

Artisan::command('evand:import-organizers {--pages=} {--per-page=50}', function () {
    $pagesOption = $this->option('pages');
    $pages = $pagesOption !== null ? max(1, (int) $pagesOption) : null;
    $perPage = min(max(1, (int) $this->option('per-page')), 100);
    $baseUrl = 'https://api.evand.com';
    $seen = collect();
    $imported = 0;
    $skipped = 0;

    $cityMap = collect(Http::timeout(20)->get("{$baseUrl}/cities")->json('data') ?? [])
        ->mapWithKeys(fn (array $city) => [(string) ($city['id'] ?? '') => $city])
        ->filter(fn ($city, string $id) => $id !== '');

    $page = 1;
    $totalPages = 1;

    do {
        $response = Http::timeout(30)->retry(2, 500)->get("{$baseUrl}/events", [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            $this->error("Evand page {$page} failed with HTTP {$response->status()}.");
            break;
        }

        $pagination = $response->json('meta.pagination') ?? [];
        if ($pages === null) {
            $totalPages = $pagination['total_pages'] ?? 1;
        } else {
            $totalPages = $pages;
        }

        $events = $response->json('data') ?? [];
        if (empty($events)) {
            break;
        }

        foreach ($events as $raw) {
            $organization = $raw['organization'] ?? null;
            if (! is_array($organization)) {
                $skipped++;
                continue;
            }

            $orgSlug = (string) ($organization['slug'] ?? '');
            $orgId = (string) ($organization['id'] ?? $raw['organization_id'] ?? '');
            $dedupeKey = $orgSlug ?: $orgId;
            if ($dedupeKey === '' || $seen->has($dedupeKey)) {
                continue;
            }
            $seen->put($dedupeKey, true);

            $city = null;
            $rawCity = $cityMap->get((string) ($raw['city_id'] ?? ''));
            if ($rawCity) {
                $cityName = $rawCity['name'] ?? $rawCity['title'] ?? 'ایران';
                $city = City::query()->updateOrCreate(
                    ['slug' => 'evand-'.$raw['city_id']],
                    ['name' => $cityName, 'province' => $rawCity['province'] ?? null, 'country_code' => 'IR', 'is_active' => true],
                );
            }

            $detailedOrganization = $orgSlug ? fetchEvandOrganization($orgSlug) : null;
            upsertEvandOrganizer($detailedOrganization ?: $organization, $city);
            $imported++;
        }

        $page++;
    } while ($page <= $totalPages);

    $this->info("Imported {$imported} Evand organizers. Skipped {$skipped} records without organization data.");
})->purpose('Import and enrich Evand organizer profiles into Rokhdad organizers table');

if (! function_exists('arrayFirstValue')) {
    function arrayFirstValue(array $source, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
        }

        return $default;
    }
}

if (! function_exists('upsertEseminarOrganizer')) {
    function upsertEseminarOrganizer(array $raw, ?City $city = null): ?Organizer
    {
        $organization = $raw['organizer'] ?? $raw['organization'] ?? $raw['provider'] ?? $raw['teacher'] ?? null;
        if (! is_array($organization)) {
            $name = arrayFirstValue($raw, ['organizer_name', 'provider_name', 'teacher_name']);
            if (! $name) {
                return null;
            }
            $organization = ['name' => $name];
        }

        $name = (string) arrayFirstValue($organization, ['name', 'title', 'full_name'], 'برگزارکننده ایسمینار');
        $externalId = (string) arrayFirstValue($organization, ['id', 'uuid', 'slug'], '');
        $slug = (string) arrayFirstValue($organization, ['slug', 'username'], $externalId ?: Str::slug($name));

        $payload = [
            'source_key' => 'eseminar',
            'external_id' => $externalId ?: null,
            'city_id' => $city?->id,
            'name' => $name,
            'slug' => 'eseminar-'.$slug,
            'description' => isset($organization['description'])
                ? strip_tags((string) $organization['description'])
                : null,
            'website_url' => isset($organization['slug'])
                ? rtrim((string) config('services.eseminar.site_url'), '/').'/organizers/'.$organization['slug']
                : null,
            'logo_url' => arrayFirstValue($organization, ['logo', 'avatar', 'image', 'picture']),
            'cover_url' => arrayFirstValue($organization, ['cover', 'banner']),
            'metadata' => ['source' => 'eseminar', 'eseminar' => ['id' => $organization['id'] ?? null]],
            'is_active' => true,
        ];

        $existing = Organizer::query()
            ->when($payload['external_id'], fn ($query) => $query->where(function ($inner) use ($payload) {
                $inner->where(['source_key' => 'eseminar', 'external_id' => $payload['external_id']])
                    ->orWhere('slug', $payload['slug']);
            }), fn ($query) => $query->where('slug', $payload['slug']))
            ->first();

        if ($existing) {
            $existing->update($payload);

            return $existing;
        }

        return Organizer::query()->create($payload);
    }
}

Artisan::command('eseminar:import {--pages=} {--per-page=50}', function () {
    $pagesOption = $this->option('pages');
    $pages = $pagesOption !== null ? max(1, (int) $pagesOption) : null;
    $perPage = min(max(1, (int) $this->option('per-page')), 100);
    $imported = 0;
    $skipped = 0;

    $baseUrl = rtrim((string) config('services.eseminar.base_url'), '/');
    $eventsPath = '/'.ltrim((string) config('services.eseminar.events_path'), '/');
    $token = config('services.eseminar.token');
    $siteUrl = rtrim((string) config('services.eseminar.site_url'), '/');

    if ($baseUrl === '') {
        $this->error('Eseminar API base URL is not configured. Set ESEMINAR_API_BASE in .env.');

        return 1;
    }

    $request = fn () => $token
        ? Http::withToken($token)->timeout(30)->retry(2, 500)
        : Http::timeout(30)->retry(2, 500);

    $page = 1;
    $totalPages = 1;

    do {
        $response = $request()->get($baseUrl.$eventsPath, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            $this->error("Eseminar page {$page} failed with HTTP {$response->status()}.");
            break;
        }

        // Support several common envelope shapes: {data:[...]}, {events:[...]}, or a bare array.
        $events = $response->json('data')
            ?? $response->json('events')
            ?? $response->json('results')
            ?? (is_array($response->json()) ? $response->json() : []);

        if (! is_array($events) || $events === []) {
            break;
        }

        if ($pages === null) {
            $totalPages = $response->json('meta.pagination.total_pages')
                ?? $response->json('meta.last_page')
                ?? $response->json('last_page')
                ?? $page; // single-page fallback
        } else {
            $totalPages = $pages;
        }

        foreach ($events as $raw) {
            if (! is_array($raw)) {
                $skipped++;
                continue;
            }

            $externalId = (string) arrayFirstValue($raw, ['id', 'uuid', 'slug'], '');
            if ($externalId === '') {
                $skipped++;
                continue;
            }

            // Category
            $category = null;
            $rawCategory = $raw['category'] ?? null;
            $categoryId = arrayFirstValue($raw, ['category_id']) ?? ($rawCategory['id'] ?? null);
            $categoryName = is_array($rawCategory)
                ? arrayFirstValue($rawCategory, ['name', 'title'])
                : (is_string($rawCategory) ? $rawCategory : null);
            if ($categoryName) {
                $category = Category::query()->updateOrCreate(
                    ['slug' => 'eseminar-'.($categoryId ?? Str::slug($categoryName))],
                    ['name' => $categoryName, 'is_active' => true],
                );
            }

            // City
            $city = null;
            $rawCity = $raw['city'] ?? null;
            $cityId = arrayFirstValue($raw, ['city_id']) ?? ($rawCity['id'] ?? null);
            $cityName = is_array($rawCity)
                ? arrayFirstValue($rawCity, ['name', 'title'])
                : (is_string($rawCity) ? $rawCity : null);
            if ($cityName) {
                $city = City::query()->updateOrCreate(
                    ['slug' => 'eseminar-'.($cityId ?? Str::slug($cityName))],
                    ['name' => $cityName, 'country_code' => 'IR', 'is_active' => true],
                );
            }

            $organizer = upsertEseminarOrganizer($raw, $city);

            $title = (string) arrayFirstValue($raw, ['title', 'name'], 'وبینار ایسمینار');
            $slug = (string) arrayFirstValue($raw, ['slug'], $externalId);
            $eventSlug = 'eseminar-'.$externalId;
            $externalUrl = (string) (arrayFirstValue($raw, ['url', 'link'])
                ?: $siteUrl.'/events/'.$slug);

            // Eseminar is a webinar platform: treat as online unless flagged otherwise.
            $isOnline = arrayFirstValue($raw, ['is_online', 'online'], true);
            $eventType = ($isOnline === false || $isOnline === 'no' || $isOnline === 0) ? 'in_person' : 'online';

            $startsAt = arrayFirstValue($raw, ['start_date', 'starts_at', 'start_at', 'date', 'held_at']);
            $endsAt = arrayFirstValue($raw, ['end_date', 'ends_at', 'end_at', 'finish_at']);
            $cover = arrayFirstValue($raw, ['cover', 'image', 'thumbnail', 'banner', 'poster']);
            $description = arrayFirstValue($raw, ['description', 'body', 'content', 'about']);

            $event = Event::query()->updateOrCreate(
                ['slug' => $eventSlug],
                [
                    'category_id' => $category?->id,
                    'city_id' => $city?->id,
                    'organizer_id' => $organizer?->id,
                    'title' => $title,
                    'summary' => Str::limit(strip_tags((string) ($description ?? '')), 240),
                    'description' => $description,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'timezone' => 'Asia/Tehran',
                    'event_type' => $eventType,
                    'status' => 'published',
                    'visibility' => 'public',
                    'venue_name' => $eventType === 'online' ? null : ($cityName ?: null),
                    'venue_address' => arrayFirstValue($raw, ['address', 'location']),
                    'online_url' => $eventType === 'online' ? $externalUrl : null,
                    'canonical_url' => $externalUrl,
                    'metadata' => [
                        'source' => 'eseminar',
                        'eseminar' => [
                            'id' => $raw['id'] ?? null,
                            'slug' => $slug,
                            'cover' => $cover,
                            'price' => arrayFirstValue($raw, ['price', 'cost']),
                            'is_free' => arrayFirstValue($raw, ['is_free', 'free']),
                        ],
                    ],
                    'is_featured' => (bool) arrayFirstValue($raw, ['is_featured', 'featured', 'is_special'], false),
                ],
            );

            EventSourceAttribution::query()->updateOrCreate(
                ['source_key' => 'eseminar', 'external_id' => $externalId],
                [
                    'event_id' => $event->id,
                    'external_url' => $externalUrl,
                    'payload_hash' => hash('sha256', json_encode($raw, JSON_UNESCAPED_UNICODE)),
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'last_synced_at' => now(),
                    'sync_status' => 'synced',
                    'confidence_score' => 1,
                    'metadata' => ['source_slug' => $slug],
                ],
            );

            $imported++;
        }

        $page++;
    } while ($page <= $totalPages);

    $this->info("Imported {$imported} Eseminar events. Skipped {$skipped} records.");

    return 0;
})->purpose('Import public Eseminar webinars into canonical Rokhdad event tables');

/*
|--------------------------------------------------------------------------
| Scheduled aggregation
|--------------------------------------------------------------------------
| Pull fresh events from every external source once an hour. Requires the
| Laravel scheduler to be running, e.g. a cron entry on the server:
|   * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('evand:import')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('eseminar:import')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

