<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HermesError extends Model
{
    protected $fillable = [
        'type',
        'message',
        'trace',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
