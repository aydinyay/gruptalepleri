<?php

namespace App\Console\Commands;

use App\Models\BroadcastNotification;
use App\Services\BroadcastService;
use Illuminate\Console\Command;

class SendScheduledBroadcasts extends Command
{
    protected $signature   = 'broadcast:send-scheduled';
    protected $description = 'Zamanı gelen broadcast duyurularını gönderir';

    public function handle(): void
    {
        $bekleyenler = BroadcastNotification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($bekleyenler->isEmpty()) {
            return;
        }

        $service = new BroadcastService();
        foreach ($bekleyenler as $broadcast) {
            $service->send($broadcast);
            $this->info("Gönderildi: [{$broadcast->id}] {$broadcast->title} — {$broadcast->sent_count} kullanıcı");
        }
    }
}
