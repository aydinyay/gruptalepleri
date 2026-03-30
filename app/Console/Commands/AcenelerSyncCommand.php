<?php

namespace App\Console\Commands;

use App\Models\SistemAyar;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

/**
 * Bakanlık resmi kaydıyla tam senkronizasyon.
 *
 * İki geçiş: GEÇERLİ + İPTAL → updateOrInsert + synced_at takibi.
 * Sync sonunda görülmeyen bakanlik kayıtları → durum='İPTAL'.
 * Tursab satırları ilk temizlikte silinir (grup aktarıldıktan sonra).
 */
class AcenelerSyncCommand extends Command
{
    protected $signature = 'acenteler:sync
                            {--batch=20    : Bu çalışmada kaç belge no işlensin}
                            {--delay=400   : İstekler arası bekleme (ms)}
                            {--reset       : İlerlemeyi sıfırla, baştan başla}
                            {--skip-cleanup: Tursab silme adımını atla}';

    protected $description = 'Bakanlık ile tam senkronizasyon: GEÇERLİ + İPTAL, updateOrInsert, kaybolmuş→İPTAL.';

    private const BASE_URL  = 'https://yatirimisletmeleruygulama.kultur.gov.tr';
    private const SORGU_URL = self::BASE_URL . '/acente.web.sorgu/sorgu/acentesorgu';
    private const JSON_URL  = self::BASE_URL . '/Acente.Web.Sorgu/Sorgu/AcentelerSearchJson';
    private const END_NO    = 20000;

    // SistemAyar keys
    private const SK_STATUS  = 'acente_sync_status';
    private const SK_GECIS   = 'acente_sync_gecis';      // 1=GEÇERLİ, 2=İPTAL
    private const SK_CURRENT = 'acente_sync_current_no';
    private const SK_FOUND   = 'acente_sync_found';
    private const SK_AT      = 'acente_sync_at';
    private const SK_STARTED = 'acente_sync_started_at'; // İPTAL tespiti için

