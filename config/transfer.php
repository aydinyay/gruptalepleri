<?php

return [
    'provider' => env('TRANSFER_PROVIDER', 'internal'),
    'quote_ttl_minutes' => (int) env('TRANSFER_QUOTE_TTL_MINUTES', 10),

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'distance_matrix_url' => env('GOOGLE_MAPS_DISTANCE_MATRIX_URL', 'https://maps.googleapis.com/maps/api/distancematrix/json'),
        'timeout' => (int) env('GOOGLE_MAPS_TIMEOUT', 20),
    ],

    'paynkolay' => [
        'base_url' => env('PAYNKOLAY_BASE_URL', 'https://paynkolaytest.nkolayislem.com.tr'),
        'sx' => env('PAYNKOLAY_SX'),
        'sx_list' => env('PAYNKOLAY_SX_LIST'),
        'sx_cancel' => env('PAYNKOLAY_SX_CANCEL'),
        'merchant_no' => env('PAYNKOLAY_MERCHANT_NO'),
        'merchant_secret_key' => env('PAYNKOLAY_MERCHANT_SECRET_KEY'),
        'by_link_create_path' => env('PAYNKOLAY_BY_LINK_CREATE_PATH', '/Vpos/by-link-create'),
        'environment' => env('PAYNKOLAY_ENVIRONMENT', 'API'),
        'currency_number' => env('PAYNKOLAY_CURRENCY_NUMBER', '949'),
        'use_3d' => filter_var(env('PAYNKOLAY_USE_3D', true), FILTER_VALIDATE_BOOL),
        'installment' => (int) env('PAYNKOLAY_INSTALLMENT', 1),
        'timeout' => (int) env('PAYNKOLAY_TIMEOUT', 25),
        // Legacy keys kept for backward compatibility.
        'merchant_id' => env('PAYNKOLAY_MERCHANT_ID'),
        'api_key' => env('PAYNKOLAY_API_KEY'),
        'api_secret' => env('PAYNKOLAY_API_SECRET'),
        'init_path' => env('PAYNKOLAY_INIT_PATH', '/api/payment/init'),
    ],
];
