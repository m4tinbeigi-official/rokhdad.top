<?php

namespace Tests\Feature;

use App\Models\EventSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventSourceHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_sources_table_has_health_tracking_columns(): void
    {
        foreach (['health_status', 'consecutive_failures', 'last_success_at', 'last_failure_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('event_sources', $column), "Missing event_sources.$column");
        }

        $this->assertTrue(Schema::hasColumn('event_sources', 'last_error_message'));
    }

    public function test_health_success_resets_failure_state(): void
    {
        $source = EventSource::factory()->create([
            'health_status' => 'failing',
            'consecutive_failures' => 4,
            'last_error_message' => 'Timeout',
        ]);

        $source->recordHealthSuccess();
        $source->refresh();

        $this->assertSame('healthy', $source->health_status);
        $this->assertSame(0, $source->consecutive_failures);
        $this->assertNull($source->last_error_message);
        $this->assertNotNull($source->last_success_at);
    }

    public function test_repeated_health_failures_mark_source_failing(): void
    {
        $source = EventSource::factory()->create();

        $source->recordHealthFailure('HTTP 500');
        $source->refresh();

        $this->assertSame('degraded', $source->health_status);
        $this->assertSame(1, $source->consecutive_failures);

        $source->recordHealthFailure('HTTP 500');
        $source->recordHealthFailure('HTTP 500');
        $source->refresh();

        $this->assertSame('failing', $source->health_status);
        $this->assertSame(3, $source->consecutive_failures);
        $this->assertSame('HTTP 500', $source->last_error_message);
        $this->assertNotNull($source->last_failure_at);
    }
}
