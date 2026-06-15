<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSourceAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventSourceAttributionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_source_attributions_table_exists_with_core_columns(): void
    {
        foreach (['event_id', 'source_key', 'external_id', 'sync_status'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('event_source_attributions', $column),
                "Missing event_source_attributions.$column"
            );
        }

        $this->assertTrue(Schema::hasColumn('event_source_attributions', 'payload_hash'));
        $this->assertTrue(Schema::hasColumn('event_source_attributions', 'snapshot_ref'));
    }

    public function test_event_has_source_attributions(): void
    {
        $event = Event::factory()->create();
        $attribution = EventSourceAttribution::factory()->create([
            'event_id' => $event->id,
            'source_key' => 'evand',
            'external_id' => '12345',
        ]);

        $this->assertTrue($event->sourceAttributions->contains($attribution));
        $this->assertTrue($attribution->event->is($event));
    }

    public function test_source_attribution_casts_dates_score_and_metadata(): void
    {
        $attribution = EventSourceAttribution::factory()->create([
            'confidence_score' => 0.875,
            'metadata' => ['dedupe_reason' => 'exact_external_id'],
        ]);

        $this->assertSame('0.8750', $attribution->confidence_score);
        $this->assertSame('exact_external_id', $attribution->metadata['dedupe_reason']);
        $this->assertTrue($attribution->first_seen_at->isBefore($attribution->last_seen_at->addSecond()));
    }
}
