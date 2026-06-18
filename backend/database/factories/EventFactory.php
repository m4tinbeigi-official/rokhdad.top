<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);
        $startsAt = fake()->dateTimeBetween('+1 day', '+90 days');

        return [
            'category_id' => Category::factory(),
            'city_id' => City::factory(),
            'organizer_id' => Organizer::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'timezone' => 'Asia/Tehran',
            'event_type' => 'in_person',
            'status' => 'published',
            'visibility' => 'public',
            'series_slug' => null,
            'recurrence_rule' => null,
            'recurrence_ends_at' => null,
            'venue_name' => fake()->company(),
            'venue_address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'online_url' => null,
            'canonical_url' => fake()->url(),
            'metadata' => ['language' => 'fa'],
            'is_featured' => false,
        ];
    }
}
