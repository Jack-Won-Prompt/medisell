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

    'toss' => [
        'test_mode'      => env('TOSS_TEST_MODE', true),
        'client_key'     => env('TOSS_CLIENT_KEY'),
        'secret_key'     => env('TOSS_SECRET_KEY'),
        'webhook_secret' => env('TOSS_WEBHOOK_SECRET'),
        'api_base'       => 'https://api.tosspayments.com',
        'va' => [
            'enabled'          => env('TOSS_VA_ENABLED', true),
            'bank'             => env('TOSS_VA_BANK', 'IBK'),
            'valid_hours'      => (int) env('TOSS_VA_VALID_HOURS', 72),
            'fallback_bank'    => env('TOSS_VA_FALLBACK_BANK'),
            'fallback_account' => env('TOSS_VA_FALLBACK_ACCOUNT'),
        ],
    ],

];
