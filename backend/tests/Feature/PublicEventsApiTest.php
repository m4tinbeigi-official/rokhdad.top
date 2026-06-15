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
                ]],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
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
}
