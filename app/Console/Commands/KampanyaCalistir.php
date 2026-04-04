<?php

namespace App\Console\Commands;

use App\Models\Acenteler;
use App\Models\Kampanya;
use App\Models\TursabDavet;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class KampanyaCalistir extends Command
{
    protected $signature = 'kampanya:calistir {--force : Zaman kontrolünü atla, şimdi çalıştır} {--dry-run : Gerçekten gönderme} {--id= : Sadece bu ID\'li kampanyayı çalıştır}';
    protected $description = 'Aktif kampanyaları çalıştırır — slot saatlerini kontrol ederek gönderim yapar.';

    private const COOLDOWN_DAYS = 7;

    public function handle(): int
    {
        $lockFile = storage_path('app/kampanya-calistir.lock');
        $lock = fopen($lockFile, 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->line('Başka bir kampanya:calistir çalışıyor, atlanıyor.');
            if ($lock) fclose($lock);
            return self::SUCCESS;
        }

        try {
            return $this->doRun();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function doRun(): int
    {
        $query = Kampanya::with('sablon')->where('durum', 'aktif');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $kampanyalar = $query->get();

        if ($kampanyalar->isEmpty()) {
            $this->line('Çalışacak aktif kampanya yok.');
            return self::SUCCESS;
        }

        foreach ($kampanyalar as $kampanya) {
            $this->isleKampanya($kampanya);
        }

        return self::SUCCESS;
    }

    private function isleKampanya(Kampanya $kampanya): void
    {
        $z          = $kampanya->zamanlama ?? [];
        $bugunTarih = now()->format('Y-m-d');
        $force      = $this->option('force');

        if (!$force) {
            $baslangic = $z['baslangic'] ?? '';
            $bitis     = $z['bitis']     ?? '';
            if ($baslangic && $bugunTarih < $baslangic) {
                $this->line("[{$kampanya->etiket}] Henüz başlamadı ({$baslangic}).");
                return;
            }
            if ($bitis && $bugunTarih > $bitis) {
                $this->line("[{$kampanya->etiket}] Sona erdi ({$bitis}), tamamlandı olarak işaretleniyor.");
                $kampanya->update(['durum' => 'tamamlandi']);
                return;
            }
        }

        $simdikiSaat = now()->format('H');
        $slotlar     = $z['slotlar'] ?? [];

        foreach ($slotlar as $slot) {
            $slotSaatFull = $slot['saat'] ?? '';
            $slotSaatSaat = substr($slotSaatFull, 0, 2);
            $adet         = (int) ($slot['adet'] ?? 50);

            if (!$force && $simdikiSaat !== $slotSaatSaat) continue;
            if ($adet <= 0) continue;

            // Bu slot bugün zaten çalıştı mı?
            if (!$force && $this->slotBugünCalistiMi($kampanya->etiket, $slotSaatSaat, $bugunTarih)) {
                $this->line("[{$kampanya->etiket}] [{$slotSaatFull}] Bu slot bugün çalıştı, atlanıyor.");
                continue;
            }

            if ($kampanya->tip === 'email') {
                $this->gonderEmail($kampanya, $adet, $slotSaatFull);
            } else {
                $this->gonderSms($kampanya, $adet, $slotSaatFull);
            }
        }
    }

    private function slotBugünCalistiMi(string $etiket, string $saatSadece, string $bugun): bool
    {
        return TursabDavet::where('kampanya_etiket', $etiket)
            ->whereDate('created_at', $bugun)
            ->whereRaw("HOUR(created_at) = ?", [(int) $saatSadece])
            ->exists();
    }

    private function gonderEmail(Kampanya $kampanya, int $adet, string $slotSaat): void
    {
        $etiket = $kampanya->etiket;
        $sablon = $kampanya->sablon;
        $h      = $kampanya->hedef ?? [];
        $dryRun = $this->option('dry-run');

        $this->info("[{$etiket}] [{$slotSaat}] Email gönderimi başlıyor — {$adet} hedef...");

        // Cooldown (7 gün) + kampanya dedup birleşik hariç listesi
        $hariç = TursabDavet::where('tip', 'email')
            ->where(function ($q) use ($etiket) {
                $q->where(function ($q2) {
                    $q2->where('status', 'sent')
                       ->where('created_at', '>=', now()->subDays(self::COOLDOWN_DAYS));
                })->orWhere('kampanya_etiket', $etiket);
            })
            ->whereNotNull('eposta')
            ->pluck('eposta')
            ->map(fn($e) => strtolower(trim($e)))
            ->unique()->toArray();

        $query = Acenteler::whereNotNull('eposta')->where('eposta', '!=', '');
        if (!empty($h['il']))   $query->where('il', $h['il']);
        if (!empty($h['grup'])) $query->where('grup', $h['grup']);
        if (count($hariç)) {
            $placeholders = implode(',', array_fill(0, count($hariç), '?'));
            $query->whereRaw("LOWER(eposta) NOT IN ({$placeholders})", $hariç);
        }

        $acenteler = $query->select('id', 'belge_no', 'acente_unvani', 'il', 'eposta')
            ->orderByRaw('CAST(belge_no AS UNSIGNED) ASC')
            ->limit($adet * 3)
            ->get();

        $basarili = 0;
        $basarisiz = 0;

        foreach ($acenteler as $a) {
            if ($basarili >= $adet) break;
            $email = trim($a->eposta ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $basarisiz++; continue; }

            if ($dryRun) {
                $this->line("  [DRY-RUN] → {$email} ({$a->acente_unvani})");
                $basarili++;
                continue;
            }

            try {
                $izLink = url('/iz/' . base64_encode($etiket . '|' . ($a->belge_no ?? '')));
                $konu   = $sablon->konu ?: 'GrupTalepleri.com — Davet';
                $html   = str_replace(
                    ['{{acente_adi}}', '{{belge_no}}', '{{kayit_url}}', '{{il}}'],
                    [$a->acente_unvani, $a->belge_no ?? '', $izLink, $a->il ?? ''],
                    $sablon->html_icerik ?? ''
                );

                Mail::html($html, fn($mail) => $mail->to($email, $a->acente_unvani)->subject($konu));

                TursabDavet::create([
                    'belge_no'         => $a->belge_no ?: null,
                    'eposta'           => $email,
                    'acente_unvani'    => $a->acente_unvani,
                    'il'               => $a->il ?: null,
                    'tip'              => 'email',
                    'kampanya_etiket'  => $etiket,
                    'status'           => 'sent',
                    'gonderen_user_id' => null,
                ]);
                $hariç[] = strtolower($email);
                $basarili++;
            } catch (\Throwable $e) {
                TursabDavet::create([
                    'belge_no'         => $a->belge_no ?: null,
                    'eposta'           => $email,
                    'acente_unvani'    => $a->acente_unvani,
                    'il'               => $a->il ?: null,
                    'tip'              => 'email',
                    'kampanya_etiket'  => $etiket,
                    'status'           => 'failed',
                    'hata'             => $e->getMessage(),
                    'gonderen_user_id' => null,
                ]);
                $basarisiz++;
            }
        }

        $this->info("[{$etiket}] [{$slotSaat}] Tamamlandı: {$basarili} gönderildi, {$basarisiz} başarısız.");
    }

    private function gonderSms(Kampanya $kampanya, int $adet, string $slotSaat): void
    {
        $etiket     = $kampanya->etiket;
        $sablon     = $kampanya->sablon;
        $h          = $kampanya->hedef ?? [];
        $dryRun     = $this->option('dry-run');
        $smsService = app(SmsService::class);

        $this->info("[{$etiket}] [{$slotSaat}] SMS gönderimi başlıyor — {$adet} hedef...");

        $hariçBelge = TursabDavet::where('tip', 'sms')
            ->where(function ($q) use ($etiket) {
                $q->where(function ($q2) {
                    $q2->where('status', 'sent')
                       ->where('created_at', '>=', now()->subDays(self::COOLDOWN_DAYS));
                })->orWhere('kampanya_etiket', $etiket);
            })
            ->whereNotNull('belge_no')
            ->pluck('belge_no')
            ->unique()->toArray();

        $query = Acenteler::whereNotNull('telefon')
            ->where('telefon', '!=', '')
            ->whereRaw("telefon REGEXP '^[[:space:]]*(\\+?90)?0?5[0-9]'");

        if (!empty($h['il']))   $query->where('il', $h['il']);
        if (!empty($h['grup'])) $query->where('grup', $h['grup']);
        if (count($hariçBelge)) $query->whereNotIn('belge_no', $hariçBelge);

        $acenteler = $query->select('id', 'belge_no', 'acente_unvani', 'il', 'eposta', 'telefon')
            ->orderByRaw('CAST(belge_no AS UNSIGNED) ASC')
            ->limit($adet)
            ->get();

        $basarili = 0;
        $basarisiz = 0;

        foreach ($acenteler as $a) {
            if ($basarili >= $adet) break;
            $telefon = $this->normalizeTelefon($a->telefon ?? '');
            if (!$telefon) { $basarisiz++; continue; }

            if ($dryRun) {
                $this->line("  [DRY-RUN] SMS → {$telefon} ({$a->acente_unvani})");
                $basarili++;
                continue;
            }

            try {
                $mesaj = str_replace(
                    ['{{acente_adi}}', '{{belge_no}}', '{{il}}'],
                    [$a->acente_unvani, $a->belge_no ?? '', $a->il ?? ''],
                    $sablon->sms_icerik ?? ''
                );

                $smsService->send(null, 'acente', $a->acente_unvani, $telefon, $mesaj);

                TursabDavet::create([
                    'belge_no'         => $a->belge_no ?: null,
                    'eposta'           => $a->eposta   ?: null,
                    'acente_unvani'    => $a->acente_unvani,
                    'il'               => $a->il       ?: null,
                    'tip'              => 'sms',
                    'kampanya_etiket'  => $etiket,
                    'status'           => 'sent',
                    'gonderen_user_id' => null,
                ]);
                $hariçBelge[] = $a->belge_no;
                $basarili++;
            } catch (\Throwable $e) {
                TursabDavet::create([
                    'belge_no'         => $a->belge_no ?: null,
                    'eposta'           => $a->eposta   ?: null,
                    'acente_unvani'    => $a->acente_unvani,
                    'il'               => $a->il       ?: null,
                    'tip'              => 'sms',
                    'kampanya_etiket'  => $etiket,
                    'status'           => 'failed',
                    'hata'             => $e->getMessage(),
                    'gonderen_user_id' => null,
                ]);
                $basarisiz++;
            }
        }

        $this->info("[{$etiket}] [{$slotSaat}] Tamamlandı: {$basarili} SMS gönderildi, {$basarisiz} başarısız.");
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
