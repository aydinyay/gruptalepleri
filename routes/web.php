<?php

use App\Http\Controllers\AirportController;
use App\Http\Controllers\ProfileController;
use App\Models\Airline;
use App\Models\Airport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

// ── Git Pull (deploy, token korumalı) ──
Route::get('/git-pull-2026', function () {
    if (request('t') !== 'grtdeploy2026') abort(403);
    $lines = [];

    // exec() var mı?
    if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
        $lines[] = 'exec: DISABLED';
    } else {
        $lines[] = 'exec: available';
        $output = [];
        $code = -1;
        try {
            @exec('cd ' . base_path() . ' && git pull origin main 2>&1', $output, $code);
        } catch (\Throwable $e) {
            $lines[] = 'exec exception: ' . $e->getMessage();
        }
        $lines[] = 'exit code: ' . $code;
        $lines[] = implode("\n", $output);
    }

    // Cache temizle
    try { \Illuminate\Support\Facades\Artisan::call('view:clear');   $lines[] = 'view:clear: OK'; } catch (\Throwable $e) { $lines[] = 'view:clear ERR: ' . $e->getMessage(); }
    try { \Illuminate\Support\Facades\Artisan::call('route:clear');  $lines[] = 'route:clear: OK'; } catch (\Throwable $e) { $lines[] = 'route:clear ERR: ' . $e->getMessage(); }
    try { \Illuminate\Support\Facades\Artisan::call('cache:clear');  $lines[] = 'cache:clear: OK'; } catch (\Throwable $e) { $lines[] = 'cache:clear ERR: ' . $e->getMessage(); }

    return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
});

// ── Migration + Seeder çalıştırıcı (token korumalı) ──
Route::get('/migrate-run-2026', function () {
    if (request('t') !== 'grtdeploy2026') abort(403);
    $lines = [];

    // View cache temizle
    try {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        $lines[] = 'view:clear: OK';
    } catch (\Throwable $e) {
        $lines[] = 'view:clear ERROR: ' . $e->getMessage();
    }

    // Config cache temizle
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $lines[] = 'config:clear: OK';
    } catch (\Throwable $e) {
        $lines[] = 'config:clear SKIP: ' . $e->getMessage();
    }

    // Route cache temizle
    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        $lines[] = 'route:clear: OK';
    } catch (\Throwable $e) {
        $lines[] = 'route:clear SKIP: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $lines[] = 'migrate: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $e) {
        $lines[] = 'migrate ERROR: ' . $e->getMessage();
    }
    $seedClass = request('seed');
    if ($seedClass) {
        $allowed = ['BlogSeeder', 'SistemOlaySeeder', 'B2cSampleDataSeeder', 'SmallYachtSeeder'];
        if (in_array($seedClass, $allowed, true)) {
            try {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => $seedClass, '--force' => true]);
                $lines[] = 'seed: ' . \Illuminate\Support\Facades\Artisan::output();
            } catch (\Throwable $e) {
                $lines[] = 'seed ERROR: ' . $e->getMessage();
            }
        } else {
            $lines[] = 'seed: izin verilmeyen seeder: ' . $seedClass;
        }
    }
    // Git pull (fallback, exec ile)
    if (request('debug') === 'gitpull') {
        $out = [];
        $code = -1;
        try {
            $cmd = 'cd ' . base_path() . ' && git pull origin main 2>&1';
            exec($cmd, $out, $code);
        } catch (\Throwable $e) {
            $out[] = 'exec ERROR: ' . $e->getMessage();
        }
        $lines[] = 'git pull exit: ' . $code;
        $lines[] = implode("\n", $out);
        // Pull sonrası cacheları temizle
        try { \Illuminate\Support\Facades\Artisan::call('view:clear'); $lines[] = 'view:clear after pull: OK'; } catch (\Throwable $e) {}
        try { \Illuminate\Support\Facades\Artisan::call('route:clear'); $lines[] = 'route:clear after pull: OK'; } catch (\Throwable $e) {}
        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }

    // Kaynak dosya içeriği kontrol
    if (request('debug') === 'src') {
        $f = resource_path('views/b2c/home/index.blade.php');
        $lines2 = file($f);
        $snippet = '';
        for ($i = 370; $i <= 382; $i++) {
            $snippet .= ($i+1) . ': ' . ($lines2[$i] ?? '');
        }
        return response("FILE_MTIME: " . date('Y-m-d H:i:s', filemtime($f)) . "\n" . $snippet, 200)->header('Content-Type', 'text/plain');
    }

    // show.blade.php dosyasını yaz
    if (request('debug') === 'writefile') {
        $target = resource_path('views/b2c/product/show.blade.php');
        $content = base64_decode(request('b64'));
        if ($content && strlen($content) > 100) {
            file_put_contents($target, $content);
            // compiled cache temizle
            foreach (glob(storage_path('framework/views/*.php')) as $f) @unlink($f);
            $lines[] = 'WRITTEN: ' . strlen($content) . ' bytes to ' . $target;
        } else {
            $lines[] = 'ERROR: b64 param missing or too short';
        }
    }

    // B2C ürün hata testi
    if (request('debug') === 'product') {
        $slug = request('slug', 'istanbul-havalimani-taksim-sisli-vip-karsilama');
        try {
            $ctrl = app()->make(\App\Http\Controllers\B2C\ProductController::class);
            $response = $ctrl->show($slug);
            $html = $response->render();
            $lines[] = 'b2c_product: OK — ' . strlen($html) . ' bytes';
        } catch (\Throwable $e) {
            $lines[] = 'b2c_product ERROR: ' . $e->getMessage();
            $lines[] = 'FILE: ' . $e->getFile() . ':' . $e->getLine();
            $compiledFile = $e->getFile();
            if (file_exists($compiledFile)) {
                $compiledLines = file($compiledFile);
                $errLine = $e->getLine();
                // if/endif sayısını bul
                $ifCount = 0; $endifCount = 0;
                $lastOpenIf = 0;
                foreach ($compiledLines as $li => $cl) {
                    if (preg_match('/\bif\s*\(/', $cl)) { $ifCount++; $lastOpenIf = $li+1; }
                    if (strpos($cl, 'endif;') !== false) $endifCount++;
                }
                $lines[] = 'if_count: ' . $ifCount . ' | endif_count: ' . $endifCount . ' | last_open_if_line: ' . $lastOpenIf;
                // Son açık if'in çevresini göster
                $start = max(0, $lastOpenIf - 3);
                $end   = min(count($compiledLines), $lastOpenIf + 5);
                $snippet = '';
                for ($i = $start; $i < $end; $i++) {
                    $snippet .= ($i+1) . ': ' . $compiledLines[$i];
                }
                $lines[] = 'LAST OPEN IF SNIPPET:' . "\n" . $snippet;
            }
        }
    }

    // B2C hata testi
    if (request('debug') === 'b2c') {
        try {
            $view = app()->make(\App\Http\Controllers\B2C\HomeController::class)->index();
            $html = $view->render();
            $lines[] = 'b2c_home: OK — ' . strlen($html) . ' bytes';
        } catch (\Throwable $e) {
            $lines[] = 'b2c_home ERROR: ' . $e->getMessage();
            $lines[] = 'FILE: ' . $e->getFile() . ':' . $e->getLine();
            // Compiled dosyanın 360-390 satırlarını göster
            $compiledFile = $e->getFile();
            if (file_exists($compiledFile)) {
                $compiledLines = file($compiledFile);
                $errLine = $e->getLine();
                $start = max(0, $errLine - 15);
                $end   = min(count($compiledLines), $errLine + 5);
                $snippet = '';
                for ($i = $start; $i < $end; $i++) {
                    $snippet .= ($i+1) . ': ' . $compiledLines[$i];
                }
                $lines[] = 'COMPILED SNIPPET:' . "\n" . $snippet;
            }
        }
    }

    return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
});

