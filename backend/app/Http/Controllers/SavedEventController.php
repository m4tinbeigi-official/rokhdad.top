<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SavedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedEventController extends Controller
{
    /**
     * List the authenticated user's saved events.
     * GET /api/v1/me/saved-events
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $saved = SavedEvent::query()
            ->with(['event' => fn ($q) => $q->with(['category', 'city', 'organizer'])])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $saved->map(fn (SavedEvent $se) => [
                'saved_at' => $se->created_at?->toJSON(),
                'event' => $se->event ? [
                    'id' => $se->event->id,
                    'title' => $se->event->title,
                    'slug' => $se->event->slug,
                    'starts_at' => $se->event->starts_at?->toJSON(),
                    'event_type' => $se->event->event_type,
                    'status' => $se->event->status,
                    'category' => $se->event->category?->name,
                    'city' => $se->event->city?->name,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
                'total' => $saved->total(),
            ],
        ]);
    }

    /**
     * Save (bookmark) an event.
     * POST /api/v1/events/{slug}/save
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        SavedEvent::query()->firstOrCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        return response()->json(['message' => 'Event saved.'], 201);
    }

    /**
     * Remove a saved event (unsave).
     * DELETE /api/v1/events/{slug}/save
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $event = Event::query()->where('slug', $slug)->firstOrFail();

        SavedEvent::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->delete();

        return response()->json(['message' => 'Event unsaved.']);
    }
}
