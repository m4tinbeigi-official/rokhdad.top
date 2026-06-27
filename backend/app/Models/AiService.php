<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiService extends Model
{
    /** @use HasFactory<\Database\Factories\AiServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'api_key',
        'model_name',
        'is_active',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'api_key' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AiService $aiService) {
            if ($aiService->is_active) {
                // If this service is being set to active, deactivate all others
                static::where('id', '!=', $aiService->id)->update(['is_active' => false]);
            }
        });
    }
}
