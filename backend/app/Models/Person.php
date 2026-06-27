<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'slug',
        'title',
        'bio',
        'website_url',
        'email',
        'phone_e164',
        'social_links',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class)->withPivot('role_title');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withPivot(['role_title', 'sort_order']);
    }

    /**
     * Get all events associated with this person, either directly as an instructor
     * or indirectly through their linked organizers.
     */
    public function getAllEvents()
    {
        $directEvents = $this->events;
        $organizerEvents = $this->organizers()->with('events')->get()->pluck('events')->flatten();
        
        return $directEvents->concat($organizerEvents)->unique('id')->sortByDesc('starts_at')->values();
    }
}
