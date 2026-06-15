<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSourceAttribution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventSourceAttribution>
 */
class EventSourceAttributionFactory extends Factory
{
    public function definition(): array
    {
        $sourceKey = fake()->randomElement(['evand', 'eseminar']);
        $externalId = (string) fake()->unique()->numberBetween(100000, 999999);

        return [
            'event_id' => Event::factory(),
            'source_key' => $sourceKey,
            'external_id' => $externalId,
            'external_url' => "https://{$sourceKey}.example/events/{$externalId}",
            'payload_hash' => hash('sha256', $sourceKey.':'.$externalId),
            'snapshot_ref' => (string) Str::uuid(),
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
            'last_synced_at' => now(),
            'sync_status' => 'synced',
            'confidence_score' => 1,
            'metadata' => ['title_source' => 'external'],
        ];
    }
}
