<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Her dakika çağrılır; komut kendi içinde ayarlanan aralığa göre çalışıp çalışmayacağına karar verir
Schedule::command('opsiyon:check')->everyMinute();
