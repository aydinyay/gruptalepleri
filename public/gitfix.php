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
