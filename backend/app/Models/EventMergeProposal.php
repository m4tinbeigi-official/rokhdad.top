<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMergeProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'primary_event_id',
        'duplicate_event_id',
        'confidence_score',
        'ai_reasoning',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
        ];
    }

    public function primaryEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'primary_event_id');
    }

    public function duplicateEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'duplicate_event_id');
    }
}
