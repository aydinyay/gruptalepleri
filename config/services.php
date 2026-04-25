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
        'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.0-flash'),
        'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.0-flash-preview-image-generation'),
        'image_model_fallbacks' => env('GEMINI_IMAGE_MODEL_FALLBACKS', 'imagen-3.0-generate-002'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],

    'sms' => [
        'kno'        => env('SMS_KNO', '30415'),
        'username'   => env('SMS_USERNAME', 'aydin'),
        'password'   => env('SMS_PASSWORD'),
        'originator' => env('SMS_ORIGINATOR', 'G.Talepleri'),
        'admin_phone'   => env('SMS_ADMIN_PHONE'),
        'notify_phone'  => env('SMS_NOTIFY_PHONE'),
        'balance_url'   => env('SMS_BALANCE_URL'),
        'delivery_report_url' => env('SMS_DELIVERY_REPORT_URL', 'http://www.toplusmsyolla.com/smsrapor.php'),
        'originator_list_url' => env('SMS_ORIGINATOR_LIST_URL', 'http://www.toplusmsyolla.com/orjinatorliste.php'),
        'strict_originator_check' => (bool) env('SMS_STRICT_ORIGINATOR_CHECK', false),
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

    'airport_transfer_portal' => [
        'enabled' => (bool) env('AIRPORT_TRANSFER_PORTAL_ENABLED', true),
        'api_base_url' => rtrim((string) env('AIRPORT_TRANSFER_PORTAL_API_BASE_URL', 'https://www.airporttransferportal.com'), '/'),
        'partner_code' => env('AIRPORT_TRANSFER_PORTAL_PARTNER_CODE'),
        'api_key' => env('AIRPORT_TRANSFER_PORTAL_API_KEY'),
        'airports_cache_seconds' => (int) env('AIRPORT_TRANSFER_PORTAL_AIRPORTS_CACHE_SECONDS', 21600),
        'zones_cache_seconds' => (int) env('AIRPORT_TRANSFER_PORTAL_ZONES_CACHE_SECONDS', 1800),
    ],

    'tcmb' => [
        'url' => env('TCMB_EXCHANGE_RATE_URL', 'https://www.tcmb.gov.tr/kurlar/today.xml'),
        'cache_seconds' => (int) env('TCMB_EXCHANGE_RATE_CACHE_SECONDS', 600),
        'timeout' => (int) env('TCMB_EXCHANGE_RATE_TIMEOUT', 15),
    ],

    'paonet' => [
        'api_key'    => env('PAONET_API_KEY'),
        'ws_url'     => env('PAONET_WS_URL', 'https://app.sigortambudur.com:909'),
        'server_ip'  => env('PAONET_SERVER_IP'),
        'timeout'    => (int) env('PAONET_TIMEOUT', 30),
    ],

];
