<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSource;
use App\Models\EventSourceAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventSourceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_sources_table_exists_with_core_columns(): void
    {
        foreach (['source_key', 'name', 'auth_type', 'status', 'is_enabled'] as $column) {
            $this->assertTrue(Schema::hasColumn('event_sources', $column), "Missing event_sources.$column");
        }

        $this->assertTrue(Schema::hasColumn('event_sources', 'rate_limit_per_minute'));
        $this->assertTrue(Schema::hasColumn('event_sources', 'config'));
    }

    public function test_event_source_links_to_attributions_by_source_key(): void
    {
        $source = EventSource::factory()->create(['source_key' => 'evand']);
        $attribution = EventSourceAttribution::factory()->create([
            'event_id' => Event::factory(),
            'source_key' => 'evand',
            'external_id' => '123',
        ]);

        $this->assertTrue($source->attributions->contains($attribution));
        $this->assertTrue($attribution->source->is($source));
    }

    public function test_event_source_casts_config_and_flags(): void
    {
        $source = EventSource::factory()->create([
            'is_enabled' => false,
            'rate_limit_per_minute' => 120,
            'config' => ['supports_api' => true],
        ]);

        $this->assertFalse($source->is_enabled);
        $this->assertSame(120, $source->rate_limit_per_minute);
        $this->assertTrue($source->config['supports_api']);
    }
}
