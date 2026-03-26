<?php
// Veritabanı bağlantısı
$pdo = new PDO(
    //'mysql:host=localhost;dbname=gruprez1_vt;charset=utf8mb4',
    'mysql:host=localhost;dbname=gruprez1_gruptalepleri;charset=utf8mb4',


    'gruprez1_gruprez1',
    'pqcAnGRD6qF0Sz.G',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$analizler = [];

// 1. Toplam acenta sayısı
$analizler['toplam'] = $pdo->query("SELECT COUNT(*) FROM acenteler")->fetchColumn();

// 2. Şehirlere göre dağılım (TOP 20 - en çok)
$analizler['en_cok_il'] = $pdo->query("
    SELECT il, COUNT(*) as sayi
    FROM acenteler
    WHERE il IS NOT NULL AND il != ''
    GROUP BY il
    ORDER BY sayi DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// 3. En az acenteli şehirler
$analizler['en_az_il'] = $pdo->query("
    SELECT il, COUNT(*) as sayi
    FROM acenteler
    WHERE il IS NOT NULL AND il != ''
    GROUP BY il
    ORDER BY sayi ASC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Grup dağılımı (A/B/diğer)
$analizler['grup'] = $pdo->query("
    SELECT
        COALESCE(NULLIF(grup,''), 'Belirtilmemiş') as grup,
        COUNT(*) as sayi
    FROM acenteler
    GROUP BY grup
    ORDER BY sayi DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 5. E-posta durumu
$analizler['eposta'] = $pdo->query("
    SELECT
        SUM(CASE WHEN eposta IS NOT NULL AND eposta != '' THEN 1 ELSE 0 END) as var,
        SUM(CASE WHEN eposta IS NULL OR eposta = '' THEN 1 ELSE 0 END) as yok
    FROM acenteler
")->fetch(PDO::FETCH_ASSOC);

// 6. İlçe bazlı TOP 10
$analizler['en_cok_ilce'] = $pdo->query("
    SELECT il, il_ilce, COUNT(*) as sayi
    FROM acenteler
    WHERE il IS NOT NULL AND il != ''
    GROUP BY il, il_ilce
    ORDER BY sayi DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 7. Telefonu olan vs olmayan
$analizler['telefon'] = $pdo->query("
    SELECT
        SUM(CASE WHEN telefon IS NOT NULL AND telefon != '' THEN 1 ELSE 0 END) as var,
        SUM(CASE WHEN telefon IS NULL OR telefon = '' THEN 1 ELSE 0 END) as yok
    FROM acenteler
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Acenta İstatistikleri</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; background: #f5f5f5; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .kart { background: white; border-radius: 8px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .toplam { font-size: 48px; font-weight: bold; color: #3498db; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #3498db; color: white; padding: 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .bar { background: #3498db; height: 20px; border-radius: 3px; display: inline-block; }
        .yuzde { color: #7f8c8d; font-size: 13px; }
    </style>
</head>
<body>
<h1>Acenta Analiz Raporu</h1>

<div class="kart">
    <h2>Toplam Acenta Sayısı</h2>
    <div class="toplam"><?= number_format($analizler['toplam'], 0, ',', '.') ?></div>
</div>

<div class="kart">
    <h2>En Çok Acenteli Şehirler (TOP 20)</h2>
    <table>
        <tr><th>Şehir</th><th>Acenta Sayısı</th><th>Oran</th><th></th></tr>
        <?php foreach ($analizler['en_cok_il'] as $r):
            $oran = round($r['sayi'] / $analizler['toplam'] * 100, 1); ?>
        <tr>
            <td><?= htmlspecialchars($r['il']) ?></td>
            <td><strong><?= number_format($r['sayi'], 0, ',', '.') ?></strong></td>
            <td class="yuzde">%<?= $oran ?></td>
            <td><span class="bar" style="width:<?= min($oran * 5, 200) ?>px"></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="kart">
    <h2>En Az Acenteli Şehirler</h2>
    <table>
        <tr><th>Şehir</th><th>Acenta Sayısı</th></tr>
        <?php foreach ($analizler['en_az_il'] as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['il']) ?></td>
            <td><?= $r['sayi'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="kart">
    <h2>Grup Dağılımı</h2>
    <table>
        <tr><th>Grup</th><th>Sayı</th><th>Oran</th></tr>
        <?php foreach ($analizler['grup'] as $r):
            $oran = round($r['sayi'] / $analizler['toplam'] * 100, 1); ?>
        <tr>
            <td><?= htmlspecialchars($r['grup']) ?></td>
            <td><?= number_format($r['sayi'], 0, ',', '.') ?></td>
            <td>%<?= $oran ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="kart">
    <h2>En Çok Acenteli İlçeler (TOP 10)</h2>
    <table>
        <tr><th>İl</th><th>İlçe</th><th>Sayı</th></tr>
        <?php foreach ($analizler['en_cok_ilce'] as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['il']) ?></td>
            <td><?= htmlspecialchars($r['il_ilce']) ?></td>
            <td><?= number_format($r['sayi'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="kart">
    <h2>İletişim Bilgisi Durumu</h2>
    <table>
        <tr><th></th><th>E-posta</th><th>Telefon</th></tr>
        <tr>
            <td>Kayıtlı</td>
            <td><?= number_format($analizler['eposta']['var'], 0, ',', '.') ?>
                <span class="yuzde">(%<?= round($analizler['eposta']['var']/$analizler['toplam']*100,1) ?>)</span></td>
            <td><?= number_format($analizler['telefon']['var'], 0, ',', '.') ?>
                <span class="yuzde">(%<?= round($analizler['telefon']['var']/$analizler['toplam']*100,1) ?>)</span></td>
        </tr>
        <tr>
            <td>Eksik</td>
            <td><?= number_format($analizler['eposta']['yok'], 0, ',', '.') ?>
                <span class="yuzde">(%<?= round($analizler['eposta']['yok']/$analizler['toplam']*100,1) ?>)</span></td>
            <td><?= number_format($analizler['telefon']['yok'], 0, ',', '.') ?>
                <span class="yuzde">(%<?= round($analizler['telefon']['yok']/$analizler['toplam']*100,1) ?>)</span></td>
        </tr>
    </table>
</div>

</body>
</html>
