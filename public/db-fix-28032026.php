<?php
if (($_GET['k'] ?? '') !== 'grt9x') { http_response_code(403); die('403'); }
$dryRun = ($_GET['m'] ?? 'dry') !== 'run';

$env = [];
foreach (file(__DIR__.'/../.env') as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line,'#') && str_contains($line,'=')) {
        [$a,$b] = explode('=', $line, 2);
        $env[trim($a)] = trim($b,"\"'");
    }
}
$pdo = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$out = [$dryRun ? '=== DRY-RUN ===' : '=== UYGULANIYOR ===', ''];
$out[] = '-- Mevcut dağılım --';
foreach ($pdo->query("SELECT COALESCE(kaynak,'NULL') k, COUNT(*) t FROM acenteler GROUP BY k ORDER BY t DESC")->fetchAll(PDO::FETCH_ASSOC) as $r)
    $out[] = "  {$r['k']}: {$r['t']}";

$out[] = ''; $out[] = '-- kaynak normalize --';
foreach (['tursab'=>"LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'",'bakanlik'=>"LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'",'manuel'=>"LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'"] as $v=>$c) {
    $n = $pdo->query("SELECT COUNT(*) FROM acenteler WHERE $c AND kaynak!='$v'")->fetchColumn();
    $out[] = "  '$v' → $n kayıt";
    if (!$dryRun && $n) $pdo->exec("UPDATE acenteler SET kaynak='$v' WHERE $c AND kaynak!='$v'");
}

$out[] = ''; $out[] = '-- is_sube normalize --';
$n = $pdo->query("SELECT COUNT(*) FROM acenteler WHERE is_sube=0 AND (UPPER(acente_unvani) LIKE '%ŞUBE%' OR UPPER(acente_unvani) LIKE '%SUBE%')")->fetchColumn();
$out[] = "  ŞUBE adında ama is_sube=0: $n";
if (!$dryRun && $n) $pdo->exec("UPDATE acenteler SET is_sube=1 WHERE is_sube=0 AND (UPPER(acente_unvani) LIKE '%ŞUBE%' OR UPPER(acente_unvani) LIKE '%SUBE%')");

if (!$dryRun) {
    $out[] = ''; $out[] = '-- Sonuç --';
    foreach ($pdo->query("SELECT COALESCE(kaynak,'NULL') k, COUNT(*) t FROM acenteler GROUP BY k ORDER BY t DESC")->fetchAll(PDO::FETCH_ASSOC) as $r)
        $out[] = "  {$r['k']}: {$r['t']}";
    $out[] = '  is_sube=1: '.$pdo->query("SELECT COUNT(*) FROM acenteler WHERE is_sube=1")->fetchColumn();
}
$out[] = ''; $out[] = $dryRun ? '>> Uygulamak: ?k=grt9x&m=run' : '>> TAMAMLANDI!';
?>
<style>body{background:#111;margin:0;padding:20px;}</style>
<pre style="color:#0f0;font-size:15px;"><?=implode("\n",$out)?></pre>
<?php if($dryRun):?><a href="?k=grt9x&m=run" style="background:#e94560;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-size:16px;display:inline-block;margin:10px 0;">▶ UYGULA</a><?php endif;?>
