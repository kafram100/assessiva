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

    'paystack' => [
        'secret' => env('PAYSTACK_SECRET'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'moolre' => [
        'base_url' => env('MOOLRE_BASE_URL', 'https://api.moolre.com'),
        'username' => env('MOOLRE_USERNAME'),
        'api_key' => env('MOOLRE_API_KEY'),
        'public_key' => env('MOOLRE_PUBLIC_KEY'),
        'account_number' => env('MOOLRE_ACCOUNT_NUMBER'),
    ],

    'ghdataconnect' => [
        'base_url' => env('GHDATACONNECT_BASE_URL', 'https://ghdataconnect.com/api'),
        'api_key' => env('GHDATACONNECT_API_KEY'),
    ],

    'dataflow' => [
        'base_url' => env('DATAFLOW_BASE_URL', 'https://dataflowghana.com/api/v1'),
        'email' => env('DATAFLOW_EMAIL'),
        'password' => env('DATAFLOW_PASSWORD'),
        'api_token' => env('DATAFLOW_API_TOKEN'),
    ],

    'databundleshub' => [
        'base_url' => env('DATABUNDLESHUB_BASE_URL', 'https://www.databundleshub.com'),
        'api_key' => env('DATABUNDLESHUB_API_KEY'),
    ],

];
