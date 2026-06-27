<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Durable record of who did what — admin authentication, money movement, and
 * other sensitive changes. Persisted independently of rolling stdout logs so it
 * outlives log rotation (see docs/LOGGING.md).
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_label',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit event. Resolves the current actor and request IP
     * automatically; pass an explicit $user for events outside a web request.
     *
     * @param  Model|null  $subject  the affected record, if any
     */
    public static function record(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): self {
        $actor = $user ?? Auth::user();

        return self::create([
            'user_id' => $actor?->getKey(),
            'actor_label' => $actor?->email ?? $actor?->name,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
