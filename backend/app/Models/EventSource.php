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
        'health_status',
        'consecutive_failures',
        'is_enabled',
        'rate_limit_per_minute',
        'config',
        'last_checked_at',
        'last_success_at',
        'last_failure_at',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'consecutive_failures' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'config' => 'array',
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(EventSourceAttribution::class, 'source_key', 'source_key');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(EventSourceApiKey::class);
    }

    public function activeApiKeys(): HasMany
    {
        return $this->apiKeys()->where('status', 'active');
    }

    public function recordHealthSuccess(): void
    {
        $this->forceFill([
            'health_status' => 'healthy',
            'consecutive_failures' => 0,
            'last_checked_at' => now(),
            'last_success_at' => now(),
            'last_error_message' => null,
        ])->save();
    }

    public function recordHealthFailure(string $message): void
    {
        $failures = $this->consecutive_failures + 1;

        $this->forceFill([
            'health_status' => $failures >= 3 ? 'failing' : 'degraded',
            'consecutive_failures' => $failures,
            'last_checked_at' => now(),
            'last_failure_at' => now(),
            'last_error_message' => mb_substr($message, 0, 1000),
        ])->save();
    }
}
