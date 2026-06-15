<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfilesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizers_endpoint_returns_active_organizers_with_published_event_counts(): void
    {
        Organizer::factory()->create(['name' => 'Inactive Org', 'slug' => 'inactive-org', 'is_active' => false]);
        $second = Organizer::factory()->create(['name' => 'Beta Org', 'slug' => 'beta-org']);
        $first = Organizer::factory()->create(['name' => 'Alpha Org', 'slug' => 'alpha-org']);
        Event::factory()->create(['organizer_id' => $first->id, 'status' => 'published']);
        Event::factory()->create(['organizer_id' => $first->id, 'status' => 'draft']);

        $response = $this->getJson('/api/v1/organizers');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.events_count', 1)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'slug',
                    'description',
                    'website_url',
                    'social_links',
                    'events_count',
                    'city',
                ]],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    public function test_organizer_detail_returns_people_and_published_events(): void
    {
        $city = City::factory()->create(['name' => 'Tehran', 'slug' => 'tehran']);
        $organizer = Organizer::factory()->create([
            'city_id' => $city->id,
            'name' => 'Rokhdad Org',
            'slug' => 'rokhdad-org',
        ]);
        $person = Person::factory()->create(['full_name' => 'Rokhdad Speaker', 'slug' => 'rokhdad-speaker']);
        $inactivePerson = Person::factory()->create(['is_active' => false]);
        $organizer->people()->attach($person, ['role_title' => 'Host']);
        $organizer->people()->attach($inactivePerson, ['role_title' => 'Hidden']);
        Event::factory()->create([
            'organizer_id' => $organizer->id,
            'title' => 'Published Event',
            'slug' => 'published-event',
            'status' => 'published',
        ]);
        Event::factory()->create([
            'organizer_id' => $organizer->id,
            'title' => 'Draft Event',
            'slug' => 'draft-event',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/organizers/rokhdad-org');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $organizer->id)
            ->assertJsonPath('data.city.slug', 'tehran')
            ->assertJsonCount(1, 'data.people')
            ->assertJsonPath('data.people.0.full_name', 'Rokhdad Speaker')
            ->assertJsonPath('data.people.0.role_title', 'Host')
            ->assertJsonCount(1, 'data.events')
            ->assertJsonPath('data.events.0.slug', 'published-event');
    }

    public function test_people_endpoint_returns_active_people_with_published_event_counts(): void
    {
        Person::factory()->create(['full_name' => 'Inactive Person', 'slug' => 'inactive-person', 'is_active' => false]);
        $second = Person::factory()->create(['full_name' => 'Beta Speaker', 'slug' => 'beta-speaker']);
        $first = Person::factory()->create(['full_name' => 'Alpha Speaker', 'slug' => 'alpha-speaker']);
        $published = Event::factory()->create(['status' => 'published']);
        $draft = Event::factory()->create(['status' => 'draft']);
        $first->events()->attach($published, ['role_title' => 'Speaker', 'sort_order' => 1]);
        $first->events()->attach($draft, ['role_title' => 'Speaker', 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/people');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.events_count', 1)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'full_name',
                    'slug',
                    'title',
                    'bio',
                    'website_url',
                    'social_links',
                    'events_count',
                ]],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    public function test_person_detail_returns_organizers_and_published_events(): void
    {
        $organizer = Organizer::factory()->create(['name' => 'Rokhdad Org', 'slug' => 'person-org']);
        $person = Person::factory()->create(['full_name' => 'Detail Speaker', 'slug' => 'detail-speaker']);
        $person->organizers()->attach($organizer, ['role_title' => 'Instructor']);
        $published = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'title' => 'Visible Talk',
            'slug' => 'visible-talk',
            'status' => 'published',
        ]);
        $draft = Event::factory()->create([
            'title' => 'Hidden Talk',
            'slug' => 'hidden-talk',
            'status' => 'draft',
        ]);
        $person->events()->attach($published, ['role_title' => 'Speaker', 'sort_order' => 1]);
        $person->events()->attach($draft, ['role_title' => 'Speaker', 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/people/detail-speaker');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $person->id)
            ->assertJsonCount(1, 'data.organizers')
            ->assertJsonPath('data.organizers.0.slug', 'person-org')
            ->assertJsonPath('data.organizers.0.role_title', 'Instructor')
            ->assertJsonCount(1, 'data.events')
            ->assertJsonPath('data.events.0.slug', 'visible-talk')
            ->assertJsonPath('data.events.0.role_title', 'Speaker')
            ->assertJsonPath('data.events.0.organizer.slug', 'person-org');
    }

    public function test_inactive_profiles_are_not_publicly_accessible(): void
    {
        Organizer::factory()->create(['slug' => 'inactive-public-org', 'is_active' => false]);
        Person::factory()->create(['slug' => 'inactive-public-person', 'is_active' => false]);

        $this->getJson('/api/v1/organizers/inactive-public-org')->assertNotFound();
        $this->getJson('/api/v1/people/inactive-public-person')->assertNotFound();
    }
}
