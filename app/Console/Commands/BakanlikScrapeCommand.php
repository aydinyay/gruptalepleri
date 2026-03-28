<?php

namespace App\Console\Commands;

use App\Models\Acenteler;
use App\Models\SistemAyar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BakanlikScrapeCommand extends Command
{
    protected $signature = 'bakanlik:scrape
                            {--batch=5       : Bu çalışmada kaç il işlensin}
                            {--delay=1500    : İstekler arası bekleme (ms)}
                            {--reset         : İl indexini sıfırla, baştan başla}
                            {--iller-reset   : İl listesi önbelleğini temizle ve yeniden çek}';

    protected $description = 'Turizm Bakanlığı acente veritabanından GEÇERLİ acenteleri çeker ve acenteler tablosuna kaydeder.';

    private const BASE_URL  = 'https://yatirimisletmeleruygulama.kultur.gov.tr';
    private const SORGU_URL = self::BASE_URL . '/acente.web.sorgu/sorgu/acentesorgu';
    private const DETAY_URL = self::BASE_URL . '/Acente.Web.Sorgu/Sorgu/AcenteBilgi';
    private const KAYNAK    = 'bakanlik';

    private const SK_IL_IDX = 'bakanlik_scrape_il_idx';
    private const SK_FOUND  = 'bakanlik_scrape_found';
    private const SK_STATUS = 'bakanlik_scrape_status';
    private const SK_AT     = 'bakanlik_scrape_at';
    private const SK_END    = 'bakanlik_scrape_end';
    private const SK_ILLER  = 'bakanlik_scrape_iller';

    public function handle(): int
    {
        if ($this->option('reset')) {
            SistemAyar::set(self::SK_IL_IDX, '0');
            SistemAyar::set(self::SK_FOUND,  '0');
            SistemAyar::set(self::SK_STATUS, 'idle');
            $this->info('[reset] Progress sıfırlandı.');
        }

        if ($this->option('iller-reset')) {
            SistemAyar::set(self::SK_ILLER, '');
            $this->info('[iller-reset] İl listesi önbelleği temizlendi.');
        }

        $batch = max(1, (int) ($this->option('batch') ?: 5));
        $delay = max(500, (int) ($this->option('delay') ?: 1500));

        SistemAyar::set(self::SK_STATUS, 'running');
        SistemAyar::set(self::SK_AT,     now()->toDateTimeString());

        // ── İl listesi ────────────────────────────────────────────────────
        $iller = $this->getIller();
        if (empty($iller)) {
            SistemAyar::set(self::SK_STATUS, 'error');
            $this->error('İl listesi alınamadı. Site erişilebilir değil veya sayfa yapısı değişmiş.');
            return 1;
        }

        $totalIller = count($iller);
        SistemAyar::set(self::SK_END, (string) $totalIller);

        $currentIdx = max(0, (int) (SistemAyar::get(self::SK_IL_IDX, '0') ?: 0));

        if ($currentIdx >= $totalIller) {
            $this->info("Tüm {$totalIller} il tarandı. Yeniden başlamak için --reset kullanın.");
            SistemAyar::set(self::SK_STATUS, 'idle');
            return 0;
        }

        $this->info("[bakanlik:scrape] {$totalIller} il | Başlangıç: {$currentIdx} | Batch: {$batch}");

        $found     = 0;
        $processed = 0;

        for ($i = $currentIdx; $i < $totalIller && $processed < $batch; $i++) {
            [$ilVal, $ilText] = $iller[$i];
            $this->line("  [{$i}/{$totalIller}] {$ilText}…");

            try {
                $rows = $this->scrapeIl($ilVal, $delay);

                if ($rows === null) {
                    $this->warn("    ⚠ Sayfa alınamadı.");
                } elseif (empty($rows)) {
                    $this->line("    — Kayıt bulunamadı (sayfa JS ile yükleniyor olabilir).");
                } else {
                    $newCount = $this->saveRows($rows);
                    $found   += $newCount;
                    $this->line("    ✓ " . count($rows) . " çekildi, {$newCount} yeni kaydedildi.");
                }
            } catch (\Throwable $e) {
                $this->error("    Hata [{$ilText}]: " . $e->getMessage());
            }

            SistemAyar::set(self::SK_IL_IDX, (string) ($i + 1));
            SistemAyar::set(self::SK_AT,     now()->toDateTimeString());
            $processed++;

            if ($processed < $batch && $i + 1 < $totalIller) {
                usleep($delay * 1000);
            }
        }

        $totalFound = (int) (SistemAyar::get(self::SK_FOUND, '0') ?: 0) + $found;
        SistemAyar::set(self::SK_FOUND, (string) $totalFound);

        $nextIdx = $currentIdx + $processed;
        $isDone  = ($nextIdx >= $totalIller);
        SistemAyar::set(self::SK_STATUS, $isDone ? 'idle' : 'paused');

        $this->info("[bakanlik:scrape] Bitti — {$processed} il | Bu batch: {$found} yeni | Toplam DB: {$totalFound}");

        return 0;
    }

    // ── İl listesini çek (önbellekli) ────────────────────────────────────
    private function getIller(): array
    {
        $cached = SistemAyar::get(self::SK_ILLER, '');
        if ($cached) {
            $decoded = json_decode($cached, true);
            if (!empty($decoded)) return $decoded;
        }

        try {
            $resp = Http::timeout(30)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->get(self::SORGU_URL);

            if (!$resp->successful()) return [];

            $iller = $this->parseIlListFromHtml($resp->body());
            if (!empty($iller)) {
                SistemAyar::set(self::SK_ILLER, json_encode($iller, JSON_UNESCAPED_UNICODE));
            }

            return $iller;
        } catch (\Throwable) {
            return [];
        }
    }

    // ── HTML'den il dropdown'unu parse et ────────────────────────────────
    private function parseIlListFromHtml(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        $iller = [];

        foreach ($xpath->query('//select') as $select) {
            $id   = strtolower((string) $select->getAttribute('id'));
            $name = strtolower((string) $select->getAttribute('name'));
            $opts = $xpath->query('option', $select);

            if ($opts->length < 10) continue;

            // İl dropdown: id/name içinde 'il' geçiyor veya 50+ seçenek var
            $isIlDropdown = stripos($id, 'il') !== false
                         || stripos($name, 'il') !== false
                         || $opts->length >= 50;

            if (!$isIlDropdown) continue;

            $skip = ['', '0', 'Seçiniz', 'Seçiniz...', '--Seçiniz--', 'Tümü', 'Tüm İller'];

            foreach ($opts as $opt) {
                $val  = trim((string) $opt->getAttribute('value'));
                $text = trim((string) $opt->textContent);
                if ($val && $text && !in_array($text, $skip) && $val !== '0') {
                    $iller[] = [$val, $text];
                }
            }

            if (!empty($iller)) break;
        }

        return $iller;
    }

    // ── Bir il için arama yap ─────────────────────────────────────────────
    private function scrapeIl(string $ilVal, int $delay): ?array
    {
        $jar = new \GuzzleHttp\Cookie\CookieJar();

        // 1) GET → token + cookie + form yapısı
        try {
            $getResp = Http::withOptions(['cookies' => $jar])
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                ->get(self::SORGU_URL);

            if (!$getResp->successful()) return null;
        } catch (\Throwable) {
            return null;
        }

        $html       = $getResp->body();
        $token      = $this->extractToken($html);
        $formAction = $this->extractFormAction($html) ?? self::SORGU_URL;
        $postFields = $this->buildPostFields($html, $ilVal, $token);

        usleep($delay * 1000);

        // 2) POST → arama
        try {
            $postResp = Http::withOptions(['cookies' => $jar])
                ->timeout(90)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36')
                ->withHeaders([
                    'Referer' => self::SORGU_URL,
                    'Accept'  => 'text/html,application/xhtml+xml',
                ])
                ->asForm()
                ->post($formAction, $postFields);

            if (!$postResp->successful()) return null;
        } catch (\Throwable) {
            return null;
        }

        $rows = $this->parseTable($postResp->body());

        // 3) Detay çek (internal_id olan satırlar için)
        if (!empty($rows)) {
            $this->fetchDetails($jar, $token, $rows);
        }

        return $rows;
    }

    // ── CSRF token çıkar ─────────────────────────────────────────────────
    private function extractToken(string $html): string
    {
        if (preg_match('/<input[^>]+name="__RequestVerificationToken"[^>]+value="([^"]+)"/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<input[^>]+value="([^"]+)"[^>]+name="__RequestVerificationToken"/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    // ── Form action URL çıkar ────────────────────────────────────────────
    private function extractFormAction(string $html): ?string
    {
        if (preg_match('/<form[^>]+action="([^"]+)"/i', $html, $m)) {
            $action = html_entity_decode($m[1]);
            if (str_starts_with($action, 'http')) return $action;
            if (str_starts_with($action, '/'))    return self::BASE_URL . $action;
        }
        return null;
    }

    // ── POST alanlarını oluştur ───────────────────────────────────────────
    private function buildPostFields(string $html, string $ilVal, string $token): array
    {
        $fields = [];

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        // Hidden input'lar
        foreach ($xpath->query('//form//input[@type="hidden"]') as $input) {
            $n = (string) $input->getAttribute('name');
            $v = (string) $input->getAttribute('value');
            if ($n && $n !== '__RequestVerificationToken') {
                $fields[$n] = $v;
            }
        }

        // CSRF token
        if ($token) {
            $fields['__RequestVerificationToken'] = $token;
        }

        // Durum alanı (sadece GEÇERLİ indir)
        $durumFieldName = 'Durum';
        foreach ($xpath->query('//select') as $select) {
            $id   = (string) $select->getAttribute('id');
            $name = (string) $select->getAttribute('name');
            if (strtolower($id) === 'durum' || strtolower($name) === 'durum') {
                $durumFieldName = $name ?: $id;
                break;
            }
        }
        $fields[$durumFieldName] = 'GEÇERLİ';

        // İl alanı
        foreach ($xpath->query('//select') as $select) {
            $id   = (string) $select->getAttribute('id');
            $name = (string) $select->getAttribute('name');
            $opts = $xpath->query('option', $select);

            $isIl = stripos($id, 'il') !== false
                 || stripos($name, 'il') !== false
                 || $opts->length >= 50;

            if ($isIl && $opts->length >= 10) {
                $fieldName = $name ?: $id;
                if ($fieldName) {
                    $fields[$fieldName] = $ilVal;
                    break;
                }
            }
        }

        return $fields;
    }

    // ── Sonuç tablosunu parse et ──────────────────────────────────────────
    private function parseTable(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        $rows = [];

        // #acenteTable'dan satırları çek
        $trs = $xpath->query('//table[@id="acenteTable"]//tbody/tr');
        if (!$trs || $trs->length === 0) {
            $trs = $xpath->query('//table[@id="acenteTable"]//tr[position()>1]');
        }
        if (!$trs || $trs->length === 0) {
            // Tablo bulunamadı — muhtemelen JS gerektiriyor
            return [];
        }

        foreach ($trs as $tr) {
            $internalId = trim((string) $tr->getAttribute('id'));
            $cells      = $xpath->query('td', $tr);

            if ($cells->length < 2) continue;

            $texts = [];
            foreach ($cells as $cell) {
                $texts[] = trim((string) $cell->textContent);
            }

            // İlk hücre sıra numarası ise atla (ör. "1", "2")
            $offset = (isset($texts[0]) && is_numeric($texts[0]) && strlen($texts[0]) <= 5) ? 1 : 0;

            // Beklenen sütun düzeni: BelgeNo | Unvan | TicariUnvan | Grup | İl | Telefon
            $rows[] = [
                'internal_id'   => $internalId ?: null,
                'belge_no'      => $texts[$offset]     ?? null,
                'acente_unvani' => $texts[$offset + 1] ?? null,
                'ticari_unvan'  => $texts[$offset + 2] ?? null,
                'grup'          => $texts[$offset + 3] ?? null,
                'il'            => $texts[$offset + 4] ?? null,
                'telefon'       => $texts[$offset + 5] ?? null,
                'eposta'        => null,
                'adres'         => null,
                'il_ilce'       => null,
            ];
        }

        return $rows;
    }

    // ── Toplu detay çek ──────────────────────────────────────────────────
    private function fetchDetails(\GuzzleHttp\Cookie\CookieJar $jar, string $token, array &$rows): void
    {
        foreach ($rows as &$row) {
            $iid = $row['internal_id'] ?? '';
            if (!$iid) continue;

            try {
                $resp = Http::withOptions(['cookies' => $jar])
                    ->timeout(15)
                    ->withUserAgent('Mozilla/5.0')
                    ->withHeaders([
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer'          => self::SORGU_URL,
                    ])
                    ->asForm()
                    ->post(self::DETAY_URL, [
                        'id'                          => $iid,
                        'subeid'                      => '',
                        '__RequestVerificationToken'  => $token,
                    ]);

                if ($resp->successful() && strlen($resp->body()) > 50) {
                    $detail = $this->parseDetail($resp->body());
                    foreach ($detail as $k => $v) {
                        if ($v && !isset($row[$k])) {
                            $row[$k] = $v;
                        }
                    }
                }
            } catch (\Throwable) {
                // Detay alınamazsa liste verisiyle devam et
            }

            usleep(150_000); // 150 ms
        }
    }

    // ── Detay HTML'inden alanları çıkar ──────────────────────────────────
    private function parseDetail(string $html): array
    {
        $result = [];
        $map    = [
            'Belge No'     => 'belge_no',
            'Unvan'        => 'acente_unvani',
            'Ticari Unvan' => 'ticari_unvan',
            'Adres'        => 'adres',
            'İl-İlçe'      => 'il_ilce',
            'Telefon'      => 'telefon',
            'E-posta'      => 'eposta',
            'E-Posta'      => 'eposta',
        ];

        foreach ([
            '/<td[^>]*>\s*<b>\s*(.*?)\s*<\/b>\s*<\/td>\s*<td[^>]*>\s*(.*?)\s*<\/td>/is',
            '/<b>\s*(.*?)\s*<\/b>\s*:?\s*(.*?)(?=<b>|<\/tr>|<\/div>|$)/is',
        ] as $pattern) {
            preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $key = trim(strip_tags($m[1]));
                $val = trim(strip_tags($m[2]));
                if ($key && $val && strlen($key) < 50 && isset($map[$key]) && $map[$key]) {
                    $col = $map[$key];
                    if (empty($result[$col])) {
                        $result[$col] = $val;
                    }
                }
            }
        }

        return $result;
    }

    // ── DB'ye kaydet ─────────────────────────────────────────────────────
    private function saveRows(array $rows): int
    {
        $saved = 0;

        foreach ($rows as $row) {
            $internalId = $row['internal_id'] ?? null;
            $belgeNo    = trim($row['belge_no'] ?? '');
            $unvani     = trim($row['acente_unvani'] ?? '');

            if (!$unvani) continue;

            // Dedup 1: internal_id üzerinden
            if ($internalId) {
                $exists = DB::table('acenteler')
                    ->where('kaynak', self::KAYNAK)
                    ->where('internal_id', $internalId)
                    ->exists();
                if ($exists) continue;
            } else {
                // Dedup 2: belge_no + acente_unvani üzerinden
                if ($belgeNo) {
                    $exists = DB::table('acenteler')
                        ->where('belge_no', $belgeNo)
                        ->whereRaw('LOWER(TRIM(acente_unvani)) = ?', [mb_strtolower($unvani)])
                        ->exists();
                    if ($exists) continue;
                }
            }

            DB::table('acenteler')->insert([
                'belge_no'      => $belgeNo    ?: null,
                'sube_sira'     => 0,
                'is_sube'       => 0,
                'acente_unvani' => $unvani,
                'ticari_unvan'  => $row['ticari_unvan'] ?? null,
                'grup'          => $row['grup']         ?? null,
                'il'            => $row['il']           ?? null,
                'il_ilce'       => $row['il_ilce']      ?? null,
                'telefon'       => $row['telefon']      ?? null,
                'eposta'        => $row['eposta']       ?? null,
                'adres'         => $row['adres']        ?? null,
                'btk'           => null,
                'kaynak'        => self::KAYNAK,
                'internal_id'   => $internalId,
            ]);
            $saved++;
        }

        return $saved;
    }
}
