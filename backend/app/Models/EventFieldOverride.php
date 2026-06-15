<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFieldOverride extends Model
{
    protected $fillable = [
        'event_id',
        'field_path',
        'original_value',
        'override_value',
        'source_key',
        'applied_by_user_id',
        'applied_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'original_value' => 'array',
            'override_value' => 'array',
            'applied_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public static function apply(
        Event $event,
        string $fieldPath,
        mixed $value,
        ?User $user = null,
        ?string $sourceKey = null,
        bool $lockField = true,
        ?string $reason = null,
    ): self {
        if (! in_array($fieldPath, $event->getFillable(), true)) {
            throw new \InvalidArgumentException("Field [$fieldPath] cannot be overridden.");
        }

        $original = $event->getAttribute($fieldPath);
        $event->forceFill([$fieldPath => $value])->save();

        $override = self::query()->create([
            'event_id' => $event->id,
            'field_path' => $fieldPath,
            'original_value' => ['value' => $original],
            'override_value' => ['value' => $value],
            'source_key' => $sourceKey,
            'applied_by_user_id' => $user?->id,
            'applied_at' => now(),
            'metadata' => ['reason' => $reason],
        ]);

        if ($lockField) {
            EventFieldLock::lock($event, $fieldPath, $user, $reason);
        }

        return $override;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }
}
