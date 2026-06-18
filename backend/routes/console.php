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
            $organizerName = $organization['name'] ?? 'برگزارکننده ایوند';
            $organizerSlug = 'evand-'.($organization['slug'] ?? $raw['organization_id'] ?? Str::slug($organizerName));
            $organizer = Organizer::query()->updateOrCreate(
                ['slug' => $organizerSlug],
                [
                    'city_id' => $city?->id,
                    'name' => $organizerName,
                    'website_url' => isset($organization['slug']) ? "https://evand.com/organizations/{$organization['slug']}" : null,
                    'is_active' => true,
                ],
            );

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
