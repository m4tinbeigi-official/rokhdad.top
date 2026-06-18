<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizerDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_user_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('organizer_user'));
        $this->assertTrue(Schema::hasColumn('organizer_user', 'role'));
    }

    public function test_user_can_fetch_only_their_organizer_dashboard(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $otherOrganizer = Organizer::factory()->create();
        $organizer->users()->attach($user->id, [
            'role' => 'owner',
            'accepted_at' => now(),
        ]);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'is_internal' => true,
            'registration_open' => true,
            'capacity' => 50,
        ]);
        Event::factory()->create(['organizer_id' => $otherOrganizer->id]);

        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'quantity' => 2,
            'total_amount' => 1_500_000,
            'currency' => 'IRR',
            'confirmed_at' => now(),
        ]);
        Ticket::factory()->create([
            'registration_id' => $registration->id,
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'price' => 750_000,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/organizer-dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.organizers_count', 1)
            ->assertJsonPath('data.summary.events_count', 1)
            ->assertJsonPath('data.summary.registrations_count', 1)
            ->assertJsonPath('data.summary.confirmed_registrations_count', 1)
            ->assertJsonPath('data.summary.tickets_count', 1)
            ->assertJsonPath('data.summary.revenue_total', 1500000)
            ->assertJsonPath('data.organizers.0.slug', $organizer->slug)
            ->assertJsonPath('data.events.0.slug', $event->slug)
            ->assertJsonMissingPath('data.events.1');
    }

    public function test_organizer_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/me/organizer-dashboard')->assertUnauthorized();
    }
}
