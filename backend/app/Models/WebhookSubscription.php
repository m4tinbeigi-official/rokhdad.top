<?php

namespace App\Models;

use Database\Factories\WebhookSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    /** @use HasFactory<WebhookSubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'name',
        'target_url',
        'secret',
        'subscribed_events',
        'is_active',
        'last_delivered_at',
        'last_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_events' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function listensTo(string $eventName): bool
    {
        return in_array($eventName, $this->subscribed_events ?? [], true);
    }
}
