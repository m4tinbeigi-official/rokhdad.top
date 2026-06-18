<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'parent_id',
        'body',
        'status',
        'is_pinned',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved', 'approved_at' => now()]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
