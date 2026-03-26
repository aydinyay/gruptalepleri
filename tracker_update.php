<?php
/**
 * tracker_update.php — JS Beacon Alıcı
 * (Sayfada kalma süresi, çıkış linki, yüklenme hızını günceller)
 * Bu dosyayı doğrudan ziyaret etmeye gerek yok, otomatik çalışır.
 */

header('Content-Type: text/plain');

require_once __DIR__ . '/gt_config.php';

$raw = file_get_contents('php://input');
if (!$raw) exit;

$data = json_decode($raw, true);
if (!$data || !isset($data['vid']) || !is_numeric($data['vid'])) exit;

try {
    $pdo = new PDO(
        "mysql:host=".GT_DB_HOST.";dbname=".GT_DB_NAME.";charset=utf8mb4",
        GT_DB_USER, GT_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT, PDO::ATTR_TIMEOUT => 2]
    );

    $sets   = [];
    $params = [];

    if (isset($data['time_on_page']) && is_numeric($data['time_on_page'])) {
        $sec = min((int)$data['time_on_page'], 86400); // max 24 saat
        $sets[]   = 'time_on_page = ?';
        $params[] = $sec;
    }
    if (isset($data['load_time']) && is_numeric($data['load_time'])) {
        $sets[]   = 'load_time = ?';
        $params[] = round((float)$data['load_time'], 2);
    }
    if (isset($data['exit_url'])) {
        $sets[]   = 'exit_url = ?';
        $params[] = substr(trim($data['exit_url']), 0, 500);
    }

    if ($sets) {
        $params[] = (int)$data['vid'];
        $pdo->prepare("UPDATE gt_visits SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    }
} catch (Exception $e) {}

exit;
