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

        return response()->json([
            'data' => $organizers->through(fn (Organizer $organizer) => $this->serializeOrganizer($organizer))->items(),
            'meta' => [
                'current_page' => $organizers->currentPage(),
                'from' => $organizers->firstItem(),
                'last_page' => $organizers->lastPage(),
                'per_page' => $organizers->perPage(),
                'to' => $organizers->lastItem(),
                'total' => $organizers->total(),
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
}
