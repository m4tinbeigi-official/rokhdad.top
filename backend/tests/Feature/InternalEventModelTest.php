<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InternalEventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_table_has_internal_event_registration_columns(): void
    {
        foreach ([
            'is_internal',
            'registration_open',
            'capacity',
            'registration_starts_at',
            'registration_ends_at',
            'requires_approval',
            'registration_instructions',
            'visibility',
            'series_slug',
            'recurrence_rule',
            'recurrence_ends_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('events', $column), "Missing events.$column");
        }

        $this->assertTrue(Schema::hasTable('event_ticket_types'));
    }

    public function test_event_casts_internal_registration_fields(): void
    {
        $event = Event::factory()->create([
            'is_internal' => true,
            'registration_open' => true,
            'capacity' => 120,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addWeek(),
            'requires_approval' => true,
            'registration_instructions' => 'Bring national ID.',
            'visibility' => 'private',
            'series_slug' => 'weekly-product-clinic',
            'recurrence_rule' => 'weekly',
            'recurrence_ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($event->is_internal);
        $this->assertTrue($event->registration_open);
        $this->assertSame(120, $event->capacity);
        $this->assertTrue($event->registration_starts_at->isBefore($event->registration_ends_at));
        $this->assertTrue($event->requires_approval);
        $this->assertSame('private', $event->visibility);
        $this->assertSame('weekly-product-clinic', $event->series_slug);
        $this->assertSame('weekly', $event->recurrence_rule);
        $this->assertNotNull($event->recurrence_ends_at);
    }

    public function test_event_has_ticket_types_and_registrations(): void
    {
        $event = Event::factory()->create(['is_internal' => true, 'registration_open' => true]);
        $ticketType = EventTicketType::factory()->create(['event_id' => $event->id, 'name' => 'General']);
        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'quantity' => 1,
            'total_amount' => 0,
            'currency' => 'IRR',
        ]);

        $this->assertTrue($event->ticketTypes->contains($ticketType));
        $this->assertTrue($event->registrations->contains($registration));
        $this->assertTrue($ticketType->event->is($event));
    }

    public function test_ticket_type_tracks_capacity_and_sale_availability(): void
    {
        $available = EventTicketType::factory()->create([
            'capacity' => 10,
            'sold_count' => 3,
            'is_active' => true,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);
        $soldOut = EventTicketType::factory()->create([
            'capacity' => 3,
            'sold_count' => 3,
            'is_active' => true,
        ]);

        $this->assertSame(7, $available->remainingCapacity());
        $this->assertTrue($available->isAvailableForSale());
        $this->assertSame(0, $soldOut->remainingCapacity());
        $this->assertFalse($soldOut->isAvailableForSale());
    }
}
