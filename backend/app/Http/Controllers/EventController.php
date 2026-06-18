<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 50);

        $query = Event::query()
            ->with(['category', 'city', 'organizer', 'sourceAttributions'])
            ->where('status', 'published');

        if ($request->has('q')) {
            $keyword = trim($request->string('q'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                      ->orWhere('summary', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
        } elseif ($request->has('keyword')) {
            $keyword = trim($request->string('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                      ->orWhere('summary', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
        }

        if ($request->has('category')) {
            $category = trim($request->string('category'));
            if ($category !== '') {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category);
                });
            }
        }

        if ($request->has('city')) {
            $city = trim($request->string('city'));
            if ($city !== '') {
                $query->whereHas('city', function ($q) use ($city) {
                    $q->where('slug', $city);
                });
            }
        }

        if ($request->has('event_type')) {
            $type = trim($request->string('event_type'));
            if ($type !== '') {
                $query->where('event_type', $type);
            }
        }

        if ($request->has('source')) {
            $source = trim($request->string('source'));
            if ($source !== '') {
                $query->whereHas('sourceAttributions', function ($q) use ($source) {
                    $q->where('source_key', $source);
                });
            }
        }

        if ($request->has('start_date')) {
            $startDate = trim($request->string('start_date'));
            if ($startDate !== '') {
                $query->where('starts_at', '>=', $startDate);
            }
        }

        if ($request->has('end_date')) {
            $endDate = trim($request->string('end_date'));
            if ($endDate !== '') {
                $query->where('starts_at', '<=', $endDate . ' 23:59:59');
            }
        }

        $events = $query
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate($perPage);

        $items = $events->through(fn (Event $event) => $this->serializeEvent($event))->items();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $events->currentPage(),
                'from' => $events->firstItem(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'to' => $events->lastItem(),
                'total' => $events->total(),
                'seo' => $this->indexSeoPayload($items),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::query()
            ->with(['category', 'city', 'organizer', 'people', 'sourceAttributions'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json([
            'data' => $this->serializeEvent($event, detailed: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(Event $event, bool $detailed = false): array
    {
        $payload = [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'summary' => $event->summary,
            'starts_at' => $event->starts_at?->toJSON(),
            'ends_at' => $event->ends_at?->toJSON(),
            'timezone' => $event->timezone,
            'event_type' => $event->event_type,
            'status' => $event->status,
            'venue_name' => $event->venue_name,
            'online_url' => $event->online_url,
            'canonical_url' => $event->canonical_url,
            'is_featured' => $event->is_featured,
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
            'organizer' => $event->organizer ? [
                'id' => $event->organizer->id,
                'name' => $event->organizer->name,
                'slug' => $event->organizer->slug,
            ] : null,
            'source_attributions' => $event->sourceAttributions->map(fn ($source) => [
                'source_key' => $source->source_key,
                'external_id' => $source->external_id,
                'external_url' => $source->external_url,
                'sync_status' => $source->sync_status,
            ])->values(),
        ];

        if (! $detailed) {
            return $payload;
        }

        return [
            ...$payload,
            'description' => $event->description,
            'venue_address' => $event->venue_address,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'metadata' => $event->metadata,
            'is_internal' => $event->is_internal,
            'registration_open' => $event->registration_open,
            'capacity' => $event->capacity,
            'registration_starts_at' => $event->registration_starts_at?->toJSON(),
            'registration_ends_at' => $event->registration_ends_at?->toJSON(),
            'requires_approval' => $event->requires_approval,
            'registration_instructions' => $event->registration_instructions,
            'registration_form' => $this->registrationFormPayload($event),
            'seo' => $this->seoPayload($event),
            'people' => $event->people->map(fn ($person) => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'slug' => $person->slug,
                'role_title' => $person->pivot->role_title,
                'sort_order' => $person->pivot->sort_order,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function registrationFormPayload(Event $event): ?array
    {
        $fields = $event->metadata['registration_form']['fields'] ?? null;

        if (! is_array($fields) || $fields === []) {
            return null;
        }

        $normalizedFields = collect($fields)
            ->filter(fn ($field) => is_array($field) && ! empty($field['name']))
            ->map(function (array $field) {
                $type = in_array(($field['type'] ?? 'text'), ['text', 'textarea', 'select', 'checkbox'], true)
                    ? $field['type']
                    : 'text';

                return [
                    'name' => (string) $field['name'],
                    'label' => (string) ($field['label'] ?? $field['name']),
                    'type' => $type,
                    'placeholder' => $field['placeholder'] ?? null,
                    'required' => (bool) ($field['required'] ?? false),
                    'max_length' => isset($field['max_length']) ? (int) $field['max_length'] : null,
                    'options' => $type === 'select'
                        ? collect($field['options'] ?? [])
                            ->filter(fn ($option) => is_array($option) && isset($option['value']))
                            ->map(fn (array $option) => [
                                'label' => (string) ($option['label'] ?? $option['value']),
                                'value' => (string) $option['value'],
                            ])->values()->all()
                        : [],
                ];
            })
            ->values()
            ->all();

        if ($normalizedFields === []) {
            return null;
        }

        return [
            'title' => $event->metadata['registration_form']['title'] ?? 'فرم ثبت نام',
            'description' => $event->metadata['registration_form']['description'] ?? null,
            'fields' => $normalizedFields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seoPayload(Event $event): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $canonicalUrl = "{$siteUrl}/events/{$event->slug}";
        $description = $event->summary
            ?: Str::limit(trim(strip_tags((string) $event->description)), 155);

        if (! $description) {
            $description = 'جزئیات رویداد، زمان برگزاری، برگزارکننده و مسیر ثبت نام در رخداد.';
        }

        $title = "{$event->title} | رخداد";
        $externalImage = $event->metadata['evand']['cover'] ?? null;

        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => $event->title,
                'description' => $description,
                'url' => $canonicalUrl,
                'startDate' => $event->starts_at?->toAtomString(),
                'endDate' => $event->ends_at?->toAtomString(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => match ($event->event_type) {
                    'online' => 'https://schema.org/OnlineEventAttendanceMode',
                    'hybrid' => 'https://schema.org/MixedEventAttendanceMode',
                    default => 'https://schema.org/OfflineEventAttendanceMode',
                },
                'image' => $externalImage ? [$externalImage] : [],
                'organizer' => $event->organizer ? [
                    '@type' => 'Organization',
                    'name' => $event->organizer->name,
                    'url' => "{$siteUrl}/organizers/{$event->organizer->slug}",
                ] : null,
                'location' => $event->event_type === 'online'
                    ? [
                        '@type' => 'VirtualLocation',
                        'url' => $event->online_url ?: $event->canonical_url ?: $canonicalUrl,
                    ]
                    : [
                        '@type' => 'Place',
                        'name' => $event->venue_name ?: $event->city?->name,
                        'address' => $event->venue_address,
                    ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect(array_values(array_filter([
                    ['name' => 'رخداد', 'url' => $siteUrl.'/'],
                    $event->category ? ['name' => $event->category->name, 'url' => "{$siteUrl}/categories/{$event->category->slug}"] : null,
                    ['name' => $event->title, 'url' => $canonicalUrl],
                ])))->map(fn ($item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])->values()->all(),
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonicalUrl,
            'robots' => 'index,follow',
            'open_graph' => [
                'type' => 'event',
                'title' => $title,
                'description' => $description,
                'url' => $canonicalUrl,
                'image' => $externalImage,
                'site_name' => 'رخداد',
                'locale' => 'fa_IR',
            ],
            'twitter' => [
                'card' => $externalImage ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $externalImage,
            ],
            'breadcrumbs' => array_values(array_filter([
                ['name' => 'رخداد', 'url' => $siteUrl.'/'],
                $event->category ? ['name' => $event->category->name, 'url' => "{$siteUrl}/categories/{$event->category->slug}"] : null,
                ['name' => $event->title, 'url' => $canonicalUrl],
            ])),
            'json_ld' => $jsonLd,
        ];
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return array<string, mixed>
     */
    private function indexSeoPayload(array $events): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');

        return [
            'title' => 'رخداد | کشف رویدادهای ایران',
            'description' => 'فهرست رویدادهای منتشرشده در رخداد برای جستجو، کشف و ثبت نام.',
            'canonical_url' => $siteUrl.'/',
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'رخداد',
                    'url' => $siteUrl.'/',
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $siteUrl.'/?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'itemListElement' => collect($events)->map(fn (array $event, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => "{$siteUrl}/events/{$event['slug']}",
                        'name' => $event['title'],
                    ])->values()->all(),
                ],
            ],
        ];
    }
}
