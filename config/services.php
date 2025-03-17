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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'idempotency' => [
        'key' => env('IDEMPOTENCY_KEY', 'Idempotency-Key'),
        'ttl' => env('IDEMPOTENCY_TTL', '60'),
        'enabled' => env('IDEMPOTENCY_ENABLED', false),
        'allowed_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'cache_prefix' => 'idempotency',
    ],
    'nyt' => [
        'baseUrl' => env('NYT_URL', 'https://api.nytimes.com/svc/books/v3'),
        'key' => env('NYT_KEY'),
        'secret' => env('NYT_SECRET'),
        'app_id' => env('NYT_APP_ID'),
        'enabled' => env('NYT_ENABLED', false),
    ],

];
