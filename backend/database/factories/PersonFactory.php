<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'full_name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'website_url' => fake()->url(),
            'email' => fake()->safeEmail(),
            'phone_e164' => fake()->unique()->numerify('+98912#######'),
            'social_links' => ['linkedin' => 'https://linkedin.com/in/'.fake()->userName()],
            'is_active' => true,
        ];
    }
}
