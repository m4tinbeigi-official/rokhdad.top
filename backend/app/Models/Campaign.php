<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'organizer_id', 'name', 'description', 'channel', 
        'target_audience', 'starts_at', 'ends_at', 'status',
        'template', 'settings'
    ];

    protected function casts(): array
    {
        return [
            'target_audience' => 'array',
            'settings' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(CampaignAnalytics::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->starts_at <= now() && 
               $this->ends_at >= now();
    }

    public function launch(): void
    {
        $this->update(['status' => 'active', 'starts_at' => now()]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
