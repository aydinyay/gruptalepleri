<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dinner Cruise timeline_tr verisini 'event' key'den 'title'/'desc' formatına düzeltir.
 * timeline_en kolonunu ekler ve tüm paketleri TR + EN ile günceller.
 * Önceki migration (150000) kolon ekleme hatası nedeniyle güncellemeyi atlayabilir —
 * bu migration bağımsız olarak çalışır.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        // timeline_en kolonu yoksa ekle
        if (! Schema::hasColumn('leisure_package_templates', 'timeline_en')) {
            Schema::table('leisure_package_templates', function (Blueprint $table): void {
                $table->text('timeline_en')->nullable();
            });
        }

        $standardTr = json_encode([
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

        $standardEn = json_encode([
            ['time' => '19:00', 'title' => 'Hotel Pickup',        'desc' => 'Shuttle service picks you up from your hotel to Kabataş Pier (optional add-on)'],
            ['time' => '19:45', 'title' => 'Boarding',            'desc' => 'Welcome by guide at Kabataş Pier and find your seats on board'],
            ['time' => '20:00', 'title' => 'Departure',           'desc' => 'Bosphorus cruise begins; welcome drink service'],
            ['time' => '20:15', 'title' => 'First Course',        'desc' => 'Soup, cold starters and bread service'],
            ['time' => '20:45', 'title' => 'Turkish Night Show',  'desc' => 'Live music, Turkish folk dance and oriental dance performance'],
            ['time' => '21:30', 'title' => 'Main Course',         'desc' => 'Turkish cuisine main dish and dessert service'],
            ['time' => '22:00', 'title' => 'Free Time',           'desc' => 'Dance floor, live music and photo opportunities'],
            ['time' => '23:00', 'title' => 'Return',              'desc' => 'Docking back at Kabataş Pier'],
            ['time' => '23:30', 'title' => 'Hotel Drop-off',      'desc' => 'Shuttle service back to your hotel (optional add-on)'],
        ], JSON_UNESCAPED_UNICODE);

        $vipTr = json_encode([
            ['time' => '19:00', 'title' => 'Otelden Alınma',   'desc' => 'Servis aracı otelinizden sizi alarak Kabataş İskelesi\'ne götürür (opsiyonel eklenti)'],
            ['time' => '19:45', 'title' => 'VIP Gemiye Biniş', 'desc' => 'Kabataş İskelesi D kapısı — VIP öncelikli karşılama ve masalara yerleşme'],
            ['time' => '20:00', 'title' => 'Gemi Hareketi',    'desc' => 'Boğaz turu başlıyor; sınırsız alkollü/alkolsüz içecek servisi açılıyor'],
            ['time' => '20:15', 'title' => 'İlk Kurs',         'desc' => 'Çorba, soğuk mezeler ve ekmek servisi'],
            ['time' => '20:45', 'title' => 'Türk Gecesi Şovu', 'desc' => 'Canlı müzik, Türk halk dansları ve oryantal dans gösterisi'],
            ['time' => '21:30', 'title' => 'Ana Yemek',        'desc' => 'Türk mutfağından ana yemek ve tatlı; sınırsız içecek devam ediyor'],
            ['time' => '22:00', 'title' => 'Serbest Eğlence',  'desc' => 'Dans pisti, canlı müzik ve fotoğraf çekimi'],
            ['time' => '23:00', 'title' => 'Gemi Dönüşü',      'desc' => 'Kabataş İskelesi\'ne yanaşma'],
            ['time' => '23:30', 'title' => 'Otele Transfer',   'desc' => 'Servis aracı ile otelinize dönüş (opsiyonel eklenti)'],
        ], JSON_UNESCAPED_UNICODE);

        $vipEn = json_encode([
            ['time' => '19:00', 'title' => 'Hotel Pickup',        'desc' => 'Shuttle service picks you up from your hotel to Kabataş Pier (optional add-on)'],
            ['time' => '19:45', 'title' => 'VIP Boarding',        'desc' => 'Kabataş Pier gate D — VIP priority welcome and seating'],
            ['time' => '20:00', 'title' => 'Departure',           'desc' => 'Bosphorus cruise begins; unlimited alcoholic & soft drink service opens'],
            ['time' => '20:15', 'title' => 'First Course',        'desc' => 'Soup, cold starters and bread service'],
            ['time' => '20:45', 'title' => 'Turkish Night Show',  'desc' => 'Live music, Turkish folk dance and oriental dance performance'],
            ['time' => '21:30', 'title' => 'Main Course',         'desc' => 'Turkish cuisine main dish and dessert; unlimited drinks continue'],
            ['time' => '22:00', 'title' => 'Free Time',           'desc' => 'Dance floor, live music and photo opportunities'],
            ['time' => '23:00', 'title' => 'Return',              'desc' => 'Docking back at Kabataş Pier'],
            ['time' => '23:30', 'title' => 'Hotel Drop-off',      'desc' => 'Shuttle service back to your hotel (optional add-on)'],
        ], JSON_UNESCAPED_UNICODE);

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'standard')
            ->update(['timeline_tr' => $standardTr, 'timeline_en' => $standardEn, 'updated_at' => now()]);

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'vip')
            ->update(['timeline_tr' => $vipTr, 'timeline_en' => $vipEn, 'updated_at' => now()]);
    }

    public function down(): void {}
};
