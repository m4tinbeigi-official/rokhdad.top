<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleAnalyticsMetric extends Model
{
    use HasFactory;

    protected $table = 'google_analytics_metrics';

    protected $fillable = [
        'date',
        'sessions',
        'pageviews',
        'active_users',
        'bounce_rate',
        'avg_session_duration',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'pageviews' => 'integer',
        'active_users' => 'integer',
        'bounce_rate' => 'float',
        'avg_session_duration' => 'float',
    ];
}
