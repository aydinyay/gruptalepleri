<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\OpsiyonUyariAyar;
use App\Models\OpsiyonUyariGonderim;
use App\Models\SistemAyar;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckOpsiyonExpiry extends Command
{
    protected $signature   = 'opsiyon:check';
    protected $description = 'Süresi yaklaşan opsiyonlar için SMS ve push bildirimi gönder';

    public function handle(): void
    {
        // Ayarlanan aralık kontrolü — Task Scheduler her dakika çağırır ama
        // komut kendi ayarına göre atlayıp atlamayacağına karar verir
        $aralikDakika = (int) SistemAyar::get('opsiyon_check_aralik', 15);
        $sonCalisma   = Cache::get('opsiyon_check_son_calisma');

        if ($sonCalisma && Carbon::parse($sonCalisma)->diffInMinutes(now()) < $aralikDakika) {
            return; // Henüz erken, atla
        }

        Cache::put('opsiyon_check_son_calisma', now()->toISOString(), 1440);

        $ayarlar = OpsiyonUyariAyar::aktifler();

        if ($ayarlar->isEmpty()) {
            return;
        }

        // option_date + option_time olan, henüz dolmamış teklifler
        $teklifler = Offer::whereNotNull('option_date')
            ->whereRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') > NOW()")
            ->with('request')
            ->get();

        $smsService   = new SmsService();
        $notifService = new NotificationService();
        $emailService = new EmailService();

        foreach ($ayarlar as $ayar) {
            $hedefZaman = Carbon::now()->addHours($ayar->saat_oncesi);
            // Bu saat_oncesi penceresinde olan teklifler: şu an ile hedefZaman arasında dolan
            $pencereBaslangic = Carbon::now();
            $pencereBitis     = $hedefZaman;

            foreach ($teklifler as $teklif) {
                $opsTs = Carbon::parse(
                    $teklif->option_date . ' ' . ($teklif->option_time ?? '23:59')
                );

                // Bu teklif bu pencerede mi?
                if ($opsTs->between($pencereBaslangic, $pencereBitis)) {
                    // Daha önce bu kural için gönderildi mi?
                    if (OpsiyonUyariGonderim::gonderildiMi($teklif->id, $ayar->saat_oncesi)) {
                        continue;
                    }

                    $saatKaldi = (int) Carbon::now()->diffInHours($opsTs, false);
                    $gtpnr     = $teklif->request?->gtpnr ?? '—';
                    $airline   = $teklif->airline ?? '—';
                    $url       = route('admin.requests.show', $gtpnr);

                    // Push bildirimi
                    if ($ayar->push_aktif) {
                        $notifService->opsiyonUyarisi($gtpnr, $airline, $saatKaldi, $url);
                    }

                    // SMS
                    if ($ayar->sms_aktif) {
                        $msg = "OPSİYON UYARISI: {$gtpnr} / {$airline} — {$saatKaldi} saat sonra opsiyon doluyor! {$opsTs->format('d.m.Y H:i')}";
                        $smsService->sendByEvent('opsiyon_uyarisi', $teklif->request_id, $msg);
                    }

                    // Email
                    $emailService->opsiyonUyarisi($teklif->request_id, $gtpnr, $airline, $saatKaldi, $opsTs->format('d.m.Y H:i'), $url);

                    // Gönderildi olarak kaydet
                    OpsiyonUyariGonderim::create([
                        'offer_id'    => $teklif->id,
                        'saat_oncesi' => $ayar->saat_oncesi,
                        'sent_at'     => now(),
                    ]);

                    $this->line("Uyarı gönderildi: {$gtpnr} / {$airline} ({$ayar->saat_oncesi}s önce)");
                }
            }
        }
    }
}
