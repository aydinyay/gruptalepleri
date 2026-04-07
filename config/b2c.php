<?php

return [

    /*
    |--------------------------------------------------------------------------
    | B2C Domain
    |--------------------------------------------------------------------------
    | gruprezervasyonlari.com vitrinin alan adı. DomainRouter middleware
    | bu değeri kullanarak gelen isteğin B2C domaine mi ait olduğunu belirler.
    */
    'domain' => env('B2C_DOMAIN', 'gruprezervasyonlari.com'),

    /*
    |--------------------------------------------------------------------------
    | B2C Session Cookie Adı
    |--------------------------------------------------------------------------
    | B2B tarafın session cookie'sinden farklı isim kullanılarak
    | iki domain arasında session karışması önlenir.
    */
    'session_cookie' => env('B2C_SESSION_COOKIE', 'gruprezervasyonlari-session'),

    /*
    |--------------------------------------------------------------------------
    | B2C Auth Guard
    |--------------------------------------------------------------------------
    */
    'auth_guard' => 'b2c',

    /*
    |--------------------------------------------------------------------------
    | Ürün Tipi Bazlı Markup Oranları
    |--------------------------------------------------------------------------
    | B2cPricingService bu değerleri kullanır.
    | Net tedarik maliyetine eklenen Grup Talepleri marjı (ondalık, ör: 0.15 = %15)
    */
    'markup' => [
        'transfer'  => env('B2C_MARKUP_TRANSFER',  0.20),
        'charter'   => env('B2C_MARKUP_CHARTER',   0.15),
        'leisure'   => env('B2C_MARKUP_LEISURE',   0.18),
        'tour'      => env('B2C_MARKUP_TOUR',      0.20),
        'hotel'     => env('B2C_MARKUP_HOTEL',     0.15),
        'visa'      => env('B2C_MARKUP_VISA',      0.25),
        'other'     => env('B2C_MARKUP_OTHER',     0.20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paynkolay Callback URL'leri (B2C)
    |--------------------------------------------------------------------------
    | Paynkolay merchant panelinde bu URL'ler whitelist'e eklenmelidir.
    */
    'payment_success_url' => env('B2C_PAYMENT_SUCCESS_URL', 'https://gruprezervasyonlari.com/odeme/basarili'),
    'payment_fail_url'    => env('B2C_PAYMENT_FAIL_URL',    'https://gruprezervasyonlari.com/odeme/basarisiz'),

    /*
    |--------------------------------------------------------------------------
    | Tedarikçi Başvuru Yönlendirme URL'si
    |--------------------------------------------------------------------------
    | "Tedarikçi ol" butonları bu URL'ye yönlendirir (gruptalepleri.com tarafı)
    */
    'supplier_apply_redirect' => env('B2C_SUPPLIER_REDIRECT', 'https://gruptalepleri.com/acente/kayit'),

    /*
    |--------------------------------------------------------------------------
    | B2C Mail Gönderen Adres
    |--------------------------------------------------------------------------
    */
    'mail_from_address' => env('B2C_MAIL_FROM_ADDRESS', 'noreply@gruprezervasyonlari.com'),
    'mail_from_name'    => env('B2C_MAIL_FROM_NAME',    'Grup Rezervasyonları'),

];
