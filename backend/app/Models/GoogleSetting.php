<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_secret',
        'redirect_uri',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'created_at_timestamp',
        'analytics_property_id',
        'search_console_site_url',
    ];

    /**
     * Get the single active settings instance, or create one.
     */
    public static function getActive(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }

    /**
     * Check if client credentials are set.
     */
    public function hasCredentials(): bool
    {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Check if we are connected (have tokens).
     */
    public function isConnected(): bool
    {
        return !empty($this->access_token) || !empty($this->refresh_token);
    }

    /**
     * Check if the access token has expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->created_at_timestamp || !$this->expires_in) {
            return true;
        }

        // Add a buffer of 30 seconds
        return (time() - $this->created_at_timestamp) > ($this->expires_in - 30);
    }
}
