<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 50);

        $people = Person::query()
            ->withCount(['events' => fn ($query) => $query->where('status', 'published')])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $people->through(fn (Person $person) => $this->serializePerson($person))->items(),
            'meta' => [
                'current_page' => $people->currentPage(),
                'from' => $people->firstItem(),
                'last_page' => $people->lastPage(),
                'per_page' => $people->perPage(),
                'to' => $people->lastItem(),
                'total' => $people->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $person = Person::query()
            ->with([
                'organizers' => fn ($query) => $query->where('organizers.is_active', true)->orderBy('name'),
                'events' => fn ($query) => $query
                    ->with(['category', 'city', 'organizer', 'sourceAttributions'])
                    ->where('status', 'published')
                    ->orderBy('event_person.sort_order')
                    ->orderByRaw('starts_at IS NULL')
                    ->orderBy('starts_at')
                    ->orderBy('events.id'),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => $this->serializePerson($person, detailed: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePerson(Person $person, bool $detailed = false): array
    {
        $payload = [
            'id' => $person->id,
            'full_name' => $person->full_name,
            'slug' => $person->slug,
            'title' => $person->title,
            'bio' => $person->bio,
            'website_url' => $person->website_url,
            'social_links' => $person->social_links,
            'events_count' => $person->events_count,
        ];

        if (! $detailed) {
            return $payload;
        }

        return [
            ...$payload,
            'organizers' => $person->organizers->map(fn ($organizer) => [
                'id' => $organizer->id,
                'name' => $organizer->name,
                'slug' => $organizer->slug,
                'role_title' => $organizer->pivot->role_title,
            ])->values(),
            'events' => $person->events->map(fn (Event $event) => [
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
                'role_title' => $event->pivot->role_title,
                'sort_order' => $event->pivot->sort_order,
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
                'organizer' => $event->organizer ? [
                    'id' => $event->organizer->id,
                    'name' => $event->organizer->name,
                    'slug' => $event->organizer->slug,
                ] : null,
            ])->values(),
        ];
    }
}
