<?php

namespace App\Services;

use App\Models\Organizer;
use App\Models\Payout;
use App\Models\SettlementLedger;
use Carbon\CarbonImmutable;

class SettlementService
{
    /**
     * Platform commission rate applied to each successful payment.
     */
    public const PLATFORM_FEE_RATE = 0.10;

    /**
     * Compute the platform fee (in integer Rials) for a gross amount.
     */
    public static function platformFee(int $grossAmount): int
    {
        return (int) floor($grossAmount * self::PLATFORM_FEE_RATE);
    }

    /**
     * Current settlement balance for an organizer.
     *
     * - `balance`   : net amount accrued in the ledger (credits - fees - completed payouts)
     * - `pending`   : amount locked in not-yet-completed payout requests
     * - `available` : what can still be withdrawn (balance - pending)
     *
     * @return array{balance:int, pending:int, available:int}
     */
    public static function calculateOrganizerBalance(Organizer $organizer): array
    {
        $balance = SettlementLedger::getBalance($organizer->id);

        $pending = (int) Payout::query()
            ->where('organizer_id', $organizer->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        return [
            'balance' => $balance,
            'pending' => $pending,
            'available' => max(0, $balance - $pending),
        ];
    }

    /**
     * Monthly statement for an organizer.
     *
     * @param  string  $month  format `Y-m` (e.g. "2026-06")
     * @return array{month:string, gross:int, platform_fees:int, net:int, payouts:int, entry_count:int}
     */
    public static function generateMonthlyStatement(Organizer $organizer, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();

        $entries = SettlementLedger::query()
            ->where('organizer_id', $organizer->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $gross = (int) $entries
            ->where('type', 'credit')
            ->where('reference_type', 'payment')
            ->sum('amount');

        $fees = (int) $entries
            ->where('type', 'debit')
            ->where('reference_type', 'payment')
            ->sum('amount');

        $payouts = (int) $entries
            ->where('type', 'debit')
            ->where('reference_type', 'payout')
            ->sum('amount');

        return [
            'month' => $month,
            'gross' => $gross,
            'platform_fees' => $fees,
            'net' => $gross - $fees,
            'payouts' => $payouts,
            'entry_count' => $entries->count(),
        ];
    }

    /**
     * Record a successful payment in the organizer's ledger: credit the gross
     * amount, then debit the platform fee. Returns the net amount credited.
     */
    public static function recordSuccessfulPayment(int $organizerId, int $paymentId, int $grossAmount): int
    {
        $fee = self::platformFee($grossAmount);

        SettlementLedger::recordPayment($organizerId, $paymentId, $grossAmount);

        if ($fee > 0) {
            SettlementLedger::recordPlatformFee($organizerId, $paymentId, $fee);
        }

        return $grossAmount - $fee;
    }

    /**
     * Create a pending withdrawal (payout) request. The caller is expected to
     * have already validated that the amount is within the available balance.
     */
    public static function createWithdrawalRequest(Organizer $organizer, int $amount, array $meta = []): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $available = self::calculateOrganizerBalance($organizer)['available'];

        if ($amount > $available) {
            return false;
        }

        Payout::query()->create([
            'organizer_id' => $organizer->id,
            'amount' => $amount,
            'status' => 'pending',
            'bank_account' => $meta['bank_account'] ?? null,
            'notes' => $meta['notes'] ?? null,
        ]);

        return true;
    }
}
