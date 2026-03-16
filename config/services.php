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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],

    'sms' => [
        'kno'        => env('SMS_KNO', '30415'),
        'username'   => env('SMS_USERNAME', 'aydin'),
        'password'   => env('SMS_PASSWORD'),
        'originator' => env('SMS_ORIGINATOR', 'G.Talepleri'),
        'admin_phone'   => env('SMS_ADMIN_PHONE'),
        'notify_phone'  => env('SMS_NOTIFY_PHONE'),
        'balance_url'   => env('SMS_BALANCE_URL'),
        'balance_timeout' => (int) env('SMS_BALANCE_TIMEOUT', 10),
    ],

    'whatsapp' => [
        'enabled'   => env('WHATSAPP_RESET_ENABLED', false),
        'api_url'   => env('WHATSAPP_API_URL'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'timeout'   => env('WHATSAPP_API_TIMEOUT', 10),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
