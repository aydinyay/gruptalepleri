<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') && strcasecmp(config('app.name'), 'Laravel') !== 0 ? config('app.name') : 'GrupTalepleri' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <div class="mt-6 text-center" style="font-size:0.72rem;color:#9ca3af;line-height:1.8;">
                <div>Grup Talepleri Turizm San. ve Tic. Ltd. Şti.</div>
                <div>İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli/İstanbul &nbsp;·&nbsp; 0535 415 47 99</div>
                <div>Beyoğlu VD · Vergi No: 4110477529 &nbsp;·&nbsp; TÜRSAB A Grubu Belge No: 12572</div>
            </div>
        </div>
    </body>
</html>
