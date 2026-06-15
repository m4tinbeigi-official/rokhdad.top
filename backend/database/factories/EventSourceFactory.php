<?php

namespace Database\Factories;

use App\Models\EventSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventSource>
 */
class EventSourceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();
        $sourceKey = Str::slug($name);

        return [
            'source_key' => $sourceKey.'-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => $name,
            'base_url' => fake()->url(),
            'api_base_url' => fake()->url(),
            'auth_type' => 'api_key',
            'status' => 'active',
            'is_enabled' => true,
            'rate_limit_per_minute' => 60,
            'config' => ['supports_api' => true],
            'last_checked_at' => now(),
        ];
    }
}
