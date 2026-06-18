<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesSavedEventsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_and_update_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/preferences')
            ->assertOk()
            ->assertJsonPath('data.notification_channel', 'sms');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/preferences', [
                'favorite_category_ids' => [10, 20],
                'favorite_city_ids' => [3],
                'preferred_event_type' => 'online',
                'notification_channel' => 'both',
                'notify_new_events' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.favorite_category_ids.0', 10)
            ->assertJsonPath('data.notification_channel', 'both')
            ->assertJsonPath('data.notify_new_events', false);
    }

    public function test_user_can_save_list_and_unsave_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'published']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/save")
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/saved-events')
            ->assertOk()
            ->assertJsonPath('data.0.event.slug', $event->slug);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/events/{$event->slug}/save")
            ->assertOk();

        $this->assertDatabaseMissing('saved_events', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }
}
