<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tüm dinner_cruise paketlerinin timeline_tr verisini event→title formatına çevirir.
 * code koşulu olmadan tüm paketleri günceller; hangi paket bağlı olursa olsun çalışır.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        $tr = json_encode([
            ['time' => '19:00', 'title' => 'Otelden Alınma',   'desc' => 'Servis aracı otelinizden sizi alarak Kabataş İskelesi\'ne götürür (opsiyonel eklenti)'],
            ['time' => '19:45', 'title' => 'Gemiye Biniş',     'desc' => 'Kabataş İskelesi\'nde rehber karşılaması ve masalara yerleşme'],
            ['time' => '20:00', 'title' => 'Gemi Hareketi',    'desc' => 'Boğaz turu başlıyor; hoş geldiniz içeceği servisi'],
            ['time' => '20:15', 'title' => 'İlk Kurs',         'desc' => 'Çorba, soğuk mezeler ve ekmek servisi'],
            ['time' => '20:45', 'title' => 'Türk Gecesi Şovu', 'desc' => 'Canlı müzik, Türk halk dansları ve oryantal dans gösterisi'],
            ['time' => '21:30', 'title' => 'Ana Yemek',        'desc' => 'Türk mutfağından ana yemek ve tatlı servisi'],
            ['time' => '22:00', 'title' => 'Serbest Eğlence',  'desc' => 'Dans pisti, canlı müzik ve fotoğraf çekimi'],
            ['time' => '23:00', 'title' => 'Gemi Dönüşü',      'desc' => 'Kabataş İskelesi\'ne yanaşma'],
            ['time' => '23:30', 'title' => 'Otele Transfer',   'desc' => 'Servis aracı ile otelinize dönüş (opsiyonel eklenti)'],
        ], JSON_UNESCAPED_UNICODE);

        $en = json_encode([
            ['time' => '19:00', 'title' => 'Hotel Pickup',       'desc' => 'Shuttle service picks you up from your hotel to Kabataş Pier (optional add-on)'],
            ['time' => '19:45', 'title' => 'Boarding',           'desc' => 'Welcome by guide at Kabataş Pier and find your seats on board'],
            ['time' => '20:00', 'title' => 'Departure',          'desc' => 'Bosphorus cruise begins; welcome drink service'],
            ['time' => '20:15', 'title' => 'First Course',       'desc' => 'Soup, cold starters and bread service'],
            ['time' => '20:45', 'title' => 'Turkish Night Show', 'desc' => 'Live music, Turkish folk dance and oriental dance performance'],
            ['time' => '21:30', 'title' => 'Main Course',        'desc' => 'Turkish cuisine main dish and dessert service'],
            ['time' => '22:00', 'title' => 'Free Time',          'desc' => 'Dance floor, live music and photo opportunities'],
            ['time' => '23:00', 'title' => 'Return',             'desc' => 'Docking back at Kabataş Pier'],
            ['time' => '23:30', 'title' => 'Hotel Drop-off',     'desc' => 'Shuttle service back to your hotel (optional add-on)'],
        ], JSON_UNESCAPED_UNICODE);

        // code koşulu yok — tüm dinner_cruise paketleri güncellenir
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->update([
                'timeline_tr' => $tr,
                'timeline_en' => $en,
                'updated_at'  => now(),
            ]);

        // Blade cache temizle
        Artisan::call('view:clear');
    }

    public function down(): void {}
};
