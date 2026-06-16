<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'user_id',
        'gateway',
        'gateway_authority',
        'gateway_ref_id',
        'status',
        'amount',
        'currency',
        'callback_url',
        'gateway_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markPaid(string $refId, array $gatewayResponse = []): void
    {
        $this->update([
            'gateway_ref_id' => $refId,
            'status' => 'paid',
            'gateway_response' => $gatewayResponse,
            'paid_at' => now(),
        ]);
    }

    public function markFailed(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse,
        ]);
    }
}
