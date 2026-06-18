<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventPromoCode>
 */
class EventPromoCodeFactory extends Factory
{
    protected $model = EventPromoCode::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'code' => Str::upper(fake()->bothify('PROMO-####')),
            'discount_type' => fake()->randomElement(['fixed', 'percent']),
            'discount_value' => fake()->numberBetween(10, 500000),
            'max_uses' => fake()->optional()->numberBetween(10, 100),
            'used_count' => 0,
            'min_quantity' => null,
            'max_quantity' => null,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ];
    }
}
