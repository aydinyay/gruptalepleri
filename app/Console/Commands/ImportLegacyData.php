<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Request as TalepModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
                            {--dry-run : Kaydetmeden sadece önizle}
                            {--limit=0 : Kaç satır işlenecek (0=tümü)}
                            {--offset=0 : Kaçıncı satırdan başla}
                            {--file= : CSV dosya yolu (varsayılan: storage/app/legacy_import.csv)}';

    protected $description = 'Eski sistemden export edilmiş CSV dosyasını yeni sisteme aktar';

    private array $statusMap = [
        '0' => 'beklemede',
        '1' => 'islemde',
        '2' => 'islemde',
        '3' => 'depozitoda',
        '4' => 'biletlendi',
        '5' => 'olumsuz',
        '6' => 'olumsuz',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit  = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $file   = $this->option('file') ?: storage_path('app/legacy_import.csv');

        if (!file_exists($file)) {
            $this->error("Dosya bulunamadı: {$file}");
            $this->line('→ phpMyAdmin\'den CSV export edip buraya koy: storage/app/legacy_import.csv');
            return 1;
        }

        $this->info($dryRun
            ? '--- DRY-RUN: hiçbir şey kaydedilmeyecek ---'
            : '--- GERÇEK AKTARIM başlıyor ---'
        );
        $this->info("Kaynak: {$file}");

        // CSV oku
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error('CSV dosyası açılamadı.');
            return 1;
        }

        // Encoding tespiti ve BOM temizleme
        $firstBytes = fread($handle, 3);
        // UTF-8 BOM varsa atla, yoksa geri sar
        if ($firstBytes !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Header satırı
        $headers = fgetcsv($handle, 0, ',');
        if (!$headers) {
            $this->error('CSV başlık satırı okunamadı.');
            fclose($handle);
            return 1;
        }
        // Başlıklardaki BOM ve boşlukları temizle
        $headers = array_map(fn($h) => trim(str_replace("\xEF\xBB\xBF", '', $h)), $headers);
        $this->info('Sütunlar: ' . implode(', ', $headers));

        // Tüm satırları oku
        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        $total = count($rows);
        $this->info("Toplam {$total} satır okundu.");

        if ($offset > 0) {
            $rows = array_slice($rows, $offset);
        }
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $imported  = 0;
        $skipped   = 0;
        $errors    = 0;
        $newUsers  = 0;
        $errorList = [];

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            try {
                $gtpnr = strtoupper(trim($row['gtpnr'] ?? ''));
                $email = strtolower(trim($row['email'] ?? ''));

                if (!$gtpnr || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                if (TalepModel::where('gtpnr', $gtpnr)->exists()) {
                    $skipped++;
                    continue;
                }

                $fromIata = $this->extractIata($row['gidiskalkishavalimani'] ?? '');
                $toIata   = $this->extractIata($row['gidisvarishavalimani']  ?? '');

                if (!$fromIata || !$toIata) {
                    $skipped++;
                    $errorList[] = "GTPNR {$gtpnr}: havalimanı eksik → atlandı";
                    continue;
                }

                if ($dryRun) {
                    $acenteAdi = $this->titleCase($this->sanitize($row['acentaadi'] ?? '') ?? '') ?? $email;
                    $this->newLine();
                    $this->line(sprintf(
                        '  ✓ %s | %s | %s | %s→%s | PAX:%s',
                        $gtpnr, $email, $acenteAdi,
                        $fromIata, $toIata,
                        $row['pax'] ?: $row['kisisayisi'] ?? '?'
                    ));
                    $imported++;
                    continue;
                }

                DB::transaction(function () use (
                    $row, $gtpnr, $email, $fromIata, $toIata,
                    &$imported, &$newUsers
                ) {
                    $acenteAdi = $this->titleCase($this->sanitize($row['acentaadi'] ?? '') ?? '') ?? $email;
                    $telefon   = $this->sanitizePhone($row['telefon'] ?? '');

                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name'              => $acenteAdi,
                            'password'          => Hash::make(Str::random(20)),
                            'role'              => 'acente',
                            'phone'             => $telefon ?: null,
                            'email_verified_at' => now(),
                        ]
                    );
                    if ($user->wasRecentlyCreated) $newUsers++;

                    $paxRaw    = $this->extractNumber($row['pax'] ?? '') ?: $this->extractNumber($row['kisisayisi'] ?? '') ?: 1;
                    $paxChild  = $this->extractNumber($row['cocuksayisi']  ?? 0);
                    $paxInfant = $this->extractNumber($row['bebeksayisi']  ?? 0);
                    $paxAdult  = max(0, $paxRaw - $paxChild - $paxInfant);

                    $status = $this->statusMap[strval($row['islemdurumu'] ?? '0')] ?? 'beklemede';

                    $donusKalkisIata = $this->extractIata($row['donuskalkishavalimani'] ?? '');
                    $donusVarisIata  = $this->extractIata($row['donusvarishavalimani']  ?? '');
                    $tripType        = ($donusKalkisIata && $donusVarisIata) ? 'round_trip' : 'one_way';

                    $hotelNeeded = $this->isTruthy($row['otel'] ?? '');
                    $visaNeeded  = $this->isTruthy($row['vize'] ?? '');
                    $createdAt   = $this->parseDateTime($row['islemintarihi'] ?? '') ?? now();

                    $talep = TalepModel::create([
                        'gtpnr'              => $gtpnr,
                        'user_id'            => $user->id,
                        'type'               => 'group_flight',
                        'status'             => $status,
                        'agency_name'        => $acenteAdi,
                        'phone'              => $telefon ?: null,
                        'email'              => $email,
                        'group_company_name' => $this->sanitize($row['grupfirmabilgisi'] ?? '') ?: null,
                        'flight_purpose'     => $this->sanitize($row['ucusamaci']        ?? '') ?: null,
                        'trip_type'          => $tripType,
                        'preferred_airline'  => $this->extractAirlineName($row['hangihavayolu'] ?? '') ?: null,
                        'hotel_needed'       => $hotelNeeded,
                        'visa_needed'        => $visaNeeded,
                        'pax_total'          => $paxRaw,
                        'pax_adult'          => $paxAdult,
                        'pax_child'          => $paxChild,
                        'pax_infant'         => $paxInfant,
                        'notes'              => $this->sanitize($row['notlar'] ?? '') ?: null,
                        'created_at'         => $createdAt,
                        'updated_at'         => $createdAt,
                    ]);

                    // Gidiş segmenti
                    $talep->segments()->create([
                        'order'          => 0,
                        'from_iata'      => $fromIata,
                        'from_city'      => $this->extractCity($row['gidiskalkishavalimani'] ?? ''),
                        'to_iata'        => $toIata,
                        'to_city'        => $this->extractCity($row['gidisvarishavalimani']  ?? ''),
                        'departure_date' => $this->parseDate($row['gidiszamani'] ?? ''),
                        'departure_time' => $this->parseTime($row['gidissaat1']  ?? '', $row['gidisdakika1'] ?? ''),
                    ]);

                    // Dönüş segmenti (varsa)
                    if ($donusKalkisIata && $donusVarisIata) {
                        $talep->segments()->create([
                            'order'          => 1,
                            'from_iata'      => $donusKalkisIata,
                            'from_city'      => $this->extractCity($row['donuskalkishavalimani'] ?? ''),
                            'to_iata'        => $donusVarisIata,
                            'to_city'        => $this->extractCity($row['donusvarishavalimani']  ?? ''),
                            'departure_date' => $this->parseDate($row['donuszamani'] ?? ''),
                            'departure_time' => $this->parseTime($row['donussaat1']  ?? '', $row['donusdakika1'] ?? ''),
                        ]);
                    }

                    // Offer
                    $cevapMetni    = $this->sanitize($row['cevapmetni']    ?? '');
                    $fiyat         = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $row['fiyat'] ?? '0')));
                    $currency      = strtoupper(trim($row['parabirimi']    ?? 'TRY')) ?: 'TRY';
                    $depRani       = floatval($row['depozitorani']   ?? 0);
                    $depTutari     = floatval($row['depozitotutari'] ?? 0);
                    $opsiyonTarihi = $this->parseDate($row['opsiyontarihi'] ?? '');
                    $opsiyonSaati  = $this->parseOpsiyonSaati($row['opsiyonsaati'] ?? '');

                    if ($cevapMetni || $fiyat > 0) {
                        $talep->offers()->create([
                            'offer_text'     => $cevapMetni ?: null,
                            'price_per_pax'  => $fiyat > 0 ? $fiyat : null,
                            'total_price'    => $fiyat > 0 ? $fiyat * max(1, $paxRaw) : null,
                            'currency'       => $currency,
                            'deposit_rate'   => $depRani   > 0 ? $depRani   : null,
                            'deposit_amount' => $depTutari > 0 ? $depTutari : null,
                            'option_date'    => $opsiyonTarihi,
                            'option_time'    => $opsiyonSaati,
                            'is_visible'     => true,
                            'created_at'     => $createdAt,
                            'updated_at'     => $createdAt,
                        ]);
                    }

                    $imported++;
                });

            } catch (\Exception $e) {
                $errors++;
                $errorList[] = "GTPNR {$gtpnr}: " . $e->getMessage();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['', 'Sayı'],
            [
                ['Aktarılan talep',           $imported],
                ['Atlanan (duplicate/eksik)',  $skipped],
                ['Hata',                       $errors],
                ['Oluşturulan kullanıcı',      $newUsers],
            ]
        );

        if ($errorList) {
            $this->newLine();
            $this->warn('Hata/uyarı detayları:');
            foreach (array_slice($errorList, 0, 30) as $err) {
                $this->line('  ' . $err);
            }
            if (count($errorList) > 30) {
                $this->line('  ... ve ' . (count($errorList) - 30) . ' hata daha');
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY-RUN tamamlandı. Gerçek import için --dry-run olmadan çalıştırın.');
        } else {
            $this->info('Import tamamlandı.');
        }

        return 0;
    }

    // ══════════════════════════════════════════════════════
    //  YARDIMCI METODLAR
    // ══════════════════════════════════════════════════════

    private function sanitize(?string $val): ?string
    {
        if ($val === null || $val === '') return null;
        $val = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $val) ?? $val;
        $val = str_replace(["\r\n", "\r"], "\n", $val);
        $val = preg_replace('/[ \t]+/', ' ', $val) ?? $val;
        if (class_exists('Normalizer')) {
            $n = \Normalizer::normalize($val, \Normalizer::NFC);
            if ($n !== false) $val = $n;
        }
        return trim($val) ?: null;
    }

    /**
     * Türkçe farkında title case.
     * Sadece TÜM BÜYÜK veya tüm küçük stringlere uygulanır.
     */
    private function titleCase(?string $str): ?string
    {
        if (!$str) return null;
        $str = trim($str);
        $isAllUpper = ($str === mb_strtoupper($str, 'UTF-8'));
        $isAllLower = ($str === mb_strtolower($str, 'UTF-8'));
        if (!$isAllUpper && !$isAllLower) return $str;

        $words = explode(' ', mb_strtolower($str, 'UTF-8'));
        $result = array_map(function (string $word): string {
            if ($word === '') return '';
            $first = mb_substr($word, 0, 1, 'UTF-8');
            $rest  = mb_substr($word, 1, null, 'UTF-8');
            $map   = ['i' => 'İ', 'ı' => 'I'];
            $first = $map[$first] ?? mb_strtoupper($first, 'UTF-8');
            return $first . $rest;
        }, $words);

        return implode(' ', $result);
    }

    private function sanitizePhone(?string $phone): string
    {
        if (!$phone) return '';
        $clean = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
        if (!$clean) return '';
        if (str_starts_with($clean, '+90')) return $clean;
        if (str_starts_with($clean, '90') && strlen($clean) === 12) return '0' . substr($clean, 2);
        if (str_starts_with($clean, '5')  && strlen($clean) === 10)  return '0' . $clean;
        return $clean;
    }

    private function extractNumber(mixed $val): int
    {
        if (!$val) return 0;
        preg_match('/\d+/', strval($val), $m);
        return (int) ($m[0] ?? 0);
    }

    private function isTruthy(mixed $val): bool
    {
        return in_array(
            strtolower(trim(strval($val ?? ''))),
            ['1', 'evet', 'yes', 'var', 'true', 'x'],
            true
        );
    }

    private function extractIata(string $str): string
    {
        $str  = trim($str);
        if (!$str) return '';
        $iata = strtoupper(trim(explode(',', $str)[0]));
        return preg_match('/^[A-Z]{3}$/', $iata) ? $iata : '';
    }

    private function extractCity(string $str): ?string
    {
        $parts = explode(',', $str);
        return isset($parts[2]) ? ($this->titleCase(trim($parts[2])) ?: null) : null;
    }

    private function extractAirlineName(string $str): string
    {
        $str = trim($str);
        if (!$str) return '';
        $parts = explode(',', $str);
        return count($parts) >= 3 ? trim($parts[2]) : $str;
    }

    private function parseDate(mixed $str): ?string
    {
        $str = trim(strval($str ?? ''));
        if (!$str || str_starts_with($str, '0000') || $str === 'NULL') return null;
        try { return Carbon::parse($str)->format('Y-m-d'); }
        catch (\Exception) { return null; }
    }

    private function parseDateTime(mixed $str): ?Carbon
    {
        $str = trim(strval($str ?? ''));
        if (!$str || str_starts_with($str, '0000') || $str === 'NULL') return null;
        try { return Carbon::parse($str); }
        catch (\Exception) { return null; }
    }

    private function parseTime(mixed $saat, mixed $dakika): ?string
    {
        $s = $this->extractNumber($saat);
        $d = $this->extractNumber($dakika);
        if ($s === 0 && $d === 0) return null;
        return sprintf('%02d:%02d', $s, $d);
    }

    private function parseOpsiyonSaati(mixed $val): ?string
    {
        $v = trim(strval($val ?? ''));
        if (!$v || $v === '00' || $v === '0' || $v === 'NULL') return null;
        $n = $this->extractNumber($v);
        return $n > 0 ? sprintf('%02d:00', $n) : null;
    }
}
