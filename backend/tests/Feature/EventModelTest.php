<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_table_exists_with_core_columns(): void
    {
        foreach (['title', 'slug', 'starts_at', 'ends_at', 'event_type', 'status', 'is_featured'] as $column) {
            $this->assertTrue(Schema::hasColumn('events', $column), "Missing events.$column");
        }

        $this->assertTrue(Schema::hasColumn('events', 'category_id'));
        $this->assertTrue(Schema::hasColumn('events', 'city_id'));
        $this->assertTrue(Schema::hasColumn('events', 'organizer_id'));
        $this->assertTrue(Schema::hasTable('event_person'));
    }

    public function test_event_belongs_to_category_city_and_organizer(): void
    {
        $category = Category::factory()->create(['slug' => 'tech']);
        $city = City::factory()->create(['slug' => 'tehran']);
        $organizer = Organizer::factory()->create(['slug' => 'rokhdad-team']);
        $event = Event::factory()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'organizer_id' => $organizer->id,
        ]);

        $this->assertTrue($event->category->is($category));
        $this->assertTrue($event->city->is($city));
        $this->assertTrue($event->organizer->is($organizer));
    }

    public function test_event_people_relationship_with_role_title_and_sort_order(): void
    {
        $event = Event::factory()->create();
        $person = Person::factory()->create(['full_name' => 'Rokhdad Speaker']);

        $event->people()->attach($person, [
            'role_title' => 'Speaker',
            'sort_order' => 10,
        ]);

        $speaker = $event->people->first();

        $this->assertTrue($event->people->contains($person));
        $this->assertSame('Speaker', $speaker->pivot->role_title);
        $this->assertSame(10, $speaker->pivot->sort_order);
        $this->assertTrue($person->events->contains($event));
    }

    public function test_event_casts_dates_booleans_and_metadata(): void
    {
        $event = Event::factory()->create([
            'metadata' => ['source' => 'manual'],
            'is_featured' => true,
        ]);

        $this->assertTrue($event->starts_at->isBefore($event->ends_at));
        $this->assertSame('manual', $event->metadata['source']);
        $this->assertTrue($event->is_featured);
    }
}
