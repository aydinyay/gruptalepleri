<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BakanlikCsvImport extends Command
{
    protected $signature   = 'bakanlik:csv-import
                                {--file= : CSV dosya yolu (varsayılan: storage/app/import/acenteler.csv)}
                                {--truncate : Önce tabloyu sıfırla (varsayılan: true)}
                                {--no-truncate : Truncate yapmadan sadece updateOrCreate}';

    protected $description = 'Bakanlık CSV dosyasını acenteler tablosuna aktarır. Varsayılan olarak tabloyu önce temizler.';

    public function handle(): int
    {
        $file = $this->option('file')
            ?: storage_path('app/import/acenteler.csv');

        if (!file_exists($file)) {
            $this->error("Dosya bulunamadı: {$file}");
            $this->line("CSV dosyasını storage/app/import/acenteler.csv yoluna yükleyin.");
            return self::FAILURE;
        }

        $noTruncate = $this->option('no-truncate');

        if (!$noTruncate) {
            $this->warn('Acenteler tablosu temizleniyor (TRUNCATE)...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('acenteler')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Tablo temizlendi.');
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("CSV dosyası açılamadı: {$file}");
            return self::FAILURE;
        }

        // Delimiter otomatik tespit
        $firstLine = fgets($handle);
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $headers = fgetcsv($handle, 0, $delim);
        if (!$headers) {
            $this->error('CSV başlık satırı okunamadı.');
            fclose($handle);
            return self::FAILURE;
        }

        // BOM kalıntısı ve whitespace temizle
        $headers = array_map(fn($h) => trim(ltrim($h, "\xEF\xBB\xBF\xE2\x80\x8B")), $headers);
        $this->line('Delimiter: ' . ($delim === ';' ? 'noktalıvirgül' : 'virgül'));

        $this->line('Kolonlar: ' . implode(', ', $headers));

        $toplam     = 0;
        $yeni       = 0;
        $guncellenen = 0;
        $hatali     = 0;

        $isCli = php_sapi_name() === 'cli';
        $bar = null;
        if ($isCli) {
            $bar = $this->output->createProgressBar();
            $bar->start();
        }

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (count($row) < 2) continue;

            $normalized = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
            $data = array_combine($headers, $normalized);
            if ($data === false) { $hatali++; continue; }

            $belgeNo = trim($data['belgeNo'] ?? $data['Detay_BelgeNo'] ?? '');
            if (!$belgeNo) { $hatali++; continue; }

            // Unvan: Detay_Unvan öncelikli
            $unvan = trim($data['Detay_Unvan'] ?? '') ?: trim($data['unvan'] ?? '');
            $ticariUnvan = trim($data['Detay_TicariUnvan'] ?? '') ?: trim($data['ticariUnvan'] ?? '');

            // İl: _Il öncelikli (daha temiz), ilAd fallback
            $il    = trim($data['_Il'] ?? '') ?: trim($data['ilAd'] ?? '');
            $ilIlce = trim($data['Il_Ilce'] ?? '');

            // Telefon: Detay_Telefon öncelikli
            $telefon = trim($data['Detay_Telefon'] ?? '') ?: trim($data['telefon'] ?? '');

            $eposta  = trim($data['E-posta'] ?? '');
            $faks    = trim($data['Faks'] ?? '');
            $adres   = trim($data['Adres'] ?? $data['adres'] ?? '');
            $harita  = trim($data['Harita'] ?? '');
            $grup    = trim($data['grup'] ?? '');
            $durum   = trim($data['_Durum'] ?? '');
            $internalId = trim($data['internalId'] ?? '');

            $payload = [
                'acente_unvani' => $unvan,
                'ticari_unvan'  => $ticariUnvan,
                'grup'          => $grup,
                'il'            => $il,
                'il_ilce'       => $ilIlce,
                'telefon'       => $telefon,
                'eposta'        => $eposta,
                'faks'          => $faks ?: null,
                'adres'         => $adres ?: null,
                'harita'        => $harita ?: null,
                'internal_id'   => $internalId ?: null,
                'durum'         => $durum ?: null,
                'kaynak'        => 'bakanlik',
                'synced_at'     => now(),
            ];

            try {
                $existing = DB::table('acenteler')->where('belge_no', $belgeNo)->first();

                if ($existing) {
                    DB::table('acenteler')->where('belge_no', $belgeNo)->update($payload);
                    $guncellenen++;
                } else {
                    DB::table('acenteler')->insert(array_merge($payload, [
                        'belge_no'   => $belgeNo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $yeni++;
                }
                $toplam++;
            } catch (\Throwable $e) {
                $hatali++;
                $this->newLine();
                $this->warn("Hata ({$belgeNo}): " . $e->getMessage());
            }

            if ($bar) $bar->advance();
        }

        fclose($handle);
        if ($bar) $bar->finish();
        if ($isCli) $this->newLine(2);

        $this->info("Tamamlandı!");
        $this->table(
            ['Toplam', 'Yeni', 'Güncellenen', 'Hatalı'],
            [[$toplam, $yeni, $guncellenen, $hatali]]
        );

        return self::SUCCESS;
    }
}
