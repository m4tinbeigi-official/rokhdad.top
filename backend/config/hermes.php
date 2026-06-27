<?php

return [
    // Master switch. Hermes is developer/admin tooling, so it is OFF by default
    // and only auto-enabled in local. Set HERMES_ENABLED=true to use it elsewhere.
    'enabled' => (bool) env('HERMES_ENABLED', env('APP_ENV') === 'local'),

    // Hermes endpoint URL (e.g., http://localhost:8000/api)
    'endpoint' => env('HERMES_ENDPOINT', 'http://localhost:8000/api'),

    // Secret API key used for authentication with Hermes
    'api_key' => env('HERMES_API_KEY'),
];
