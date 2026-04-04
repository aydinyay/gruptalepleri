<?php

namespace App\Console\Commands;

use App\Models\Acenteler;
use App\Models\SistemAyar;
use App\Models\TursabDavet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class KampanyaEmailOtomatik extends Command
{
    protected $signature = 'kampanya:email-otomatik {--force : Zaman kontrolünü atla, şimdi çalıştır} {--dry-run : Gerçekten gönderme, sadece ne gideceğini göster}';
    protected $description = 'Zamanlanmış email kampanyasını çalıştırır. Her çalıştırmada ayarlanan saatlerle karşılaştırır.';

    private const AYAR_KEY       = 'kampanya_email_zamanlama';
    private const LOG_KEY        = 'kampanya_email_calisma_log';
    private const SABLON_DEFAULT = 'emails.tursab_davet';

    public function handle(): int
    {
        // Eş zamanlı çalışmayı önle — dosya kilidi
        $lockFile = storage_path('app/kampanya-email.lock');
        $lock = fopen($lockFile, 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->line('Başka bir kampanya email örneği çalışıyor, atlanıyor.');
            if ($lock) fclose($lock);
            return self::SUCCESS;
        }

        try {
            return $this->run();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function run(): int
    {
        $ayarJson = SistemAyar::get(self::AYAR_KEY, '');
        if (!$ayarJson) {
            $this->line('Email kampanya ayarı bulunamadı.');
            return self::SUCCESS;
        }

        $ayar = json_decode($ayarJson, true);
        if (empty($ayar['aktif'])) {
            $this->line('Email kampanyası devre dışı.');
            return self::SUCCESS;
        }

        $slotlar    = $ayar['slotlar'] ?? [];
        $filtre     = $ayar['filtre']  ?? [];
        $sablon     = $filtre['sablon'] ?? self::SABLON_DEFAULT;
        $sadeceYeni = $filtre['sadece_yeni'] ?? true; // varsayılan: spam önleme açık
        $force      = $this->option('force');
        $dryRun  = $this->option('dry-run');

        $simdikiSaat = now()->format('H:i');
        $simdikiSaatSadece = now()->format('H'); // sadece saat (00-23)
        $bugun = now()->format('Y-m-d');

        // Bugünkü çalışma logu
        $logJson = SistemAyar::get(self::LOG_KEY, '{}');
        $log = json_decode($logJson, true) ?? [];
        $bugunCalisanlar = $log[$bugun] ?? [];

        foreach ($slotlar as $slot) {
            if (empty($slot['aktif'])) continue;

            $slotSaat = $slot['saat'] ?? '';    // "09:00"
            $adet     = (int) ($slot['adet'] ?? 50);
            if (!$slotSaat || $adet <= 0) continue;

            $slotSaatSadece = substr($slotSaat, 0, 2); // "09"

            // Saat eşleşiyor mu?
            if (!$force && $simdikiSaatSadece !== $slotSaatSadece) continue;

            // Bu slot bugün zaten çalıştı mı?
            if (!$force && in_array($slotSaat, $bugunCalisanlar)) {
                $this->line("[$slotSaat] Bu slot bugün zaten çalıştı, atlanıyor.");
                continue;
            }

            $this->info("[$slotSaat] Email kampanyası başlıyor — $adet acente hedefleniyor...");

            // Acente sorgusu
            $query = Acenteler::whereNotNull('eposta')->where('eposta', '!=', '');

            if (!empty($filtre['il']))   $query->where('il', $filtre['il']);
            if (!empty($filtre['ilce'])) $query->where('il_ilce', $filtre['ilce']);
            if (!empty($filtre['grup'])) $query->where('grup', $filtre['grup']);

            // Spam önleme: daha önce gönderilenler hariç
            if ($sadeceYeni) {
                $gidenlEpostalar = TursabDavet::where('tip', 'email')
                    ->whereNotNull('eposta')
                    ->pluck('eposta')
                    ->map(fn($e) => strtolower($e))
                    ->toArray();
                if (count($gidenlEpostalar)) {
                    $placeholders = implode(',', array_fill(0, count($gidenlEpostalar), '?'));
                    $query->whereRaw("LOWER(eposta) NOT IN ({$placeholders})", $gidenlEpostalar);
                }
            }

            // Yeni acente tebrik şablonunda en son eklenenler önce gelsin
            $siralama = ($sablon === 'emails.tursab_davet_yeni_acente') ? 'DESC' : 'ASC';

            // Geçersiz email adreslerini absorbe etmek için $adet'in 3 katı çek
            $acenteler = $query
                ->select('id','belge_no','acente_unvani','ticari_unvan','il','eposta','telefon')
                ->orderByRaw("CAST(belge_no AS UNSIGNED) {$siralama}")
                ->limit($adet * 3)
                ->get();

            if ($acenteler->isEmpty()) {
                $this->warn("[$slotSaat] Gönderilecek acente kalmadı.");
                $this->markSlotDone($bugun, $slotSaat, $log);
                continue;
            }

            $basarili = 0;
            $basarisiz = 0;

            foreach ($acenteler as $a) {
                if ($basarili >= $adet) break; // yeterli sayıya ulaşıldı

                $email = trim($a->eposta ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $basarisiz++; continue; }
                $localPart = explode('@', $email)[0] ?? '';
                if (!preg_match('/^[\x20-\x7E]+$/', $localPart)) { $basarisiz++; continue; }

                if ($dryRun) {
                    $this->line("  [DRY-RUN] → $email ({$a->acente_unvani})");
                    $basarili++;
                    continue;
                }

                try {
                    $this->gonder($email, $a->acente_unvani, $a->belge_no ?? '', $sablon);
                    TursabDavet::create([
                        'belge_no'         => $a->belge_no ?: null,
                        'eposta'           => $email,
                        'acente_unvani'    => $a->acente_unvani,
                        'il'               => $a->il ?: null,
                        'tip'              => 'email',
                        'status'           => 'sent',
                        'gonderen_user_id' => null,
                    ]);
                    $basarili++;
                } catch (\Throwable $e) {
                    TursabDavet::create([
                        'belge_no'         => $a->belge_no ?: null,
                        'eposta'           => $email,
                        'acente_unvani'    => $a->acente_unvani,
                        'il'               => $a->il ?: null,
                        'tip'              => 'email',
                        'status'           => 'failed',
                        'hata'             => $e->getMessage(),
                        'gonderen_user_id' => null,
                    ]);
                    $basarisiz++;
                }
            }

            $this->info("[$slotSaat] Tamamlandı: $basarili gönderildi, $basarisiz başarısız.");

            if (!$dryRun) {
                $this->markSlotDone($bugun, $slotSaat, $log);
            }
        }

        return self::SUCCESS;
    }

    private function markSlotDone(string $bugun, string $slotSaat, array &$log): void
    {
        $log[$bugun][] = $slotSaat;
        // Sadece son 7 günü tut
        $log = array_filter($log, fn($k) => $k >= now()->subDays(7)->format('Y-m-d'), ARRAY_FILTER_USE_KEY);
        SistemAyar::set(self::LOG_KEY, json_encode($log));
    }

    private function gonder(string $email, string $acenteAdi, string $belgeNo, string $sablon): void
    {
        $konu = $sablon === 'emails.tursab_davet_yeni_acente'
            ? 'Hayırlı Olsun! GrupTalepleri\'nden tebrikler 🎉'
            : 'GrupTalepleri.com — Platforma Davet';

        $vars = ['acenteUnvani' => $acenteAdi, 'belgeNo' => $belgeNo, 'kayitUrl' => route('register'), 'aiParagraf' => ''];

        Mail::send(
            $sablon,
            $vars,
            fn($mail) => $mail->to($email, $acenteAdi)->bcc('aydinyay@gmail.com')->subject($konu)
        );
    }
}
