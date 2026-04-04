<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron heartbeat — her dakika çalışarak son tetiklenme zamanını yazar
Schedule::call(function () {
    $file = storage_path('app/cron_heartbeat.txt');
    file_put_contents($file, now()->toISOString());
    \App\Models\SistemAyar::set('cron_heartbeat', now()->toISOString());
})->everyMinute()->name('cron-heartbeat')->withoutOverlapping();

// Her dakika çağrılır; komut kendi içinde ayarlanan aralığa göre çalışıp çalışmayacağına karar verir
Schedule::command('opsiyon:check')->everyMinute();

// Zamanlanmış email kampanyası — Schedule::call kullanılıyor, proc_open gerektirmez
Schedule::call(function () {
    Artisan::call('kampanya:email-otomatik');
})->everyFiveMinutes()->name('kampanya-email-otomatik')->environments(['production']);

// Zamanlanmış SMS kampanyası
Schedule::call(function () {
    Artisan::call('kampanya:sms-otomatik');
})->everyFiveMinutes()->name('kampanya-sms-otomatik')->environments(['production']);

// Zamanlanmış SMS'leri her dakika kontrol et ve gönder
Schedule::command('sms:send-scheduled')->everyMinute();

// Zamanlanmış broadcast duyurularını her dakika kontrol et ve gönder
Schedule::command('broadcast:send-scheduled')->everyMinute();

// Bakanlık ile haftalık tam senkronizasyon — Her Pazar 02:00
// Her çalışmada 50 belge_no işler; tüm listeyi taramak için birden fazla
// schedule:run çevrimi gerekir (cron her dakika tetikliyor, withoutOverlapping korur)
Schedule::command('acenteler:sync', ['--batch' => '50', '--delay' => '300'])
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping(120)
    ->runInBackground()
    ->environments(['production']);

// Local geliştirme ortamında DB boşalma riskine karşı otomatik sağlık kontrolü
Schedule::command('db:ensure-local-health --import-on-empty --min-requests=1')
    ->everyTenMinutes()
    ->environments(['local']);
