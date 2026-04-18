<?php
$host = 'localhost';
$db   = 'gruprez1_gruptalepleri';
$user = 'gruprez1_gruprez1';
$pass = 'pqcAnGRD6qF0Sz.G';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rows = $pdo->query("SELECT id, name, email FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($rows);
    echo "</pre>";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
