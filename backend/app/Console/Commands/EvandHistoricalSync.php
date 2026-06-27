<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventSourceAttribution;
use App\Models\Organizer;
use App\Services\CentralizedLoggingService;
use Illuminate\Support\Str;

class EvandHistoricalSync extends Command
{
    protected $signature = 'evand:historical-sync';
    protected $description = 'Sync all historical events from Evand via organizers to bypass rate limits';

    public function handle()
    {
        $this->info("Starting Evand Historical Sync...");
        CentralizedLoggingService::logWorker('EvandHistoricalSync', 'Started');

        $baseUrl = 'https://api.evand.com';
        
        $this->info("Fetching category and city maps...");
        $categoryMap = collect(Http::timeout(20)->get("{$baseUrl}/categories")->json('data') ?? [])
            ->mapWithKeys(fn (array $c) => [(string) ($c['id'] ?? '') => $c])
            ->filter(fn ($c, string $id) => $id !== '');

        $cityMap = collect(Http::timeout(20)->get("{$baseUrl}/cities")->json('data') ?? [])
            ->mapWithKeys(fn (array $c) => [(string) ($c['id'] ?? '') => $c])
            ->filter(fn ($c, string $id) => $id !== '');

        // 1. Paginate through Organizations
        $orgPage = 1;
        $totalOrgPages = 1;
        $totalOrgsProcessed = 0;
        $totalEventsProcessed = 0;

        do {
            $this->info("Fetching organizations page {$orgPage} / {$totalOrgPages}");
            $response = Http::timeout(30)->retry(3, 1000)->get("{$baseUrl}/organizations", [
                'page' => $orgPage,
                'per_page' => 100,
            ]);

            if (! $response->successful()) {
                $this->error("Failed to fetch organizations page {$orgPage}");
                CentralizedLoggingService::logError("Evand API Error: Orgs page {$orgPage}");
                break;
            }

            $pagination = $response->json('meta.pagination') ?? [];
            $totalOrgPages = $pagination['total_pages'] ?? 1;
            
            $organizations = $response->json('data') ?? [];
            if (empty($organizations)) break;

            foreach ($organizations as $orgRaw) {
                // Upsert organizer
                $orgSlug = (string) ($orgRaw['slug'] ?? '');
                $orgName = $orgRaw['name'] ?? 'برگزارکننده ایوند';
                if ($orgSlug === '') continue;

                $organizer = Organizer::updateOrCreate(
                    ['slug' => 'evand-'.$orgSlug],
                    [
                        'source_key' => 'evand',
                        'external_id' => (string) ($orgRaw['id'] ?? ''),
                        'name' => $orgName,
                        'description' => isset($orgRaw['description']) ? strip_tags((string) $orgRaw['description']) : null,
                        'website_url' => "https://evand.com/organizations/{$orgSlug}",
                        'logo_url' => $orgRaw['logo']['original'] ?? null,
                        'cover_url' => $orgRaw['cover']['original'] ?? null,
                        'social_links' => is_array($orgRaw['socials'] ?? null) ? $orgRaw['socials'] : null,
                        'metadata' => ['source' => 'evand', 'evand' => ['id' => $orgRaw['id'] ?? null, 'slug' => $orgSlug]],
                        'is_active' => true,
                    ]
                );

                $totalOrgsProcessed++;

                // Skip orgs with no events to save API calls
                if (($orgRaw['events_count'] ?? 0) === 0) continue;

                // 2. Fetch events for this organizer
                $this->processOrganizerEvents($orgSlug, $organizer, $categoryMap, $cityMap, $baseUrl, $totalEventsProcessed);
            }

            // Log progress every page
            CentralizedLoggingService::logWorker('EvandHistoricalSync', 'Progress', [
                'org_page' => $orgPage,
                'total_org_pages' => $totalOrgPages,
                'orgs_processed' => $totalOrgsProcessed,
                'events_processed' => $totalEventsProcessed,
            ]);

            $orgPage++;
            
            // Sleep to be nice to API
            usleep(200000); 

        } while ($orgPage <= $totalOrgPages);

        $this->info("Completed! Processed {$totalOrgsProcessed} organizers and {$totalEventsProcessed} events.");
        CentralizedLoggingService::logWorker('EvandHistoricalSync', 'Completed', [
            'total_orgs' => $totalOrgsProcessed,
            'total_events' => $totalEventsProcessed,
        ]);
    }

    protected function processOrganizerEvents($orgSlug, $organizer, $categoryMap, $cityMap, $baseUrl, &$totalEventsProcessed)
    {
        $eventPage = 1;
        $totalEventPages = 1;

        do {
            $response = Http::timeout(20)->retry(2, 500)->get("{$baseUrl}/organizations/{$orgSlug}/events", [
                'page' => $eventPage,
                'per_page' => 50,
            ]);

            if (! $response->successful()) break;

            $pagination = $response->json('meta.pagination') ?? [];
            $totalEventPages = $pagination['total_pages'] ?? 1;

            $events = $response->json('data') ?? [];
            if (empty($events)) break;

            foreach ($events as $eventRaw) {
                // Fetch full event detail
                $eventSlug = (string) ($eventRaw['slug'] ?? '');
                if ($eventSlug === '') continue;

                $detailResponse = Http::timeout(20)->retry(2, 500)->get("{$baseUrl}/events/{$eventSlug}");
                if (! $detailResponse->successful()) continue;

                $raw = $detailResponse->json('data') ?? [];
                if (empty($raw)) continue;

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

                $externalId = (string) ($raw['id'] ?? $eventSlug);
                $finalEventSlug = 'evand-'.$externalId;
                $externalUrl = "https://evand.com/events/{$eventSlug}";
                $eventType = (($raw['online'] ?? 'no') === 'yes') ? 'online' : 'in_person';

                $event = Event::query()->updateOrCreate(
                    ['slug' => $finalEventSlug],
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
                                'cover' => $raw['cover']['original'] ?? $eventRaw['cover'] ?? null,
                                'is_free' => $raw['is_free'] ?? null,
                                'soldout' => $raw['soldout'] ?? null,
                                'timing_status' => $raw['timing_status'] ?? null,
                            ],
                        ],
                        'is_featured' => (bool) ($raw['is_trended_event'] ?? false),
                    ]
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
                        'metadata' => ['source_slug' => $eventSlug],
                    ]
                );

                $totalEventsProcessed++;
            }

            $eventPage++;
        } while ($eventPage <= $totalEventPages);
    }
}
