<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dinner Cruise paketlerine timeline_en kolonu ekler ve mevcut timeline_tr
 * verisini 'event' key yerine 'title'/'desc' formatına dönüştürür.
 * Standard (alkolsuz) paket için görsel kaynaktan türetilmiş saatli program eklenir.
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
                $table->text('timeline_en')->nullable()->after('timeline_tr')
                    ->comment('Program akışı İngilizce — JSON');
            });
        }

        // ── STANDARD (alkolsuz) ────────────────────────────────────────────
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'standard')
            ->update([
                'timeline_tr' => json_encode([
                    ['time' => '19:00', 'title' => 'Otelden Alınma',          'desc' => 'Servis aracı otelinizden sizi alarak Kabataş İskelesi\'ne götürür (opsiyonel eklenti)'],
                    ['time' => '19:45', 'title' => 'Gemiye Biniş',            'desc' => 'Kabataş İskelesi\'nde rehber karşılaması ve masalara yerleşme'],
                    ['time' => '20:00', 'title' => 'Gemi Hareketi',           'desc' => 'Boğaz turu başlıyor; hoş geldiniz içeceği servisi'],
                    ['time' => '20:15', 'title' => 'İlk Kurs',                'desc' => 'Çorba, soğuk mezeler ve ekmek servisi'],
                    ['time' => '20:45', 'title' => 'Türk Gecesi Şovu',        'desc' => 'Canlı müzik, Türk halk dansları ve oryantal dans gösterisi'],
                    ['time' => '21:30', 'title' => 'Ana Yemek',               'desc' => 'Türk mutfağından ana yemek ve tatlı servisi'],
                    ['time' => '22:00', 'title' => 'Serbest Eğlence',         'desc' => 'Dans pisti, canlı müzik ve fotoğraf çekimi'],
                    ['time' => '23:00', 'title' => 'Gemi Dönüşü',             'desc' => 'Kabataş İskelesi\'ne yanaşma'],
                    ['time' => '23:30', 'title' => 'Otele Transfer',          'desc' => 'Servis aracı ile otelinize dönüş (opsiyonel eklenti)'],
                ], JSON_UNESCAPED_UNICODE),
                'timeline_en' => json_encode([
                    ['time' => '19:00', 'title' => 'Hotel Pickup',            'desc' => 'Shuttle service picks you up from your hotel and drives to Kabataş Pier (optional add-on)'],
                    ['time' => '19:45', 'title' => 'Boarding',                'desc' => 'Welcome by guide at Kabataş Pier and find your seats on board'],
                    ['time' => '20:00', 'title' => 'Departure',               'desc' => 'Bosphorus cruise begins; welcome drink service'],
                    ['time' => '20:15', 'title' => 'First Course',            'desc' => 'Soup, cold starters and bread service'],
                    ['time' => '20:45', 'title' => 'Turkish Night Show',      'desc' => 'Live music, Turkish folk dance and oriental dance performance'],
                    ['time' => '21:30', 'title' => 'Main Course',             'desc' => 'Turkish cuisine main dish and dessert service'],
                    ['time' => '22:00', 'title' => 'Free Time',               'desc' => 'Dance floor, live music and photo opportunities'],
                    ['time' => '23:00', 'title' => 'Return',                  'desc' => 'Docking back at Kabataş Pier'],
                    ['time' => '23:30', 'title' => 'Hotel Drop-off',          'desc' => 'Shuttle service back to your hotel (optional add-on)'],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        // ── VIP — format düzelt (event → title) ───────────────────────────
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'vip')
            ->update([
                'timeline_tr' => json_encode([
                    ['time' => '18:30', 'title' => 'VIP Öncelikli Biniş',     'desc' => 'Kabataş İskelesi D kapısı — VIP öncelikli karşılama'],
                    ['time' => '19:00', 'title' => 'Gemi Hareketi',           'desc' => 'Boğaz turuna başlangıç; içecek servisi açılıyor'],
                    ['time' => '19:15', 'title' => 'İlk Kurs',                'desc' => 'Deniz ürünleri ve seçkin mezeler'],
                    ['time' => '19:45', 'title' => 'Türk Gecesi Şovu',        'desc' => 'Oryantal dans, halk oyunları ve canlı müzik'],
                    ['time' => '20:30', 'title' => 'VIP Ana Yemek',           'desc' => 'Ana yemek ve baklava servisi; sınırsız içecek devam ediyor'],
                    ['time' => '21:00', 'title' => 'Serbest Eğlence',         'desc' => 'Fotoğraf çekimi ve dans pisti'],
                    ['time' => '21:30', 'title' => 'Dönüş ve İniş',          'desc' => 'Kabataş İskelesi\'ne yanaşma'],
                ], JSON_UNESCAPED_UNICODE),
                'timeline_en' => json_encode([
                    ['time' => '18:30', 'title' => 'VIP Priority Boarding',   'desc' => 'Kabataş Pier gate D — priority welcome and seating'],
                    ['time' => '19:00', 'title' => 'Departure',               'desc' => 'Bosphorus cruise begins; drink service opens'],
                    ['time' => '19:15', 'title' => 'First Course',            'desc' => 'Seafood and premium cold starters'],
                    ['time' => '19:45', 'title' => 'Turkish Night Show',      'desc' => 'Oriental dance, folk performances and live music'],
                    ['time' => '20:30', 'title' => 'VIP Main Course',         'desc' => 'Main dish and baklava service; unlimited drinks continue'],
                    ['time' => '21:00', 'title' => 'Free Time',               'desc' => 'Photo opportunities and dance floor'],
                    ['time' => '21:30', 'title' => 'Return & Disembark',      'desc' => 'Docking at Kabataş Pier'],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        // ── PREMIUM — format düzelt (event → title) ───────────────────────
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'premium')
            ->update([
                'timeline_tr' => json_encode([
                    ['time' => '18:30', 'title' => 'Otelden Alınma',          'desc' => 'Özel araç ile Kabataş İskelesi\'ne transfer'],
                    ['time' => '19:30', 'title' => 'Premium Biniş',           'desc' => 'Kabataş İskelesi D kapısı — öncelikli VIP karşılama'],
                    ['time' => '20:00', 'title' => 'Gemi Hareketi',           'desc' => 'Karşılama kokteyliği eşliğinde Boğaz turuna başlangıç'],
                    ['time' => '20:15', 'title' => 'Açık Büfe Başlangıcı',   'desc' => 'Türk mutfağından açık büfe yemek servisi açılıyor'],
                    ['time' => '20:45', 'title' => 'Premium Türk Gecesi Şovu','desc' => 'Profesyonel oryantal dans, halk oyunları ve özel müzik ekibi'],
                    ['time' => '21:30', 'title' => 'Serbest Eğlence',         'desc' => 'Dans pisti ve fotoğraf çekimi'],
                    ['time' => '22:30', 'title' => 'Dönüş',                  'desc' => 'Kabataş İskelesi\'ne yanaşma; isteğe bağlı otele transfer'],
                ], JSON_UNESCAPED_UNICODE),
                'timeline_en' => json_encode([
                    ['time' => '18:30', 'title' => 'Hotel Pickup',            'desc' => 'Private vehicle transfer to Kabataş Pier'],
                    ['time' => '19:30', 'title' => 'Premium Boarding',        'desc' => 'Kabataş Pier gate D — priority VIP welcome'],
                    ['time' => '20:00', 'title' => 'Departure',               'desc' => 'Bosphorus cruise begins with welcome cocktail'],
                    ['time' => '20:15', 'title' => 'Open Buffet Begins',      'desc' => 'Turkish cuisine open buffet service opens'],
                    ['time' => '20:45', 'title' => 'Premium Turkish Night Show', 'desc' => 'Professional oriental dance, folk performances and live music ensemble'],
                    ['time' => '21:30', 'title' => 'Free Time',               'desc' => 'Dance floor and photo opportunities'],
                    ['time' => '22:30', 'title' => 'Return',                  'desc' => 'Docking at Kabataş Pier; optional hotel drop-off'],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // timeline_en kolonunu kaldır
        if (Schema::hasColumn('leisure_package_templates', 'timeline_en')) {
            Schema::table('leisure_package_templates', function (Blueprint $table): void {
                $table->dropColumn('timeline_en');
            });
        }
    }
};
