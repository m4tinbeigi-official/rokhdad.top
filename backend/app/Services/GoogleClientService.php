<?php

namespace App\Services;

use App\Models\GoogleSetting;
use Google\Client;

class GoogleClientService
{
    protected ?Client $client = null;

    /**
     * Get or build the Google Client.
     */
    public function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $settings = GoogleSetting::getActive();

        $client = new Client();
        
        // Load credentials from database settings, fallback to config
        $clientId = $settings->client_id ?: config('google.client_id');
        $clientSecret = $settings->client_secret ?: config('google.client_secret');
        $redirectUri = $settings->redirect_uri ?: config('google.redirect_uri');

        if ($clientId) {
            $client->setClientId($clientId);
        }
        if ($clientSecret) {
            $client->setClientSecret($clientSecret);
        }
        if ($redirectUri) {
            $client->setRedirectUri($redirectUri);
        }

        // We request offline access to get a refresh token
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force'); // Required to ensure we get a refresh token every time

        // Scopes
        $client->setScopes([
            'https://www.googleapis.com/auth/analytics.readonly',
            'https://www.googleapis.com/auth/webmasters.readonly',
        ]);

        // Load token from DB if exists
        if ($settings->isConnected()) {
            $token = [
                'access_token' => $settings->access_token,
                'refresh_token' => $settings->refresh_token,
                'token_type' => $settings->token_type,
                'expires_in' => $settings->expires_in,
                'created' => $settings->created_at_timestamp,
            ];

            $client->setAccessToken($token);

            // Check expiration and refresh if needed
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    
                    if (isset($newToken['error'])) {
                        // Handle auth failure / token revoked
                        logger()->error('Google API token refresh failed: ' . json_encode($newToken));
                    } else {
                        // Save refreshed token
                        $settings->update([
                            'access_token' => $newToken['access_token'] ?? $settings->access_token,
                            'refresh_token' => $newToken['refresh_token'] ?? $settings->refresh_token, // might not always return refresh_token on refresh
                            'token_type' => $newToken['token_type'] ?? $settings->token_type,
                            'expires_in' => $newToken['expires_in'] ?? $settings->expires_in,
                            'created_at_timestamp' => $newToken['created'] ?? time(),
                        ]);
                        $client->setAccessToken($newToken);
                    }
                }
            }
        }

        $this->client = $client;
        return $client;
    }

    /**
     * Generate the authorization URL.
     */
    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    /**
     * Authenticate and save tokens.
     */
    public function authenticate(string $code): array
    {
        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \Exception('Failed to authenticate with Google: ' . ($token['error_description'] ?? $token['error']));
        }

        $settings = GoogleSetting::getActive();
        $settings->update([
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? $settings->refresh_token, // ensure we don't overwrite with null if missing
            'token_type' => $token['token_type'] ?? null,
            'expires_in' => $token['expires_in'] ?? null,
            'created_at_timestamp' => $token['created'] ?? time(),
        ]);

        return $token;
    }
}
