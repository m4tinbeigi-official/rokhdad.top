<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_index_returns_published_events_only(): void
    {
        $published = Event::factory()->create([
            'title' => 'Published Event',
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);
        Event::factory()->create(['status' => 'draft']);
        Event::factory()->create(['status' => 'cancelled']);

        $response = $this->getJson('/api/v1/events');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.title', 'Published Event')
            ->assertJsonPath('data.0.status', 'published')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'title',
                    'slug',
                    'summary',
                    'starts_at',
                    'ends_at',
                    'timezone',
                    'event_type',
                    'status',
                    'category',
                    'city',
                    'organizer',
                    'source_attributions',
                ]],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'seo',
                ],
            ])
            ->assertJsonPath('meta.seo.json_ld.0.@type', 'WebSite')
            ->assertJsonPath('meta.seo.json_ld.1.@type', 'ItemList')
            ->assertJsonPath('meta.seo.json_ld.1.itemListElement.0.name', 'Published Event');
    }

    public function test_events_index_is_paginated_and_ordered_by_start_time(): void
    {
        $later = Event::factory()->create([
            'title' => 'Later',
            'status' => 'published',
            'starts_at' => now()->addDays(2),
        ]);
        $earlier = Event::factory()->create([
            'title' => 'Earlier',
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/events?per_page=1');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $earlier->id);

        $this->assertNotSame($later->id, $response->json('data.0.id'));
    }

    public function test_events_index_filters_by_keyword(): void
    {
        Event::factory()->create(['title' => 'Laravel Meetup', 'status' => 'published']);
        Event::factory()->create(['title' => 'Vue JS Workshop', 'status' => 'published']);

        $response = $this->getJson('/api/v1/events?q=Laravel');
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Laravel Meetup');
    }

    public function test_events_index_filters_by_category(): void
    {
        $category1 = \App\Models\Category::factory()->create(['slug' => 'tech']);
        $category2 = \App\Models\Category::factory()->create(['slug' => 'art']);

        Event::factory()->create(['category_id' => $category1->id, 'title' => 'Tech Event', 'status' => 'published']);
        Event::factory()->create(['category_id' => $category2->id, 'title' => 'Art Event', 'status' => 'published']);

        $response = $this->getJson('/api/v1/events?category=tech');
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Tech Event');
    }

    public function test_events_index_filters_by_city(): void
    {
        $city1 = \App\Models\City::factory()->create(['slug' => 'tehran']);
        $city2 = \App\Models\City::factory()->create(['slug' => 'shiraz']);

        Event::factory()->create(['city_id' => $city1->id, 'title' => 'Tehran Event', 'status' => 'published']);
        Event::factory()->create(['city_id' => $city2->id, 'title' => 'Shiraz Event', 'status' => 'published']);

        $response = $this->getJson('/api/v1/events?city=tehran');
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Tehran Event');
    }

    public function test_events_index_filters_by_event_type(): void
    {
        Event::factory()->create(['event_type' => 'online', 'title' => 'Online Event', 'status' => 'published']);
        Event::factory()->create(['event_type' => 'in_person', 'title' => 'In Person Event', 'status' => 'published']);

        $response = $this->getJson('/api/v1/events?event_type=online');
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Online Event');
    }

    public function test_events_index_filters_by_source(): void
    {
        $event1 = Event::factory()->create(['title' => 'Evand Event', 'status' => 'published']);
        $event2 = Event::factory()->create(['title' => 'Eseminar Event', 'status' => 'published']);

        \App\Models\EventSourceAttribution::factory()->create([
            'event_id' => $event1->id,
            'source_key' => 'evand',
            'external_id' => '1',
        ]);
        \App\Models\EventSourceAttribution::factory()->create([
            'event_id' => $event2->id,
            'source_key' => 'es Lightning',
            'external_id' => '2',
        ]);

        $response = $this->getJson('/api/v1/events?source=evand');
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Evand Event');
    }

    public function test_events_index_filters_by_date(): void
    {
        Event::factory()->create(['title' => 'Today Event', 'starts_at' => now()->startOfDay(), 'status' => 'published']);
        Event::factory()->create(['title' => 'Next Week Event', 'starts_at' => now()->addDays(7), 'status' => 'published']);

        $response = $this->getJson('/api/v1/events?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));
        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Today Event');
    }
}
