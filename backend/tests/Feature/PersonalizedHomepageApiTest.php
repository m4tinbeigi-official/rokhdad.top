<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\SavedEvent;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalizedHomepageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_personalized_events_rank_user_preferences_first(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $matched = Event::factory()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'event_type' => 'online',
            'status' => 'published',
            'starts_at' => now()->addDays(10),
        ]);
        $other = Event::factory()->create([
            'event_type' => 'in_person',
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'favorite_category_ids' => [$category->id],
            'favorite_city_ids' => [$city->id],
            'preferred_event_type' => 'online',
            'notification_channel' => 'sms',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/personalized-events')
            ->assertOk()
            ->assertJsonPath('data.0.id', $matched->id)
            ->assertJsonPath('data.0.personalization_score', 90)
            ->assertJsonPath('data.1.id', $other->id);
    }

    public function test_personalized_events_include_saved_state(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'published']);
        SavedEvent::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/personalized-events')
            ->assertOk()
            ->assertJsonPath('data.0.id', $event->id)
            ->assertJsonPath('data.0.is_saved', true)
            ->assertJsonPath('data.0.personalization_score', 10);
    }

    public function test_personalized_events_require_authentication(): void
    {
        $this->getJson('/api/v1/me/personalized-events')
            ->assertUnauthorized();
    }
}
