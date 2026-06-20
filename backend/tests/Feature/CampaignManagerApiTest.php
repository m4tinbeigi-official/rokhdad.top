<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignManagerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_and_list_campaign(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        $organizer->users()->attach($user->id, ['role' => 'owner', 'accepted_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/campaigns', [
                'organizer_id' => $organizer->id,
                'event_id' => $event->id,
                'name' => 'یادآوری رویداد',
                'channel' => 'email',
                'audience_type' => 'confirmed_only',
                'subject' => 'یادآوری',
                'message' => 'فردا در رویداد حاضر باشید.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'یادآوری رویداد')
            ->assertJsonPath('data.event.slug', $event->slug);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/campaigns')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'یادآوری رویداد')
            ->assertJsonPath('meta.available_channels.0', 'email');
    }

    public function test_campaign_simulation_sends_notifications_to_target_audience(): void
    {
        Http::fake([
            'app.pakett.ir/api/v1/send/template' => Http::response(['id' => 'CMP123']),
        ]);

        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@example.com']);
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        $organizer->users()->attach($owner->id, ['role' => 'owner', 'accepted_at' => now()]);

        Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'quantity' => 1,
            'total_amount' => 100_000,
            'currency' => 'IRR',
            'confirmed_at' => now(),
        ]);

        $campaign = Campaign::query()->create([
            'organizer_id' => $organizer->id,
            'event_id' => $event->id,
            'name' => 'فراخوان نهایی',
            'channel' => 'email',
            'audience_type' => 'confirmed_only',
            'status' => 'draft',
            'subject' => 'فراخوان',
            'message' => 'این یک پیام آزمایشی است.',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/me/campaigns/{$campaign->id}/simulate")
            ->assertOk()
            ->assertJsonPath('data.status', 'simulated')
            ->assertJsonPath('data.sent_count', 1);

        $this->assertDatabaseHas('notification_logs', [
            'recipient' => 'recipient@example.com',
            'type' => 'campaign_simulation',
            'channel' => 'email',
        ]);
    }
}
