<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Her dakika çağrılır; komut kendi içinde ayarlanan aralığa göre çalışıp çalışmayacağına karar verir
Schedule::command('opsiyon:check')->everyMinute();

// Zamanlanmış SMS'leri her dakika kontrol et ve gönder
Schedule::command('sms:send-scheduled')->everyMinute();

// Zamanlanmış broadcast duyurularını her dakika kontrol et ve gönder
Schedule::command('broadcast:send-scheduled')->everyMinute();

// Local geliştirme ortamında DB boşalma riskine karşı otomatik sağlık kontrolü
Schedule::command('db:ensure-local-health --import-on-empty --min-requests=1')
    ->everyTenMinutes()
    ->environments(['local']);
