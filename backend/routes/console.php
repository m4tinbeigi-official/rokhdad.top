<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
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

Artisan::command('evand:import {--pages=2} {--per-page=50}', function () {
    $pages = max(1, (int) $this->option('pages'));
    $perPage = min(max(1, (int) $this->option('per-page')), 100);
    $imported = 0;
    $baseUrl = 'https://api.evand.com';

    $categoryMap = collect(Http::timeout(20)->get("{$baseUrl}/categories")->json('data') ?? [])
        ->mapWithKeys(fn (array $category) => [(string) ($category['id'] ?? '') => $category])
        ->filter(fn ($category, string $id) => $id !== '');

    $cityMap = collect(Http::timeout(20)->get("{$baseUrl}/cities")->json('data') ?? [])
        ->mapWithKeys(fn (array $city) => [(string) ($city['id'] ?? '') => $city])
        ->filter(fn ($city, string $id) => $id !== '');

    for ($page = 1; $page <= $pages; $page++) {
        $response = Http::timeout(30)->retry(2, 500)->get("{$baseUrl}/events", [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            $this->error("Evand page {$page} failed with HTTP {$response->status()}.");
            continue;
        }

        foreach ($response->json('data') ?? [] as $raw) {
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
    }

    $this->info("Imported {$imported} Evand events.");
})->purpose('Import public Evand events into canonical Rokhdad event tables');

Artisan::command('evand:import-organizers {--pages=5} {--per-page=50}', function () {
    $pages = max(1, (int) $this->option('pages'));
    $perPage = min(max(1, (int) $this->option('per-page')), 100);
    $baseUrl = 'https://api.evand.com';
    $seen = collect();
    $imported = 0;
    $skipped = 0;

    $cityMap = collect(Http::timeout(20)->get("{$baseUrl}/cities")->json('data') ?? [])
        ->mapWithKeys(fn (array $city) => [(string) ($city['id'] ?? '') => $city])
        ->filter(fn ($city, string $id) => $id !== '');

    for ($page = 1; $page <= $pages; $page++) {
        $response = Http::timeout(30)->retry(2, 500)->get("{$baseUrl}/events", [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            $this->error("Evand page {$page} failed with HTTP {$response->status()}.");
            continue;
        }

        foreach ($response->json('data') ?? [] as $raw) {
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
    }

    $this->info("Imported {$imported} Evand organizers. Skipped {$skipped} records without organization data.");
})->purpose('Import and enrich Evand organizer profiles into Rokhdad organizers table');