// ── Bakanlık Sync HTTP tetikleyici (cron + manuel için, token korumalı) ──
Route::get('/acente-sync-run', function () {
    if (request('t') !== 'grtacesync2026') abort(403);
    $batch = min(100, max(1, (int) (request('batch') ?: 50)));
    $reset = request('reset') === '1';
    $args  = ['--batch' => $batch, '--delay' => 300];
    if ($reset) $args['--reset'] = true;
    \Illuminate\Support\Facades\Artisan::call('acenteler:sync', $args);
    return response(\Illuminate\Support\Facades\Artisan::output())
        ->header('Content-Type', 'text/plain');
});

// ── Migration runner (durum + synced_at kolonları) ──
Route::get('/mig-acesync-2026', function () {
    if (request('t') !== 'grtmigace2026') abort(403);
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--force' => true,
            '--path'  => 'database/migrations/2026_03_29_163021_add_durum_syncedat_to_acenteler.php',
        ]);
        return response(\Illuminate\Support\Facades\Artisan::output())
            ->header('Content-Type', 'text/plain');
    } catch (\Throwable $e) {
        return response('❌ ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
    }
});

// ── Tek seferlik migration runner (token korumalı, tablolar oluştuktan sonra bu satır silinir) ──
Route::get('/mig-sm-2026', function () {
    if (request('t') !== 'grtmig2026sm') abort(403);
    $out = [];
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations/2026_03_29_085116_create_sosyal_medya_icerikleri_table.php']);
        $out[] = trim(\Illuminate\Support\Facades\Artisan::output());
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations/2026_03_29_085117_create_ozel_gunler_table.php']);
        $out[] = trim(\Illuminate\Support\Facades\Artisan::output());
        $count = DB::table('ozel_gunler')->count();
        if ($count === 0) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\OzelGunlerSeeder', '--force' => true]);
            $out[] = trim(\Illuminate\Support\Facades\Artisan::output());
            $out[] = 'Seeder: ' . DB::table('ozel_gunler')->count() . ' kayıt eklendi.';
        } else {
            $out[] = "ozel_gunler zaten dolu ({$count} kayıt), seeder atlandı.";
        }
        $out[] = '✅ Tamamlandı.';
    } catch (\Throwable $e) {
        return response('❌ HATA: ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
    }
    return response(implode("\n", $out))->header('Content-Type', 'text/plain');
});

// Kampanya link takibi — public, auth gerektirmez
Route::get('/iz/{token}', [\App\Http\Controllers\KampanyaTiklamaController::class, 'izle'])->name('kampanya.izle');

// Blog — public
Route::get('/blog',                           [\App\Http\Controllers\BlogPublicController::class, 'index'])->name('blog.index');
Route::get('/blog/kategori/{kategori:slug}',  [\App\Http\Controllers\BlogPublicController::class, 'kategori'])->name('blog.kategori');
Route::get('/blog/{slug}',                    [\App\Http\Controllers\BlogPublicController::class, 'show'])->name('blog.show');

// Sitemap — dinamik (blog yazılarını dahil eder)
Route::get('/sitemap-dinamik.xml', [\App\Http\Controllers\SitemapController::class, 'index']);

// Ana sayfa — giriş yapılmışsa dashboard'a, yapmamışsa welcome'a

