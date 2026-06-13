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

    'ai' => [
        'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8088'),
        'timeout' => env('AI_SERVICE_TIMEOUT', 120),
        /*
         * PHP max_execution_time defaults to 30s while Guzzle may wait up to `timeout`
         * per HTTP call (and batched generation can run several calls). This value is
         * applied via set_time_limit() before synchronous AI generation so the worker
         * is not killed mid-request. Raise it if you generate very large batches.
         */
        'sync_max_execution' => env('AI_GENERATION_SYNC_MAX_EXECUTION', 600),
    ],

];
