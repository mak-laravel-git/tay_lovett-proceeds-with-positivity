<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Credentials live encrypted in the ad_providers table and are edited in the admin panel; only the
    // non-secret endpoints are configured here.
    'tapresearch' => [
        // Survey Feed API v4 (offers).
        'base_url' => env('TAPRESEARCH_BASE_URL', 'https://api.tapresearch.com/supply_api/v4'),
        // Reporting API (daily stats). A different path root from the Survey Feed API.
        'reporting_base_url' => env('TAPRESEARCH_REPORTING_BASE_URL', 'https://api.tapresearch.com/supply_api'),
        'timeout' => (int) env('TAPRESEARCH_TIMEOUT', 8),
        // Optional extra check on top of the signature: comma-separated callback source IPs. Empty = off.
        'callback_ips' => env('TAPRESEARCH_CALLBACK_IPS', ''),
    ],

];
