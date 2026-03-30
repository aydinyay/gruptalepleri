<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow, noarchive">
        <meta name="googlebot" content="noindex, nofollow, noarchive">

        <title>{{ config('app.name') && strcasecmp(config('app.name'), 'Laravel') !== 0 ? config('app.name') : 'GrupTalepleri' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div style="background:#1a1a2e;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <a href="/" style="color:#fff;font-weight:700;font-size:15px;text-decoration:none;letter-spacing:-0.2px;">
                ✈ Grup<span style="color:#e8a020;">Talepleri</span>.com
            </a>
            <a href="/acente-tanitim.html"
               style="color:rgba(255,255,255,0.75);font-size:13px;border:1px solid rgba(255,255,255,0.25);padding:5px 14px;border-radius:4px;text-decoration:none;white-space:nowrap;">
                Platform Tanıtımı →
            </a>
        </div>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            @php
                $sa = fn($k,$d='') => (string)\App\Models\SistemAyar::get($k,$d);
            @endphp
            <div class="mt-6 text-center" style="font-size:0.72rem;color:#9ca3af;line-height:1.8;">
                <div>{{ $sa('sirket_unvan','Grup Talepleri Turizm San. ve Tic. Ltd. Şti.') }}</div>
                <div>{{ $sa('sirket_adres','İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli/İstanbul') }} &nbsp;·&nbsp; {{ $sa('sirket_telefon','0535 415 47 99') }}</div>
                <div>{{ $sa('sirket_vergi_dairesi','Beyoğlu VD') }} · Vergi No: {{ $sa('sirket_vkn','4110477529') }} &nbsp;·&nbsp; TÜRSAB {{ $sa('sirket_tursab_grup','A') }} Grubu Belge No: {{ $sa('sirket_tursab_no','12572') }}</div>
            </div>
        </div>
    </body>
</html>
