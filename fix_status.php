<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$n1 = DB::table('requests')->where('status', 'depozito')->update(['status' => 'depozitoda']);
$n2 = DB::table('requests')->where('status', 'fiyatlandirildi')->update(['status' => 'fiyatlandirıldi']);

echo "depozito → depozitoda: $n1 kayıt güncellendi\n";
echo "fiyatlandirildi → fiyatlandirıldi: $n2 kayıt güncellendi\n";
echo "Toplam güncellenen: " . ($n1 + $n2) . "\n";
