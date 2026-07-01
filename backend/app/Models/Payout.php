<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payout extends Model
{
    protected $fillable = [
        'organizer_id',
        'amount',
        'status',
        'bank_account',
        'notes',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Mark this payout as completed and record the debit in the settlement ledger.
     * Idempotent: does nothing if already completed.
     */
    public function markCompleted(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        DB::transaction(function () {
            SettlementLedger::recordPayout($this->organizer_id, $this->id, $this->amount);

            $this->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
        });

        AuditLog::record(
            action: 'payout.completed',
            description: 'تسویه برگزارکننده تکمیل شد',
            subject: $this,
            properties: ['organizer_id' => $this->organizer_id, 'amount' => $this->amount],
        );
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'notes' => $reason ?? $this->notes,
            'processed_at' => now(),
        ]);

        AuditLog::record(
            action: 'payout.rejected',
            description: 'درخواست تسویه رد شد',
            subject: $this,
            properties: ['organizer_id' => $this->organizer_id, 'amount' => $this->amount, 'reason' => $reason],
        );
    }
}
