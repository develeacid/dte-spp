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

    'geobase' => [
        'url' => env('GEOBASE_API_URL', 'http://localhost:8081/api/v1/geobase'),
        'token' => env('GEOBASE_API_TOKEN'),
        'webhook_secret' => env('GEOBASE_WEBHOOK_SECRET'),
        'timeout' => (int) env('GEOBASE_API_TIMEOUT', 15),
        'retry_times' => (int) env('GEOBASE_API_RETRY_TIMES', 3),
        'retry_sleep' => (int) env('GEOBASE_API_RETRY_SLEEP', 500),
        'delivery_retention_days' => env('GEOBASE_DELIVERY_RETENTION_DAYS', 90),
    ],

    'embedding' => [
        'api_key' => env('EMBEDDING_API_KEY', ''),
        'url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
    ],

];
