<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'event_id',
        'user_id',
        'ticket_number',
        'qr_code_token',
        'status',
        'price',
        'seat_info',
        'used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            $ticket->ticket_number ??= self::generateTicketNumber();
            $ticket->qr_code_token ??= self::generateQrCodeToken();
        });
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        if ($this->status !== 'issued') {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public static function generateTicketNumber(): string
    {
        return 'RKT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    public static function generateQrCodeToken(): string
    {
        return Str::random(64);
    }
}
