<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'favorite_category_ids',
        'favorite_city_ids',
        'preferred_event_type',
        'notification_channel',
        'notify_new_events',
        'notify_reminders',
    ];

    protected function casts(): array
    {
        return [
            'favorite_category_ids' => 'array',
            'favorite_city_ids' => 'array',
            'notify_new_events' => 'boolean',
            'notify_reminders' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
