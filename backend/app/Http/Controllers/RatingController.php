<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Get rating summary for an event.
     * GET /api/v1/events/{slug}/ratings
     */
    public function index(string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $ratings = Rating::query()->where('event_id', $event->id);

        $avg = round((float) $ratings->avg('score'), 1);
        $count = $ratings->count();

        $distribution = Rating::query()
            ->where('event_id', $event->id)
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->orderBy('score')
            ->get()
            ->keyBy('score')
            ->map(fn ($r) => (int) $r->count);

        return response()->json([
            'data' => [
                'average' => $avg,
                'count' => $count,
                'distribution' => $distribution,
            ],
        ]);
    }

    /**
     * Submit or update the authenticated user's rating.
     * POST /api/v1/events/{slug}/ratings
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $rating = Rating::query()->updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['score' => $data['score'], 'review' => $data['review'] ?? null],
        );

        return response()->json([
            'data' => [
                'id' => $rating->id,
                'score' => $rating->score,
                'review' => $rating->review,
                'created_at' => $rating->created_at?->toJSON(),
                'updated_at' => $rating->updated_at?->toJSON(),
            ],
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Get the authenticated user's rating for an event.
     * GET /api/v1/events/{slug}/my-rating
     */
    public function myRating(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $rating = Rating::query()->where('event_id', $event->id)->where('user_id', $user->id)->first();

        if (! $rating) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $rating->id,
                'score' => $rating->score,
                'review' => $rating->review,
                'updated_at' => $rating->updated_at?->toJSON(),
            ],
        ]);
    }

    /**
     * Delete the authenticated user's rating.
     * DELETE /api/v1/events/{slug}/ratings
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        /** @var \App\Models\User $user */
        $user = $request->user();

        Rating::query()->where('event_id', $event->id)->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Rating removed.']);
    }
}
