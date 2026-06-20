<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'organizer_id',
        'event_id',
        'name',
        'channel',
        'audience_type',
        'status',
        'subject',
        'message',
        'recipients_count',
        'sent_count',
        'last_sent_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'last_sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
