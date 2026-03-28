<?php
if (($_GET['t'] ?? '') !== 'grt2026dp') { http_response_code(403); exit; }

// OpCache temizle (web SAPI'de)
if (function_exists('opcache_reset')) opcache_reset();

$base = __DIR__ . '/..';

// Route cache sil
@unlink("$base/bootstrap/cache/routes-v7.php");
@unlink("$base/bootstrap/cache/config.php");

// View cache sil
foreach (glob("$base/storage/framework/views/*.php") ?: [] as $f) @unlink($f);

echo 'OK';
