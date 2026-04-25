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
Schedule::call(fn() => \Artisan::call('opsiyon:check'))->everyMinute();

// Zamanlanmış email kampanyası — Schedule::call kullanılıyor, proc_open gerektirmez
Schedule::call(function () {
    Artisan::call('kampanya:email-otomatik');
    $out = trim(Artisan::output());
    file_put_contents(
        storage_path('app/kampanya-email-out.txt'),
        date('Y-m-d H:i:s') . "\n" . ($out ?: '(çıktı yok)') . "\n---\n",
        FILE_APPEND
    );
})->everyFiveMinutes()->name('kampanya-email-otomatik')->environments(['production']);

// Zamanlanmış SMS kampanyası
Schedule::call(function () {
    Artisan::call('kampanya:sms-otomatik');
})->everyFiveMinutes()->name('kampanya-sms-otomatik')->environments(['production']);

// Kampanya sistemi — DB'deki aktif kampanyaları çalıştırır (email + SMS)
Schedule::call(function () {
    Artisan::call('kampanya:calistir');
    $out = trim(Artisan::output());
    if ($out) {
        file_put_contents(
            storage_path('app/kampanya-calistir-out.txt'),
            date('Y-m-d H:i:s') . "\n" . $out . "\n---\n",
            FILE_APPEND
        );
    }
})->everyFiveMinutes()->name('kampanya-calistir')->environments(['production']);

// Zamanlanmış SMS'leri her dakika kontrol et ve gönder
Schedule::call(fn() => \Artisan::call('sms:send-scheduled'))->everyMinute();

// Zamanlanmış broadcast duyurularını her dakika kontrol et ve gönder
Schedule::call(fn() => \Artisan::call('broadcast:send-scheduled'))->everyMinute();

// Bakanlık ile haftalık tam senkronizasyon — Her Pazar 02:00
// Her çalışmada 50 belge_no işler; tüm listeyi taramak için birden fazla
// schedule:run çevrimi gerekir (cron her dakika tetikliyor, withoutOverlapping korur)
Schedule::call(fn() => \Artisan::call('acenteler:sync', ['--batch' => '50', '--delay' => '300']))
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->name('acenteler-sync')
    ->withoutOverlapping(120)
    ->environments(['production']);

// B2C fiyat alarmlarını günde iki kez kontrol et (09:00 ve 18:00)
Schedule::call(fn() => \Artisan::call('gr:check-price-alerts'))
    ->twiceDaily(9, 18)
    ->name('gr-price-alerts')
    ->withoutOverlapping(30)
    ->environments(['production']);

// İptal bekleyen sigorta poliçelerini her 30 dakikada PAO-Net'e sor
Schedule::call(function () {
    if (empty(config('services.paonet.api_key'))) {
        return;
    }
    $bekleyenler = \App\Models\SigortaPolice::where('durum', 'iptal_bekliyor')
        ->whereNotNull('police_no')
        ->get();
    foreach ($bekleyenler as $police) {
        try {
            $svc   = app(\App\Services\PaoNetService::class);
            $sonuc = $svc->iptalKontrol($police->police_no);
            $durum = $sonuc['IptalDurum'] ?? $sonuc['iptalDurum'] ?? '';
            if (in_array(strtolower($durum), ['iptal', 'cancelled', 'onaylandi', '1', 'true'])) {
                $police->update(['durum' => 'iptal']);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('sigorta.iptal_kontrol hata: ' . $e->getMessage(), ['police_id' => $police->id]);
        }
    }
})->everyThirtyMinutes()->name('sigorta-iptal-kontrol')->withoutOverlapping(20)->environments(['production']);

// Local geliştirme ortamında DB boşalma riskine karşı otomatik sağlık kontrolü
Schedule::command('db:ensure-local-health --import-on-empty --min-requests=1')
    ->everyTenMinutes()
    ->environments(['local']);
