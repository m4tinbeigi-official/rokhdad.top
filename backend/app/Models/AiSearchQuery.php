<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSearchQuery extends Model
{
    protected $fillable = ['user_query', 'extracted_filters', 'usage_count'];
    protected $casts = [
        'extracted_filters' => 'array',
        'usage_count' => 'integer',
    ];
}
