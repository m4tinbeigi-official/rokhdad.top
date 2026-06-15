<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Organizer;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizerPersonModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_and_people_tables_exist_with_core_columns(): void
    {
        foreach (['name', 'slug', 'is_active'] as $column) {
            $this->assertTrue(Schema::hasColumn('organizers', $column), "Missing organizers.$column");
        }

        foreach (['full_name', 'slug', 'is_active'] as $column) {
            $this->assertTrue(Schema::hasColumn('people', $column), "Missing people.$column");
        }

        $this->assertTrue(Schema::hasTable('organizer_person'));
    }

    public function test_organizer_belongs_to_city(): void
    {
        $city = City::factory()->create(['slug' => 'tehran']);
        $organizer = Organizer::factory()->create(['city_id' => $city->id]);

        $this->assertTrue($organizer->city->is($city));
    }

    public function test_organizer_and_person_relationship_with_role_title(): void
    {
        $organizer = Organizer::factory()->create();
        $person = Person::factory()->create(['full_name' => 'Rokhdad Speaker']);

        $organizer->people()->attach($person, ['role_title' => 'Speaker']);

        $this->assertTrue($organizer->people->contains($person));
        $this->assertSame('Speaker', $organizer->people->first()->pivot->role_title);
        $this->assertTrue($person->organizers->contains($organizer));
    }

    public function test_social_links_are_cast_to_array(): void
    {
        $person = Person::factory()->create([
            'social_links' => ['website' => 'https://rokhdad.top'],
        ]);

        $this->assertSame('https://rokhdad.top', $person->social_links['website']);
    }
}