    public function handle(): int
    {
        if ($this->option('reset')) {
            SistemAyar::set(self::SK_STATUS,  'idle');
            SistemAyar::set(self::SK_GECIS,   '1');
            SistemAyar::set(self::SK_CURRENT, '1');
            SistemAyar::set(self::SK_FOUND,   '0');
            SistemAyar::set(self::SK_STARTED, now()->toDateTimeString());
            $this->info('[reset] Senkronizasyon sıfırlandı.');
        }

        $batch = max(1, (int) ($this->option('batch') ?: 20));
        $delay = max(200, (int) ($this->option('delay') ?: 400));

        // İlk çalışmada started_at kaydet
        if (!SistemAyar::get(self::SK_STARTED)) {
            SistemAyar::set(self::SK_STARTED, now()->toDateTimeString());
        }

        $gecis     = (int) (SistemAyar::get(self::SK_GECIS, '1') ?: 1);
        $currentNo = max(1, (int) (SistemAyar::get(self::SK_CURRENT, '1') ?: 1));
        $durum     = $gecis === 1 ? 'GEÇERLİ' : 'İPTAL';

        SistemAyar::set(self::SK_STATUS, 'running');

        $this->info("[acenteler:sync] Geçiş {$gecis}/2 ({$durum}) | {$currentNo}–" . self::END_NO . " | Batch: {$batch}");

        [$jar, $token] = $this->initSessionWithRetry();
        if (!$token) {
            SistemAyar::set(self::SK_STATUS, 'error');
            $this->error('Bakanlık oturumu açılamadı (3 deneme başarısız).');
            return 1;
        }

        $processed = 0;
        $found     = 0;

        for ($no = $currentNo; $no <= self::END_NO && $processed < $batch; $no++) {
            try {
                $rows = $this->fetchBelgeNo($jar, $token, $no, $durum);

                if ($rows === null) {
                    // Token süresi dolmuş veya bağlantı koptu — yenile ve tekrar dene
                    usleep(2000 * 1000); // 2 sn bekle
                    [$jar, $token] = $this->initSessionWithRetry();
                    if (!$token) {
                        $this->warn("  [{$no}] Oturum yenilenemedi, bu kayıt atlanıyor.");
                        SistemAyar::set(self::SK_CURRENT, (string) ($no + 1));
                        SistemAyar::set(self::SK_AT, now()->toDateTimeString());
                        $processed++;
                        continue;
                    }
                    $rows = $this->fetchBelgeNo($jar, $token, $no, $durum);
                }

                if (!empty($rows)) {
                    $saved  = $this->syncRows($rows, $durum);
                    $found += $saved;
                    $this->line("  [{$no}] " . count($rows) . " kayıt, {$saved} güncellendi/eklendi.");
                }
            } catch (\Throwable $e) {
                $this->error("  [{$no}] Hata: " . $e->getMessage());
            }

            SistemAyar::set(self::SK_CURRENT, (string) ($no + 1));
            SistemAyar::set(self::SK_AT,      now()->toDateTimeString());
            $processed++;

            if ($processed < $batch && $no < self::END_NO) {
                usleep($delay * 1000);
            }
        }

        $totalFound = (int) (SistemAyar::get(self::SK_FOUND, '0') ?: 0) + $found;
        SistemAyar::set(self::SK_FOUND, (string) $totalFound);

        $gecisiBitti = ((int) SistemAyar::get(self::SK_CURRENT) > self::END_NO);

        if ($gecisiBitti && $gecis === 1) {
            // Geçiş 1 bitti → Geçiş 2'ye geç
            SistemAyar::set(self::SK_GECIS,   '2');
            SistemAyar::set(self::SK_CURRENT, '1');
            SistemAyar::set(self::SK_STATUS,  'paused');
            $this->info('Geçiş 1 (GEÇERLİ) tamamlandı. Geçiş 2 (İPTAL) için tekrar çalıştırın.');
        } elseif ($gecisiBitti && $gecis === 2) {
            // Her iki geçiş bitti → tamamlama adımları
            $this->tamamla();
            SistemAyar::set(self::SK_STATUS, 'done');
            $this->info('✅ Tam senkronizasyon tamamlandı.');
        } else {
            SistemAyar::set(self::SK_STATUS, 'paused');
        }

        $this->info("[acenteler:sync] Bu batch: {$processed} belge no | {$found} güncellendi/eklendi | Toplam: {$totalFound}");
        return 0;
    }

    // ── Her kayıt için updateOrInsert ────────────────────────────────────────
    private function syncRows(array $rows, string $durum): int
    {
        $saved = 0;
        foreach ($rows as $row) {
            $internalId = $row['AcenteId'] ?? null;
            $unvani     = trim($row['Unvan'] ?? '');
            if (!$unvani) continue;

            DB::table('acenteler')->updateOrInsert(
                ['kaynak' => 'bakanlik', 'internal_id' => $internalId ?: ('bk_' . md5($unvani . ($row['BelgeNo'] ?? '')))],
                [
                    'belge_no'      => (string) ($row['BelgeNo'] ?? ''),
                    'sube_sira'     => 0,
                    'is_sube'       => str_contains(mb_strtoupper($unvani), 'ŞUBE') ? 1 : 0,
                    'acente_unvani' => $unvani,
                    'ticari_unvan'  => trim($row['TicaretUnvan'] ?? '') ?: null,
                    'grup'          => $row['Grup']    ?? null,
                    'il'            => $row['IlAd']    ?? null,
                    'il_ilce'       => $row['IlceAd']  ?? null,
                    'telefon'       => $row['Telefon']  ?? null,
                    'eposta'        => $row['Eposta']   ?? null,
                    'adres'         => $row['Adres']    ?? null,
                    'kaynak'        => 'bakanlik',
                    'internal_id'   => $internalId,
                    'durum'         => $durum,
                    'synced_at'     => now(),
                ]
            );
            $saved++;
        }
        return $saved;
    }

