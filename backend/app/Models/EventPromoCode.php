<?php

namespace App\Models;

use Database\Factories\EventPromoCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPromoCode extends Model
{
    /** @use HasFactory<EventPromoCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'code',
        'discount_type',
        'discount_value',
        'max_uses',
        'used_count',
        'min_quantity',
        'max_quantity',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isAvailableForQuantity(int $quantity): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->min_quantity !== null && $quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function discountAmount(int $subtotal): int
    {
        if ($subtotal <= 0 || $this->discount_value <= 0) {
            return 0;
        }

        if ($this->discount_type === 'percent') {
            return min($subtotal, (int) floor($subtotal * min($this->discount_value, 100) / 100));
        }

        return min($subtotal, $this->discount_value);
    }
}
