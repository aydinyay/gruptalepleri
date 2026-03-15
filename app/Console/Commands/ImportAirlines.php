<?php

namespace App\Console\Commands;

use App\Models\Airline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAirlines extends Command
{
    protected $signature   = 'airlines:import {--file= : Yerel DAT/CSV dosya yolu}';
    protected $description = 'OpenFlights.org verilerinden havayollarını içe aktarır';

    private array $countryTr = [
        'Turkey' => 'Türkiye', 'Germany' => 'Almanya', 'France' => 'Fransa',
        'United Kingdom' => 'İngiltere', 'Italy' => 'İtalya', 'Spain' => 'İspanya',
        'United States' => 'Amerika', 'United Arab Emirates' => 'BAE',
        'Saudi Arabia' => 'Suudi Arabistan', 'Egypt' => 'Mısır',
        'Morocco' => 'Fas', 'Tunisia' => 'Tunus', 'Greece' => 'Yunanistan',
        'Netherlands' => 'Hollanda', 'Belgium' => 'Belçika', 'Austria' => 'Avusturya',
        'Switzerland' => 'İsviçre', 'Portugal' => 'Portekiz', 'Sweden' => 'İsveç',
        'Norway' => 'Norveç', 'Denmark' => 'Danimarka', 'Finland' => 'Finlandiya',
        'Poland' => 'Polonya', 'Czech Republic' => 'Çekya', 'Hungary' => 'Macaristan',
        'Romania' => 'Romanya', 'Bulgaria' => 'Bulgaristan', 'Croatia' => 'Hırvatistan',
        'Serbia' => 'Sırbistan', 'Bosnia and Herzegovina' => 'Bosna Hersek',
        'Albania' => 'Arnavutluk', 'Slovenia' => 'Slovenya', 'Slovakia' => 'Slovakya',
        'Russia' => 'Rusya', 'Ukraine' => 'Ukrayna', 'Georgia' => 'Gürcistan',
        'Armenia' => 'Ermenistan', 'Azerbaijan' => 'Azerbaycan',
        'Kazakhstan' => 'Kazakistan', 'Uzbekistan' => 'Özbekistan',
        'Iran' => 'İran', 'Iraq' => 'Irak', 'Syria' => 'Suriye',
        'Lebanon' => 'Lübnan', 'Jordan' => 'Ürdün', 'Israel' => 'İsrail',
        'Kuwait' => 'Kuveyt', 'Qatar' => 'Katar', 'Bahrain' => 'Bahreyn',
        'Oman' => 'Umman', 'Pakistan' => 'Pakistan', 'India' => 'Hindistan',
        'Thailand' => 'Tayland', 'Malaysia' => 'Malezya', 'Singapore' => 'Singapur',
        'Indonesia' => 'Endonezya', 'China' => 'Çin', 'Japan' => 'Japonya',
        'South Korea' => 'Güney Kore', 'Australia' => 'Avustralya',
        'Canada' => 'Kanada', 'Mexico' => 'Meksika', 'Brazil' => 'Brezilya',
        'Argentina' => 'Arjantin', 'South Africa' => 'Güney Afrika',
        'Kenya' => 'Kenya', 'Ethiopia' => 'Etiyopya', 'Cyprus' => 'Kıbrıs',
        'Malta' => 'Malta', 'Iceland' => 'İzlanda', 'Ireland' => 'İrlanda',
        'Luxembourg' => 'Lüksemburg', 'Libya' => 'Libya', 'Algeria' => 'Cezayir',
        'Sudan' => 'Sudan', 'Afghanistan' => 'Afganistan',
    ];

    public function handle(): int
    {
        $file = $this->option('file');

        if ($file && file_exists($file)) {
            $this->info("Yerel dosya kullanılıyor: {$file}");
            $raw = file_get_contents($file);
        } else {
            $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airlines.dat';
            $this->info('OpenFlights.org\'dan indiriliyor...');
            $raw = @file_get_contents($url);

            if (!$raw) {
                $this->error('İndirme başarısız. --file seçeneği ile DAT dosyasını belirtin.');
                $this->line('İndirme linki: https://raw.githubusercontent.com/jpatokal/openflights/master/data/airlines.dat');
                return 1;
            }
        }

        $this->info('Ayrıştırılıyor...');
        $lines = explode("\n", $raw);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Airline::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $batch   = [];
        $count   = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($lines));
        $bar->start();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { $bar->advance(); continue; }

            // OpenFlights format: ID,Name,Alias,IATA,ICAO,Callsign,Country,Active
            $row = str_getcsv($line);
            if (count($row) < 7) { $skipped++; $bar->advance(); continue; }

            $name    = trim($row[1] ?? '');
            $iata    = trim($row[3] ?? '');
            $icao    = trim($row[4] ?? '');
            $active  = trim($row[7] ?? 'N');

            // Geçersiz kayıtları atla
            if ($name === '' || $name === '\N') { $skipped++; $bar->advance(); continue; }
            if ($iata === '\N' || $iata === '-' || $iata === '--') $iata = '';
            if ($icao === '\N' || $icao === '-' || $icao === '--') $icao = '';
            // Uzun/geçersiz kodları temizle
            if (strlen($iata) > 4) $iata = '';
            if (strlen($icao) > 4) $icao = '';

            // En az bir kod olmalı
            if ($iata === '' && $icao === '') { $skipped++; $bar->advance(); continue; }

            $alias   = trim($row[2] ?? '');
            $country = trim($row[6] ?? '');

            $batch[] = [
                'iata'       => $iata !== '' ? strtoupper($iata) : null,
                'icao'       => $icao !== '' ? strtoupper($icao) : null,
                'name'       => $name,
                'alias'      => ($alias !== '' && $alias !== '\N') ? $alias : null,
                'callsign'   => ($row[5] ?? '') !== '\N' ? trim($row[5] ?? '') : null,
                'country'    => $country !== '\N' ? $country : null,
                'country_tr' => $this->countryTr[$country] ?? null,
                'active'     => $active === 'Y',
            ];
            $count++;

            if (count($batch) >= 500) {
                Airline::insert($batch);
                $batch = [];
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            Airline::insert($batch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$count} havayolu içe aktarıldı. ({$skipped} atlandı)");

        return 0;
    }
}
