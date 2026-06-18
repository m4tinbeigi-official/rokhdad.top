<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 50);

        $organizers = Organizer::query()
            ->with('city')
            ->withCount(['events' => fn ($query) => $query->where('status', 'published')])
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        $items = $organizers->through(fn (Organizer $organizer) => $this->serializeOrganizer($organizer))->items();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $organizers->currentPage(),
                'from' => $organizers->firstItem(),
                'last_page' => $organizers->lastPage(),
                'per_page' => $organizers->perPage(),
                'to' => $organizers->lastItem(),
                'total' => $organizers->total(),
                'seo' => $this->indexSeoPayload($items),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $organizer = Organizer::query()
            ->with([
                'city',
                'people' => fn ($query) => $query->where('people.is_active', true)->orderBy('full_name'),
                'events' => fn ($query) => $query
                    ->with(['category', 'city', 'sourceAttributions'])
                    ->where('status', 'published')
                    ->orderByRaw('starts_at IS NULL')
                    ->orderBy('starts_at')
                    ->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => $this->serializeOrganizer($organizer, detailed: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrganizer(Organizer $organizer, bool $detailed = false): array
    {
        $payload = [
            'id' => $organizer->id,
            'name' => $organizer->name,
            'slug' => $organizer->slug,
            'description' => $organizer->description,
            'website_url' => $organizer->website_url,
            'social_links' => $organizer->social_links,
            'events_count' => $organizer->events_count,
            'city' => $organizer->city ? [
                'id' => $organizer->city->id,
                'name' => $organizer->city->name,
                'slug' => $organizer->city->slug,
                'province' => $organizer->city->province,
            ] : null,
        ];

        if (! $detailed) {
            return $payload;
        }

        return [
            ...$payload,
            'seo' => $this->seoPayload($organizer),
            'people' => $organizer->people->map(fn ($person) => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'slug' => $person->slug,
                'title' => $person->title,
                'role_title' => $person->pivot->role_title,
            ])->values(),
            'events' => $organizer->events->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'summary' => $event->summary,
                'starts_at' => $event->starts_at?->toJSON(),
                'ends_at' => $event->ends_at?->toJSON(),
                'timezone' => $event->timezone,
                'event_type' => $event->event_type,
                'venue_name' => $event->venue_name,
                'canonical_url' => $event->canonical_url,
                'source_attributions' => $event->sourceAttributions->map(fn ($source) => [
                    'source_key' => $source->source_key,
                    'external_id' => $source->external_id,
                    'external_url' => $source->external_url,
                    'sync_status' => $source->sync_status,
                ])->values(),
                'category' => $event->category ? [
                    'id' => $event->category->id,
                    'name' => $event->category->name,
                    'slug' => $event->category->slug,
                ] : null,
                'city' => $event->city ? [
                    'id' => $event->city->id,
                    'name' => $event->city->name,
                    'slug' => $event->city->slug,
                ] : null,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seoPayload(Organizer $organizer): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $url = "{$siteUrl}/organizers/{$organizer->slug}";

        return [
            'title' => "{$organizer->name} | رخداد",
            'description' => $organizer->description ?: "صفحه برگزارکننده {$organizer->name} در رخداد.",
            'canonical_url' => $url,
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $organizer->name,
                    'url' => $url,
                    'description' => $organizer->description,
                    'sameAs' => array_values(array_filter(array_merge(
                        [$organizer->website_url],
                        is_array($organizer->social_links) ? array_values($organizer->social_links) : [],
                    ))),
                    'address' => $organizer->city ? [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $organizer->city->name,
                        'addressRegion' => $organizer->city->province,
                        'addressCountry' => 'IR',
                    ] : null,
                ],
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $organizers
     * @return array<string, mixed>
     */
    private function indexSeoPayload(array $organizers): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');

        return [
            'title' => 'برگزارکنندگان | رخداد',
            'canonical_url' => $siteUrl.'/organizers',
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'itemListElement' => collect($organizers)->map(fn (array $organizer, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => "{$siteUrl}/organizers/{$organizer['slug']}",
                        'name' => $organizer['name'],
                    ])->values()->all(),
                ],
            ],
        ];
    }
}
