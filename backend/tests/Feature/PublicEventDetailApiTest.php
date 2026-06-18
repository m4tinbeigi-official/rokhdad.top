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
            'is_internal' => true,
            'registration_open' => true,
            'capacity' => 50,
            'requires_approval' => true,
            'registration_instructions' => 'Bring ID.',
            'metadata' => [
                'language' => 'fa',
                'registration_form' => [
                    'title' => 'فرم تکمیلی',
                    'description' => 'اطلاعات شغلی خود را تکمیل کنید.',
                    'fields' => [
                        [
                            'name' => 'company',
                            'label' => 'نام شرکت',
                            'type' => 'text',
                            'required' => true,
                        ],
                    ],
                ],
                'registration_rules' => [
                    'min_quantity' => 2,
                    'max_quantity' => 5,
                ],
            ],
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
            ->assertJsonPath('data.is_internal', true)
            ->assertJsonPath('data.registration_open', true)
            ->assertJsonPath('data.capacity', 50)
            ->assertJsonPath('data.requires_approval', true)
            ->assertJsonPath('data.registration_instructions', 'Bring ID.')
            ->assertJsonPath('data.registration_form.title', 'فرم تکمیلی')
            ->assertJsonPath('data.registration_form.fields.0.name', 'company')
            ->assertJsonPath('data.registration_form.fields.0.required', true)
            ->assertJsonPath('data.registration_rules.min_quantity', 2)
            ->assertJsonPath('data.registration_rules.max_quantity', 5)
            ->assertJsonPath('data.seo.title', 'Published Detail Event | رخداد')
            ->assertJsonPath('data.seo.canonical_url', config('app.url').'/events/published-detail-event')
            ->assertJsonPath('data.seo.open_graph.type', 'event')
            ->assertJsonPath('data.seo.twitter.card', 'summary')
            ->assertJsonPath('data.seo.breadcrumbs.0.name', 'رخداد')
            ->assertJsonPath('data.seo.json_ld.0.@type', 'Event')
            ->assertJsonPath('data.seo.json_ld.0.name', 'Published Detail Event')
            ->assertJsonPath('data.seo.json_ld.1.@type', 'BreadcrumbList')
            ->assertJsonPath('data.seo.json_ld.1.itemListElement.2.name', 'Published Detail Event')
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
