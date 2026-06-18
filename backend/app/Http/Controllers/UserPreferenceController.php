<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Get the authenticated user's preferences.
     * GET /api/v1/me/preferences
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $prefs = UserPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'favorite_category_ids' => [],
                'favorite_city_ids' => [],
                'notification_channel' => 'sms',
                'notify_new_events' => true,
                'notify_reminders' => true,
            ]
        );

        return response()->json(['data' => $this->serialize($prefs)]);
    }

    /**
     * Update the authenticated user's preferences.
     * PUT /api/v1/me/preferences
     */
    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'favorite_category_ids' => ['nullable', 'array'],
            'favorite_category_ids.*' => ['integer'],
            'favorite_city_ids' => ['nullable', 'array'],
            'favorite_city_ids.*' => ['integer'],
            'preferred_event_type' => ['nullable', 'string', 'in:online,in_person'],
            'notification_channel' => ['nullable', 'string', 'in:sms,email,both'],
            'notify_new_events' => ['nullable', 'boolean'],
            'notify_reminders' => ['nullable', 'boolean'],
        ]);

        $prefs = UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            array_filter($data, fn ($v) => $v !== null),
        );

        return response()->json(['data' => $this->serialize($prefs)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(UserPreference $prefs): array
    {
        return [
            'favorite_category_ids' => $prefs->favorite_category_ids ?? [],
            'favorite_city_ids' => $prefs->favorite_city_ids ?? [],
            'preferred_event_type' => $prefs->preferred_event_type,
            'notification_channel' => $prefs->notification_channel,
            'notify_new_events' => $prefs->notify_new_events,
            'notify_reminders' => $prefs->notify_reminders,
        ];
    }
}
