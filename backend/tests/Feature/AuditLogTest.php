<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organizer;
use App\Models\Payout;
use App\Models\SettlementLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_captures_actor_and_subject(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organizer = Organizer::factory()->create();

        AuditLog::record(
            action: 'test.event',
            description: 'something happened',
            subject: $organizer,
            properties: ['foo' => 'bar'],
        );

        $log = AuditLog::query()->latest('id')->first();

        $this->assertSame('test.event', $log->action);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(Organizer::class, $log->subject_type);
        $this->assertSame($organizer->id, $log->subject_id);
        $this->assertSame('bar', $log->properties['foo']);
    }

    public function test_completing_payout_writes_audit_entry(): void
    {
        $organizer = Organizer::factory()->create();
        SettlementLedger::recordPayment($organizer->id, 1, 300_000);

        $payout = Payout::query()->create([
            'organizer_id' => $organizer->id,
            'amount' => 120_000,
            'status' => 'pending',
        ]);

        $payout->markCompleted();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payout.completed',
            'subject_type' => Payout::class,
            'subject_id' => $payout->id,
        ]);
    }

    public function test_rejecting_payout_writes_audit_entry(): void
    {
        $organizer = Organizer::factory()->create();

        $payout = Payout::query()->create([
            'organizer_id' => $organizer->id,
            'amount' => 120_000,
            'status' => 'pending',
        ]);

        $payout->reject('invalid bank account');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payout.rejected',
            'subject_id' => $payout->id,
        ]);
    }

    public function test_login_event_is_audited(): void
    {
        $user = User::factory()->create();

        event(new \Illuminate\Auth\Events\Login('web', $user, false));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
        ]);
    }
}
