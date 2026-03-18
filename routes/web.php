<?php

use App\Http\Controllers\AirportController;
use App\Http\Controllers\ProfileController;
use App\Models\Airline;
use App\Models\Airport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

// Ana sayfa — giriş yapılmışsa dashboard'a, yapmamışsa welcome'a
Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'superadmin') return redirect()->route('superadmin.dashboard');
        if ($role === 'admin') return redirect()->route('admin.dashboard');
        return redirect()->route('acente.dashboard');
    }

    $stats = Cache::remember('welcome_stats', 3600, function () {
        if (! Schema::hasTable('flight_segments') || ! Schema::hasTable('requests') || ! Schema::hasTable('airports')) {
            return [
                'toplam_grup' => 0,
                'toplam_yolcu' => 0,
                'toplam_ulke' => 0,
                'toplam_destinasyon' => 0,
                'toplam_ucus' => 0,
                'airports' => 0,
                'airlines' => 0,
                'countries' => 0,
                'large_airports' => 0,
            ];
        }

        $iatas = DB::table('flight_segments')
            ->selectRaw('from_iata as iata')->whereNotNull('from_iata')->where('from_iata', '!=', '')
            ->union(DB::table('flight_segments')->selectRaw('to_iata as iata')->whereNotNull('to_iata')->where('to_iata', '!=', ''))
            ->get()->pluck('iata')->unique();

        return [
            'toplam_grup' => \App\Models\Request::count(),
            'toplam_yolcu' => (int) \App\Models\Request::sum('pax_total'),
            'toplam_ulke' => \App\Models\Airport::whereIn('iata', $iatas->values())->distinct('country_code')->count('country_code'),
            'toplam_destinasyon' => $iatas->count(),
            'toplam_ucus' => DB::table('flight_segments')->count(),
            'airports' => \App\Models\Airport::count(),
            'airlines' => \App\Models\Airline::count(),
            'countries' => \App\Models\Airport::distinct('country_code')->count('country_code'),
            'large_airports' => \App\Models\Airport::where('type', 'large_airport')->count(),
        ];
    });

    return view('welcome', compact('stats'));
});

// SEO odakli landing sayfasi (public)
Route::get('/grup-talepleri', function () {
    return view('marketing.grup-talepleri');
})->name('marketing.grup-talepleri');

// Air Charter public lead sayfalari
Route::get('/private-jet-kiralama', [\App\Http\Controllers\Marketing\CharterLeadController::class, 'jet'])->name('charter.public.jet');
Route::get('/helikopter-kiralama', [\App\Http\Controllers\Marketing\CharterLeadController::class, 'helicopter'])->name('charter.public.helicopter');
Route::get('/charter-ucak', [\App\Http\Controllers\Marketing\CharterLeadController::class, 'airliner'])->name('charter.public.airliner');
Route::post('/charter-talep', [\App\Http\Controllers\Marketing\CharterLeadController::class, 'store'])->name('charter.public.store');

