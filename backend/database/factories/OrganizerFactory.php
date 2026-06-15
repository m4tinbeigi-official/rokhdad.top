<?php

namespace Database\Factories;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'website_url' => fake()->url(),
            'email' => fake()->safeEmail(),
            'phone_e164' => fake()->unique()->numerify('+98912#######'),
            'social_links' => ['instagram' => 'https://instagram.com/'.fake()->userName()],
            'is_active' => true,
        ];
    }
}
