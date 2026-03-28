<?php
// Güvenlik token kontrolü
if (($_GET['t'] ?? '') !== 'grt2026dbx') {
    http_response_code(403);
    die('Forbidden');
}

$host = '127.0.0.1';
$user = 'gruprez1_gruprez1';
$pass = 'pqcAnGRD6qF0Sz.G';
$db   = 'gruprez1_gruptalepleri';

$filename = 'gruptalepleri_' . date('Ymd_His') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$cmd = "mysqldump --single-transaction --no-tablespaces --routines --triggers "
     . "-h " . escapeshellarg($host) . " "
     . "-u " . escapeshellarg($user) . " "
     . "-p" . escapeshellarg($pass) . " "
     . escapeshellarg($db);

passthru($cmd);
