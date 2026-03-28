<?php
if (($_GET['t'] ?? '') !== 'grt2026fix') { http_response_code(403); die('Forbidden'); }

try {
    $pdo = new PDO('mysql:host=localhost;dbname=gruprez1_gruptalepleri;charset=utf8mb4',
        'gruprez1_gruprez1', 'pqcAnGRD6qF0Sz.G',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $sonuc = [];

    // tip kolonu
    $cols = $pdo->query("SHOW COLUMNS FROM tursab_davetler LIKE 'tip'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE tursab_davetler ADD COLUMN tip VARCHAR(10) NOT NULL DEFAULT 'email' AFTER status");
        $sonuc[] = "✓ tip kolonu eklendi";
    } else {
        $sonuc[] = "- tip kolonu zaten vardı";
    }

    // icerik kolonu
    $cols2 = $pdo->query("SHOW COLUMNS FROM tursab_davetler LIKE 'icerik'")->fetchAll();
    if (empty($cols2)) {
        $pdo->exec("ALTER TABLE tursab_davetler ADD COLUMN icerik TEXT NULL AFTER tip");
        $sonuc[] = "✓ icerik kolonu eklendi";
    } else {
        $sonuc[] = "- icerik kolonu zaten vardı";
    }

    echo implode("\n", $sonuc) . "\nTAMAM";
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}
