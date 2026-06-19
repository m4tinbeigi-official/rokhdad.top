<?php

namespace App\Models;

use Database\Factories\OrganizerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\WebhookSubscription;

class Organizer extends Model
{
    /** @use HasFactory<OrganizerFactory> */
    use HasFactory;

    protected $fillable = [
        'source_key',
        'external_id',
        'city_id',
        'name',
        'slug',
        'description',
        'website_url',
        'email',
        'phone_e164',
        'logo_url',
        'cover_url',
        'social_links',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot('role_title');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'accepted_at'])
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }
}
