<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_for_internal_event(): void
    {
        $user = User::factory()->create();
        $event = $this->internalEvent();
        $ticketType = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 250_000,
            'capacity' => 10,
            'sold_count' => 0,
            'max_per_user' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/events/{$event->slug}/registrations", [
            'quantity' => 2,
            'ticket_type_id' => $ticketType->id,
            'form_data' => ['company' => 'Rokhdad'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.event.slug', $event->slug)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.total_amount', 500000)
            ->assertJsonPath('data.form_data.company', 'Rokhdad')
            ->assertJsonCount(2, 'data.tickets');

        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'total_amount' => 500_000,
        ]);
        $this->assertSame(2, $ticketType->fresh()->sold_count);
        $this->assertDatabaseCount('tickets', 2);
    }

    public function test_registration_requires_authentication(): void
    {
        $event = $this->internalEvent();

        $this->postJson("/api/v1/events/{$event->slug}/registrations")
            ->assertUnauthorized();
    }

    public function test_user_cannot_register_twice_for_same_event(): void
    {
        $user = User::factory()->create();
        $event = $this->internalEvent();

        Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'free',
            'quantity' => 1,
            'total_amount' => 0,
            'currency' => 'IRR',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/registrations")
            ->assertConflict();
    }

    public function test_non_internal_events_are_not_registrable(): void
    {
        $event = Event::factory()->create([
            'is_internal' => false,
            'registration_open' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/registrations")
            ->assertNotFound();
    }

    public function test_registration_validates_event_capacity(): void
    {
        $event = $this->internalEvent(['capacity' => 1]);
        Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'confirmed',
            'payment_status' => 'free',
            'quantity' => 1,
            'total_amount' => 0,
            'currency' => 'IRR',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/registrations")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_registration_validates_ticket_type_availability(): void
    {
        $event = $this->internalEvent();
        $ticketType = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'capacity' => 1,
            'sold_count' => 1,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/registrations", [
                'ticket_type_id' => $ticketType->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_type_id');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function internalEvent(array $overrides = []): Event
    {
        return Event::factory()->create([
            'is_internal' => true,
            'registration_open' => true,
            'capacity' => 100,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addWeek(),
            'requires_approval' => false,
            ...$overrides,
        ]);
    }
}
