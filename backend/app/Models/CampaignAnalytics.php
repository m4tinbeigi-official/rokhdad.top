<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAnalytics extends Model
{
    protected $fillable = [
        'campaign_id', 'metric_type', 'value', 'details'
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
