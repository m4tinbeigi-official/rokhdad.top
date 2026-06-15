<?php

namespace App\Models;

use Database\Factories\EventSourceAttributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSourceAttribution extends Model
{
    /** @use HasFactory<EventSourceAttributionFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'source_key',
        'external_id',
        'external_url',
        'payload_hash',
        'snapshot_ref',
        'first_seen_at',
        'last_seen_at',
        'last_synced_at',
        'sync_status',
        'confidence_score',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'confidence_score' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(EventSource::class, 'source_key', 'source_key');
    }
}
