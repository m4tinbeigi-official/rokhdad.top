<?php

namespace App\Models;

use Database\Factories\EventSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSource extends Model
{
    /** @use HasFactory<EventSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'source_key',
        'name',
        'base_url',
        'api_base_url',
        'auth_type',
        'status',
        'is_enabled',
        'rate_limit_per_minute',
        'config',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'rate_limit_per_minute' => 'integer',
            'config' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(EventSourceAttribution::class, 'source_key', 'source_key');
    }
}
