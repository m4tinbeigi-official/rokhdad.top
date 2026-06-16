<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'status', 'payment_status',
        'quantity', 'total_amount', 'currency', 'form_data',
        'confirmed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed', 'confirmed_at' => now()]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }
}
