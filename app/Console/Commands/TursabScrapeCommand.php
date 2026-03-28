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

    private const TURSAB_URL   = 'https://online.tursab.org.tr/publicpages/embedded/agencysearch/';
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
                            'kaynak'        => 'tursab',
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
            $rsmTsm             = $this->extractHidden($html, 'RadScriptManager_TSM');
            $cookies            = $response->cookies()->toArray();

            return [
                '__VIEWSTATE'          => $viewState,
                '__VIEWSTATEGENERATOR' => $viewStateGen,
                '__EVENTVALIDATION'    => $eventValidation,
                'RadScriptManager_TSM' => $rsmTsm,
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
            // ASP.NET WebForms POST isteği (online.tursab.org.tr)
            $postData = [
                'RadScriptManager_TSM'                                          => $tokens['RadScriptManager_TSM']   ?? '',
                '__EVENTTARGET'                                                 => '',
                '__EVENTARGUMENT'                                               => '',
                '__LASTFOCUS'                                                   => '',
                '__VIEWSTATE'                                                   => $tokens['__VIEWSTATE']            ?? '',
                '__VIEWSTATEGENERATOR'                                          => $tokens['__VIEWSTATEGENERATOR']   ?? '',
                '__EVENTVALIDATION'                                             => $tokens['__EVENTVALIDATION']      ?? '',
                'ctl00$ContentPlaceHolder1$OprGroup'                            => 'NameSearchRadio',
                'ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBox'        => '',
                'ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBoxHF'      => '',
                'ctl00$ContentPlaceHolder1$TursabNo$AutoCompleteTextBoxTF'      => '',
                'ctl00$ContentPlaceHolder1$TursabNoText'                        => (string) $belgeNo,
                'ctl00$ContentPlaceHolder1$SearchButton'                        => 'Ara',
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
    // Yeni format: div.lit-container içinde her satır
    private function parseResults(string $html): array
    {
        $results = [];

        // Sonuç yok kontrolü
        if (
            str_contains($html, 'Acenta Bulunamadı') ||
            str_contains($html, 'Arama sonucu bulunamadı') ||
            str_contains($html, 'kayıt bulunamadı') ||
            str_contains($html, 'sonuç bulunamadı')
        ) {
            return [];
        }

        // div.lit-container yapısı:
        // <div class="lit-container">
        //   <div class="w3-row">
        //     <div class="w3-col l1 lit"><div class="litc">18801</div></div>   ← belge no
        //     <div class="w3-col l5 lit"><div class="litc"><span>Seyahat Acentası Adı : </span>CTG...</div></div>
        //     <div class="w3-col l3 lit"><div class="litc"><span>Telefon : </span>...</div></div>
        //     <div class="w3-col l3 lit"><div class="litc"><span>Email : </span>...</div></div>
        //   </div>
        //   <div class="w3-row"><div ...><span>Adres : </span>...</div></div>
        //   <div class="w3-row"><div ...><span>BTK :</span> ...</div></div>
        // </div>

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        $containers = $xpath->query('//*[contains(@class,"lit-container")]');

        if ($containers && $containers->length > 0) {
            foreach ($containers as $container) {
                $record = $this->parseLitContainer($container, $xpath);
                if ($record) $results[] = $record;
            }
            return $results;
        }

        // Fallback: metin bazlı parse (div yapısı da değişmişse)
        return $this->parseFromText($html);
    }

    // ── div.lit-container'dan kayıt oluştur ──────────────────────────────
    private function parseLitContainer(\DOMElement $container, \DOMXPath $xpath): ?array
    {
        // Tüm metin içeriğini al ve " : " kalıbıyla parse et
        $fullText = html_entity_decode($container->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $labelMap = [
            'seyahat acentası adı' => 'acente_unvani',
            'acenta adı'           => 'acente_unvani',
            'telefon'              => 'telefon',
            'email'                => 'eposta',
            'e-posta'              => 'eposta',
            'adres'                => 'adres',
            'btk'                  => 'btk',
            'il'                   => 'il',
            'ilçe'                 => 'il_ilce',
            'grup'                 => 'grup',
        ];

        $record   = [];
        $belgeNo  = null;

        // Belge no: litc içinde font-weight:700 olan ya da ilk l1 sütunundaki sayısal değer
        $litcNodes = $xpath->query('.//*[contains(@class,"litc")]', $container);
        foreach ($litcNodes as $litc) {
            $style = strtolower($litc->getAttribute('style'));
            $text  = trim($litc->textContent);
            if ((str_contains($style, 'font-weight:700') || str_contains($style, 'font-weight: 700'))
                && is_numeric($text)) {
                $belgeNo = $text;
                break;
            }
        }

        // Span'ları parse et: "Label : Değer" veya "Label :" + sonraki text
        $spans = $xpath->query('.//span', $container);
        foreach ($spans as $span) {
            $spanText = trim($span->textContent);
            // "Seyahat Acentası Adı : " gibi — iki nokta üstüyle biter
            $colonPos = mb_strpos($spanText, ':');
            if ($colonPos === false) continue;

            $label = mb_strtolower(trim(mb_substr($spanText, 0, $colonPos)));
            $label = preg_replace('/\s+/', ' ', $label);

            if (!isset($labelMap[$label])) continue;

            $col = $labelMap[$label];

            // Değer: span'ın nextSibling text node'u veya parent litc'in span sonrası metni
            $parentText = trim($span->parentNode->textContent ?? '');
            $afterColon = trim(mb_substr($parentText, mb_strpos($parentText, ':') + 1));

            if ($afterColon && !isset($record[$col])) {
                $record[$col] = $afterColon;
            }
        }

        // Belge no'yu adres satırından çıkarmaya çalış (bold şehir/ilçe)
        // Adres içindeki <b> tagları: il ve ilçe
        if (isset($record['adres']) && !isset($record['il'])) {
            $bolds = $xpath->query('.//b', $container);
            $boldTexts = [];
            foreach ($bolds as $b) $boldTexts[] = trim($b->textContent);
            if (count($boldTexts) >= 2) {
                $record['il_ilce'] = $boldTexts[0] . ' / ' . $boldTexts[1];
                $record['il']      = $boldTexts[1]; // son bold = il
            } elseif (count($boldTexts) === 1) {
                $record['il'] = $boldTexts[0];
            }
        }

        if (empty($record['acente_unvani'])) return null;

        $record['belge_no_ham'] = $belgeNo; // kayıt için sakla (belge_no field değil)

        return $record;
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
