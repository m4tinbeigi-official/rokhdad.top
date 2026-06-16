<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_table_has_qr_code_columns(): void
    {
        foreach ([
            'registration_id',
            'event_id',
            'user_id',
            'ticket_number',
            'qr_code_token',
            'status',
            'price',
            'used_at',
            'expires_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('tickets', $column), "Missing tickets.$column");
        }
    }

    public function test_ticket_generates_number_and_qr_token(): void
    {
        $ticket = Ticket::factory()->create([
            'ticket_number' => null,
            'qr_code_token' => null,
            'status' => 'issued',
        ]);

        $this->assertStringStartsWith('RKT-', $ticket->ticket_number);
        $this->assertSame(64, strlen($ticket->qr_code_token));
        $this->assertTrue($ticket->isUsable());
        $this->assertTrue($ticket->registration->event->is($ticket->event));
    }

    public function test_ticket_can_be_marked_used(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'issued', 'used_at' => null]);

        $ticket->markUsed();

        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->used_at);
        $this->assertFalse($ticket->fresh()->isUsable());
    }
}
