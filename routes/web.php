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
Route::get('/teklif/paylas/{offer}', \App\Http\Controllers\LeisureShareController::class)
    ->middleware('signed')
    ->name('leisure.share');

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

// TÜRSAB sorgulama — kayıt sayfasında kullanılır, auth gerekmez
Route::get('/tursab-sorgula', [\App\Http\Controllers\TursabController::class, 'sorgula'])->name('tursab.sorgula');

Route::post('/transfer/payment/callback', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paymentCallback'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('transfer.payment.callback');

Route::match(['GET', 'POST'], '/transfer/payment/paynkolay/success', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paynkolaySuccess'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('transfer.payment.paynkolay.success');

Route::match(['GET', 'POST'], '/transfer/payment/paynkolay/fail', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paynkolayFail'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('transfer.payment.paynkolay.fail');

Route::middleware(['auth'])->get('/transfer/payment/simulate/{reference}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paymentSimulate'])
    ->name('transfer.payment.simulate');

Route::match(['GET', 'POST'], '/payment/paynkolay/success', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'paynkolaySuccess'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payment.paynkolay.success');

Route::match(['GET', 'POST'], '/payment/paynkolay/fail', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'paynkolayFail'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payment.paynkolay.fail');

Route::middleware(['auth'])->get('/payment/paynkolay/simulate/{reference}', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'paynkolaySimulate'])
    ->name('payment.paynkolay.simulate');

// Superadmin
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('superadmin.dashboard');
    })->name('dashboard');

    Route::get('/show-last-error', function () {
        $log = storage_path('logs/laravel.log');
        if (!file_exists($log)) return response('Log yok');
        $lastLines = array_slice(file($log), -120);
        return response('<pre style="font-size:11px;padding:10px;">' . htmlspecialchars(implode('', $lastLines)) . '</pre>');
    });

    Route::get('/run-migrate-once', function () {
        \Artisan::call('migrate', ['--force' => true]);
        return response('<pre>' . \Artisan::output() . '</pre>');
    });

    Route::get('/clear-view-cache', function () {
        $dir = storage_path('framework/views');
        $files = glob($dir . '/*.php');
        $deleted = 0;
        foreach ($files as $f) { @unlink($f); $deleted++; }
        return response('Silindi: ' . $deleted . ' dosya. View cache temizlendi.');
    });

    // Route::get('/tursab-debug/{no}', function (int $no = 18801) { /* debug kaldırıldı */ });
    Route::get('/tursab-debug-disabled', function (int $no = 18801) {
        $base = 'https://online.tursab.org.tr/publicpages/embedded/agencysearch/';
        $postUrl = $base;
        $http = \Illuminate\Support\Facades\Http::withHeaders([
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'tr-TR,tr;q=0.9',
        ])->timeout(20);

        $out = "<pre style='font-size:11px;padding:10px;white-space:pre-wrap;'>";

        // 1) GET sayfayı incele
        $get  = $http->get($base);
        $html = $get->body();
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) $title = trim(strip_tags($m[1]));

        $out .= "=== GET {$base} ===\n";
        $out .= "Status: " . $get->status() . " | Boyut: " . strlen($html) . "\n";
        $out .= "Title: {$title}\n";
        $out .= "ViewState: " . (str_contains($html,'__VIEWSTATE') ? 'EVET':'HAYIR') . "\n";
        $out .= "Form: "      . (str_contains($html,'<form')       ? 'EVET':'HAYIR') . "\n";
        $out .= "Table: "     . (str_contains($html,'<table')      ? 'EVET':'HAYIR') . "\n";

        // Input adlarını çıkar
        preg_match_all('/<input[^>]+name=["\']([^"\']+)["\'][^>]*>/i', $html, $inputs);
        $out .= "Input isimleri: " . implode(', ', array_unique($inputs[1])) . "\n";

        // Form action
        preg_match('/<form[^>]+action=["\']([^"\']*)["\'][^>]*>/i', $html, $fa);
        $out .= "Form action: " . ($fa[1] ?? '(yok)') . "\n";

        // API/fetch ipuçları
        preg_match_all('#["\']([^"\']*(?:api|search|agency|acen)[^"\']{0,60})["\']#i', $html, $apis);
        $hints = array_slice(array_unique($apis[1]), 0, 10);
        $out .= "API ipuçları:\n  " . implode("\n  ", array_map('htmlspecialchars', $hints)) . "\n";

        // İlk 800 karakter
        $out .= "\n--- HTML (ilk 800 karakter) ---\n" . htmlspecialchars(substr($html, 0, 800)) . "\n\n";

        // 2) ViewState varsa POST dene
        $extract = function(string $h, string $name): string {
            if (preg_match('/name=["\']'.preg_quote($name,'/').'["\'][^>]*value=["\']([^"\']*)["\']/', $h, $m)) return $m[1];
            if (preg_match('/value=["\']([^"\']*)["\'][^>]*name=["\']'.preg_quote($name,'/').'["\']/', $h, $m)) return $m[1];
            return '';
        };
        $vs = $extract($html, '__VIEWSTATE');
        $ev = $extract($html, '__EVENTVALIDATION');

        if ($vs || str_contains($html, '<form')) {
            $cookieHdr = '';
            foreach ($get->cookies()->toArray() as $c) {
                $n = $c['Name'] ?? $c['name'] ?? ''; $v = $c['Value'] ?? $c['value'] ?? '';
                if ($n) $cookieHdr .= "{$n}={$v}; ";
            }

            // Tüm hidden input'ları topla
            preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $html, $hiddenTags);
            $postData = [];
            foreach ($hiddenTags[0] as $tag) {
                if (preg_match('/name=["\']([^"\']+)["\']/', $tag, $nm) &&
                    preg_match('/value=["\']([^"\']*)["\']/', $tag, $vl)) {
                    $postData[$nm[1]] = $vl[1];
                }
            }

            // Bilinen alan adları
            $postData['ctl00$ContentPlaceHolder1$OprGroup']                = 'NameSearchRadio'; // radio: ad/belge no arama modu
            $postData['ctl00$ContentPlaceHolder1$TursabNoText']            = (string) $no;
            $postData['ctl00$ContentPlaceHolder1$SearchButton']            = 'Ara';
            $postData['ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBox']   = '';
            $postData['ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBoxHF'] = '';
            $postData['ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBoxTF'] = '';
            $out .= "POST data anahtarları: " . implode(', ', array_keys($postData)) . "\n";

            $post = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                'Referer'    => $base,
                'Cookie'     => trim($cookieHdr, '; '),
            ])->timeout(15)->asForm()->post($postUrl, $postData);

            $html2 = $post->body();
            $out .= "\n=== POST {$postUrl} ===\n";
            $out .= "Status: " . $post->status() . " | Boyut: " . strlen($html2) . "\n";
            $out .= "Table: " . (str_contains($html2,'<table') ? 'EVET':'HAYIR') . "\n";

            $ctgPos = stripos($html2, 'CTG');
            $out .= "CTG var mı: " . ($ctgPos !== false ? 'EVET (pos:'.$ctgPos.')' : 'HAYIR') . "\n";
            $out .= "Acente Bulunamadı: " . (str_contains($html2,'Bulunamadı') ? 'EVET' : 'HAYIR') . "\n";
            // CTG etrafındaki sonuç bölümünü göster
            if ($ctgPos !== false) {
                $out .= "\n--- CTG Bölgesi (±600 karakter) ---\n" . htmlspecialchars(substr($html2, max(0,$ctgPos-600), 1400)) . "\n";
            }
        }

        $out .= "</pre>";
        return response($out);
    });



    Route::get('/yonetim/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'yonetim')
        ->name('yonetim.hub');
    Route::get('/charter/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'charter')
        ->name('charter.hub');
    Route::get('/transfer', [\App\Http\Controllers\Transfer\TransferController::class, 'index'])
        ->defaults('role_context', 'superadmin')
        ->name('transfer.index');
    Route::get('/transfer/airports', [\App\Http\Controllers\Transfer\TransferController::class, 'airports'])
        ->defaults('role_context', 'superadmin')
        ->name('transfer.airports');
    Route::get('/transfer/zones', [\App\Http\Controllers\Transfer\TransferController::class, 'zones'])
        ->defaults('role_context', 'superadmin')
        ->name('transfer.zones');
    Route::post('/transfer/search', [\App\Http\Controllers\Transfer\TransferController::class, 'search'])
        ->defaults('role_context', 'superadmin')
        ->name('transfer.search');
    Route::get('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'show'])
        ->name('transfer.checkout.show');
    Route::post('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'book'])
        ->name('transfer.checkout.book');
    Route::get('/transfer/bookings/{booking}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'showBooking'])
        ->name('transfer.booking.show');
    Route::post('/transfer/bookings/{booking}/cancel', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'cancelBooking'])
        ->name('transfer.booking.cancel');
    Route::get('/transfer/bookings/{booking}/status', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paymentStatus'])
        ->name('transfer.booking.status');
    Route::get('/transfer/operasyon', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'index'])
        ->name('transfer.ops.index');
    Route::patch('/transfer/operasyon/suppliers/{supplier}', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'updateSupplier'])
        ->name('transfer.ops.suppliers.update');
    Route::patch('/transfer/operasyon/sozlesme', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'updateTerms'])
        ->name('transfer.ops.terms.update');
    Route::post('/transfer/operasyon/zones', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'storeZone'])
        ->name('transfer.ops.zones.store');
    Route::patch('/transfer/operasyon/zones/{zone}', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'updateZone'])
        ->name('transfer.ops.zones.update');
    Route::get('/leisure/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'leisure')
        ->name('leisure.hub');
    Route::get('/finans/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'finance')
        ->name('finance.hub');
    Route::get('/iletisim/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'iletisim')
        ->name('iletisim.hub');
    Route::get('/sistem/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'superadmin'])
        ->defaults('group', 'sistem')
        ->name('sistem.hub');

    Route::get('/finans', [\App\Http\Controllers\Superadmin\FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finans/dekontlar', [\App\Http\Controllers\Admin\FinanceReceiptController::class, 'index'])->name('finance.receipts.index');
    Route::patch('/finans/dekontlar/{submission}', [\App\Http\Controllers\Admin\FinanceReceiptController::class, 'update'])->name('finance.receipts.update');
    Route::post('/finans/manual-kayit', [\App\Http\Controllers\Admin\FinanceController::class, 'storeManualRecord'])->name('finance.manual-record.store');
    Route::post('/finans/manual-islem', [\App\Http\Controllers\Admin\FinanceController::class, 'storeManualTransaction'])->name('finance.manual-transaction.store');
    Route::post('/finans/iade', [\App\Http\Controllers\Admin\FinanceController::class, 'storeRefund'])->name('finance.refund.store');
    Route::post('/finans/odeme-plani', [\App\Http\Controllers\Admin\FinanceController::class, 'storePaymentPlan'])->name('finance.payment-plan.store');
    Route::patch('/finans/odeme-plani/{plan}', [\App\Http\Controllers\Admin\FinanceController::class, 'updatePaymentPlan'])->name('finance.payment-plan.update');

    Route::get('/charter', [\App\Http\Controllers\Admin\CharterController::class, 'index'])->name('charter.index');
    Route::get('/charter/paketler', [\App\Http\Controllers\Superadmin\CharterPresetPackageController::class, 'index'])->name('charter.packages.index');
    Route::post('/charter/paketler', [\App\Http\Controllers\Superadmin\CharterPresetPackageController::class, 'store'])->name('charter.packages.store');
    Route::patch('/charter/paketler/{packageCode}', [\App\Http\Controllers\Superadmin\CharterPresetPackageController::class, 'update'])->name('charter.packages.update');
    Route::delete('/charter/paketler/{packageCode}', [\App\Http\Controllers\Superadmin\CharterPresetPackageController::class, 'destroy'])->name('charter.packages.destroy');
    Route::get('/dinner-cruise', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'index'])->name('dinner-cruise.index');
    Route::get('/dinner-cruise/vitrin', \App\Http\Controllers\Superadmin\DinnerCruiseShowcaseController::class)->name('dinner-cruise.showcase');
    Route::get('/dinner-cruise/{leisureRequest}', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'show'])->name('dinner-cruise.show');
    Route::post('/dinner-cruise/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'storeSupplierQuote'])->name('dinner-cruise.supplier-quotes.store');
    Route::post('/dinner-cruise/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'storeClientOffer'])->name('dinner-cruise.client-offers.store');
    Route::post('/dinner-cruise/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'startOperation'])->name('dinner-cruise.start-operation');
    Route::get('/yacht-charter', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'index'])->name('yacht-charter.index');
    Route::get('/yacht-charter/{leisureRequest}', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'show'])->name('yacht-charter.show');
    Route::post('/yacht-charter/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'storeSupplierQuote'])->name('yacht-charter.supplier-quotes.store');
    Route::post('/yacht-charter/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'storeClientOffer'])->name('yacht-charter.client-offers.store');
    Route::post('/yacht-charter/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'startOperation'])->name('yacht-charter.start-operation');
    Route::get('/charter/rfq-tedarikciler', [\App\Http\Controllers\Superadmin\CharterRfqSupplierController::class, 'index'])->name('charter.rfq-suppliers.index');
    Route::post('/charter/rfq-tedarikciler', [\App\Http\Controllers\Superadmin\CharterRfqSupplierController::class, 'store'])->name('charter.rfq-suppliers.store');
    Route::patch('/charter/rfq-tedarikciler/{supplier}', [\App\Http\Controllers\Superadmin\CharterRfqSupplierController::class, 'update'])->name('charter.rfq-suppliers.update');
    Route::delete('/charter/rfq-tedarikciler/{supplier}', [\App\Http\Controllers\Superadmin\CharterRfqSupplierController::class, 'destroy'])->name('charter.rfq-suppliers.destroy');
    Route::post('/charter/rfq-tedarikciler/limit', [\App\Http\Controllers\Superadmin\CharterRfqSupplierController::class, 'updateMax'])->name('charter.rfq-suppliers.max');
    Route::get('/charter/{charterRequest}', [\App\Http\Controllers\Admin\CharterController::class, 'show'])->name('charter.show');
    Route::post('/charter/{charterRequest}/rfq', [\App\Http\Controllers\Admin\CharterController::class, 'sendRfq'])->name('charter.send-rfq');
    Route::post('/charter/{charterRequest}/supplier-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'storeSupplierQuote'])->name('charter.supplier-quotes.store');
    Route::post('/charter/{charterRequest}/sales-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'createSalesQuote'])->name('charter.sales-quotes.store');
    Route::patch('/charter/{charterRequest}/extras/{extra}', [\App\Http\Controllers\Admin\CharterController::class, 'priceExtra'])->name('charter.extras.price');
    Route::post('/charter/bookings/{booking}/payments', [\App\Http\Controllers\Admin\CharterController::class, 'storePayment'])->name('charter.payments.store');
    Route::post('/charter/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startCharter'])->name('charter.payments.gateway-start');
    Route::post('/charter/payments/{payment}/approve', [\App\Http\Controllers\Admin\CharterController::class, 'approvePayment'])->name('charter.payments.approve');
    Route::post('/charter/payments/{payment}/reject', [\App\Http\Controllers\Admin\CharterController::class, 'rejectPayment'])->name('charter.payments.reject');
    Route::post('/charter/bookings/{booking}/start-operation', [\App\Http\Controllers\Admin\CharterController::class, 'startOperation'])->name('charter.bookings.start-operation');
    Route::post('/leisure/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLeisure'])->name('leisure.payments.gateway-start');
    Route::post('/talepler/{gtpnr}/odeme/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLegacy'])->name('requests.gateway-payment.start');

    Route::get('/site-ayarlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'siteAyarlari'])->name('site.ayarlar');
    Route::get('/leisure-ayarlar', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'index'])->name('leisure.settings.index');
    Route::post('/leisure-ayarlar/paketler', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storePackage'])->name('leisure.settings.packages.store');
    Route::patch('/leisure-ayarlar/paketler/{template}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updatePackage'])->name('leisure.settings.packages.update');
    Route::post('/leisure-ayarlar/ekstralar', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storeExtra'])->name('leisure.settings.extras.store');
    Route::patch('/leisure-ayarlar/ekstralar/{option}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updateExtra'])->name('leisure.settings.extras.update');
    Route::post('/leisure-ayarlar/medya', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storeMedia'])->name('leisure.settings.media.store');
    Route::patch('/leisure-ayarlar/medya/{asset}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updateMedia'])->name('leisure.settings.media.update');
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

    // TÜRSAB listesi (superadmin)
    Route::get('/tursab-ara', [\App\Http\Controllers\TursabController::class, 'ara'])->name('tursab.ara');
    Route::post('/tursab-davet', [\App\Http\Controllers\TursabController::class, 'davetGonder'])->name('tursab.davet');
    Route::get('/tursab-kampanya', [\App\Http\Controllers\TursabController::class, 'kampanya'])->name('tursab.kampanya');
    Route::post('/tursab-toplu-davet', [\App\Http\Controllers\TursabController::class, 'topluDavet'])->name('tursab.toplu-davet');
    Route::post('/tursab-scrape-start', [\App\Http\Controllers\TursabController::class, 'scrapeStart'])->name('tursab.scrape.start');
    Route::get('/tursab-scrape-status', [\App\Http\Controllers\TursabController::class, 'scrapeStatus'])->name('tursab.scrape.status');
    Route::post('/tursab-manuel-ekle', [\App\Http\Controllers\TursabController::class, 'manuelEkle'])->name('tursab.manuel-ekle');
    Route::post('/bakanlik-scrape-start',  [\App\Http\Controllers\TursabController::class, 'bakanlikScrapeStart'])->name('bakanlik.scrape.start');
    Route::get( '/bakanlik-scrape-status', [\App\Http\Controllers\TursabController::class, 'bakanlikScrapeStatus'])->name('bakanlik.scrape.status');
    Route::get('/acenteler-istatistik', [\App\Http\Controllers\AcenetelIstatistikController::class, 'index'])->name('acenteler.istatistik');

    // Acenteler
    Route::get('/acenteler', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteler'])->name('acenteler');
    Route::post('/acenteler/{agency}/toggle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteToggle'])->name('acenteler.toggle');
    Route::post('/acenteler/{agency}/rol', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteRolDegistir'])->name('acenteler.rol');
    Route::patch('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteGuncelle'])->name('acenteler.guncelle');
    Route::delete('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteSil'])->name('acenteler.sil');
    Route::post('/acenteler/{agency}/iade-badge', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteIadeBadgeToggle'])->name('acenteler.iade-badge');
    Route::post('/acenteler/{agency}/broadcast-yetki', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteBroadcastYetkiToggle'])->name('acenteler.broadcast-yetki');
    Route::post('/acenteler/{agency}/transfer-tedarikci', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteTransferSupplierToggle'])->name('acenteler.transfer-supplier-toggle');

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
Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/talepler/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'talepler')
        ->name('talepler.hub');
    Route::get('/charter/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'charter')
        ->name('charter.hub');
    Route::get('/transfer', [\App\Http\Controllers\Transfer\TransferController::class, 'index'])
        ->defaults('role_context', 'admin')
        ->name('transfer.index');
    Route::get('/transfer/airports', [\App\Http\Controllers\Transfer\TransferController::class, 'airports'])
        ->defaults('role_context', 'admin')
        ->name('transfer.airports');
    Route::get('/transfer/zones', [\App\Http\Controllers\Transfer\TransferController::class, 'zones'])
        ->defaults('role_context', 'admin')
        ->name('transfer.zones');
    Route::post('/transfer/search', [\App\Http\Controllers\Transfer\TransferController::class, 'search'])
        ->defaults('role_context', 'admin')
        ->name('transfer.search');
    Route::get('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'show'])
        ->name('transfer.checkout.show');
    Route::post('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'book'])
        ->name('transfer.checkout.book');
    Route::get('/transfer/bookings/{booking}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'showBooking'])
        ->name('transfer.booking.show');
    Route::post('/transfer/bookings/{booking}/cancel', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'cancelBooking'])
        ->name('transfer.booking.cancel');
    Route::get('/transfer/bookings/{booking}/status', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paymentStatus'])
        ->name('transfer.booking.status');
    Route::get('/leisure/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'leisure')
        ->name('leisure.hub');
    Route::get('/finans/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'finance')
        ->name('finance.hub');
    Route::get('/iletisim/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'iletisim')
        ->name('iletisim.hub');
    Route::get('/hesap/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'admin'])
        ->defaults('group', 'hesap')
        ->name('hesap.hub');

    Route::get('/finans', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finans/dekontlar', [\App\Http\Controllers\Admin\FinanceReceiptController::class, 'index'])->name('finance.receipts.index');
    Route::patch('/finans/dekontlar/{submission}', [\App\Http\Controllers\Admin\FinanceReceiptController::class, 'update'])->name('finance.receipts.update');
    Route::post('/finans/manual-kayit', [\App\Http\Controllers\Admin\FinanceController::class, 'storeManualRecord'])->name('finance.manual-record.store');
    Route::post('/finans/manual-islem', [\App\Http\Controllers\Admin\FinanceController::class, 'storeManualTransaction'])->name('finance.manual-transaction.store');
    Route::post('/finans/iade', [\App\Http\Controllers\Admin\FinanceController::class, 'storeRefund'])->name('finance.refund.store');
    Route::post('/finans/odeme-plani', [\App\Http\Controllers\Admin\FinanceController::class, 'storePaymentPlan'])->name('finance.payment-plan.store');
    Route::patch('/finans/odeme-plani/{plan}', [\App\Http\Controllers\Admin\FinanceController::class, 'updatePaymentPlan'])->name('finance.payment-plan.update');

    Route::get('/charter', [\App\Http\Controllers\Admin\CharterController::class, 'index'])->name('charter.index');
    Route::get('/dinner-cruise', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'index'])->name('dinner-cruise.index');
    Route::get('/dinner-cruise/{leisureRequest}', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'show'])->name('dinner-cruise.show');
    Route::post('/dinner-cruise/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'storeSupplierQuote'])->name('dinner-cruise.supplier-quotes.store');
    Route::post('/dinner-cruise/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'storeClientOffer'])->name('dinner-cruise.client-offers.store');
    Route::post('/dinner-cruise/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\DinnerCruiseManagementController::class, 'startOperation'])->name('dinner-cruise.start-operation');
    Route::get('/yacht-charter', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'index'])->name('yacht-charter.index');
    Route::get('/yacht-charter/{leisureRequest}', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'show'])->name('yacht-charter.show');
    Route::post('/yacht-charter/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'storeSupplierQuote'])->name('yacht-charter.supplier-quotes.store');
    Route::post('/yacht-charter/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'storeClientOffer'])->name('yacht-charter.client-offers.store');
    Route::post('/yacht-charter/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\YachtCharterManagementController::class, 'startOperation'])->name('yacht-charter.start-operation');
    Route::get('/charter/{charterRequest}', [\App\Http\Controllers\Admin\CharterController::class, 'show'])->name('charter.show');
    Route::post('/charter/{charterRequest}/rfq', [\App\Http\Controllers\Admin\CharterController::class, 'sendRfq'])->name('charter.send-rfq');
    Route::post('/charter/{charterRequest}/supplier-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'storeSupplierQuote'])->name('charter.supplier-quotes.store');
    Route::post('/charter/{charterRequest}/sales-quotes', [\App\Http\Controllers\Admin\CharterController::class, 'createSalesQuote'])->name('charter.sales-quotes.store');
    Route::patch('/charter/{charterRequest}/extras/{extra}', [\App\Http\Controllers\Admin\CharterController::class, 'priceExtra'])->name('charter.extras.price');
    Route::post('/charter/bookings/{booking}/payments', [\App\Http\Controllers\Admin\CharterController::class, 'storePayment'])->name('charter.payments.store');
    Route::post('/charter/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startCharter'])->name('charter.payments.gateway-start');
    Route::post('/charter/payments/{payment}/approve', [\App\Http\Controllers\Admin\CharterController::class, 'approvePayment'])->name('charter.payments.approve');
    Route::post('/charter/payments/{payment}/reject', [\App\Http\Controllers\Admin\CharterController::class, 'rejectPayment'])->name('charter.payments.reject');
    Route::post('/charter/bookings/{booking}/start-operation', [\App\Http\Controllers\Admin\CharterController::class, 'startOperation'])->name('charter.bookings.start-operation');
    Route::post('/leisure/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLeisure'])->name('leisure.payments.gateway-start');

    Route::get('/talepler', [\App\Http\Controllers\Admin\RequestController::class, 'index'])->name('requests.index');
    Route::get('/talepler/olustur', [\App\Http\Controllers\Admin\RequestController::class, 'create'])->name('requests.create');
    Route::post('/talepler/olustur', [\App\Http\Controllers\Admin\RequestController::class, 'storeOnBehalf'])->name('requests.store-on-behalf');
    Route::get('/talepler/{gtpnr}', [\App\Http\Controllers\Admin\RequestController::class, 'show'])->name('requests.show');
    Route::post('/talepler/{gtpnr}/durum', [\App\Http\Controllers\Admin\RequestController::class, 'updateStatus'])->name('requests.status');
    Route::post('/talepler/{gtpnr}/teklif', [\App\Http\Controllers\Admin\RequestController::class, 'storeOffer'])->name('requests.offer');
    Route::post('/talepler/{gtpnr}/ai-parse', [\App\Http\Controllers\Admin\RequestController::class, 'aiParse'])->name('requests.ai-parse');
    Route::post('/talepler/{gtpnr}/ai-format-offer', [\App\Http\Controllers\Admin\RequestController::class, 'aiFormatOffer'])->name('requests.ai-format-offer');
    Route::post('/talepler/{gtpnr}/odeme', [\App\Http\Controllers\Admin\RequestController::class, 'storePayment'])->name('requests.payment');
    Route::post('/talepler/{gtpnr}/odeme/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLegacy'])->name('requests.gateway-payment.start');
    Route::delete('/talepler/{gtpnr}/odeme/{payment}', [\App\Http\Controllers\Admin\RequestController::class, 'deletePayment'])->name('requests.payment.delete');
    Route::patch('/talepler/{gtpnr}/odeme/{payment}', [\App\Http\Controllers\Admin\RequestController::class, 'updatePayment'])->name('requests.payment.update');
    Route::patch('/talepler/{gtpnr}/teklif/{offer}', [\App\Http\Controllers\Admin\RequestController::class, 'updateOffer'])->name('requests.offer.update');
    Route::post('/talepler/{gtpnr}/teklif/{offer}/toggle', [\App\Http\Controllers\Admin\RequestController::class, 'toggleOffer'])->name('requests.offer.toggle');
    Route::delete('/talepler/{gtpnr}/teklif/{offer}', [\App\Http\Controllers\Admin\RequestController::class, 'deleteOffer'])->name('requests.offer.delete');
    Route::patch('/talepler/{gtpnr}', [\App\Http\Controllers\Admin\RequestController::class, 'updateRequest'])->name('requests.update');
    Route::delete('/talepler/{gtpnr}', [\App\Http\Controllers\Admin\RequestController::class, 'destroy'])->name('requests.destroy');
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

    Route::get('/talepler/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'acente'])
        ->defaults('group', 'talepler')
        ->name('talepler.hub');
    Route::get('/charter/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'acente'])
        ->defaults('group', 'charter')
        ->name('charter.hub');
    Route::get('/transfer', [\App\Http\Controllers\Transfer\TransferController::class, 'index'])
        ->defaults('role_context', 'acente')
        ->name('transfer.index');
    Route::get('/transfer/airports', [\App\Http\Controllers\Transfer\TransferController::class, 'airports'])
        ->defaults('role_context', 'acente')
        ->name('transfer.airports');
    Route::get('/transfer/zones', [\App\Http\Controllers\Transfer\TransferController::class, 'zones'])
        ->defaults('role_context', 'acente')
        ->name('transfer.zones');
    Route::post('/transfer/search', [\App\Http\Controllers\Transfer\TransferController::class, 'search'])
        ->defaults('role_context', 'acente')
        ->name('transfer.search');
    Route::get('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'show'])
        ->name('transfer.checkout.show');
    Route::post('/transfer/checkout/{quoteToken}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'book'])
        ->name('transfer.checkout.book');
    Route::get('/transfer/bookings/{booking}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'showBooking'])
        ->name('transfer.booking.show');
    Route::post('/transfer/bookings/{booking}/cancel', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'cancelBooking'])
        ->name('transfer.booking.cancel');
    Route::get('/transfer/bookings/{booking}/status', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'paymentStatus'])
        ->name('transfer.booking.status');
    Route::prefix('/transfer/tedarikci')->name('transfer.supplier.')->group(function () {
        Route::middleware('transfer_supplier:approved')->group(function () {
            Route::get('/sozlesme', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'showTerms'])
                ->name('terms.show');
            Route::post('/sozlesme', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'acceptTerms'])
                ->name('terms.accept');
        });

        Route::middleware('transfer_supplier:accepted')->group(function () {
            Route::get('/panel', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'index'])
                ->name('index');
            Route::get('/bookings/{booking}', [\App\Http\Controllers\Transfer\TransferCheckoutController::class, 'showBooking'])
                ->name('bookings.show');
            Route::patch('/profil', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'updateProfile'])
                ->name('profile.update');
            Route::post('/coverage', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'storeCoverage'])
                ->name('coverage.store');
            Route::delete('/coverage/{coverage}', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'destroyCoverage'])
                ->name('coverage.destroy');
            Route::post('/pricing-rules', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'storePricingRule'])
                ->name('pricing.store');
            Route::delete('/pricing-rules/{rule}', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'destroyPricingRule'])
                ->name('pricing.destroy');
            Route::patch('/policy', [\App\Http\Controllers\Transfer\SupplierTransferController::class, 'updatePolicy'])
                ->name('policy.update');
        });
    });
    Route::get('/leisure/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'acente'])
        ->defaults('group', 'leisure')
        ->name('leisure.hub');
    Route::get('/finans/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'acente'])
        ->defaults('group', 'finance')
        ->name('finance.hub');
    Route::get('/hesap/merkez', [\App\Http\Controllers\Hub\GroupHubController::class, 'acente'])
        ->defaults('group', 'hesap')
        ->name('hesap.hub');

    Route::get('/finans', [\App\Http\Controllers\Acente\FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finans/dekont-bildirim', [\App\Http\Controllers\Acente\FinanceReceiptController::class, 'store'])->name('finance.receipts.store');

    Route::get('/charter', [\App\Http\Controllers\Acente\CharterRequestController::class, 'index'])->name('charter.index');
    Route::get('/charter/talep', [\App\Http\Controllers\Acente\CharterRequestController::class, 'create'])->name('charter.create');
    Route::post('/charter/talep', [\App\Http\Controllers\Acente\CharterRequestController::class, 'store'])->name('charter.store');
    Route::get('/charter/talep/advisory', \App\Http\Controllers\Acente\CharterAdvisoryController::class)->name('charter.advisory');
    Route::get('/charter/talep/{charterRequest}', [\App\Http\Controllers\Acente\CharterRequestController::class, 'show'])->name('charter.show');
    Route::post('/charter/talep/{charterRequest}/sales-quotes/{salesQuote}/kabul', [\App\Http\Controllers\Acente\CharterRequestController::class, 'acceptSalesQuote'])->name('charter.accept');
    Route::get('/dinner-cruise', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'index'])->name('dinner-cruise.index');
    Route::get('/dinner-cruise/talep', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'create'])->name('dinner-cruise.create');
    Route::post('/dinner-cruise/talep', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'store'])->name('dinner-cruise.store');
    Route::get('/dinner-cruise/talep/{leisureRequest}', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'show'])->name('dinner-cruise.show');
    Route::post('/dinner-cruise/talep/{leisureRequest}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'acceptOffer'])->name('dinner-cruise.accept');
    Route::get('/dinner-cruise/teklif/{offer}/yazdir', \App\Http\Controllers\Acente\LeisureOfferPrintController::class)->name('dinner-cruise.offers.print');
    Route::get('/yacht-charter', [\App\Http\Controllers\Acente\YachtCharterController::class, 'index'])->name('yacht-charter.index');
    Route::get('/yacht-charter/talep', [\App\Http\Controllers\Acente\YachtCharterController::class, 'create'])->name('yacht-charter.create');
    Route::post('/yacht-charter/talep', [\App\Http\Controllers\Acente\YachtCharterController::class, 'store'])->name('yacht-charter.store');
    Route::get('/yacht-charter/talep/{leisureRequest}', [\App\Http\Controllers\Acente\YachtCharterController::class, 'show'])->name('yacht-charter.show');
    Route::post('/yacht-charter/talep/{leisureRequest}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\YachtCharterController::class, 'acceptOffer'])->name('yacht-charter.accept');
    Route::get('/yacht-charter/teklif/{offer}/yazdir', \App\Http\Controllers\Acente\LeisureOfferPrintController::class)->name('yacht-charter.offers.print');
    Route::post('/leisure/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLeisure'])->name('leisure.payments.gateway-start');

    Route::get('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'create'])->name('requests.create');
    Route::post('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'store'])->name('requests.store');
    Route::get('/talep/{gtpnr}', [\App\Http\Controllers\Acente\RequestController::class, 'show'])->name('requests.show');
    Route::post('/talep/{gtpnr}/ai-analiz', [\App\Http\Controllers\Acente\RequestController::class, 'aiAnaliz'])->name('requests.ai-analiz');
    Route::post('/talep/{gtpnr}/ai-kaydet', [\App\Http\Controllers\Acente\RequestController::class, 'aiKaydet'])->name('requests.ai-kaydet');
    Route::post('/talep/{gtpnr}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\RequestController::class, 'acceptOffer'])->name('requests.accept');
    Route::post('/talep/{gtpnr}/odeme/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLegacy'])->name('requests.gateway-payment.start');
    Route::post('/charter/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startCharter'])->name('charter.payments.gateway-start');

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
