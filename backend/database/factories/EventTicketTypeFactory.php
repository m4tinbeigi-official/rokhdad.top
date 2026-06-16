<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicketType>
 */
class EventTicketTypeFactory extends Factory
{
    protected $model = EventTicketType::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['General', 'VIP', 'Early Bird']),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->numberBetween(0, 5_000_000),
            'currency' => 'IRR',
            'capacity' => fake()->optional()->numberBetween(20, 500),
            'sold_count' => 0,
            'max_per_user' => fake()->numberBetween(1, 5),
            'is_active' => true,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addMonth(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
