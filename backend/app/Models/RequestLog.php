<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    protected $fillable = [
        'event_source_id',
        'url',
        'method',
        'status_code',
        'response_body',
        'error_message',
        'used_proxy',
        'proxy_url',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'used_proxy'  => 'boolean',
            'status_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function eventSource(): BelongsTo
    {
        return $this->belongsTo(EventSource::class);
    }

    /** آیا درخواست با موفقیت پاسخ گرفته؟ */
    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    /** آیا درخواست مسدود شده (403/429/503)؟ */
    public function isBlocked(): bool
    {
        return in_array($this->status_code, [403, 429, 503]);
    }
}
