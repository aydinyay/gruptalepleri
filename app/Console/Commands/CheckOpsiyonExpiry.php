<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\OpsiyonUyariAyar;
use App\Models\OpsiyonUyariGonderim;
use App\Models\RequestPayment;
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
        $simdi = now();
        // Ayarlanan aralık kontrolü — Task Scheduler her dakika çağırır ama
        // komut kendi ayarına göre atlayıp atlamayacağına karar verir
        $aralikDakika = (int) SistemAyar::get('opsiyon_check_aralik', 15);
        $sonCalisma   = Cache::get('opsiyon_check_son_calisma');

        if ($sonCalisma && Carbon::parse($sonCalisma)->diffInMinutes($simdi) < $aralikDakika) {
            return; // Henüz erken, atla
        }

        Cache::put('opsiyon_check_son_calisma', $simdi->toISOString(), 1440);

        $ayarlar = OpsiyonUyariAyar::aktifler();

        if ($ayarlar->isEmpty()) {
            return;
        }

        // option_date + option_time olan, henüz dolmamış teklifler
        $teklifler = Offer::whereNotNull('option_date')
            ->with('request')
            ->get();

        $smsService   = new SmsService();
        $notifService = new NotificationService();
        $emailService = new EmailService();

        foreach ($ayarlar as $ayar) {
            $hedefZaman = $simdi->copy()->addHours($ayar->saat_oncesi);
            // Bu saat_oncesi penceresinde olan teklifler: şu an ile hedefZaman arasında dolan
            $pencereBaslangic = $simdi->copy();
            $pencereBitis     = $hedefZaman;

            foreach ($teklifler as $teklif) {
                $opsTs = Carbon::parse(
                    $teklif->option_date . ' ' . ($teklif->option_time ?? '23:59')
                );

                if (! $opsTs->isFuture()) {
                    continue;
                }

                // Bu teklif bu pencerede mi?
                if ($opsTs->between($pencereBaslangic, $pencereBitis)) {
                    // Daha önce bu kural için gönderildi mi?
                    if (OpsiyonUyariGonderim::gonderildiMi($teklif->id, $ayar->saat_oncesi)) {
                        continue;
                    }

                    $saatKaldi = (int) $simdi->diffInHours($opsTs, false);
                    $gtpnr     = $teklif->request?->gtpnr ?? '—';
                    $airline   = $teklif->airline ?? '—';
                    $url       = route('requests.short', $gtpnr);

                    // Push bildirimi
                    if ($ayar->push_aktif) {
                        $notifService->opsiyonUyarisi($gtpnr, $airline, $saatKaldi, $url);
                    }

                    // SMS
                    if ($ayar->sms_aktif) {
                        $msg = "OPSİYON UYARISI: {$gtpnr} / {$airline} — {$saatKaldi} saat sonra opsiyon doluyor! {$opsTs->format('d.m.Y H:i')}" . PHP_EOL . $url;
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

        // ── Bekleyen ödeme vadeleri için bildirim ────────────────────────────
        $odemeUyarilari = RequestPayment::where('status', 'bekleniyor')
            ->whereNotNull('due_date')
            ->with('request')
            ->get();

        foreach ($ayarlar as $ayar) {
            $hedefZaman       = $simdi->copy()->addHours($ayar->saat_oncesi);
            $pencereBaslangic = $simdi->copy();
            $pencereBitis     = $hedefZaman;

            foreach ($odemeUyarilari as $odeme) {
                $dueDt = Carbon::parse($odeme->due_date)->endOfDay();
                if (! $dueDt->isFuture()) continue;
                if (! $dueDt->between($pencereBaslangic, $pencereBitis)) continue;

                $cacheKey = "odeme_uyari_{$odeme->id}_{$ayar->saat_oncesi}";
                if (Cache::has($cacheKey)) continue;

                $saatKaldi = (int) $simdi->diffInHours($dueDt, false);
                $gtpnr     = $odeme->request?->gtpnr ?? '—';
                $url       = route('requests.short', $gtpnr);
                $msg       = "ÖDEME UYARISI: {$gtpnr} — {$saatKaldi} saat içinde ödeme vadesi doluyor! {$dueDt->format('d.m.Y')}" . PHP_EOL . $url;

                if ($ayar->push_aktif) {
                    $notifService->opsiyonUyarisi($gtpnr, '💳 Ödeme Vadesi', $saatKaldi, $url);
                }
                if ($ayar->sms_aktif) {
                    $smsService->sendByEvent('opsiyon_uyarisi', $odeme->request_id, $msg);
                }
                $emailService->opsiyonUyarisi($odeme->request_id, $gtpnr, '💳 Ödeme Vadesi', $saatKaldi, $dueDt->format('d.m.Y H:i'), $url);

                Cache::put($cacheKey, true, 60 * 48);
                $this->line("Ödeme uyarısı gönderildi: {$gtpnr} ({$ayar->saat_oncesi}s önce)");
            }
        }
    }
}
