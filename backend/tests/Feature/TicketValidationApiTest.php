<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketValidationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_validate_ticket_qr_token(): void
    {
        $ticket = Ticket::factory()->create([
            'qr_code_token' => 'qr-token-for-validation',
            'status' => 'issued',
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/tickets/validate/qr-token-for-validation')
            ->assertOk()
            ->assertJsonPath('data.ticket_number', $ticket->ticket_number)
            ->assertJsonPath('data.usable', true)
            ->assertJsonPath('data.event.slug', $ticket->event->slug)
            ->assertJsonPath('data.registration.id', $ticket->registration_id)
            ->assertJsonPath('data.attendee.email', $ticket->user->email);
    }

    public function test_ticket_qr_validation_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $this->getJson("/api/v1/tickets/validate/{$ticket->qr_code_token}")
            ->assertUnauthorized();
    }
}
