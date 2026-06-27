<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Registration;
use App\Models\SettlementLedger;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettlementLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_fee_is_ten_percent_floored(): void
    {
        $this->assertSame(25_000, SettlementService::platformFee(250_000));
        $this->assertSame(0, SettlementService::platformFee(5)); // floor(0.5)
    }

    public function test_paid_callback_records_credit_and_platform_fee(): void
    {
        config(['services.zarinpal.merchant_id' => 'test-merchant']);
        Http::fake([
            'sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 'REF777'],
                'errors' => [],
            ]),
        ]);

        $user = User::factory()->create();
        $event = Event::factory()->create(['is_internal' => true]);
        $organizerId = (int) $event->organizer_id;

        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'quantity' => 1,
            'total_amount' => 250_000,
            'currency' => 'IRR',
        ]);

        Payment::query()->create([
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'gateway' => 'zarinpal',
            'gateway_authority' => 'AUTHSET',
            'status' => 'pending',
            'amount' => 250_000,
            'currency' => 'IRR',
            'callback_url' => 'https://rokhdad.test/api/v1/payments/callback/zarinpal',
        ]);

        $this->get('/api/v1/payments/callback/zarinpal?Authority=AUTHSET&Status=OK')
            ->assertRedirect();

        // Two ledger rows: gross credit + platform fee debit
        $this->assertDatabaseHas('settlement_ledgers', [
            'organizer_id' => $organizerId,
            'type' => 'credit',
            'amount' => 250_000,
        ]);
        $this->assertDatabaseHas('settlement_ledgers', [
            'organizer_id' => $organizerId,
            'type' => 'debit',
            'amount' => 25_000,
        ]);

        // Net balance = 250_000 - 25_000
        $this->assertSame(225_000, SettlementLedger::getBalance($organizerId));
    }

    public function test_balance_accounts_for_pending_payouts(): void
    {
        $organizer = Organizer::factory()->create();
        SettlementLedger::recordPayment($organizer->id, 1, 500_000);
        SettlementLedger::recordPlatformFee($organizer->id, 1, 50_000);

        // balance = 450_000
        Payout::query()->create([
            'organizer_id' => $organizer->id,
            'amount' => 100_000,
            'status' => 'pending',
        ]);

        $balance = SettlementService::calculateOrganizerBalance($organizer);

        $this->assertSame(450_000, $balance['balance']);
        $this->assertSame(100_000, $balance['pending']);
        $this->assertSame(350_000, $balance['available']);
    }

    public function test_withdrawal_request_rejected_when_over_available(): void
    {
        $organizer = Organizer::factory()->create();
        SettlementLedger::recordPayment($organizer->id, 1, 100_000);

        $this->assertFalse(
            SettlementService::createWithdrawalRequest($organizer, 200_000, ['bank_account' => 'IR123'])
        );
        $this->assertTrue(
            SettlementService::createWithdrawalRequest($organizer, 80_000, ['bank_account' => 'IR123'])
        );
        $this->assertDatabaseHas('payouts', [
            'organizer_id' => $organizer->id,
            'amount' => 80_000,
            'status' => 'pending',
            'bank_account' => 'IR123',
        ]);
    }

    public function test_completing_payout_records_debit_and_is_idempotent(): void
    {
        $organizer = Organizer::factory()->create();
        SettlementLedger::recordPayment($organizer->id, 1, 300_000);

        $payout = Payout::query()->create([
            'organizer_id' => $organizer->id,
            'amount' => 120_000,
            'status' => 'pending',
        ]);

        $payout->markCompleted();
        $payout->markCompleted(); // second call must be a no-op

        $this->assertSame('completed', $payout->fresh()->status);
        $this->assertSame(180_000, SettlementLedger::getBalance($organizer->id));
        $this->assertSame(
            1,
            SettlementLedger::query()
                ->where('organizer_id', $organizer->id)
                ->where('reference_type', 'payout')
                ->count()
        );
    }

    public function test_monthly_statement_sums_current_month(): void
    {
        $organizer = Organizer::factory()->create();
        SettlementService::recordSuccessfulPayment($organizer->id, 1, 250_000);

        $statement = SettlementService::generateMonthlyStatement($organizer, now()->format('Y-m'));

        $this->assertSame(250_000, $statement['gross']);
        $this->assertSame(25_000, $statement['platform_fees']);
        $this->assertSame(225_000, $statement['net']);
    }
}
