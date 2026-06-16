<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $event = Event::factory()->create(['is_internal' => true]);
        $user = User::factory()->create();
        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'quantity' => 1,
            'total_amount' => 500_000,
            'currency' => 'IRR',
        ]);

        return [
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'gateway' => fake()->randomElement(['zarinpal', 'zibal']),
            'gateway_authority' => null,
            'gateway_ref_id' => null,
            'status' => 'pending',
            'amount' => $registration->total_amount,
            'currency' => 'IRR',
            'callback_url' => 'https://rokhdad.top/payments/callback',
            'gateway_response' => null,
            'paid_at' => null,
        ];
    }
}
