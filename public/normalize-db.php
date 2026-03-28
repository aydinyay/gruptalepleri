<?php
// Güvenlik: token kontrolü
$token = 'grt2024normalize';
if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    die('<pre>403 Forbidden — ?token= parametresi gerekli</pre>');
}

$dryRun = ($_GET['mode'] ?? 'dry') !== 'run';

// DB bağlantısı (.env dosyasından)
$envFile = __DIR__ . '/../.env';
$env = [];
foreach (file($envFile) as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, '"\'');
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die('<pre>DB bağlantı hatası: ' . $e->getMessage() . '</pre>');
}

$log = [];
$log[] = ($dryRun ? '🔍 DRY-RUN modu — hiçbir değişiklik uygulanmayacak' : '✅ UYGULAMA modu');
$log[] = '';

// Mevcut dağılım
$log[] = '─── Mevcut kaynak dağılımı ───';
$rows = $pdo->query("SELECT COALESCE(kaynak, 'NULL') as kaynak, COUNT(*) as toplam FROM acenteler GROUP BY kaynak ORDER BY toplam DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $log[] = "  {$r['kaynak']}: {$r['toplam']}";
}
$log[] = '';

// Normalizasyon kuralları
$rules = [
    'tursab'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'",
    'bakanlik' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'",
    'manuel'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'",
];

$log[] = '─── kaynak normalizasyonu ───';
foreach ($rules as $val => $cond) {
    $count = $pdo->query("SELECT COUNT(*) FROM acenteler WHERE {$cond} AND kaynak != '{$val}'")->fetchColumn();
    $log[] = "  '{$val}' → {$count} kayıt normalize edilecek";
    if (!$dryRun && $count > 0) {
        $pdo->exec("UPDATE acenteler SET kaynak = '{$val}' WHERE {$cond} AND kaynak != '{$val}'");
    }
}

$log[] = '';
$log[] = '─── is_sube normalizasyonu ───';
$subeCount = $pdo->query("SELECT COUNT(*) FROM acenteler WHERE is_sube = 0 AND (UPPER(acente_unvani) LIKE '%ŞUBE%' OR UPPER(acente_unvani) LIKE '%SUBE%')")->fetchColumn();
$log[] = "  is_sube=0 ama adında ŞUBE geçen: {$subeCount} kayıt";
if (!$dryRun && $subeCount > 0) {
    $pdo->exec("UPDATE acenteler SET is_sube = 1 WHERE is_sube = 0 AND (UPPER(acente_unvani) LIKE '%ŞUBE%' OR UPPER(acente_unvani) LIKE '%SUBE%')");
}

if (!$dryRun) {
    $log[] = '';
    $log[] = '─── Normalizasyon sonrası ───';
    $rows = $pdo->query("SELECT COALESCE(kaynak, 'NULL') as kaynak, COUNT(*) as toplam FROM acenteler GROUP BY kaynak ORDER BY toplam DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $log[] = "  {$r['kaynak']}: {$r['toplam']}";
    }
    $subeTotal = $pdo->query("SELECT COUNT(*) FROM acenteler WHERE is_sube = 1")->fetchColumn();
    $log[] = "  Toplam is_sube=1: {$subeTotal}";
}

$log[] = '';
$log[] = $dryRun
    ? '👆 Uygulamak için: ?token=' . $token . '&mode=run'
    : '✅ Tamamlandı!';
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>DB Normalize</title></head>
<body style="background:#1a1a2e;margin:0;padding:20px;">
<pre style="color:#0f0;font-family:monospace;font-size:14px;line-height:1.6;"><?= implode("\n", $log) ?></pre>
<?php if ($dryRun): ?>
<a href="?token=<?= $token ?>&mode=run" style="display:inline-block;background:#e94560;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-family:monospace;font-size:16px;margin-top:10px;">▶ Uygulamak için tıkla</a>
<?php endif; ?>
</body></html>
