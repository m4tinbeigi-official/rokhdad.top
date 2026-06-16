<?php

namespace App\Models;

use Database\Factories\EventTicketTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTicketType extends Model
{
    /** @use HasFactory<EventTicketTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'currency',
        'capacity',
        'sold_count',
        'max_per_user',
        'is_active',
        'sale_starts_at',
        'sale_ends_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'capacity' => 'integer',
            'sold_count' => 'integer',
            'max_per_user' => 'integer',
            'is_active' => 'boolean',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->sold_count);
    }

    public function isAvailableForSale(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->remainingCapacity() === 0) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
