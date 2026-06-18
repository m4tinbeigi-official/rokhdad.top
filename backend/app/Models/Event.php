<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'city_id',
        'organizer_id',
        'title',
        'slug',
        'summary',
        'description',
        'starts_at',
        'ends_at',
        'timezone',
        'event_type',
        'status',
        'visibility',
        'series_slug',
        'recurrence_rule',
        'recurrence_ends_at',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'online_url',
        'canonical_url',
        'metadata',
        'is_featured',
        'is_internal',
        'registration_open',
        'capacity',
        'registration_starts_at',
        'registration_ends_at',
        'requires_approval',
        'registration_instructions',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
            'is_featured' => 'boolean',
            'is_internal' => 'boolean',
            'registration_open' => 'boolean',
            'capacity' => 'integer',
            'registration_starts_at' => 'datetime',
            'registration_ends_at' => 'datetime',
            'requires_approval' => 'boolean',
            'recurrence_ends_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot(['role_title', 'sort_order']);
    }

    public function sourceAttributions(): HasMany
    {
        return $this->hasMany(EventSourceAttribution::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class);
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(EventPromoCode::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function fieldOverrides(): HasMany
    {
        return $this->hasMany(EventFieldOverride::class);
    }

    public function fieldLocks(): HasMany
    {
        return $this->hasMany(EventFieldLock::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function savedEvents(): HasMany
    {
        return $this->hasMany(SavedEvent::class);
    }

    public function isFieldLocked(string $fieldPath): bool
    {
        return $this->fieldLocks()->where('field_path', $fieldPath)->exists();
    }

    public function applyFieldOverride(
        string $fieldPath,
        mixed $value,
        ?User $user = null,
        ?string $sourceKey = null,
        bool $lockField = true,
        ?string $reason = null,
    ): EventFieldOverride {
        return EventFieldOverride::apply($this, $fieldPath, $value, $user, $sourceKey, $lockField, $reason);
    }
}
