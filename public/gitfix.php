<?php
$token = 'grt2026fix';
if (($_POST['t'] ?? $_GET['t'] ?? '') !== $token) {
    http_response_code(403); die('Forbidden');
}

$webRoot = '/home/gruprez1/gruptalepleri.com';

// Dosya yazma modu
if (!empty($_POST['p']) && isset($_POST['c'])) {
    $path = $_POST['p'];
    if (str_contains($path, '..')) die('INVALID');
    $full = "$webRoot/$path";
    $dir = dirname($full);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $result = file_put_contents($full, base64_decode($_POST['c']));
    echo $result !== false ? "OK" : "FAIL";
    exit;
}

// Migration çalıştırma
if (($_GET['action'] ?? '') === 'migrate') {
    define('LARAVEL_START', microtime(true));
    require $webRoot . '/vendor/autoload.php';
    $app = require_once $webRoot . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    header('Content-Type: text/plain');
    echo "MIGRATE_DONE exitCode={$exitCode}\n";
    echo \Illuminate\Support\Facades\Artisan::output();
    exit;
}

// Seeder çalıştırma — izin verilenler listesi (güvenlik katmanı)
if (($_GET['action'] ?? '') === 'seed') {
    $seeder = trim($_GET['class'] ?? '');
    $allowedSeeders = [
        'BestawayB2cApprovalSeeder',
        'B2cSampleDataSeeder',
        'TransferVehicleTypeSeeder',
        'TransferAirportSeeder',
    ];
    if (!in_array($seeder, $allowedSeeders, true)) {
        header('Content-Type: text/plain');
        http_response_code(400);
        echo "FORBIDDEN: '$seeder' izin verilmedi.\n";
        echo "İzin verilenler: " . implode(', ', $allowedSeeders) . "\n";
        exit;
    }
    define('LARAVEL_START', microtime(true));
    require $webRoot . '/vendor/autoload.php';
    $app = require_once $webRoot . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $exitCode = \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
    header('Content-Type: text/plain');
    echo "SEED_DONE class={$seeder} exitCode={$exitCode}\n";
    echo \Illuminate\Support\Facades\Artisan::output();
    exit;
}

// Compiled view diagnostiği
if (($_GET['action'] ?? '') === 'diag') {
    header('Content-Type: text/plain');
    $viewsDir = "$webRoot/storage/framework/views";
    $files = glob("$viewsDir/*.php") ?: [];
    sort($files);
    echo "Views dir: $viewsDir\n";
    echo "Compiled count: " . count($files) . "\n\n";
    // booking.blade.php'nin hash'ini hesapla
    $bookingPath = "$webRoot/resources/views/transfer/booking.blade.php";
    echo "booking.blade.php exists: " . (file_exists($bookingPath) ? 'YES' : 'NO') . "\n";
    echo "booking.blade.php mtime: " . (file_exists($bookingPath) ? date('Y-m-d H:i:s', filemtime($bookingPath)) : '-') . "\n";
    echo "md5(path): " . md5($bookingPath) . "\n";
    echo "xxh128(path): " . hash('xxh128', $bookingPath) . "\n";
    // Bir sonraki compile dosyasını bul (mtime en yenisi)
    echo "\nAll compiled views (newest first):\n";
    usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
    foreach (array_slice($files, 0, 10) as $f) {
        echo basename($f) . "  " . date('H:i:s', filemtime($f)) . "\n";
    }
    // Tüm compiled view'ların 110-135. satırlarını göster (parse error bölgesi)
    echo "\n=== COMPILED VIEW CONTENTS (lines 110-135) ===\n";
    usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
    foreach ($files as $f) {
        echo "\n--- " . basename($f) . " ---\n";
        $allLines = file($f);
        $total = count($allLines);
        echo "Total lines: $total\n";
        $start = max(0, 108); // line 109 (0-indexed)
        $end   = min($total, 140);
        foreach (array_slice($allLines, $start, $end - $start, true) as $ln => $content) {
            echo ($ln + 1) . ': ' . $content;
        }
    }
    exit;
}

// .env değişken yazma (sadece izin verilen anahtarlar)
if (($_GET['action'] ?? '') === 'setenv') {
    $key   = strtoupper(trim($_GET['key'] ?? ''));
    $value = trim($_GET['value'] ?? '');
    $allowed = ['ASSET_URL', 'APP_URL', 'APP_ENV', 'APP_DEBUG'];
    if (!in_array($key, $allowed, true)) {
        http_response_code(400);
        echo "FORBIDDEN: '$key' değiştirilemez."; exit;
    }
    $envFile = "$webRoot/.env";
    if (!file_exists($envFile)) { echo "ENV_NOT_FOUND"; exit; }
    $content = file_get_contents($envFile);
    // Mevcut satırı değiştir veya sona ekle
    $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
    $line = $key . '=' . $value;
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $line, $content);
    } else {
        $content .= "\n" . $line . "\n";
    }
    file_put_contents($envFile, $content);
    // Config cache temizle
    @unlink("$webRoot/bootstrap/cache/config.php");
    if (function_exists('opcache_reset')) opcache_reset();
    echo "ENV_SET: $line";
    exit;
}

// Hata logu okuma
if (($_GET['action'] ?? '') === 'log') {
    $which = $_GET['which'] ?? 'laravel';
    $logFile = $which === 'cron'
        ? "$webRoot/storage/logs/cron.log"
        : "$webRoot/storage/logs/laravel.log";
    if (!file_exists($logFile)) { echo "Log yok: $logFile"; exit; }
    $lines = file($logFile);
    $tail = array_slice($lines, -100);
    header('Content-Type: text/plain');
    echo implode('', $tail);
    exit;
}

// Katalog ürün sorgulama
if (($_GET['action'] ?? '') === 'catalog-check') {
    define('LARAVEL_START', microtime(true));
    require $webRoot . '/vendor/autoload.php';
    $app = require_once $webRoot . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    header('Content-Type: text/plain');
    $slug = $_GET['slug'] ?? '';
    if ($slug) {
        $item = \Illuminate\Support\Facades\DB::table('catalog_items')->where('slug', $slug)->first();
        echo $item ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : "NOT FOUND: $slug";
    } else {
        $items = \Illuminate\Support\Facades\DB::table('catalog_items')
            ->select('id','slug','title','is_published','is_active','product_type','pricing_type','base_price','currency')
            ->orderBy('id','desc')->limit(20)->get();
        echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

// Cache temizleme
@unlink("$webRoot/bootstrap/cache/routes-v7.php");
@unlink("$webRoot/bootstrap/cache/config.php");
@unlink("$webRoot/bootstrap/cache/services.php");
@unlink("$webRoot/bootstrap/cache/packages.php");
// Storage lock dosyasını da temizle
@unlink("$webRoot/storage/app/kampanya-email.lock");
// View cache temizle (compiled Blade dosyaları)
foreach (glob("$webRoot/storage/framework/views/*.php") ?: [] as $f) {
    @unlink($f);
}
if (function_exists('opcache_reset')) opcache_reset();
echo "CACHE_CLEARED";
