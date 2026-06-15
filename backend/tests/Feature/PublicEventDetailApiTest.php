<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSourceAttribution;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventDetailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_detail_returns_published_event_by_slug(): void
    {
        $event = Event::factory()->create([
            'title' => 'Published Detail Event',
            'slug' => 'published-detail-event',
            'status' => 'published',
            'description' => 'Long event detail',
            'metadata' => ['language' => 'fa'],
        ]);
        $person = Person::factory()->create(['full_name' => 'Detail Speaker']);
        $event->people()->attach($person, ['role_title' => 'Speaker', 'sort_order' => 1]);
        EventSourceAttribution::factory()->create([
            'event_id' => $event->id,
            'source_key' => 'evand',
            'external_id' => '101',
            'external_url' => 'https://evand.com/events/101',
            'sync_status' => 'synced',
        ]);

        $response = $this->getJson('/api/v1/events/published-detail-event');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.title', 'Published Detail Event')
            ->assertJsonPath('data.description', 'Long event detail')
            ->assertJsonPath('data.metadata.language', 'fa')
            ->assertJsonPath('data.people.0.full_name', 'Detail Speaker')
            ->assertJsonPath('data.people.0.role_title', 'Speaker')
            ->assertJsonPath('data.source_attributions.0.source_key', 'evand')
            ->assertJsonPath('data.source_attributions.0.external_id', '101');
    }

    public function test_event_detail_does_not_return_draft_events(): void
    {
        Event::factory()->create([
            'slug' => 'draft-detail-event',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/events/draft-detail-event')
            ->assertNotFound();
    }
}
