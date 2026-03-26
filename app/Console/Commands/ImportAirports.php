<?php

namespace App\Console\Commands;

use App\Models\Airport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAirports extends Command
{
    protected $signature   = 'airports:import {--file= : Yerel CSV dosya yolu (yoksa internetten indirilir)}';
    protected $description = 'OurAirports.com verilerinden havalimanlarını içe aktarır';

    // Ülke kodlarının Türkçe karşılıkları
    private array $countryTr = [
        'TR' => 'Türkiye',    'DE' => 'Almanya',    'FR' => 'Fransa',
        'GB' => 'İngiltere',  'IT' => 'İtalya',     'ES' => 'İspanya',
        'US' => 'Amerika',    'AE' => 'BAE',         'SA' => 'Suudi Arabistan',
        'EG' => 'Mısır',      'MA' => 'Fas',         'TN' => 'Tunus',
        'GR' => 'Yunanistan', 'NL' => 'Hollanda',   'BE' => 'Belçika',
        'AT' => 'Avusturya',  'CH' => 'İsviçre',    'PT' => 'Portekiz',
        'SE' => 'İsveç',      'NO' => 'Norveç',     'DK' => 'Danimarka',
        'FI' => 'Finlandiya', 'PL' => 'Polonya',    'CZ' => 'Çekya',
        'HU' => 'Macaristan', 'RO' => 'Romanya',    'BG' => 'Bulgaristan',
        'HR' => 'Hırvatistan','RS' => 'Sırbistan',  'BA' => 'Bosna Hersek',
        'AL' => 'Arnavutluk', 'MK' => 'Kuzey Makedonya', 'ME' => 'Karadağ',
        'SI' => 'Slovenya',   'SK' => 'Slovakya',   'LT' => 'Litvanya',
        'LV' => 'Letonya',    'EE' => 'Estonya',    'RU' => 'Rusya',
        'UA' => 'Ukrayna',    'BY' => 'Belarus',     'MD' => 'Moldova',
        'GE' => 'Gürcistan',  'AM' => 'Ermenistan', 'AZ' => 'Azerbaycan',
        'KZ' => 'Kazakistan', 'UZ' => 'Özbekistan', 'TM' => 'Türkmenistan',
        'KG' => 'Kırgızistan','TJ' => 'Tacikistan', 'AF' => 'Afganistan',
        'IR' => 'İran',       'IQ' => 'Irak',       'SY' => 'Suriye',
        'LB' => 'Lübnan',     'JO' => 'Ürdün',      'IL' => 'İsrail',
        'KW' => 'Kuveyt',     'QA' => 'Katar',       'BH' => 'Bahreyn',
        'OM' => 'Umman',      'YE' => 'Yemen',       'PK' => 'Pakistan',
        'IN' => 'Hindistan',  'BD' => 'Bangladeş',  'LK' => 'Sri Lanka',
        'MM' => 'Myanmar',    'TH' => 'Tayland',     'MY' => 'Malezya',
        'SG' => 'Singapur',   'ID' => 'Endonezya',  'PH' => 'Filipinler',
        'VN' => 'Vietnam',    'KH' => 'Kamboçya',   'CN' => 'Çin',
        'JP' => 'Japonya',    'KR' => 'Güney Kore', 'TW' => 'Tayvan',
        'HK' => 'Hong Kong',  'MN' => 'Moğolistan', 'AU' => 'Avustralya',
        'NZ' => 'Yeni Zelanda','CA' => 'Kanada',    'MX' => 'Meksika',
        'BR' => 'Brezilya',   'AR' => 'Arjantin',   'CL' => 'Şili',
        'CO' => 'Kolombiya',  'PE' => 'Peru',        'ZA' => 'Güney Afrika',
        'NG' => 'Nijerya',    'KE' => 'Kenya',       'ET' => 'Etiyopya',
        'GH' => 'Gana',       'TZ' => 'Tanzanya',   'UG' => 'Uganda',
        'DZ' => 'Cezayir',    'LY' => 'Libya',       'SD' => 'Sudan',
        'CY' => 'Kıbrıs',     'MT' => 'Malta',       'IS' => 'İzlanda',
        'LU' => 'Lüksemburg', 'IE' => 'İrlanda',    'MV' => 'Maldivler',
        'NP' => 'Nepal',      'BT' => 'Butan',       'CU' => 'Küba',
        'DO' => 'Dominik Cumhuriyeti', 'JM' => 'Jamaika', 'TT' => 'Trinidad',
        'UA' => 'Ukrayna',    'AX' => 'Aland Adaları',
    ];

    public function handle(): int
    {
        $file = $this->option('file');

        if ($file && file_exists($file)) {
            $this->info("Yerel dosya kullanılıyor: {$file}");
            $csv = file_get_contents($file);
        } else {
            $url = 'https://davidmegginson.github.io/ourairports-data/airports.csv';
            $this->info('OurAirports.com\'dan indiriliyor...');
            $csv = @file_get_contents($url);

            if (!$csv) {
                $this->error('İndirme başarısız. İnternet bağlantınızı kontrol edin veya --file seçeneği ile CSV dosyasını belirtin.');
                $this->line('Alternatif: https://davidmegginson.github.io/ourairports-data/airports.csv adresinden indirip php artisan airports:import --file=airports.csv çalıştırın.');
                return 1;
            }
        }

        $this->info('CSV ayrıştırılıyor...');
        $lines  = explode("\n", $csv);
        $header = str_getcsv(array_shift($lines));

        // Sütun indeksleri
        $col = array_flip($header);
        // OurAirports sütunları: id,ident,type,name,latitude_deg,longitude_deg,...,iata_code,...
        $idxIdent    = $col['ident']       ?? null;
        $idxType     = $col['type']        ?? null;
        $idxName     = $col['name']        ?? null;
        $idxLat      = $col['latitude_deg']  ?? null;
        $idxLon      = $col['longitude_deg'] ?? null;
        $idxCountry  = $col['iso_country'] ?? null;
        $idxMunic    = $col['municipality']  ?? null;
        $idxIata     = $col['iata_code']   ?? null;
        $idxGps      = $col['gps_code']    ?? null;  // ICAO

        if ($idxIata === null) {
            $this->error('CSV formatı beklenmiyor. Sütun adları uyumsuz.');
            return 1;
        }

        // Atlanacak tipler
        $skip = ['heliport', 'seaplane_base', 'balloonport', 'closed'];

        $batch   = [];
        $count   = 0;
        $skipped = 0;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Airport::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $bar = $this->output->createProgressBar(count($lines));
        $bar->start();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { $bar->advance(); continue; }

            $row  = str_getcsv($line);
            $iata = trim($row[$idxIata] ?? '');
            $type = trim($row[$idxType] ?? '');

            if ($iata === '' || in_array($type, $skip)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $countryCode = trim($row[$idxCountry] ?? '');
            $icao        = trim($row[$idxGps] ?? '');

            $batch[] = [
                'iata'         => strtoupper($iata),
                'icao'         => $icao !== '' ? strtoupper($icao) : null,
                'name'         => trim($row[$idxName] ?? ''),
                'city'         => trim($row[$idxMunic] ?? '') ?: null,
                'country'      => $countryCode,  // Önce kod, sonra isimle değiştireceğiz
                'country_tr'   => $this->countryTr[$countryCode] ?? null,
                'country_code' => $countryCode,
                'type'         => $type,
                'latitude'     => is_numeric($row[$idxLat] ?? '') ? (float)$row[$idxLat] : null,
                'longitude'    => is_numeric($row[$idxLon] ?? '') ? (float)$row[$idxLon] : null,
            ];
            $count++;

            if (count($batch) >= 500) {
                Airport::insert($batch);
                $batch = [];
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            Airport::insert($batch);
        }

        $bar->finish();
        $this->newLine();

        // country_code bilgisinden İngilizce ülke adlarını doldur (opsiyonel iyileştirme)
        // Şimdilik ISO kodu olarak bırakıyoruz, ihtiyaç duyulursa bir countries tablosu eklenebilir.

        $this->info("✓ {$count} havalimanı içe aktarıldı. ({$skipped} atlandı)");

        return 0;
    }
}