    // ── Her iki geçiş bittikten sonra çalışır ────────────────────────────────
    private function tamamla(): void
    {
        $startedAt = SistemAyar::get(self::SK_STARTED);

        // 1. Bu sync'te görülmeyen bakanlik kayıtları → İPTAL (kaybolmuş)
        if ($startedAt) {
            $affected = DB::table('acenteler')
                ->where('kaynak', 'bakanlik')
                ->where(function ($q) use ($startedAt) {
                    $q->whereNull('synced_at')
                      ->orWhere('synced_at', '<', $startedAt);
                })
                ->update(['durum' => 'İPTAL']);
            $this->info("  {$affected} kayıt İPTAL olarak işaretlendi (sync'te görülmedi).");
        }

        if ($this->option('skip-cleanup')) {
            $this->info('  --skip-cleanup: TÜRSAB temizleme atlandı.');
            return;
        }

        // 2. TÜRSAB grup bilgisini bakanlik kayıtlarına aktar
        $aktarilan = DB::statement("
            UPDATE acenteler b
            JOIN acenteler t ON t.belge_no = b.belge_no AND t.kaynak = 'tursab'
            SET b.grup = COALESCE(b.grup, t.grup)
            WHERE b.kaynak = 'bakanlik' AND t.grup IS NOT NULL AND t.grup != ''
        ");
        $this->info('  TÜRSAB grup bilgisi bakanlik kayıtlarına aktarıldı.');

        // 3. TÜRSAB satırlarını sil (sadece_tursab=0, kayıp yok)
        $silinen = DB::table('acenteler')->where('kaynak', 'tursab')->delete();
        $this->info("  {$silinen} TÜRSAB satırı silindi.");
    }

    // ── Bakanlık API ─────────────────────────────────────────────────────────
    private function fetchBelgeNo(CookieJar $jar, string $token, int $no, string $durum): ?array
    {
        try {
            $resp = Http::withOptions(['cookies' => $jar])
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->withHeaders(['Referer' => self::SORGU_URL, 'X-Requested-With' => 'XMLHttpRequest'])
                ->timeout(30)
                ->asForm()
                ->post(self::JSON_URL, [
                    '__RequestVerificationToken' => $token,
                    'BelgeNo'       => (string) $no,
                    'IlId'          => '0',
                    'Durum'         => $durum,
                    'Grup'          => '',
                    'AcenteAd'      => '',
                    'TicariUnvan'   => '',
                    'draw'          => '1',
                    'start'         => '0',
                    'length'        => '50',
                    'search[value]' => '',
                    'search[regex]' => 'false',
                ]);

            if (!$resp->successful()) return null;
            return $resp->json()['data'] ?? [];
        } catch (\Throwable) {
            return null;
        }
    }

    private function initSessionWithRetry(int $maxTry = 3): array
    {
        for ($i = 1; $i <= $maxTry; $i++) {
            $result = $this->initSession();
            if ($result[1]) return $result; // token var, başarılı
            $this->warn("  Oturum denemesi {$i}/{$maxTry} başarısız, {$i}0 sn bekleniyor...");
            sleep($i * 10); // 10s, 20s, 30s
        }
        return [new CookieJar(), ''];
    }

    private function initSession(): array
    {
        try {
            $resp = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->timeout(30)
                ->get(self::SORGU_URL);

            if (!$resp->successful()) return [new CookieJar(), ''];

            $jar = new CookieJar();
            foreach ($resp->cookies()->toArray() as $c) {
                $jar->setCookie(new SetCookie($c));
            }
            preg_match('/__RequestVerificationToken[^>]+value="([^"]+)"/i', $resp->body(), $tok);
            return [$jar, $tok[1] ?? ''];
        } catch (\Throwable) {
            return [new CookieJar(), ''];
        }
    }
}
