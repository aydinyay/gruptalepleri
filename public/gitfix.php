<?php
if (($_GET['t'] ?? '') !== 'grt2026fix') {
    http_response_code(403); die('Forbidden');
}

$webRoot = '/home/gruprez1/gruptalepleri.com';
$zipPath = '/home/gruprez1/deploy.zip';

if (!file_exists($zipPath)) { die("HATA: deploy.zip yok"); }
if (!class_exists('ZipArchive')) { die("HATA: ZipArchive yok"); }

$zip = new ZipArchive();
if ($zip->open($zipPath) !== TRUE) { die("HATA: ZIP açılamadı"); }

$result = $zip->extractTo($webRoot);
$zip->close();

@unlink($webRoot . '/bootstrap/cache/routes-v7.php');
@unlink($webRoot . '/bootstrap/cache/config.php');
if (function_exists('opcache_reset')) opcache_reset();

echo $result ? "BAŞARILI" : "HATA: extract başarısız";
