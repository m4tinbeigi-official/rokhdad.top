<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryCityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_city_tables_exist_with_core_columns(): void
    {
        foreach (['name', 'slug', 'is_active', 'sort_order'] as $column) {
            $this->assertTrue(Schema::hasColumn('categories', $column), "Missing categories.$column");
            $this->assertTrue(Schema::hasColumn('cities', $column), "Missing cities.$column");
        }

        $this->assertTrue(Schema::hasColumn('categories', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('cities', 'province'));
        $this->assertTrue(Schema::hasColumn('cities', 'country_code'));
    }

    public function test_category_parent_child_relationships_work(): void
    {
        $parent = Category::factory()->create(['slug' => 'technology']);
        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'slug' => 'technology-startups',
        ]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_city_fields_are_fillable_and_cast(): void
    {
        $city = City::factory()->create([
            'name' => 'Tehran',
            'slug' => 'tehran',
            'province' => 'Tehran',
            'country_code' => 'IR',
            'latitude' => 35.6892000,
            'longitude' => 51.3890000,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->assertSame('Tehran', $city->name);
        $this->assertSame('tehran', $city->slug);
        $this->assertTrue($city->is_active);
        $this->assertSame(10, $city->sort_order);
        $this->assertSame('35.6892000', $city->latitude);
    }
}
