<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EventSourceApiKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSourceApiKey extends Model
{
    /** @use HasFactory<EventSourceApiKeyFactory> */
    use HasFactory;

    protected $fillable = [
        'event_source_id',
        'name',
        'key_hash',
        'encrypted_secret',
        'status',
        'active_from',
        'expires_at',
        'last_used_at',
        'rotated_at',
        'metadata',
    ];

    protected $hidden = [
        'encrypted_secret',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_secret' => 'encrypted',
            'active_from' => 'datetime',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'rotated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public static function issue(
        EventSource $source,
        string $name,
        string $secret,
        ?CarbonInterface $expiresAt = null,
    ): self {
        return self::query()->create([
            'event_source_id' => $source->id,
            'name' => $name,
            'key_hash' => self::hashSecret($secret),
            'encrypted_secret' => $secret,
            'status' => 'active',
            'active_from' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    public static function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(EventSource::class, 'event_source_id');
    }

    public function matchesSecret(string $secret): bool
    {
        return hash_equals($this->key_hash, self::hashSecret($secret));
    }

    public function rotate(string $newSecret): void
    {
        $this->forceFill([
            'key_hash' => self::hashSecret($newSecret),
            'encrypted_secret' => $newSecret,
            'status' => 'active',
            'rotated_at' => now(),
        ])->save();
    }

    public function revoke(): void
    {
        $this->forceFill(['status' => 'revoked'])->save();
    }
}
