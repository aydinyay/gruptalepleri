<?php

namespace App\Console\Commands;

use App\Models\Acenteler;
use App\Models\SistemAyar;
use App\Models\TursabDavet;
use Illuminate\Console\Command;

class KampanyaSmsOtomatik extends Command
{
    protected $signature = 'kampanya:sms-otomatik {--force : Zaman kontrolünü atla, şimdi çalıştır} {--dry-run : Gerçekten gönderme, sadece ne gideceğini göster}';
    protected $description = 'Zamanlanmış SMS kampanyasını çalıştırır. Her çalıştırmada ayarlanan saatlerle karşılaştırır.';

    private const AYAR_KEY = 'kampanya_sms_zamanlama';
    private const LOG_KEY  = 'kampanya_sms_calisma_log';

    public function handle(): int
    {
        $ayarJson = SistemAyar::get(self::AYAR_KEY, '');
        if (!$ayarJson) {
            $this->line('SMS kampanya ayarı bulunamadı.');
            return self::SUCCESS;
        }

        $ayar = json_decode($ayarJson, true);
        if (empty($ayar['aktif'])) {
            $this->line('SMS kampanyası devre dışı.');
            return self::SUCCESS;
        }

        $mesaj = trim($ayar['mesaj'] ?? '');
        if (!$mesaj) {
            $this->warn('SMS metni boş, kampanya çalıştırılamaz.');
            return self::SUCCESS;
        }
        if (mb_strlen($mesaj) > 160) {
            $this->warn('SMS metni 160 karakteri geçiyor, kampanya durduruldu.');
            return self::SUCCESS;
        }

        $slotlar = $ayar['slotlar'] ?? [];
        $filtre  = $ayar['filtre']  ?? [];
        $force   = $this->option('force');
        $dryRun  = $this->option('dry-run');

        $simdikiSaatSadece = now()->format('H');
        $bugun = now()->format('Y-m-d');

        $logJson = SistemAyar::get(self::LOG_KEY, '{}');
        $log = json_decode($logJson, true) ?? [];
        $bugunCalisanlar = $log[$bugun] ?? [];

        $smsService = app(\App\Services\SmsService::class);

        foreach ($slotlar as $slot) {
            if (empty($slot['aktif'])) continue;

            $slotSaat = $slot['saat'] ?? '';
            $adet     = (int) ($slot['adet'] ?? 100);
            if (!$slotSaat || $adet <= 0) continue;

            $slotSaatSadece = substr($slotSaat, 0, 2);

            if (!$force && $simdikiSaatSadece !== $slotSaatSadece) continue;

            if (!$force && in_array($slotSaat, $bugunCalisanlar)) {
                $this->line("[$slotSaat] Bu SMS slotu bugün zaten çalıştı, atlanıyor.");
                continue;
            }

            $this->info("[$slotSaat] SMS kampanyası başlıyor — $adet acente hedefleniyor...");

            // Daha önce SMS gönderilen telefon/belge_no listesi
            $gidenlBelgeNo = TursabDavet::where('tip', 'sms')
                ->whereNotNull('belge_no')
                ->pluck('belge_no')
                ->toArray();

            $query = Acenteler::whereNotNull('telefon')
                ->where('telefon', '!=', '')
                ->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'");

            if (!empty($filtre['il']))   $query->where('il', $filtre['il']);
            if (!empty($filtre['ilce'])) $query->where('il_ilce', $filtre['ilce']);
            if (!empty($filtre['grup'])) $query->where('grup', $filtre['grup']);

            if (count($gidenlBelgeNo)) {
                $query->whereNotIn('belge_no', $gidenlBelgeNo);
            }

            $acenteler = $query
                ->select('id','belge_no','acente_unvani','il','eposta','telefon')
                ->orderByRaw('CAST(belge_no AS UNSIGNED) ASC')
                ->limit($adet)
                ->get();

            if ($acenteler->isEmpty()) {
                $this->warn("[$slotSaat] Gönderilecek acente kalmadı.");
                $this->markSlotDone($bugun, $slotSaat, $log);
                continue;
            }

            $basarili = 0;
            $basarisiz = 0;

            foreach ($acenteler as $a) {
                $telefon = $this->normalizeTelefon($a->telefon ?? '');
                if (!$telefon) { $basarisiz++; continue; }

                if ($dryRun) {
                    $this->line("  [DRY-RUN] → $telefon ({$a->acente_unvani})");
                    $basarili++;
                    continue;
                }

                try {
                    $smsService->send(null, 'acente', $a->acente_unvani, $telefon, $mesaj);
                    TursabDavet::create([
                        'belge_no'         => $a->belge_no ?: null,
                        'eposta'           => $a->eposta   ?: null,
                        'acente_unvani'    => $a->acente_unvani,
                        'il'               => $a->il       ?: null,
                        'tip'              => 'sms',
                        'status'           => 'sent',
                        'gonderen_user_id' => null,
                    ]);
                    $basarili++;
                } catch (\Throwable $e) {
                    TursabDavet::create([
                        'belge_no'         => $a->belge_no ?: null,
                        'eposta'           => $a->eposta   ?: null,
                        'acente_unvani'    => $a->acente_unvani,
                        'il'               => $a->il       ?: null,
                        'tip'              => 'sms',
                        'status'           => 'failed',
                        'hata'             => $e->getMessage(),
                        'gonderen_user_id' => null,
                    ]);
                    $basarisiz++;
                }
            }

            $this->info("[$slotSaat] Tamamlandı: $basarili SMS gönderildi, $basarisiz başarısız.");

            if (!$dryRun) {
                $this->markSlotDone($bugun, $slotSaat, $log);
            }
        }

        return self::SUCCESS;
    }

    private function markSlotDone(string $bugun, string $slotSaat, array &$log): void
    {
        $log[$bugun][] = $slotSaat;
        $log = array_filter($log, fn($k) => $k >= now()->subDays(7)->format('Y-m-d'), ARRAY_FILTER_USE_KEY);
        SistemAyar::set(self::LOG_KEY, json_encode($log));
    }

    private function normalizeTelefon(string $telefon): string
    {
        $digits = preg_replace('/[^0-9]/', '', $telefon);
        if (!$digits) return '';
        if (strlen($digits) === 12 && str_starts_with($digits, '90')) return '0' . substr($digits, 2);
        if (strlen($digits) === 11 && str_starts_with($digits, '05')) return $digits;
        if (strlen($digits) === 10 && str_starts_with($digits, '5'))  return '0' . $digits;
        return '';
    }
}
