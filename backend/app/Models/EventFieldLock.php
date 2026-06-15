<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFieldLock extends Model
{
    protected $fillable = [
        'event_id',
        'field_path',
        'locked_by_user_id',
        'reason',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
        ];
    }

    public static function lock(Event $event, string $fieldPath, ?User $user = null, ?string $reason = null): self
    {
        return self::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'field_path' => $fieldPath,
            ],
            [
                'locked_by_user_id' => $user?->id,
                'reason' => $reason,
                'locked_at' => now(),
            ],
        );
    }

    public static function unlock(Event $event, string $fieldPath): void
    {
        self::query()
            ->where('event_id', $event->id)
            ->where('field_path', $fieldPath)
            ->delete();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
