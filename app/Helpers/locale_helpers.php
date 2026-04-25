<?php

/**
 * GR Locale Helpers
 *
 * lroute()       — locale-prefixed route URL (B2C içi navigasyon için)
 * gr_locale()    — mevcut locale kodu
 * gr_locale_url() — verilen locale için mevcut sayfanın URL'i
 * gr_is_rtl()    — Arapça / Farsça kontrolü
 */

if (! function_exists('lroute')) {
    function lroute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $url    = route($name, $parameters, $absolute);
        $locale = app()->getLocale();

        if ($locale === 'tr') {
            return $url;
        }

        $b2cBase = 'https://' . rtrim(config('b2c.domain', 'gruprezervasyonlari.com'), '/');

        if ($absolute && str_starts_with($url, $b2cBase)) {
            return $b2cBase . '/' . $locale . substr($url, strlen($b2cBase));
        }

        return $url;
    }
}

if (! function_exists('gr_locale')) {
    function gr_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('gr_is_rtl')) {
    function gr_is_rtl(): bool
    {
        return in_array(app()->getLocale(), ['ar', 'fa']);
    }
}

if (! function_exists('gr_locale_url')) {
    /**
     * Mevcut sayfanın belirtilen locale'deki URL'ini döndürür.
     * Dil değiştirici linkleri için kullanılır.
     */
    function gr_locale_url(string $targetLocale): string
    {
        $domain = config('b2c.domain', 'gruprezervasyonlari.com');
        $path   = request()->path();

        // Mevcut locale prefix'ini temizle
        $supported = \App\Http\Middleware\SetLocale::SUPPORTED;
        $segments  = explode('/', $path, 2);
        if (in_array($segments[0] ?? '', $supported)) {
            $path = $segments[1] ?? '';
        }

        $query = request()->getQueryString();
        $qs    = $query ? '?' . $query : '';

        if ($targetLocale === 'tr') {
            return 'https://' . $domain . '/' . $path . $qs;
        }

        return 'https://' . $domain . '/' . $targetLocale . '/' . $path . $qs;
    }
}

if (! function_exists('gr_locale_name')) {
    function gr_locale_name(string $locale): string
    {
        return match($locale) {
            'tr' => 'Türkçe',
            'en' => 'English',
            'ar' => 'العربية',
            'ru' => 'Русский',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'fa' => 'فارسی',
            default => strtoupper($locale),
        };
    }
}

if (! function_exists('gr_locale_flag')) {
    function gr_locale_flag(string $locale): string
    {
        return match($locale) {
            'tr' => '🇹🇷',
            'en' => '🇬🇧',
            'ar' => '🇸🇦',
            'ru' => '🇷🇺',
            'de' => '🇩🇪',
            'fr' => '🇫🇷',
            'fa' => '🇮🇷',
            default => '🌐',
        };
    }
}
