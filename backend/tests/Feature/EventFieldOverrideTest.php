<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventFieldLock;
use App\Models\EventFieldOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventFieldOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_override_and_lock_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('event_field_overrides'));
        $this->assertTrue(Schema::hasTable('event_field_locks'));

        foreach (['event_id', 'field_path', 'original_value', 'override_value', 'applied_by_user_id', 'applied_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('event_field_overrides', $column), "Missing event_field_overrides.$column");
        }

        foreach (['event_id', 'field_path', 'locked_by_user_id', 'locked_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('event_field_locks', $column), "Missing event_field_locks.$column");
        }
    }

    public function test_admin_override_updates_event_and_locks_field(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Source Title']);

        $override = $event->applyFieldOverride(
            fieldPath: 'title',
            value: 'Admin Title',
            user: $admin,
            sourceKey: 'evand',
            lockField: true,
            reason: 'Corrected by admin',
        );

        $event->refresh();

        $this->assertSame('Admin Title', $event->title);
        $this->assertSame('title', $override->field_path);
        $this->assertSame('Source Title', $override->original_value['value']);
        $this->assertSame('Admin Title', $override->override_value['value']);
        $this->assertSame('evand', $override->source_key);
        $this->assertTrue($event->isFieldLocked('title'));
        $this->assertTrue($event->fieldLocks()->first()->lockedBy->is($admin));
    }

    public function test_field_lock_can_be_released(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        EventFieldLock::lock($event, 'summary', $admin, 'Manual summary');
        $this->assertTrue($event->isFieldLocked('summary'));

        EventFieldLock::unlock($event, 'summary');
        $this->assertFalse($event->isFieldLocked('summary'));
    }

    public function test_non_event_field_cannot_be_overridden(): void
    {
        $event = Event::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        EventFieldOverride::apply($event, 'not_a_field', 'value');
    }
}