Route::get('/dashboard', function () {
    $user = auth()->user();
    return match($user->role) {
        'superadmin' => redirect()->route('superadmin.dashboard'),
        'admin'      => redirect()->route('admin.dashboard'),
        default      => redirect()->route('acente.dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

// Kısa talep linki: SMS/email içinde paylaşılabilir.
Route::middleware(['auth'])->get('/t/{gtpnr}', function (string $gtpnr) {
    return redirect()->route('admin.requests.show', $gtpnr);
})->name('requests.short');

// Havalimanı & havayolu arama (giriş yapmış tüm roller)
Route::middleware(['auth'])->group(function () {
    Route::get('/airports/search', [AirportController::class, 'search'])->name('airports.search');
    Route::get('/airlines/search', [AirportController::class, 'airlineSearch'])->name('airlines.search');
});

// Superadmin
Route::middleware(['auth'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('superadmin.dashboard');
    })->name('dashboard');

    Route::get('/charter', [\App\Http\Controllers\Admin\CharterController::class, 'index'])->name('charter.index');
    Route::get('/charter/{charterRequest}', [\App\Http\Controllers\Admin\CharterController::class, 'show'])->name('charter.show');
    Route::post('/charter/{charterRequest}/rfq', [\App\Http\Controllers\Admin\CharterController::class, 'sendRfq'])->name('charter.send-rfq');
    Route::post('/charter/{charterRequest}/supplier-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'storeSupplierQuote'])->name('charter.supplier-quotes.store');
    Route::post('/charter/{charterRequest}/sales-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'createSalesQuote'])->name('charter.sales-quotes.store');
    Route::patch('/charter/{charterRequest}/extras/{extra}', [\App\Http\Controllers\Admin\CharterController::class, 'priceExtra'])->name('charter.extras.price');
    Route::post('/charter/bookings/{booking}/payments', [\App\Http\Controllers\Admin\CharterController::class, 'storePayment'])->name('charter.payments.store');
    Route::post('/charter/payments/{payment}/approve', [\App\Http\Controllers\Admin\CharterController::class, 'approvePayment'])->name('charter.payments.approve');
    Route::post('/charter/payments/{payment}/reject', [\App\Http\Controllers\Admin\CharterController::class, 'rejectPayment'])->name('charter.payments.reject');
    Route::post('/charter/bookings/{booking}/start-operation', [\App\Http\Controllers\Admin\CharterController::class, 'startOperation'])->name('charter.bookings.start-operation');

    Route::get('/site-ayarlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'siteAyarlari'])->name('site.ayarlar');
    Route::post('/ai-kutlama/ayar', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaAyarGuncelle'])->name('ai-kutlama.ayar');
    Route::post('/ai-kutlama/tara', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaTara'])->name('ai-kutlama.tara');
    Route::post('/ai-kutlama/manual', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaManuelOlustur'])->name('ai-kutlama.manual');
    Route::post('/ai-kutlama/{campaign}/yeniden-uret', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaYenidenUret'])->name('ai-kutlama.yeniden-uret');
    Route::patch('/ai-kutlama/{campaign}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaGuncelle'])->name('ai-kutlama.guncelle');
    Route::post('/ai-kutlama/{campaign}/yayinla', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaYayinla'])->name('ai-kutlama.yayinla');
    Route::post('/ai-kutlama/{campaign}/durdur', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaDurdur'])->name('ai-kutlama.durdur');
    Route::delete('/ai-kutlama/{campaign}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaIstenmeyen'])->name('ai-kutlama.istenmeyen');
    Route::post('/ai-kutlama/{campaign}/geri-al', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaGeriAl'])->name('ai-kutlama.geri-al');
    Route::get('/ai-kutlama/{campaign}/onizleme', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aiKutlamaOnizleme'])->name('ai-kutlama.onizleme');
    Route::get('/hizli-yanitla', [\App\Http\Controllers\Admin\QuickReplyController::class, 'index'])->name('quick-reply.index');
    Route::post('/hizli-yanitla/parse', [\App\Http\Controllers\Admin\QuickReplyController::class, 'parse'])->name('quick-reply.parse');
    Route::patch('/hizli-yanitla/{session}', [\App\Http\Controllers\Admin\QuickReplyController::class, 'saveReview'])->name('quick-reply.save-review');
    Route::post('/hizli-yanitla/{session}/onayla', [\App\Http\Controllers\Admin\QuickReplyController::class, 'confirm'])->name('quick-reply.confirm');
    Route::get('/hizli-yanitla/acente-ara', [\App\Http\Controllers\Admin\QuickReplyController::class, 'agencySearch'])->name('quick-reply.agency-search');

    // Acenteler
    Route::get('/acenteler', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteler'])->name('acenteler');
    Route::post('/acenteler/{agency}/toggle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteToggle'])->name('acenteler.toggle');
    Route::post('/acenteler/{agency}/rol', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteRolDegistir'])->name('acenteler.rol');
    Route::patch('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteGuncelle'])->name('acenteler.guncelle');
    Route::delete('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteSil'])->name('acenteler.sil');
    Route::post('/acenteler/{agency}/iade-badge', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteIadeBadgeToggle'])->name('acenteler.iade-badge');
    Route::post('/acenteler/{agency}/broadcast-yetki', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteBroadcastYetkiToggle'])->name('acenteler.broadcast-yetki');

    // Broadcast geçmişi & yetki yönetimi
    Route::get('/broadcast-gecmisi', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'broadcastGecmisi'])->name('broadcast.gecmisi');
    Route::post('/broadcast-yetki/{user}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'broadcastYetkiToggleById'])->name('broadcast.yetki');
    Route::delete('/broadcast-gecmisi/{broadcast}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'broadcastSil'])->name('broadcast.sil');
    Route::post('/broadcast-gecmisi/hepsini-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'broadcastHepsiniSil'])->name('broadcast.hepsini-sil');

    // SMS Ayarları
    Route::get('/sms-ayarlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsAyarlari'])->name('sms.ayarlar');
    Route::post('/bildirim-sistemleri', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimSistemleriGuncelle'])->name('bildirim.sistemleri');
    Route::post('/sms-ayarlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsAyarEkle'])->name('sms.ekle');
    Route::post('/sms-ayarlari/{ayar}/toggle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsAyarToggle'])->name('sms.toggle');
    Route::patch('/sms-ayarlari/{ayar}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsAyarGuncelle'])->name('sms.guncelle');
    Route::delete('/sms-ayarlari/{ayar}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsAyarSil'])->name('sms.sil');

    // SMS Raporlar
    Route::get('/sms-raporlar', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsRaporlar'])->name('sms.raporlar');
    Route::post('/sms-raporlar/durum-guncelle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsTeslimDurumlariGuncelle'])->name('sms.log.durum-guncelle');
    Route::delete('/sms-raporlar/{log}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsLogSil'])->name('sms.log.sil');
    Route::post('/sms-raporlar/hepsini-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsLogHepsiniSil'])->name('sms.log.hepsini-sil');

    // Bildirim silme (bell)
    Route::delete('/bildirimler/{bildirim}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimSil'])->name('bildirim.sil');
    Route::post('/bildirimler/hepsini-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimHepsiniSil'])->name('bildirim.hepsini-sil');
    Route::delete('/bildirimler/{bildirim}/herkesten-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimHerkestenSil'])->name('bildirim.herkesten-sil');
    Route::post('/bildirimler/secilenleri-herkesten-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimSecilenleriHerkestenSil'])->name('bildirim.secilenleri-herkesten-sil');
    Route::post('/bildirimler/hepsini-herkesten-sil', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'bildirimHepsiniHerkestenSil'])->name('bildirim.hepsini-herkesten-sil');

    // Scheduler aralığı
    Route::post('/scheduler-aralik', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'schedulerAralikGuncelle'])->name('scheduler.aralik');
    // SMS gönderim saatleri
    Route::post('/sms-saat', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'smsSaatGuncelle'])->name('sms.saat');

    // Opsiyon Uyarı Ayarları
    Route::post('/opsiyon-ayarlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'opsiyonAyarEkle'])->name('opsiyon.ekle');
    Route::post('/opsiyon-ayarlari/{opsiyonAyar}/toggle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'opsiyonAyarToggle'])->name('opsiyon.toggle');
    Route::delete('/opsiyon-ayarlari/{opsiyonAyar}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'opsiyonAyarSil'])->name('opsiyon.sil');
});

// Admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/charter', [\App\Http\Controllers\Admin\CharterController::class, 'index'])->name('charter.index');
    Route::get('/charter/{charterRequest}', [\App\Http\Controllers\Admin\CharterController::class, 'show'])->name('charter.show');
    Route::post('/charter/{charterRequest}/rfq', [\App\Http\Controllers\Admin\CharterController::class, 'sendRfq'])->name('charter.send-rfq');
    Route::post('/charter/{charterRequest}/supplier-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'storeSupplierQuote'])->name('charter.supplier-quotes.store');
    Route::post('/charter/{charterRequest}/sales-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'createSalesQuote'])->name('charter.sales-quotes.store');
    Route::patch('/charter/{charterRequest}/extras/{extra}', [\App\Http\Controllers\Admin\CharterController::class, 'priceExtra'])->name('charter.extras.price');
    Route::post('/charter/bookings/{booking}/payments', [\App\Http\Controllers\Admin\CharterController::class, 'storePayment'])->name('charter.payments.store');
    Route::post('/charter/payments/{payment}/approve', [\App\Http\Controllers\Admin\CharterController::class, 'approvePayment'])->name('charter.payments.approve');
    Route::post('/charter/payments/{payment}/reject', [\App\Http\Controllers\Admin\CharterController::class, 'rejectPayment'])->name('charter.payments.reject');
    Route::post('/charter/bookings/{booking}/start-operation', [\App\Http\Controllers\Admin\CharterController::class, 'startOperation'])->name('charter.bookings.start-operation');

    Route::get('/talepler', [\App\Http\Controllers\Admin\RequestController::class, 'index'])->name('requests.index');
    Route::get('/talepler/{gtpnr}', [\App\Http\Controllers\Admin\RequestController::class, 'show'])->name('requests.show');
    Route::post('/talepler/{gtpnr}/durum', [\App\Http\Controllers\Admin\RequestController::class, 'updateStatus'])->name('requests.status');
    Route::post('/talepler/{gtpnr}/teklif', [\App\Http\Controllers\Admin\RequestController::class, 'storeOffer'])->name('requests.offer');
    Route::post('/talepler/{gtpnr}/ai-parse', [\App\Http\Controllers\Admin\RequestController::class, 'aiParse'])->name('requests.ai-parse');
    Route::post('/talepler/{gtpnr}/ai-format-offer', [\App\Http\Controllers\Admin\RequestController::class, 'aiFormatOffer'])->name('requests.ai-format-offer');
    Route::post('/talepler/{gtpnr}/odeme', [\App\Http\Controllers\Admin\RequestController::class, 'storePayment'])->name('requests.payment');
    Route::delete('/talepler/{gtpnr}/odeme/{payment}', [\App\Http\Controllers\Admin\RequestController::class, 'deletePayment'])->name('requests.payment.delete');
    Route::patch('/talepler/{gtpnr}/odeme/{payment}', [\App\Http\Controllers\Admin\RequestController::class, 'updatePayment'])->name('requests.payment.update');
    Route::patch('/talepler/{gtpnr}/teklif/{offer}', [\App\Http\Controllers\Admin\RequestController::class, 'updateOffer'])->name('requests.offer.update');
    Route::post('/talepler/{gtpnr}/teklif/{offer}/toggle', [\App\Http\Controllers\Admin\RequestController::class, 'toggleOffer'])->name('requests.offer.toggle');
    Route::delete('/talepler/{gtpnr}/teklif/{offer}', [\App\Http\Controllers\Admin\RequestController::class, 'deleteOffer'])->name('requests.offer.delete');
    Route::get('/hizli-yanitla', [\App\Http\Controllers\Admin\QuickReplyController::class, 'index'])->name('quick-reply.index');
    Route::post('/hizli-yanitla/parse', [\App\Http\Controllers\Admin\QuickReplyController::class, 'parse'])->name('quick-reply.parse');
    Route::patch('/hizli-yanitla/{session}', [\App\Http\Controllers\Admin\QuickReplyController::class, 'saveReview'])->name('quick-reply.save-review');
    Route::post('/hizli-yanitla/{session}/onayla', [\App\Http\Controllers\Admin\QuickReplyController::class, 'confirm'])->name('quick-reply.confirm');
    Route::get('/hizli-yanitla/acente-ara', [\App\Http\Controllers\Admin\QuickReplyController::class, 'agencySearch'])->name('quick-reply.agency-search');

    // Eski sistem arşiv görüntüleyici
    Route::get('/eski-sistem', [\App\Http\Controllers\Admin\EskiSistemController::class, 'index'])->name('eski-sistem');
    Route::get('/eski-sistem/{gtpnr}', [\App\Http\Controllers\Admin\EskiSistemController::class, 'show'])->name('eski-sistem.show');

    // Broadcast duyurular
    Route::get('/duyurular', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('broadcast.index');
    Route::get('/duyurular/olustur', [\App\Http\Controllers\Admin\BroadcastController::class, 'create'])->name('broadcast.create');
    Route::post('/duyurular', [\App\Http\Controllers\Admin\BroadcastController::class, 'store'])->name('broadcast.store');
    Route::delete('/duyurular/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'destroy'])->name('broadcast.destroy');

    // Push polling
    Route::get('/push/yeni-talepler', function (\Illuminate\Http\Request $request) {
        if (! \App\Models\SistemAyar::pushEnabled()) {
            return response()->json(['talepler' => [], 'ts' => now()->toISOString()]);
        }

        $since = $request->input('since', now()->subMinutes(1)->toISOString());
        $yeni = \App\Models\Request::where('created_at', '>', $since)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'gtpnr', 'agency_name', 'created_at']);
        return response()->json(['talepler' => $yeni, 'ts' => now()->toISOString()]);
    })->name('push.yeni-talepler');

});

Route::middleware(['auth'])->prefix('acente/onizleme')->name('acente.preview.')->group(function () {
    Route::get('/baslat/{user}', [\App\Http\Controllers\Acente\PreviewController::class, 'start'])->name('start');
    Route::get('/talep/{gtpnr}', [\App\Http\Controllers\Acente\PreviewController::class, 'startFromRequest'])->name('request');
    Route::post('/bitir', [\App\Http\Controllers\Acente\PreviewController::class, 'stop'])->name('stop');
});

// Acente
Route::middleware(['auth'])->prefix('acente')->name('acente.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Acente\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/charter', [\App\Http\Controllers\Acente\CharterRequestController::class, 'index'])->name('charter.index');
    Route::get('/charter/talep', [\App\Http\Controllers\Acente\CharterRequestController::class, 'create'])->name('charter.create');
    Route::post('/charter/talep', [\App\Http\Controllers\Acente\CharterRequestController::class, 'store'])->name('charter.store');
    Route::get('/charter/talep/advisory', \App\Http\Controllers\Acente\CharterAdvisoryController::class)->name('charter.advisory');
    Route::get('/charter/talep/{charterRequest}', [\App\Http\Controllers\Acente\CharterRequestController::class, 'show'])->name('charter.show');
    Route::post('/charter/talep/{charterRequest}/sales-quotes/{salesQuote}/kabul', [\App\Http\Controllers\Acente\CharterRequestController::class, 'acceptSalesQuote'])->name('charter.accept');

    Route::get('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'create'])->name('requests.create');
    Route::post('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'store'])->name('requests.store');
    Route::get('/talep/{gtpnr}', [\App\Http\Controllers\Acente\RequestController::class, 'show'])->name('requests.show');
    Route::post('/talep/{gtpnr}/ai-analiz', [\App\Http\Controllers\Acente\RequestController::class, 'aiAnaliz'])->name('requests.ai-analiz');
    Route::post('/talep/{gtpnr}/ai-kaydet', [\App\Http\Controllers\Acente\RequestController::class, 'aiKaydet'])->name('requests.ai-kaydet');
    Route::post('/talep/{gtpnr}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\RequestController::class, 'acceptOffer'])->name('requests.accept');

    Route::get('/profil', [\App\Http\Controllers\Acente\ProfileController::class, 'edit'])->name('profil');
    Route::put('/profil', [\App\Http\Controllers\Acente\ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/sifre', [\App\Http\Controllers\Acente\ProfileController::class, 'updatePassword'])->name('profil.sifre');

    });

    Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Bildirimler — tüm roller için ortak
Route::middleware('auth')->prefix('bildirimler')->name('bildirimler.')->group(function () {
    Route::get('/', function (\Illuminate\Http\Request $request) {
        if (! $request->expectsJson()) {
            return redirect()->route('dashboard');
        }

        $bildirimler = \App\Models\KullaniciBildirimi::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')->limit(20)->get();
        $okunmamis = $bildirimler->where('is_read', false)->count();
        return response()->json(['bildirimler' => $bildirimler, 'okunmamis' => $okunmamis]);
    })->name('liste');

    Route::post('/okundu', function (\Illuminate\Http\Request $request) {
        \App\Models\KullaniciBildirimi::where('user_id', auth()->id())
            ->whereIn('id', $request->input('ids', []))
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['ok' => true]);
    })->name('okundu');

    Route::post('/hepsini-oku', function () {
        \App\Models\KullaniciBildirimi::where('user_id', auth()->id())
            ->where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['ok' => true]);
    })->name('hepsini-oku');
});

Route::post('/ai-kutlama/{campaign}/goruldu', [\App\Http\Controllers\AiCelebrationController::class, 'seen'])->name('ai-kutlama.seen');
Route::post('/ai-kutlama/{campaign}/kapatildi', [\App\Http\Controllers\AiCelebrationController::class, 'closed'])->name('ai-kutlama.closed');
Route::post('/ai-kutlama/{campaign}/tiklandi', [\App\Http\Controllers\AiCelebrationController::class, 'clicked'])->name('ai-kutlama.clicked');

require __DIR__.'/auth.php';
