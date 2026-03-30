<?php

namespace App\Console\Commands;

use App\Models\SistemAyar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class BakanlikScrapeCommand extends Command
{
    protected $signature = 'bakanlik:scrape
                            {--batch=20   : Bu çalışmada kaç belge no işlensin}
                            {--delay=400  : İstekler arası bekleme (ms)}
                            {--start=     : Başlangıç belge no (boş = kaldığı yer)}
                            {--end=20000  : Bitiş belge no}
                            {--reset      : İlerlemeyi sıfırla, baştan başla}';

    protected $description = 'Turizm Bakanlığı JSON API\'sinden GEÇERLİ acenteleri belge no bazlı çeker ve kaydeder.';

    private const BASE_URL  = 'https://yatirimisletmeleruygulama.kultur.gov.tr';
    private const SORGU_URL = self::BASE_URL . '/acente.web.sorgu/sorgu/acentesorgu';
    private const JSON_URL  = self::BASE_URL . '/Acente.Web.Sorgu/Sorgu/AcentelerSearchJson';
    private const KAYNAK    = 'bakanlik';

    private const SK_CURRENT = 'bakanlik_scrape_current_no';
    private const SK_FOUND   = 'bakanlik_scrape_found';
    private const SK_STATUS  = 'bakanlik_scrape_status';
    private const SK_AT      = 'bakanlik_scrape_at';
    private const SK_END     = 'bakanlik_scrape_end';

    public function handle(): int
    {
        if ($this->option('reset')) {
            SistemAyar::set(self::SK_CURRENT, '1');
            SistemAyar::set(self::SK_FOUND,   '0');
            SistemAyar::set(self::SK_STATUS,  'idle');
            $this->info('[reset] İlerleme sıfırlandı.');
        }

        $endNo     = (int) ($this->option('end') ?: SistemAyar::get(self::SK_END, '18804') ?: 18804);
        $batch     = max(1, (int) ($this->option('batch') ?: 20));
        $delay     = max(200, (int) ($this->option('delay') ?: 400));

        $startOpt  = $this->option('start');
        if ($startOpt) {
            $startNo = max(1, (int) $startOpt);
            SistemAyar::set(self::SK_CURRENT, (string) $startNo);
        } else {
            $startNo = max(1, (int) (SistemAyar::get(self::SK_CURRENT, '1') ?: 1));
        }

        SistemAyar::set(self::SK_END,    (string) $endNo);
        SistemAyar::set(self::SK_STATUS, 'running');
        SistemAyar::set(self::SK_AT,     now()->toDateTimeString());

        if ($startNo > $endNo) {
            $this->info("Tüm belge nolar tarandı ({$endNo}). --reset ile baştan başlayın.");
            SistemAyar::set(self::SK_STATUS, 'idle');
            return 0;
        }

        $this->info("[bakanlik:scrape] {$startNo}–{$endNo} | Batch: {$batch}");

        [$jar, $token] = $this->initSession();
        if (!$token) {
            SistemAyar::set(self::SK_STATUS, 'error');
            $this->error('Oturum açılamadı.');
            return 1;
        }

        $found     = 0;
        $processed = 0;
        $currentNo = $startNo;

        for ($no = $startNo; $no <= $endNo && $processed < $batch; $no++) {
            try {
                $rows = $this->fetchBelgeNo($jar, $token, $no);

                if ($rows === null) {
                    // Token süresi dolmuş olabilir
                    [$jar, $token] = $this->initSession();
                    $rows = $this->fetchBelgeNo($jar, $token, $no);
                }

                if (!empty($rows)) {
                    $saved  = $this->saveRows($rows);
                    $found += $saved;
                    $this->line("  [{$no}] " . count($rows) . " kayıt, {$saved} yeni.");
                }
            } catch (\Throwable $e) {
                $this->error("  [{$no}] Hata: " . $e->getMessage());
            }

            $currentNo = $no + 1;
            SistemAyar::set(self::SK_CURRENT, (string) $currentNo);
            SistemAyar::set(self::SK_AT,      now()->toDateTimeString());
            $processed++;

            if ($processed < $batch && $no < $endNo) {
                usleep($delay * 1000);
            }
        }

        $totalFound = (int) (SistemAyar::get(self::SK_FOUND, '0') ?: 0) + $found;
        SistemAyar::set(self::SK_FOUND, (string) $totalFound);

        $isDone = ($currentNo > $endNo);
        SistemAyar::set(self::SK_STATUS, $isDone ? 'idle' : 'paused');

        $this->info("[bakanlik:scrape] Bitti — {$processed} belge no | Bu batch: {$found} yeni | Toplam DB: {$totalFound}");
        return 0;
    }

    private function initSession(): array
    {
        try {
            $resp = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->timeout(20)
                ->get(self::SORGU_URL);

            if (!$resp->successful()) return [new CookieJar(), ''];

            $jar = new CookieJar();
            foreach ($resp->cookies()->toArray() as $c) {
                $jar->setCookie(new SetCookie($c));
            }

            preg_match('/__RequestVerificationToken[^>]+value="([^"]+)"/i', $resp->body(), $tok);
            $token = $tok[1] ?? '';

            return [$jar, $token];
        } catch (\Throwable) {
            return [new CookieJar(), ''];
        }
    }

    private function fetchBelgeNo(CookieJar $jar, string $token, int $no): ?array
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
                    'Durum'         => 'GEÇERLİ',
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

            $json = $resp->json();
            return $json['data'] ?? [];
        } catch (\Throwable) {
            return null;
        }
    }

    private function saveRows(array $rows): int
    {
        $saved = 0;

        foreach ($rows as $row) {
            $internalId = $row['AcenteId'] ?? null;
            $belgeNo    = (string) ($row['BelgeNo'] ?? '');
            $unvani     = trim($row['Unvan'] ?? '');

            if (!$unvani) continue;

            if ($internalId) {
                $exists = DB::table('acenteler')
                    ->where('kaynak', self::KAYNAK)
                    ->where('internal_id', $internalId)
                    ->exists();
                if ($exists) continue;
            }

            DB::table('acenteler')->insert([
                'belge_no'      => $belgeNo ?: null,
                'sube_sira'     => 0,
                'is_sube'       => 0,
                'acente_unvani' => $unvani,
                'ticari_unvan'  => trim($row['TicaretUnvan'] ?? '') ?: null,
                'grup'          => $row['Grup']     ?? null,
                'il'            => $row['IlAd']     ?? null,
                'il_ilce'       => $row['IlceAd']   ?? null,
                'telefon'       => $row['Telefon']   ?? null,
                'eposta'        => $row['Eposta']    ?? null,
                'adres'         => $row['Adres']     ?? null,
                'btk'           => null,
                'kaynak'        => self::KAYNAK,
                'internal_id'   => $internalId,
            ]);
            $saved++;
        }

        return $saved;
    }
}
