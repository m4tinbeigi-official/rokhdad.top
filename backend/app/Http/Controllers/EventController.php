<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json([
            'data' => $events->through(fn (Event $event) => $this->serializeEvent($event))->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'from' => $events->firstItem(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'to' => $events->lastItem(),
                'total' => $events->total(),
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
            'people' => $event->people->map(fn ($person) => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'slug' => $person->slug,
                'role_title' => $person->pivot->role_title,
                'sort_order' => $person->pivot->sort_order,
            ])->values(),
        ];
    }
}