Route::get('/', function () {
    // B2C domain ise B2C ana sayfasına yönlendir
    $host = preg_replace('/^www\./', '', request()->getHost());
    if ($host === config('b2c.domain', 'gruprezervasyonlari.com')) {
        return app(\App\Http\Controllers\B2C\HomeController::class)->index();
    }

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

    $s = fn(string $k, string $d = '') => (string) \App\Models\SistemAyar::get($k, $d);

    $sirket = [
        'unvan'          => $s('sirket_unvan',          'Grup Talepleri Turizm San. ve Tic. Ltd. Şti.'),
        'vkn'            => $s('sirket_vkn',            '4110477529'),
        'vergi_dairesi'  => $s('sirket_vergi_dairesi',  'Beyoğlu VD'),
        'mersis_no'      => $s('sirket_mersis_no',      '0411047752900001'),
        'adres'          => $s('sirket_adres',          'İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli / İstanbul'),
        'telefon'        => $s('sirket_telefon',        '+90 535 415 47 99'),
        'cep'            => $s('sirket_cep',            ''),
        'whatsapp'       => $s('sirket_whatsapp',       '+90 535 415 47 99'),
        'eposta'         => $s('sirket_eposta',         'destek@gruptalepleri.com'),
        'tursab_no'      => $s('sirket_tursab_no',      '12572'),
        'tursab_grup'    => $s('sirket_tursab_grup',    'A'),
        'instagram'      => $s('sirket_instagram',      'grup.talepleri'),
        'facebook'       => $s('sirket_facebook',       ''),
        'twitter'        => $s('sirket_twitter',        ''),
        'linkedin'       => $s('sirket_linkedin',       ''),
    ];

    return view('welcome', compact('stats', 'sirket'));
})->name('b2c.home');

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

// Kısa talep linki: SMS/email içinde paylaşılabilir. Role göre yönlendir.
Route::middleware(['auth'])->get('/t/{gtpnr}', function (string $gtpnr) {
    $role = auth()->user()->role;
    if ($role === 'acente') {
        return redirect()->route('acente.requests.show', $gtpnr);
    }
    return redirect()->route('admin.requests.show', $gtpnr);
})->name('requests.short');

// Havalimanı & havayolu arama (giriş yapmış tüm roller)
Route::middleware(['auth'])->group(function () {
    Route::get('/airports/search', [AirportController::class, 'search'])->name('airports.search');
    Route::get('/airlines/search', [AirportController::class, 'airlineSearch'])->name('airlines.search');
});

// TÜRSAB sorgulama — kayıt sayfasında kullanılır, auth gerekmez
Route::get('/tursab-sorgula', [\App\Http\Controllers\TursabController::class, 'sorgula'])->name('tursab.sorgula');
Route::get('/kullanim-kosullari', fn() => view('sozlesme'))->name('sozlesme');

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

// Email tracking — public (auth olmadan, email clientlardan erişilir)
Route::get('/et/o/{token}', [\App\Http\Controllers\EmailTrackController::class, 'openPixel'])->name('email.track.open');
Route::get('/et/c/{token}', [\App\Http\Controllers\EmailTrackController::class, 'clickRedirect'])->name('email.track.click');

