<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerFieldLock extends Model
{
    protected $fillable = [
        'organizer_id',
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

    public static function lock(Organizer $organizer, string $fieldPath, ?User $user = null, ?string $reason = null): self
    {
        return self::query()->updateOrCreate(
            [
                'organizer_id' => $organizer->id,
                'field_path' => $fieldPath,
            ],
            [
                'locked_by_user_id' => $user?->id,
                'reason' => $reason,
                'locked_at' => now(),
            ]
        );
    }

    public static function unlock(Organizer $organizer, string $fieldPath): void
    {
        self::query()
            ->where('organizer_id', $organizer->id)
            ->where('field_path', $fieldPath)
            ->delete();
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
