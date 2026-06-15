<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLookupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_active_categories_ordered(): void
    {
        Category::factory()->create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);
        $second = Category::factory()->create(['name' => 'Business', 'slug' => 'business', 'sort_order' => 20]);
        $first = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology', 'sort_order' => 10]);

        $response = $this->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'slug',
                    'description',
                    'sort_order',
                ]],
            ]);
    }

    public function test_cities_endpoint_returns_active_cities_ordered(): void
    {
        City::factory()->create(['name' => 'Inactive', 'slug' => 'inactive-city', 'is_active' => false]);
        $second = City::factory()->create(['name' => 'Shiraz', 'slug' => 'shiraz', 'sort_order' => 20]);
        $first = City::factory()->create(['name' => 'Tehran', 'slug' => 'tehran', 'sort_order' => 10]);

        $response = $this->getJson('/api/v1/cities');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'slug',
                    'province',
                    'country_code',
                    'latitude',
                    'longitude',
                    'sort_order',
                ]],
            ]);
    }
}