// Superadmin
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('superadmin.dashboard');
    })->name('dashboard');

    Route::get('/bekleyen-bildirimler', [\App\Http\Controllers\Superadmin\BekleyenBildirimlerController::class, 'index'])->name('bekleyen.bildirimler');

    Route::get('/show-last-error', function () {
        $log = storage_path('logs/laravel.log');
        if (!file_exists($log)) return response('Log yok');
        $lastLines = array_slice(file($log), -120);
        return response('<pre style="font-size:11px;padding:10px;">' . htmlspecialchars(implode('', $lastLines)) . '</pre>');
    });

    Route::post('/davet-ai-onizle', [\App\Http\Controllers\TursabController::class, 'davetAiOnizle'])->name('davet.ai.onizle');

    Route::get('/davet-onizleme-yeni', function () {
        return view('emails.tursab_davet_yeni_acente', [
            'acenteUnvani' => 'MAYA GLOBAL DMC TRAVEL AGENCY',
            'belgeNo'      => '18805',
            'kayitUrl'     => url('/register'),
        ]);
    });

    Route::get('/davet-onizleme', function () {
        return view('emails.tursab_davet', [
            'acenteUnvani' => 'ÖRNEK SEYAHAT ACENTASI',
            'belgeNo'      => '12345',
            'kayitUrl'     => url('/register'),
        ]);
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
    Route::post('/transfer/operasyon/vehicle-types', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'storeVehicleType'])
        ->name('transfer.ops.vehicle-types.store');
    Route::patch('/transfer/operasyon/vehicle-types/{vehicleType}', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'updateVehicleType'])
        ->name('transfer.ops.vehicle-types.update');
    Route::post('/transfer/operasyon/vehicle-types/{vehicleType}/media', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'storeVehicleMedia'])
        ->name('transfer.ops.vehicle-types.media.store');
    Route::post('/transfer/operasyon/vehicle-types/{vehicleType}/media-url', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'storeVehicleMediaUrl'])
        ->name('transfer.ops.vehicle-types.media.url.store');
    Route::delete('/transfer/operasyon/vehicle-types/media/{media}', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'deleteVehicleMedia'])
        ->name('transfer.ops.vehicle-types.media.delete');
    Route::post('/transfer/operasyon/suppliers/{supplier}/force-accept-terms', [\App\Http\Controllers\Transfer\SuperadminTransferOpsController::class, 'forceAcceptTerms'])
        ->name('transfer.ops.suppliers.force-accept-terms');
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
    Route::get('/tour', [\App\Http\Controllers\Admin\TourManagementController::class, 'index'])->name('tour.index');
    Route::get('/tour/{leisureRequest}', [\App\Http\Controllers\Admin\TourManagementController::class, 'show'])->name('tour.show');
    Route::post('/tour/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\TourManagementController::class, 'storeSupplierQuote'])->name('tour.supplier-quotes.store');
    Route::post('/tour/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\TourManagementController::class, 'storeClientOffer'])->name('tour.client-offers.store');
    Route::post('/tour/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\TourManagementController::class, 'startOperation'])->name('tour.start-operation');
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
    Route::post('/site-ayarlari/aktif-adim-yenile', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'aktifAdimYenile'])->name('aktif.adim.yenile');
    Route::post('/site-ayarlari/airline-senkronize', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'airlineSenkronize'])->name('airline.senkronize');
    Route::post('/site-ayarlari/sirket', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'sirketBilgileriGuncelle'])->name('sirket.guncelle');
    Route::get('/leisure-ayarlar', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'index'])->name('leisure.settings.index');
    Route::post('/leisure-ayarlar/paketler', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storePackage'])->name('leisure.settings.packages.store');
    Route::patch('/leisure-ayarlar/paketler/{template}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updatePackage'])->name('leisure.settings.packages.update');
    Route::post('/leisure-ayarlar/ekstralar', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storeExtra'])->name('leisure.settings.extras.store');
    Route::patch('/leisure-ayarlar/ekstralar/{option}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updateExtra'])->name('leisure.settings.extras.update');
    Route::post('/leisure-ayarlar/medya', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storeMedia'])->name('leisure.settings.media.store');
    Route::patch('/leisure-ayarlar/medya/{asset}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'updateMedia'])->name('leisure.settings.media.update');
    Route::post('/leisure-ayarlar/paketler/{template}/galeri', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'storeGalleryPhoto'])->name('leisure.settings.gallery.store');
    Route::delete('/leisure-ayarlar/galeri/{asset}', [\App\Http\Controllers\Superadmin\LeisureSettingsController::class, 'deleteGalleryPhoto'])->name('leisure.settings.gallery.delete');
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
    Route::post('/tursab-toplu-sms',   [\App\Http\Controllers\TursabController::class, 'topluSms'])->name('tursab.toplu-sms');
    Route::post('/tursab-scrape-start', [\App\Http\Controllers\TursabController::class, 'scrapeStart'])->name('tursab.scrape.start');
    Route::get('/tursab-scrape-status', [\App\Http\Controllers\TursabController::class, 'scrapeStatus'])->name('tursab.scrape.status');
    Route::post('/tursab-manuel-ekle', [\App\Http\Controllers\TursabController::class, 'manuelEkle'])->name('tursab.manuel-ekle');
    Route::post('/bakanlik-scrape-start',  [\App\Http\Controllers\TursabController::class, 'bakanlikScrapeStart'])->name('bakanlik.scrape.start');
    Route::get( '/bakanlik-scrape-status', [\App\Http\Controllers\TursabController::class, 'bakanlikScrapeStatus'])->name('bakanlik.scrape.status');
    Route::post('/acente-sync-start',  [\App\Http\Controllers\TursabController::class, 'aceneSyncBaslat'])->name('acente.sync.start');
    Route::get( '/acente-sync-status', [\App\Http\Controllers\TursabController::class, 'aceneSyncStatus'])->name('acente.sync.status');
    Route::get( '/kampanya/email',      [\App\Http\Controllers\TursabController::class, 'emailKampanya'])->name('kampanya.email');
    Route::get( '/kampanya/acenteler',  [\App\Http\Controllers\TursabController::class, 'acenteListesi'])->name('kampanya.acenteler');
    Route::get( '/kampanya/sms',        [\App\Http\Controllers\TursabController::class, 'smsKampanya'])->name('kampanya.sms');
    Route::get( '/kampanya/csv-import', [\App\Http\Controllers\TursabController::class, 'csvImportForm'])->name('kampanya.csv-import');
    Route::post('/kampanya/csv-import', [\App\Http\Controllers\TursabController::class, 'csvImportYukle'])->name('kampanya.csv-import.yukle');
    Route::get( '/tursab-ilceler',      [\App\Http\Controllers\TursabController::class, 'ilceler'])->name('tursab.ilceler');
    Route::get( '/kampanya/zamanlama',  [\App\Http\Controllers\TursabController::class, 'zamanlamaForm'])->name('kampanya.zamanlama');
    Route::post('/kampanya/zamanlama',  [\App\Http\Controllers\TursabController::class, 'zamanlamaKaydet'])->name('kampanya.zamanlama.kaydet');
    Route::post('/kampanya/zamanlama/test', [\App\Http\Controllers\TursabController::class, 'zamanlamaTestGonder'])->name('kampanya.zamanlama.test');
    Route::post('/kampanya/zamanlama/slot-sil', [\App\Http\Controllers\TursabController::class, 'slotSil'])->name('kampanya.zamanlama.slot-sil');
    Route::get('/kampanya/sicak-leadler', [\App\Http\Controllers\TursabController::class, 'sicakLeadler'])->name('kampanya.sicak-leadler');

    // Şablon kütüphanesi
    Route::get('/sablonlar',                    [\App\Http\Controllers\KampanyaSablonController::class, 'index'])->name('sablonlar.index');
    Route::get('/sablonlar/yeni',               [\App\Http\Controllers\KampanyaSablonController::class, 'create'])->name('sablonlar.create');
    Route::post('/sablonlar',                   [\App\Http\Controllers\KampanyaSablonController::class, 'store'])->name('sablonlar.store');
    Route::get('/sablonlar/{sablon}/duzenle',   [\App\Http\Controllers\KampanyaSablonController::class, 'edit'])->name('sablonlar.edit');
    Route::put('/sablonlar/{sablon}',           [\App\Http\Controllers\KampanyaSablonController::class, 'update'])->name('sablonlar.update');
    Route::delete('/sablonlar/{sablon}',        [\App\Http\Controllers\KampanyaSablonController::class, 'destroy'])->name('sablonlar.destroy');
    Route::get('/sablonlar/{sablon}/onizle',    [\App\Http\Controllers\KampanyaSablonController::class, 'preview'])->name('sablonlar.preview');

    // Kampanya yönetim rotaları
    Route::get('/kampanyalar',                  [\App\Http\Controllers\KampanyaController::class, 'index'])->name('kampanyalar.index');
    Route::get('/kampanyalar/yeni',             [\App\Http\Controllers\KampanyaController::class, 'create'])->name('kampanyalar.create');
    Route::post('/kampanyalar',                 [\App\Http\Controllers\KampanyaController::class, 'store'])->name('kampanyalar.store');
    Route::get('/kampanyalar/{kampanya}',        [\App\Http\Controllers\KampanyaController::class, 'show'])->name('kampanyalar.show');
    Route::post('/kampanyalar/{kampanya}/aktif', [\App\Http\Controllers\KampanyaController::class, 'aktifEt'])->name('kampanyalar.aktif');
    Route::post('/kampanyalar/{kampanya}/dur',   [\App\Http\Controllers\KampanyaController::class, 'durdur'])->name('kampanyalar.durdur');
    Route::delete('/kampanyalar/{kampanya}',     [\App\Http\Controllers\KampanyaController::class, 'destroy'])->name('kampanyalar.destroy');
    Route::get('/acenteler-istatistik', [\App\Http\Controllers\AcenetelIstatistikController::class, 'index'])->name('acenteler.istatistik');
    Route::get('/acenteler-normalize', [\App\Http\Controllers\AcenetelIstatistikController::class, 'normalize'])->name('acenteler.normalize');
    Route::get('/normalize-kaynak/{mode}', [\App\Http\Controllers\AcenetelIstatistikController::class, 'normalizeKaynak'])->name('normalize.kaynak');
    Route::get('/acenteler-tani', [\App\Http\Controllers\AcenetelIstatistikController::class, 'tani'])->name('acenteler.tani');
    Route::get('/acente-ai', [\App\Http\Controllers\AceneAIController::class, 'index'])->name('acente.ai');
    Route::post('/acente-ai/ask', [\App\Http\Controllers\AceneAIController::class, 'ask'])->name('acente.ai.ask');
    Route::post('/acente-ai/email-gonder', [\App\Http\Controllers\AceneAIController::class, 'emailGonder'])->name('acente.ai.email');
    Route::post('/acente-ai/sms-gonder', [\App\Http\Controllers\AceneAIController::class, 'smsGonder'])->name('acente.ai.sms');

    // Sosyal Medya Stüdyosu
    Route::get('/sosyal-medya',           [\App\Http\Controllers\SosyalMedyaController::class, 'index'])->name('sosyal.medya');
    Route::post('/sosyal-medya/uret',     [\App\Http\Controllers\SosyalMedyaController::class, 'uret'])->name('sosyal.medya.uret');
    Route::post('/sosyal-medya/gorsel',   [\App\Http\Controllers\SosyalMedyaController::class, 'gorselUret'])->name('sosyal.medya.gorsel');
    Route::post('/sosyal-medya/revize',   [\App\Http\Controllers\SosyalMedyaController::class, 'revize'])->name('sosyal.medya.revize');
    Route::post('/sosyal-medya/kaydet',   [\App\Http\Controllers\SosyalMedyaController::class, 'kaydet'])->name('sosyal.medya.kaydet');
    Route::get('/sosyal-medya/takvim',    [\App\Http\Controllers\SosyalMedyaController::class, 'takvim'])->name('sosyal.medya.takvim');
    Route::post('/sosyal-medya/buffer',   [\App\Http\Controllers\SosyalMedyaController::class, 'bufferGonder'])->name('sosyal.medya.buffer');
    Route::delete('/sosyal-medya/{id}',   [\App\Http\Controllers\SosyalMedyaController::class, 'sil'])->name('sosyal.medya.sil');

    // Acenteler
    Route::get('/acenteler', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteler'])->name('acenteler');
    Route::post('/acenteler/{agency}/toggle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteToggle'])->name('acenteler.toggle');
    Route::post('/acenteler/{agency}/rol', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteRolDegistir'])->name('acenteler.rol');
    Route::patch('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteGuncelle'])->name('acenteler.guncelle');
    Route::delete('/acenteler/{agency}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteSil'])->name('acenteler.sil');
    Route::post('/acenteler/{agency}/iade-badge', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteIadeBadgeToggle'])->name('acenteler.iade-badge');
    Route::post('/acenteler/{agency}/broadcast-yetki', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteBroadcastYetkiToggle'])->name('acenteler.broadcast-yetki');
    Route::post('/acenteler/{agency}/transfer-tedarikci', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'acenteTransferSupplierToggle'])->name('acenteler.transfer-supplier-toggle');
    Route::post('/acenteler/toplu-mesaj', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'topluMesajGonder'])->name('acenteler.toplu-mesaj');

    // Mesaj Şablonları
    Route::get('/mesaj-sablonlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'mesajSablonlari'])->name('mesaj.sablonlari');
    Route::post('/mesaj-sablonlari', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'mesajSablonKaydet'])->name('mesaj.sablonlari.kaydet');
    Route::patch('/mesaj-sablonlari/{sablon}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'mesajSablonKaydet'])->name('mesaj.sablonlari.guncelle');
    Route::delete('/mesaj-sablonlari/{sablon}', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'mesajSablonSil'])->name('mesaj.sablonlari.sil');
    Route::get('/mesaj-sablonlari/{sablon}/onizle', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'mesajSablonOnizle'])->name('mesaj.sablonlari.onizle');
    Route::post('/upload-email-image', [\App\Http\Controllers\Superadmin\SuperadminController::class, 'uploadEmailImage'])->name('upload-email-image');

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

    // Site İstatistikleri
    Route::get('/istatistik', [\App\Http\Controllers\Superadmin\StatsController::class, 'index'])->name('istatistik');

    // Sistem Olay Şablonları
    Route::get('/sistem-olaylari',                        [\App\Http\Controllers\Superadmin\SistemOlayController::class, 'index'])->name('sistem.olaylar');
    Route::post('/sistem-olaylari',                       [\App\Http\Controllers\Superadmin\SistemOlayController::class, 'store'])->name('sistem.olaylar.store');
    Route::get('/sistem-olaylari/{id}/duzenle',           [\App\Http\Controllers\Superadmin\SistemOlayController::class, 'edit'])->name('sistem.olaylar.edit');
    Route::put('/sistem-olaylari/{id}',                   [\App\Http\Controllers\Superadmin\SistemOlayController::class, 'update'])->name('sistem.olaylar.update');
    Route::post('/sistem-olaylari/{id}/sifirla',          [\App\Http\Controllers\Superadmin\SistemOlayController::class, 'sifirla'])->name('sistem.olaylar.sifirla');

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

    // Blog Yönetimi
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/',                              [\App\Http\Controllers\Superadmin\BlogController::class, 'index'])->name('index');
        Route::get('/yeni',                          [\App\Http\Controllers\Superadmin\BlogController::class, 'create'])->name('create');
        Route::post('/',                             [\App\Http\Controllers\Superadmin\BlogController::class, 'store'])->name('store');
        Route::get('/{blog}/duzenle',                [\App\Http\Controllers\Superadmin\BlogController::class, 'edit'])->name('edit');
        Route::put('/{blog}',                        [\App\Http\Controllers\Superadmin\BlogController::class, 'update'])->name('update');
        Route::delete('/{blog}',                     [\App\Http\Controllers\Superadmin\BlogController::class, 'destroy'])->name('destroy');
        Route::get('/kategoriler',                   [\App\Http\Controllers\Superadmin\BlogController::class, 'kategoriler'])->name('kategoriler');
        Route::post('/kategoriler',                  [\App\Http\Controllers\Superadmin\BlogController::class, 'kategoriStore'])->name('kategori.store');
        Route::delete('/kategoriler/{kategori}',     [\App\Http\Controllers\Superadmin\BlogController::class, 'kategoriDestroy'])->name('kategori.destroy');
    });

    // B2C Vitrin Yönetimi (gruprezervasyonlari.com)
    Route::prefix('b2c')->name('b2c.')->group(function () {
        Route::get('/dashboard',            [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'dashboard'])->name('dashboard');
        // Kategoriler
        Route::get('/kategoriler',          [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'categories'])->name('categories');
        Route::get('/kategoriler/yeni',     [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'categoryCreate'])->name('categories.create');
        Route::post('/kategoriler',         [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'categoryStore'])->name('categories.store');
        Route::get('/kategoriler/{category}/duzenle', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'categoryEdit'])->name('categories.edit');
        Route::put('/kategoriler/{category}',         [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'categoryUpdate'])->name('categories.update');
        // Ürün Kataloğu
        Route::get('/katalog',              [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalog'])->name('catalog');
        Route::get('/katalog/yeni',         [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogCreate'])->name('catalog.create');
        Route::post('/katalog',             [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogStore'])->name('catalog.store');
        Route::get('/katalog/{item}/duzenle', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogEdit'])->name('catalog.edit');
        Route::put('/katalog/{item}',        [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogUpdate'])->name('catalog.update');
        Route::post('/katalog/{item}/yayinla', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogTogglePublish'])->name('catalog.toggle-publish');
        Route::post('/katalog/{item}/one-cikan', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'catalogToggleFeatured'])->name('catalog.toggle-featured');
        // Leisure & Transfer → B2C köprü toggle'ları
        Route::post('/leisure/{template}/yayinla', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'leisureTogglePublish'])->name('leisure.toggle-publish');
        Route::post('/transfer-arac/{vehicleType}/yayinla', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'transferVehicleTogglePublish'])->name('transfer-vehicle.toggle-publish');
        // Tedarikçi Başvuruları
        Route::get('/tedarikci-basvurulari',  [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'supplierApplications'])->name('supplier-apps');
        Route::patch('/tedarikci-basvurulari/{app}', [\App\Http\Controllers\Superadmin\B2cCatalogController::class, 'supplierApplicationUpdate'])->name('supplier-apps.update');
        // Acente Başvuruları (B2C katılım)
        Route::get('/acenteler',                      [\App\Http\Controllers\Superadmin\B2cAgencyController::class, 'index'])->name('agencies');
        Route::patch('/acenteler/{sub}/onayla',       [\App\Http\Controllers\Superadmin\B2cAgencyController::class, 'approve'])->name('agencies.approve');
        Route::patch('/acenteler/{sub}/reddet',       [\App\Http\Controllers\Superadmin\B2cAgencyController::class, 'reject'])->name('agencies.reject');
        Route::patch('/acenteler/{sub}/askiya',       [\App\Http\Controllers\Superadmin\B2cAgencyController::class, 'suspend'])->name('agencies.suspend');
        // Başvuru beklemeden direkt onayla (superadmin kısayolu)
        Route::post('/acenteler/direkt-onayla',       [\App\Http\Controllers\Superadmin\B2cAgencyController::class, 'directApprove'])->name('agencies.direct-approve');
    });
});

// Admin
Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/bekleyen-bildirimler', [\App\Http\Controllers\Superadmin\BekleyenBildirimlerController::class, 'index'])->name('bekleyen.bildirimler');

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
    Route::get('/tour', [\App\Http\Controllers\Admin\TourManagementController::class, 'index'])->name('tour.index');
    Route::get('/tour/{leisureRequest}', [\App\Http\Controllers\Admin\TourManagementController::class, 'show'])->name('tour.show');
    Route::post('/tour/{leisureRequest}/supplier-quotes', [\App\Http\Controllers\Admin\TourManagementController::class, 'storeSupplierQuote'])->name('tour.supplier-quotes.store');
    Route::post('/tour/{leisureRequest}/client-offers', [\App\Http\Controllers\Admin\TourManagementController::class, 'storeClientOffer'])->name('tour.client-offers.store');
    Route::post('/tour/{leisureRequest}/start-operation', [\App\Http\Controllers\Admin\TourManagementController::class, 'startOperation'])->name('tour.start-operation');
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
    Route::post('/talepler/{gtpnr}/teklif/{offer}/geri-al', [\App\Http\Controllers\Admin\RequestController::class, 'geriAlOffer'])->name('requests.offer.geri-al');
    Route::delete('/talepler/{gtpnr}/teklif/{offer}', [\App\Http\Controllers\Admin\RequestController::class, 'deleteOffer'])->name('requests.offer.delete');
    Route::patch('/talepler/{gtpnr}', [\App\Http\Controllers\Admin\RequestController::class, 'updateRequest'])->name('requests.update');
    Route::get('/talepler/{gtpnr}/yolcular/export', function (string $gtpnr) {
        $talep = \App\Models\Request::where('gtpnr', $gtpnr)->firstOrFail();
        $yolcular = $talep->yolcular()->get();
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $gtpnr . '-yolcular.csv"',
        ];
        $callback = function () use ($yolcular, $talep) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Sira', 'Tur', 'Ad', 'Soyad', 'Kimlik No', 'Kimlik Tipi', 'Dogum Tarihi', 'Uyruk', 'Cinsiyet'], ';');
            foreach ($yolcular as $y) {
                fputcsv($handle, [$y->sira, $y->tur, $y->ad, $y->soyad, $y->kimlik_no, $y->kimlik_tipi, $y->dogum_tarihi, $y->uyruk, $y->cinsiyet], ';');
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    })->name('requests.yolcular.export');
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
    Route::get('/rezervasyonlarim', [\App\Http\Controllers\Acente\AcenteReservationsController::class, 'index'])
        ->name('rezervasyonlarim.index');
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
    // ── Dinner Cruise — GYG Katalog (yeni akış) ──────────────────────────
    Route::get('/dinner-cruise', [\App\Http\Controllers\Acente\DinnerCruiseCatalogController::class, 'catalog'])->name('dinner-cruise.catalog');
    Route::get('/dinner-cruise/urun/{code}', [\App\Http\Controllers\Acente\DinnerCruiseCatalogController::class, 'show'])->name('dinner-cruise.show-product');
    Route::post('/dinner-cruise/urun/{code}/rezervasyon', [\App\Http\Controllers\Acente\DinnerCruiseCatalogController::class, 'book'])->name('dinner-cruise.book');
    Route::get('/dinner-cruise/rezervasyon/{leisureRequest}', [\App\Http\Controllers\Acente\DinnerCruiseCatalogController::class, 'bookingShow'])->name('dinner-cruise.booking-show');
    // ── Dinner Cruise — Eski talep sistemi (korunuyor) ───────────────────
    Route::get('/dinner-cruise/talep-listesi', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'index'])->name('dinner-cruise.index');
    Route::get('/dinner-cruise/talep', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'create'])->name('dinner-cruise.create');
    Route::post('/dinner-cruise/talep', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'store'])->name('dinner-cruise.store');
    Route::get('/dinner-cruise/talep/{leisureRequest}', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'show'])->name('dinner-cruise.show');
    Route::post('/dinner-cruise/talep/{leisureRequest}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\DinnerCruiseController::class, 'acceptOffer'])->name('dinner-cruise.accept');
    Route::get('/dinner-cruise/teklif/{offer}/yazdir', \App\Http\Controllers\Acente\LeisureOfferPrintController::class)->name('dinner-cruise.offers.print');
    // ── Yacht Charter — GYG Katalog (yeni akış) ──────────────────────────
    Route::get('/yacht-charter', [\App\Http\Controllers\Acente\YachtCatalogController::class, 'catalog'])->name('yacht-charter.catalog');
    Route::get('/yacht-charter/urun/{code}', [\App\Http\Controllers\Acente\YachtCatalogController::class, 'show'])->name('yacht-charter.show-product');
    Route::post('/yacht-charter/urun/{code}/rezervasyon', [\App\Http\Controllers\Acente\YachtCatalogController::class, 'book'])->name('yacht-charter.book');
    Route::get('/yacht-charter/rezervasyon/{leisureRequest}', [\App\Http\Controllers\Acente\YachtCatalogController::class, 'bookingShow'])->name('yacht-charter.booking-show');
    // ── Yacht Charter — Eski talep sistemi (korunuyor) ───────────────────
    Route::get('/yacht-charter/taleplerim', [\App\Http\Controllers\Acente\YachtCharterController::class, 'index'])->name('yacht-charter.index');
    Route::get('/yacht-charter/talep', [\App\Http\Controllers\Acente\YachtCharterController::class, 'create'])->name('yacht-charter.create');
    Route::post('/yacht-charter/talep', [\App\Http\Controllers\Acente\YachtCharterController::class, 'store'])->name('yacht-charter.store');
    Route::get('/yacht-charter/talep/{leisureRequest}', [\App\Http\Controllers\Acente\YachtCharterController::class, 'show'])->name('yacht-charter.show');
    Route::post('/yacht-charter/talep/{leisureRequest}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\YachtCharterController::class, 'acceptOffer'])->name('yacht-charter.accept');
    Route::get('/yacht-charter/teklif/{offer}/yazdir', \App\Http\Controllers\Acente\LeisureOfferPrintController::class)->name('yacht-charter.offers.print');
    // ── Günübirlik Turlar — GYG Katalog ─────────────────────────────────────
    Route::get('/tour', [\App\Http\Controllers\Acente\TourCatalogController::class, 'catalog'])->name('tour.catalog');
    Route::get('/tour/urun/{code}', [\App\Http\Controllers\Acente\TourCatalogController::class, 'show'])->name('tour.show-product');
    Route::post('/tour/urun/{code}/rezervasyon', [\App\Http\Controllers\Acente\TourCatalogController::class, 'book'])->name('tour.book');
    Route::get('/tour/rezervasyon/{leisureRequest}', [\App\Http\Controllers\Acente\TourCatalogController::class, 'bookingShow'])->name('tour.booking-show');
    Route::post('/leisure/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLeisure'])->name('leisure.payments.gateway-start');

    Route::get('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'create'])->name('requests.create');
    Route::post('/talep/olustur', [\App\Http\Controllers\Acente\RequestController::class, 'store'])->name('requests.store');
    Route::get('/talep/{gtpnr}', [\App\Http\Controllers\Acente\RequestController::class, 'show'])->name('requests.show');
    Route::post('/talep/{gtpnr}/ai-analiz', [\App\Http\Controllers\Acente\RequestController::class, 'aiAnaliz'])->name('requests.ai-analiz');
    Route::post('/talep/{gtpnr}/turai', [\App\Http\Controllers\Acente\TuraiController::class, 'chat'])->name('requests.turai');
    Route::post('/turai/dashboard', [\App\Http\Controllers\Acente\TuraiController::class, 'dashboardChat'])->name('turai.dashboard');
    Route::post('/talep/{gtpnr}/acil-sms', [\App\Http\Controllers\Acente\TuraiController::class, 'acilSms'])->name('requests.acil-sms');
    Route::post('/talep/{gtpnr}/self-sms', [\App\Http\Controllers\Acente\TuraiController::class, 'selfSms'])->name('requests.self-sms');
    Route::post('/talep/{gtpnr}/ai-kaydet', [\App\Http\Controllers\Acente\RequestController::class, 'aiKaydet'])->name('requests.ai-kaydet');
    Route::post('/talep/{gtpnr}/teklif/{offer}/kabul', [\App\Http\Controllers\Acente\RequestController::class, 'acceptOffer'])->name('requests.accept');
    Route::post('/talep/{gtpnr}/odeme/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startLegacy'])->name('requests.gateway-payment.start');
    Route::post('/charter/bookings/{booking}/payments/gateway-start', [\App\Http\Controllers\Payments\ModulePaymentController::class, 'startCharter'])->name('charter.payments.gateway-start');

    Route::get('/profil', [\App\Http\Controllers\Acente\ProfileController::class, 'edit'])->name('profil');
    Route::put('/profil', [\App\Http\Controllers\Acente\ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/sifre', [\App\Http\Controllers\Acente\ProfileController::class, 'updatePassword'])->name('profil.sifre');
    Route::put('/profil/bildirim', [\App\Http\Controllers\Acente\ProfileController::class, 'updateBildirim'])->name('profil.bildirim');

    Route::get('/talep/yolcu-sablon', [\App\Http\Controllers\Acente\YolcuController::class, 'sablonIndir'])->name('yolcular.sablon');
    Route::get('/talep/{gtpnr}/yolcular', [\App\Http\Controllers\Acente\YolcuController::class, 'index'])->name('yolcular.index');
    Route::post('/talep/{gtpnr}/yolcular', [\App\Http\Controllers\Acente\YolcuController::class, 'store'])->name('yolcular.store');
    Route::delete('/talep/{gtpnr}/yolcular/{id}', [\App\Http\Controllers\Acente\YolcuController::class, 'destroy'])->name('yolcular.destroy');
    Route::post('/talep/{gtpnr}/yolcular/csv', [\App\Http\Controllers\Acente\YolcuController::class, 'csvYukle'])->name('yolcular.csv');

    Route::get('/calisanlar', [\App\Http\Controllers\Acente\CalisanController::class, 'index'])->name('calisanlar.index');
    Route::post('/calisanlar/davet', [\App\Http\Controllers\Acente\CalisanController::class, 'davetGonder'])->name('calisanlar.davet');
    Route::delete('/calisanlar/{id}', [\App\Http\Controllers\Acente\CalisanController::class, 'sil'])->name('calisanlar.sil');
    Route::patch('/calisanlar/{id}/yetki', [\App\Http\Controllers\Acente\CalisanController::class, 'yetkiGuncelle'])->name('calisanlar.yetki');

    // GrupRezervasyonlari.com B2C Katılım
    Route::get('/gruprezervasyonlari',       [\App\Http\Controllers\Acente\B2cSubscriptionController::class, 'index'])->name('b2c.index');
    Route::post('/gruprezervasyonlari',      [\App\Http\Controllers\Acente\B2cSubscriptionController::class, 'apply'])->name('b2c.apply');
    Route::post('/gruprezervasyonlari/filo', [\App\Http\Controllers\Acente\B2cSubscriptionController::class, 'saveFleet'])->name('b2c.fleet.save');

    });

    Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Çalışan davet kabul — auth gerektirmez
Route::get('/davet/{token}', [\App\Http\Controllers\DavetController::class, 'show'])->name('davet.show');
Route::post('/davet/{token}', [\App\Http\Controllers\DavetController::class, 'kabul'])->name('davet.kabul');

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

// E-posta abonelik yönetimi — kayıtlı kullanıcılar (signed URL)
Route::get('/abonelik/iptal/{user}',  [\App\Http\Controllers\AbonelikController::class, 'confirm'])->name('abonelik.confirm')->middleware('signed');
Route::post('/abonelik/iptal/{user}', [\App\Http\Controllers\AbonelikController::class, 'iptal'])->name('abonelik.iptal')->middleware('signed');
Route::post('/abonelik/baslat/{user}',[\App\Http\Controllers\AbonelikController::class, 'baslat'])->name('abonelik.baslat')->middleware('signed');

// Footer e-posta aboneliği — misafir ziyaretçiler
Route::post('/footer-abone',                        [\App\Http\Controllers\EmailAboneController::class, 'store'])->name('abone.store')->middleware('throttle:5,60');
Route::get('/abonelik/misafir-iptal/{token}',       [\App\Http\Controllers\EmailAboneController::class, 'iptal'])->name('abone.iptal');
Route::post('/abonelik/misafir-iptal/{token}',      [\App\Http\Controllers\EmailAboneController::class, 'iptalOnayla'])->name('abone.iptal.onayla');
Route::post('/abonelik/misafir-baslat/{token}',     [\App\Http\Controllers\EmailAboneController::class, 'baslatOnayla'])->name('abone.baslat.onayla');

require __DIR__.'/auth.php';

// B2C route'ları web.php'den SONRA yüklenir — sıra sorunu bu şekilde çözülür
require __DIR__.'/b2c.php';
