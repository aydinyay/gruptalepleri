<?php

namespace App\Console\Commands;

use App\Models\Acenteler;
use App\Models\SistemAyar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TursabScrapeCommand extends Command
{
    protected $signature = 'tursab:scrape
                            {--start=    : Başlangıç belge no (boşsa kaldığı yerden devam)}
                            {--end=18804 : Bitiş belge no}
                            {--batch=50  : Bu çalışmada kaç belge no işlensin}
                            {--delay=800 : İstekler arası bekleme (ms)}
                            {--beyond    : 18804 sonrasını da tara, 100 ardışık boşta dur}
                            {--reset     : Sıfırdan başla (progress sıfırla)}';

    protected $description = 'TÜRSAB acenta-arama sayfasından belge no ile acente bilgisi çeker ve acenteler tablosuna kaydeder.';

    private const TURSAB_URL   = 'https://www.tursab.org.tr/acenta-arama';
    private const SK_LAST_NO   = 'tursab_scrape_last_no';
    private const SK_FOUND     = 'tursab_scrape_found';
    private const SK_STATUS    = 'tursab_scrape_status';
    private const SK_AT        = 'tursab_scrape_at';
    private const SK_END       = 'tursab_scrape_end';

    public function handle(): int
    {
        // ── Başlangıç parametreleri ──────────────────────────────────────
        if ($this->option('reset')) {
            SistemAyar::set(self::SK_LAST_NO, '0');
            SistemAyar::set(self::SK_FOUND,   '0');
            SistemAyar::set(self::SK_STATUS,  'idle');
            $this->info('[reset] Progress sıfırlandı.');
        }

        $endNo    = (int) ($this->option('end') ?: 18804);
        $batch    = max(1, (int) ($this->option('batch') ?: 50));
        $delay    = max(300, (int) ($this->option('delay') ?: 800));
        $beyond   = $this->option('beyond');

        // Kaldığı yerden devam mı, yoksa belirlenen start'tan mı?
        if ($this->option('start')) {
            $currentNo = max(1, (int) $this->option('start'));
        } else {
            $currentNo = (int) (SistemAyar::get(self::SK_LAST_NO, '0') ?: 0);
            $currentNo = max(1, $currentNo + 1);
        }

        // beyond modunda bitiş no'yu büyüt
        if ($beyond) {
            $endNo = max($endNo, $currentNo + $batch + 100);
        }

        SistemAyar::set(self::SK_STATUS, 'running');
        SistemAyar::set(self::SK_AT,     now()->toDateTimeString());
        SistemAyar::set(self::SK_END,    (string) $endNo);

        $this->info("[tursab:scrape] Başlangıç: {$currentNo} | Bitiş: {$endNo} | Batch: {$batch}");

        // ── ViewState token al ───────────────────────────────────────────
        $tokens = $this->fetchTokens();
        if (!$tokens) {
            SistemAyar::set(self::SK_STATUS, 'error');
            $this->error('TÜRSAB sayfasına ulaşılamadı veya form tokeni bulunamadı.');
            return 1;
        }

        // ── Ana döngü ────────────────────────────────────────────────────
        $processed        = 0;
        $found            = 0;
        $consecutiveEmpty = 0;

        for ($no = $currentNo; $no <= $endNo && $processed < $batch; $no++) {
            $rows = $this->searchBelgeNo($no, $tokens, $delay);

            if ($rows === null) {
                // Hata — token yenile ve devam et
                $tokens = $this->fetchTokens();
                if (!$tokens) break;
                $rows = $this->searchBelgeNo($no, $tokens, $delay);
            }

            if (empty($rows)) {
                $consecutiveEmpty++;
                if ($beyond && $consecutiveEmpty >= 100) {
                    $this->info("100 ardışık boş belge no — beyond tarama durdu.");
                    break;
                }
            } else {
                $consecutiveEmpty = 0;
                $subeIdx          = 0;

                // Bu belge noya ait mevcut en büyük sube_sira'yı al
                $maxSube = DB::table('acenteler')
                    ->where('belge_no', (string) $no)
                    ->max('sube_sira') ?? -1;

                foreach ($rows as $row) {
                    $isSube  = $this->isSube($row['acente_unvani'] ?? '');
                    $subeSira = $maxSube + 1 + $subeIdx;

                    // Aynı isim zaten kayıtlı mı?
                    $exists = DB::table('acenteler')
                        ->where('belge_no', (string) $no)
                        ->whereRaw('LOWER(TRIM(acente_unvani)) = ?', [
                            mb_strtolower(trim($row['acente_unvani'] ?? ''))
                        ])
                        ->exists();

                    if (!$exists) {
                        DB::table('acenteler')->insert([
                            'belge_no'      => (string) $no,
                            'sube_sira'     => $subeSira,
                            'is_sube'       => $isSube ? 1 : 0,
                            'acente_unvani' => $row['acente_unvani'] ?? null,
                            'ticari_unvan'  => $row['ticari_unvan']  ?? null,
                            'grup'          => $row['grup']          ?? null,
                            'il'            => $row['il']            ?? null,
                            'il_ilce'       => $row['il_ilce']       ?? null,
                            'telefon'       => $row['telefon']       ?? null,
                            'eposta'        => $row['eposta']        ?? null,
                            'adres'         => $row['adres']         ?? null,
                            'btk'           => $row['btk']           ?? null,
                        ]);
                        $found++;
                        $subeIdx++;
                    }
                }

                if ($found <= 5 || $no % 200 === 0) {
                    $label = count($rows) > 1 ? ' (' . count($rows) . ' şube)' : '';
                    $this->line("  [{$no}] " . ($rows[0]['acente_unvani'] ?? '?') . $label);
                }
            }

            $processed++;
            SistemAyar::set(self::SK_LAST_NO, (string) $no);
        }

        // ── Bitti ────────────────────────────────────────────────────────
        $totalFound = (int) (SistemAyar::get(self::SK_FOUND, '0') ?: 0);
        $totalFound += $found;
        SistemAyar::set(self::SK_FOUND,  (string) $totalFound);

        $lastNo    = (int) SistemAyar::get(self::SK_LAST_NO, '0');
        $isDone    = ($lastNo >= $endNo);
        SistemAyar::set(self::SK_STATUS, $isDone ? 'idle' : 'paused');

        $this->info("[tursab:scrape] Bitti — İşlenen: {$processed} | Bu batch bulunan: {$found} | Toplam: {$totalFound} | Son no: {$lastNo}");

        return 0;
    }

    // ── TÜRSAB sayfasından ViewState tokenlarını çek ─────────────────────
    private function fetchTokens(): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'tr-TR,tr;q=0.9,en;q=0.8',
            ])->timeout(20)->get(self::TURSAB_URL);

            if (!$response->ok()) return null;

            $html = $response->body();

            $viewState          = $this->extractHidden($html, '__VIEWSTATE');
            $viewStateGen       = $this->extractHidden($html, '__VIEWSTATEGENERATOR');
            $eventValidation    = $this->extractHidden($html, '__EVENTVALIDATION');
            $cookies            = $response->cookies()->toArray();

            return [
                '__VIEWSTATE'          => $viewState,
                '__VIEWSTATEGENERATOR' => $viewStateGen,
                '__EVENTVALIDATION'    => $eventValidation,
                'cookies'              => $cookies,
                'raw'                  => $html,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Belirli bir belge no için arama yap, tüm sonuçları döndür ────────
    private function searchBelgeNo(int $belgeNo, array $tokens, int $delayMs): ?array
    {
        usleep($delayMs * 1000);

        try {
            // ASP.NET WebForms POST isteği
            $postData = [
                '__VIEWSTATE'                                => $tokens['__VIEWSTATE']          ?? '',
                '__VIEWSTATEGENERATOR'                       => $tokens['__VIEWSTATEGENERATOR'] ?? '',
                '__EVENTVALIDATION'                          => $tokens['__EVENTVALIDATION']    ?? '',
                '__EVENTTARGET'                              => '',
                '__EVENTARGUMENT'                            => '',
                'ContentPlaceHolder1_TursabNoText'           => (string) $belgeNo,
                'ContentPlaceHolder1_AcentaAdText'           => '',
                'ContentPlaceHolder1_SearchButton'           => 'ARA',
            ];

            // Cookie header oluştur
            $cookieHeader = '';
            foreach (($tokens['cookies'] ?? []) as $cookie) {
                $name  = $cookie['Name']  ?? $cookie['name']  ?? '';
                $value = $cookie['Value'] ?? $cookie['value'] ?? '';
                if ($name) $cookieHeader .= "{$name}={$value}; ";
            }

            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                'Referer'         => self::TURSAB_URL,
                'Content-Type'    => 'application/x-www-form-urlencoded',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Cookie'          => trim($cookieHeader, '; '),
            ])->timeout(15)->asForm()->post(self::TURSAB_URL, $postData);

            if (!$response->ok()) return null;

            return $this->parseResults($response->body());

        } catch (\Throwable) {
            return null;
        }
    }

    // ── HTML yanıtından tüm acente kayıtlarını parse et ─────────────────
    private function parseResults(string $html): array
    {
        $results = [];

        // Hata veya sonuç yok kontrolü
        if (
            str_contains($html, 'Acenta Bulunamadı') ||
            str_contains($html, 'Arama sonucu bulunamadı') ||
            str_contains($html, 'kayıt bulunamadı')
        ) {
            return [];
        }

        // Önce DOMDocument ile parse etmeyi dene
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        // TÜRSAB sonuç satırları genellikle <tr> içinde ya da tekrarlayan div blokları
        // Önce <tr> ile deneyelim (tablo sonuç)
        $rows = $xpath->query('//table[@id]//tr[position()>1]');
        if ($rows && $rows->length > 0) {
            foreach ($rows as $row) {
                $cells = $xpath->query('td', $row);
                if ($cells->length >= 3) {
                    $record = $this->buildRecordFromCells($cells, $xpath);
                    if ($record) $results[] = $record;
                }
            }
            if (!empty($results)) return $results;
        }

        // Tablo bulunamazsa metin bazlı parse
        return $this->parseFromText($html);
    }

    // ── Tablo hücrelerinden kayıt oluştur ────────────────────────────────
    private function buildRecordFromCells(\DOMNodeList $cells, \DOMXPath $xpath): ?array
    {
        $texts = [];
        foreach ($cells as $cell) {
            $texts[] = trim($cell->textContent);
        }

        // En az acente adı olmalı
        if (empty(array_filter($texts))) return null;

        // Belirli index'lere göre eşleştir (TÜRSAB tablo yapısına göre)
        // Tipik düzen: BelgeNo | AcenteAdı | Grup | İl | İlçe | Telefon | Email
        return [
            'acente_unvani' => $texts[1] ?? $texts[0] ?? null,
            'ticari_unvan'  => $texts[2] ?? null,
            'grup'          => $texts[3] ?? null,
            'il'            => $texts[4] ?? null,
            'il_ilce'       => $texts[5] ?? null,
            'telefon'       => $texts[6] ?? null,
            'eposta'        => isset($texts[7]) && str_contains($texts[7], '@') ? $texts[7] : null,
            'adres'         => null,
            'btk'           => null,
        ];
    }

    // ── Metin bazlı parse (tablo bulunamadığında fallback) ───────────────
    private function parseFromText(string $html): array
    {
        $results = [];
        $text    = strip_tags($html);
        $text    = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // " : " pattern ile satır satır parse
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines);

        $current = [];

        foreach ($lines as $line) {
            $colonIdx = mb_strpos($line, ' : ');
            if ($colonIdx === false) continue;

            $label = mb_strtolower(trim(mb_substr($line, 0, $colonIdx)));
            $value = trim(mb_substr($line, $colonIdx + 3));

            if (!$value) continue;

            if (str_contains($label, 'acenta') && str_contains($label, 'ad')) {
                // Yeni kayıt başlıyor
                if (!empty($current)) $results[] = $current;
                $current = ['acente_unvani' => $value];
            } elseif ($label === 'telefon') {
                $current['telefon'] = $value;
            } elseif ($label === 'email' || $label === 'e-posta') {
                $current['eposta'] = $value;
            } elseif ($label === 'adres') {
                $current['adres'] = $value;
            } elseif ($label === 'btk') {
                $current['btk'] = $value;
            } elseif (str_contains($label, 'il') && !str_contains($label, 'ilçe')) {
                $current['il'] = $value;
            } elseif (str_contains($label, 'ilçe') || str_contains($label, 'ilce')) {
                $current['il_ilce'] = $value;
            } elseif ($label === 'grup' || $label === 'belge grubu') {
                $current['grup'] = $value;
            }
        }

        if (!empty($current)) $results[] = $current;

        return $results;
    }

    // ── Şube tespiti: unvanda "ŞUBE" geçiyor mu? ────────────────────────
    private function isSube(string $unvan): bool
    {
        $upper = mb_strtoupper($unvan, 'UTF-8');
        return str_contains($upper, 'ŞUBE') || str_contains($upper, 'SUBE');
    }

    // ── Hidden input değerini HTML'den çek ──────────────────────────────
    private function extractHidden(string $html, string $name): string
    {
        $pattern = '/<input[^>]+name=["\']' . preg_quote($name, '/') . '["\'][^>]+value=["\']([^"\']*)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $m)) return $m[1];

        // value önce geliyorsa
        $pattern2 = '/<input[^>]+value=["\']([^"\']*)["\'][^>]+name=["\']' . preg_quote($name, '/') . '["\'][^>]*>/i';
        if (preg_match($pattern2, $html, $m)) return $m[1];

        return '';
    }
}
