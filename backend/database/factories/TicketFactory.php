<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        return [
            'registration_id' => Registration::query()->create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => 'confirmed',
                'payment_status' => 'free',
                'quantity' => 1,
                'total_amount' => 0,
                'currency' => 'IRR',
            ])->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'ticket_number' => Ticket::generateTicketNumber(),
            'qr_code_token' => Ticket::generateQrCodeToken(),
            'status' => 'issued',
            'price' => 0,
            'seat_info' => null,
            'used_at' => null,
            'expires_at' => now()->addMonth(),
        ];
    }
}
