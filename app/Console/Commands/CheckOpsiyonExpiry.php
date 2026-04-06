<?php

namespace App\Console\Commands;

use App\Models\OpsiyonUyariAyar;
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
    protected $description = 'Süresi yaklaşan ödeme vadeleri için SMS ve push bildirimi gönder';

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

        $smsService   = new SmsService();
        $notifService = new NotificationService();
        $emailService = new EmailService();

        // ── Aktif ödeme vadeleri için bildirim (is_active=true olan payment'lar) ──
        $odemeUyarilari = RequestPayment::where('is_active', true)
            ->whereIn('status', ['aktif', 'gecikti'])
            ->whereNotNull('due_date')
            ->with('request')
            ->get();

        foreach ($ayarlar as $ayar) {
            $hedefZaman       = $simdi->copy()->addHours($ayar->saat_oncesi);
            $pencereBaslangic = $simdi->copy();
            $pencereBitis     = $hedefZaman;

            foreach ($odemeUyarilari as $odeme) {
                $dueDt = Carbon::parse($odeme->due_date . ' ' . ($odeme->due_time ?: '23:59:59'));
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
                $acenteUserId = $odeme->request?->user_id;
                if ($ayar->sms_aktif) {
                    // Admin SMS
                    $smsService->sendByEvent('opsiyon_uyarisi', $odeme->request_id, $msg);

                    // Acente SMS
                    $acenteUser = $acenteUserId ? \App\Models\User::find($acenteUserId) : null;
                    $acentePhone = $acenteUser?->phone ?? $odeme->request?->phone ?? null;
                    if ($acentePhone) {
                        $acenteMsg = \App\Models\SistemOlaySablon::resolveSms('opsiyon_uyarisi', [
                            'gtpnr'      => $gtpnr,
                            'saat_kaldi' => $saatKaldi,
                            'bitis'      => $dueDt->format('d.m.Y'),
                            'link'       => $url,
                        ]) ?? "HATIRLATMA: {$gtpnr} talebiniz için ödeme vadesi {$saatKaldi} saat içinde doluyor. {$dueDt->format('d.m.Y')}";
                        $smsService->send($odeme->request_id, 'acente', $acenteUser?->name ?? 'Acente', (string) $acentePhone, $acenteMsg);
                    }
                }
                $emailService->opsiyonUyarisi($odeme->request_id, $gtpnr, '💳 Ödeme Vadesi', $saatKaldi, $dueDt->format('d.m.Y H:i'), $url, $acenteUserId);

                Cache::put($cacheKey, true, 60 * 48);
                $this->line("Ödeme uyarısı gönderildi: {$gtpnr} ({$ayar->saat_oncesi}s önce)");
            }
        }
    }
}
