<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SavedEvent;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalizedHomepageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 12), 1), 50);
        $user = $request->user();

        /** @var UserPreference|null $preferences */
        $preferences = UserPreference::query()->where('user_id', $user->id)->first();
        $savedEventIds = SavedEvent::query()
            ->where('user_id', $user->id)
            ->pluck('event_id')
            ->all();

        $favoriteCategoryIds = $preferences?->favorite_category_ids ?? [];
        $favoriteCityIds = $preferences?->favorite_city_ids ?? [];
        $preferredType = $preferences?->preferred_event_type;

        $events = Event::query()
            ->with(['category', 'city', 'organizer', 'sourceAttributions'])
            ->where('status', 'published')
            ->orderByDesc('is_featured')
            ->orderByRaw($this->scoreSql(
                $favoriteCategoryIds,
                $favoriteCityIds,
                $savedEventIds,
                $preferredType,
            ))
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $events->through(fn (Event $event) => $this->serializeEvent($event, [
                'is_saved' => in_array($event->id, $savedEventIds, true),
                'personalization_score' => $this->scoreEvent($event, $favoriteCategoryIds, $favoriteCityIds, $savedEventIds, $preferredType),
            ]))->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * @param list<int> $categoryIds
     * @param list<int> $cityIds
     * @param list<int> $savedEventIds
     */
    private function scoreSql(array $categoryIds, array $cityIds, array $savedEventIds, ?string $preferredType): string
    {
        $parts = ['0'];

        if ($categoryIds !== []) {
            $parts[] = 'CASE WHEN category_id IN ('.implode(',', array_map('intval', $categoryIds)).') THEN 40 ELSE 0 END';
        }

        if ($cityIds !== []) {
            $parts[] = 'CASE WHEN city_id IN ('.implode(',', array_map('intval', $cityIds)).') THEN 30 ELSE 0 END';
        }

        if ($preferredType) {
            $parts[] = "CASE WHEN event_type = '".str_replace("'", "''", $preferredType)."' THEN 20 ELSE 0 END";
        }

        if ($savedEventIds !== []) {
            $parts[] = 'CASE WHEN id IN ('.implode(',', array_map('intval', $savedEventIds)).') THEN 10 ELSE 0 END';
        }

        return '('.implode(' + ', $parts).') DESC';
    }

    /**
     * @param list<int> $categoryIds
     * @param list<int> $cityIds
     * @param list<int> $savedEventIds
     */
    private function scoreEvent(Event $event, array $categoryIds, array $cityIds, array $savedEventIds, ?string $preferredType): int
    {
        return (int) (
            (in_array($event->category_id, $categoryIds, true) ? 40 : 0)
            + (in_array($event->city_id, $cityIds, true) ? 30 : 0)
            + ($preferredType && $event->event_type === $preferredType ? 20 : 0)
            + (in_array($event->id, $savedEventIds, true) ? 10 : 0)
        );
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function serializeEvent(Event $event, array $extra = []): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'summary' => $event->summary,
            'starts_at' => $event->starts_at?->toJSON(),
            'event_type' => $event->event_type,
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
            ...$extra,
        ];
    }
}
