<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),
    ],

    'zibal' => [
        'merchant' => env('ZIBAL_MERCHANT_ID', 'zibal'),
    ],

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'line_number' => env('SMSIR_LINE_NUMBER', 30007732),
        'otp_template_id' => env('SMSIR_TEMPLATE_ID_OTP'),
    ],

    'pakett' => [
        'api_key' => env('PAKETT_API_KEY'),
        'from_email' => env('PAKETT_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@rokhdad.top')),
        'from_name' => env('PAKETT_FROM_NAME', env('MAIL_FROM_NAME', 'رخداد')),
    ],

];
