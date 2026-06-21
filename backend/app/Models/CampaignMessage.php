<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMessage extends Model
{
    protected $fillable = [
        'campaign_id', 'type', 'subject', 'body', 
        'send_at', 'status', 'sent_count'
    ];

    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function schedule(string $type, string $subject, string $body): void
    {
        $this->update([
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'status' => 'scheduled',
        ]);
    }

    public function send(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_count' => $this->sent_count + 1,
        ]);
    }
}
