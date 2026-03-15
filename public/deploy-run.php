<?php
// GÜVENLİK: Bu dosyayı kullandıktan sonra HEMEN SİLİN!
$secret = $_GET['key'] ?? '';
if ($secret !== 'gtp2026deploy') {
    die('Yetkisiz erişim.');
}

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$action = $_GET['action'] ?? 'status';
ob_start();

if ($action === 'migrate') {
    $kernel->call('migrate', ['--force' => true]);
} elseif ($action === 'cache-clear') {
    $kernel->call('config:clear');
    $kernel->call('cache:clear');
    $kernel->call('view:clear');
    $kernel->call('route:clear');
} elseif ($action === 'status') {
    $kernel->call('migrate:status');
} elseif ($action === 'import-airports') {
    set_time_limit(300);
    $kernel->call('airports:import');
} elseif ($action === 'import-airlines') {
    set_time_limit(300);
    $kernel->call('airlines:import');
} elseif ($action === 'sync-legacy-offers') {
    set_time_limit(300);
    $kernel->call('legacy:sync-offers');
}

$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Deploy Runner</title>
<style>body{font-family:monospace;background:#1a1a2e;color:#fff;padding:2rem;}
pre{background:#000;padding:1rem;border-radius:8px;color:#0f0;white-space:pre-wrap;}
.btn{display:inline-block;padding:10px 20px;margin:8px 4px;border-radius:6px;text-decoration:none;font-weight:bold;}
.red{background:#e94560;color:#fff;} .green{background:#198754;color:#fff;} .blue{background:#0d6efd;color:#fff;}
.warn{background:#fff3cd;color:#856404;padding:1rem;border-radius:8px;margin-bottom:1rem;}
</style>
</head>
<body>
<h2>🚀 Deploy Runner — GrupTalepleri</h2>
<div class="warn">⚠️ Bu dosyayı kullandıktan sonra <strong>hemen silin!</strong></div>

<a href="?key=gtp2026deploy&action=status" class="btn blue">📋 Migration Durumu</a>
<a href="?key=gtp2026deploy&action=migrate" class="btn green"
   onclick="return confirm('Migration çalıştırılsın mı?')">▶ Migrate Çalıştır</a>
<a href="?key=gtp2026deploy&action=cache-clear" class="btn red">🗑 Cache Temizle</a>
<a href="?key=gtp2026deploy&action=import-airports" class="btn green"
   onclick="return confirm('Havalimanları içe aktarılsın mı? (1-2 dk sürebilir)')">✈ Havalimanları İçe Aktar</a>
<a href="?key=gtp2026deploy&action=import-airlines" class="btn green"
   onclick="return confirm('Havayolları içe aktarılsın mı?')">🛫 Havayolları İçe Aktar</a>
<a href="?key=gtp2026deploy&action=sync-legacy-offers" class="btn green"
   onclick="return confirm('Eski sistemden opsiyon/fiyat verileri yeni sisteme aktarılsın mı?')">🔄 Eski Sistem Opsiyon Sync</a>

<?php if ($output): ?>
<h3>Çıktı:</h3>
<pre><?= htmlspecialchars($output) ?></pre>
<?php endif; ?>

<p style="color:#666;font-size:0.8rem;">
    PHP: <?= PHP_VERSION ?> |
    Laravel: <?= app()->version() ?> |
    <?= now()->format('d.m.Y H:i:s') ?>
</p>
</body>
</html>
