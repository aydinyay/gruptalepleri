<?php
if (($_GET['t'] ?? '') !== 'grt2026dbx') {
    http_response_code(403);
    die('Forbidden');
}

@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '512M');

$host = 'localhost';
$user = 'gruprez1_gruprez1';
$pass = 'pqcAnGRD6qF0Sz.G';
$db   = 'gruprez1_gruptalepleri';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    die("DB HATA: " . $e->getMessage());
}

$filename = 'gruptalepleri_' . date('Ymd_His') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

ob_implicit_flush(true);
ob_end_flush();

echo "-- MySQL dump via PHP PDO\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
flush();

$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $quoted = "`$table`";

    $row = $pdo->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM);
    echo "DROP TABLE IF EXISTS $quoted;\n";
    echo $row[1] . ";\n\n";

    $count = $pdo->query("SELECT COUNT(*) FROM $quoted")->fetchColumn();
    if ($count == 0) continue;

    $stmt = $pdo->query("SELECT * FROM $quoted");
    $cols = null;
    $batch = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($cols === null) {
            $cols = '`' . implode('`, `', array_keys($row)) . '`';
        }
        $vals = array_map(function($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote($v);
        }, array_values($row));
        $batch[] = '(' . implode(',', $vals) . ')';

        if (count($batch) >= 200) {
            echo "INSERT INTO $quoted ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
            $batch = [];
            flush();
        }
    }
    if ($batch) {
        echo "INSERT INTO $quoted ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
    }
    echo "\n";
    flush();
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
