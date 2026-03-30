<?php
if (($_GET['t'] ?? '') !== 'grt2026chk') { http_response_code(403); exit; }
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gruprez1_gruptalepleri;charset=utf8mb4',
        'gruprez1_gruprez1', 'pqcAnGRD6qF0Sz.G',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $gtpnr = $_GET['gtpnr'] ?? '';
    $stmt = $pdo->prepare("SELECT id, status, user_id, created_at FROM requests WHERE gtpnr = ?");
    $stmt->execute([$gtpnr]);
    $talep = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$talep) { echo "TALEP YOK: $gtpnr\n"; exit; }
    echo "ID: {$talep['id']} | Status: {$talep['status']} | User: {$talep['user_id']} | Oluşturuldu: {$talep['created_at']}\n\n";

    // Kolon isimlerini öğren
    $cols = $pdo->query("SHOW COLUMNS FROM request_notifications")->fetchAll(PDO::FETCH_COLUMN);
    echo "Kolonlar: " . implode(', ', $cols) . "\n\n";

    $stmt2 = $pdo->prepare("SELECT * FROM request_notifications WHERE request_id = ? ORDER BY created_at DESC");
    $stmt2->execute([$talep['id']]);
    $notifler = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Bildirim sayısı: " . count($notifler) . "\n";
    foreach ($notifler as $n) {
        echo "  " . json_encode($n, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}
