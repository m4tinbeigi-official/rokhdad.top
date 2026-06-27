<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementLedger extends Model
{
    protected $fillable = [
        'organizer_id', 'reference_type', 'reference_id', 'amount', 
        'type', 'description', 'balance_before', 'balance_after'
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public static function recordPayment(int $organizerId, int $paymentId, int $amount): void
    {
        $balance = self::getBalance($organizerId);
        
        self::create([
            'organizer_id' => $organizerId,
            'reference_type' => 'payment',
            'reference_id' => $paymentId,
            'amount' => $amount,
            'type' => 'credit',
            'description' => 'Payment received',
            'balance_before' => $balance,
            'balance_after' => $balance + $amount,
        ]);
    }

    public static function recordPlatformFee(int $organizerId, int $paymentId, int $fee): void
    {
        $balance = self::getBalance($organizerId);
        
        self::create([
            'organizer_id' => $organizerId,
            'reference_type' => 'payment',
            'reference_id' => $paymentId,
            'amount' => $fee,
            'type' => 'debit',
            'description' => 'Platform fee (10%)',
            'balance_before' => $balance,
            'balance_after' => $balance - $fee,
        ]);
    }

    public static function recordPayout(int $organizerId, int $payoutId, int $amount): void
    {
        $balance = self::getBalance($organizerId);
        
        self::create([
            'organizer_id' => $organizerId,
            'reference_type' => 'payout',
            'reference_id' => $payoutId,
            'amount' => $amount,
            'type' => 'debit',
            'description' => 'Payout withdrawal',
            'balance_before' => $balance,
            'balance_after' => $balance - $amount,
        ]);
    }

    public static function getBalance(int $organizerId): int
    {
        return self::where('organizer_id', $organizerId)
            ->latest()
            ->value('balance_after') ?? 0;
    }

    public static function getLedger(int $organizerId, int $limit = 100): array
    {
        return [
            'current_balance' => self::getBalance($organizerId),
            'entries' => self::where('organizer_id', $organizerId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(),
        ];
    }
}
