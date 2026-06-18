<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'code_hash',
        'purpose',
        'used',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function markUsed(): void
    {
        $this->update(['used' => true]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
