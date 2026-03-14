<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class SendScheduledSms extends Command
{
    protected $signature   = 'sms:send-scheduled';
    protected $description = 'Zamanlanmış bekleyen SMS\'leri gönderir';

    public function handle(): void
    {
        (new SmsService())->sendScheduled();
    }
}
