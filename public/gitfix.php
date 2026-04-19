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
        'EventCategorySeeder',
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

// Laravel log — son 80 satır
if (($_GET['action'] ?? '') === 'log') {
    header('Content-Type: text/plain; charset=utf-8');
    $logFile = "$webRoot/storage/logs/laravel.log";
    if (!file_exists($logFile)) { echo "Log dosyası bulunamadı."; exit; }
    $lines = file($logFile);
    echo implode('', array_slice($lines, -80));
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

// Gemini API key + model listesi kontrolü
if (($_GET['action'] ?? '') === 'geminicheck') {
    header('Content-Type: text/plain');
    $envFile = "$webRoot/.env";
    $content = file_get_contents($envFile);
    preg_match('/^GEMINI_API_KEY=(.*)$/m', $content, $m);
    $key = trim($m[1] ?? '');
    if (!$key) { echo "GEMINI_API_KEY .env'de yok veya boş!\n"; exit; }
    echo "Key bulundu: " . substr($key, 0, 8) . "...\n\n";
    // ListModels çağrısı
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP: $http\n";
    $data = json_decode($resp, true);
    if (isset($data['models'])) {
        echo "Kullanılabilir modeller:\n";
        foreach ($data['models'] as $model) {
            $name = $model['name'] ?? '';
            $methods = implode(',', $model['supportedGenerationMethods'] ?? []);
            if (str_contains($methods, 'generateContent')) {
                echo "  ✓ $name\n";
            }
        }
    } else {
        echo $resp;
    }
    exit;
}

// Route listesi diagnostiği
if (($_GET['action'] ?? '') === 'routes') {
    define('LARAVEL_START', microtime(true));
    require $webRoot . '/vendor/autoload.php';
    $app = require_once $webRoot . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    header('Content-Type: text/plain');
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_contains($name, 'acente')) {
            echo $route->methods()[0] . ' ' . $route->uri() . ' → ' . $name . "\n";
        }
    }
    exit;
}

// web.php satır kontrolü
if (($_GET['action'] ?? '') === 'checkweb') {
    header('Content-Type: text/plain');
    $webphp = file("$webRoot/routes/web.php");
    echo "Total lines: " . count($webphp) . "\n";
    echo "mtime: " . date('Y-m-d H:i:s', filemtime("$webRoot/routes/web.php")) . "\n\n";
    foreach ($webphp as $i => $line) {
        if (str_contains($line, 'katalog') || str_contains($line, 'CatalogProduct') || str_contains($line, 'acente.catalog') || str_contains($line, 'acente.product')) {
            echo ($i+1) . ': ' . $line;
        }
    }
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
    $tail = array_slice($lines, -300);
    header('Content-Type: text/plain');
    echo implode('', $tail);
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
if (function_exists('opcache_invalidate')) {
    foreach ([
        "$webRoot/routes/web.php",
        "$webRoot/routes/b2c.php",
        "$webRoot/routes/auth.php",
        "$webRoot/app/Http/Controllers/Acente/CatalogProductController.php",
    ] as $f) {
        opcache_invalidate($f, true);
    }
}
if (function_exists('opcache_reset')) opcache_reset();
echo "CACHE_CLEARED";
