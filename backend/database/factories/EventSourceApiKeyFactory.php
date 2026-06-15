<?php

namespace Database\Factories;

use App\Models\EventSource;
use App\Models\EventSourceApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSourceApiKey>
 */
class EventSourceApiKeyFactory extends Factory
{
    public function definition(): array
    {
        $secret = fake()->sha256();

        return [
            'event_source_id' => EventSource::factory(),
            'name' => 'Primary key',
            'key_hash' => EventSourceApiKey::hashSecret($secret),
            'encrypted_secret' => $secret,
            'status' => 'active',
            'active_from' => now(),
            'expires_at' => now()->addMonth(),
            'metadata' => ['rotation_policy' => 'manual'],
        ];
    }
}
