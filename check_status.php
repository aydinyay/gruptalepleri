<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = DB::table('requests')->selectRaw('status, count(*) as n')->groupBy('status')->orderBy('n','desc')->get();
foreach ($rows as $r) {
    echo $r->status . ': ' . $r->n . PHP_EOL;
}
