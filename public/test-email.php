<?php
$key = $_GET['key'] ?? '';
if (!hash_equals('gtp2026deploy', $key)) { http_response_code(403); exit('Yetkisiz.'); }

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = App\Models\User::find(1);
$u->email = 'aydinyay@gmail.com';
$u->name = 'Aydin Yaylaciklilar';
$u->role = 'acente';

(new App\Services\EmailService())->hosgeldiniz(
    $u,
    'TEST Acente A.S.',
    'Aydin Yaylaciklilar',
    'https://gruptalepleri.com/acente/dashboard'
);

echo 'Email gonderildi: aydinyay@gmail.com';
