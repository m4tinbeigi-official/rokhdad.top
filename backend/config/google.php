<?php
return [
    // Google API credentials
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    // Scopes needed for Analytics and Search Console
    'analytics_scopes' => [
        'https://www.googleapis.com/auth/analytics.readonly',
    ],
    'search_console_scopes' => [
        'https://www.googleapis.com/auth/webmasters',
    ],
    // Redirect URI for OAuth flow (must match the one configured in Google Cloud console)
    'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI'),
];
