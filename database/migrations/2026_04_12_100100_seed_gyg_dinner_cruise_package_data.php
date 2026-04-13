<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GYG — İstanbul: Türk Gecesi Gösterisi ile Boğazda Akşam Yemeği Gezisi
 * Ürün verilerini dinner_cruise paketlerine işle.
 * Mevcut standard/vip/premium kayıtları güncellenir.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        // Tablodaki sütunlar henüz eklenmemişse sessizce çık
        if (! Schema::hasColumn('leisure_package_templates', 'base_price_per_person')) {
            return;
        }

        // ── Ortak bilgiler ────────────────────────────────────────────────
        $commonDepartureTimes = [
            '18:30 Biniş / 19:00 Kalkış',
            '20:30 Biniş / 21:00 Kalkış',
        ];
        $commonPier = 'Kabataş İskelesi, D kapısı';
        $commonMeetingPoint = 'Kabataş İskelesi, D kapısı önünde rehber karşılama — kalkıştan 30 dk önce olunuz.';
        $commonDurationHours = 3.0;
        $commonRating = 4.6;
        $commonReviewCount = 2053;
        $commonCancellationPolicy = 'Hizmetten 24 saat öncesine kadar ücretsiz iptal. Sonrasında iade yapılmaz.';

        // ── STANDARD — Akşam Yemeği, Şov ve Alkolsüz İçecekler ──────────
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'standard')
            ->update([
                'name_tr'                  => 'Boğazda Türk Gecesi — Standart',
                'name_en'                  => 'Bosphorus Turkish Night — Standard',
                'summary_tr'               => 'Canlı Türk Gecesi şovu, 3 kurslu akşam yemeği ve alkolsüz içecekler eşliğinde 3 saatlik Boğaz turu.',
                'summary_en'               => '3-hour Bosphorus cruise with live Turkish Night show, 3-course dinner and soft drinks.',
                'badge_text'               => 'En Çok Tercih Edilen',
                'base_price_per_person'    => 850.00,
                'original_price_per_person'=> 1060.00,
                'currency'                 => 'TRY',
                'duration_hours'           => $commonDurationHours,
                'departure_times'          => json_encode($commonDepartureTimes),
                'pier_name'                => $commonPier,
                'meeting_point'            => $commonMeetingPoint,
                'max_pax'                  => 400,
                'rating'                   => $commonRating,
                'review_count'             => $commonReviewCount,
                'long_description_tr'      => "İstanbul'un eşsiz silueti eşliğinde 3 saatlik Boğaz yolculuğuna çıkın. Kabataş İskelesi'nden kalkan gemimizde Türk Gecesi gösterimizin keyfini çıkarın; oryantal dans, halk oyunları ve canlı müzik performansıyla dolu bir gece sizi bekliyor.\n\nAkşam yemeğiniz 3 kurs olarak servis edilir; ana yemek ve tatlıyla tamamlanan bu menü, alkolsüz içeceklerle (su, ayran, meşrubat) eşleştirilmiştir. Boğaz'daki köprülerin, sarayların ve yalıların altından geçerken İstanbul'un ışıltılı gece manzarasının tadını çıkarın.",
                'long_description_en'      => "Set sail on a 3-hour Bosphorus cruise against Istanbul's iconic skyline. Departing from Kabataş Pier, enjoy our Turkish Night Show filled with oriental dance, folk performances and live music.\n\nDinner is served in 3 courses paired with soft drinks (water, ayran, soft beverages). Sail beneath the Bosphorus bridges past palaces and waterfront mansions while taking in Istanbul's glittering night panorama.",
                'timeline_tr'              => json_encode([
                    ['time' => '18:30', 'event' => 'Kabataş İskelesi D kapısı — Karşılama ve biniş'],
                    ['time' => '19:00', 'event' => 'Gemi hareketi — Boğaz turu başlangıcı'],
                    ['time' => '19:30', 'event' => 'İlk kurs servisi — Çorba ve mezeler'],
                    ['time' => '20:00', 'event' => 'Türk Gecesi Şovu başlangıcı — Oryantal dans, halk oyunları'],
                    ['time' => '20:45', 'event' => 'Ana yemek ve tatlı servisi'],
                    ['time' => '21:30', 'event' => 'Kabataş İskelesi — Dönüş ve iniş'],
                ]),
                'includes_tr'              => json_encode([
                    '3 saatlik Boğaz turu',
                    'Canlı Türk Gecesi Şovu (oryantal dans, halk oyunları)',
                    '3 kurslu akşam yemeği (çorba, ana yemek, tatlı)',
                    'Alkolsüz içecekler (su, ayran, meşrubat)',
                    'Canlı müzik performansı',
                    'Gemi içi rehberlik',
                ]),
                'includes_en'              => json_encode([
                    '3-hour Bosphorus cruise',
                    'Live Turkish Night Show (oriental dance, folk performances)',
                    '3-course dinner (soup, main, dessert)',
                    'Soft drinks (water, ayran, soft beverages)',
                    'Live music performance',
                    'On-board guidance',
                ]),
                'excludes_tr'              => json_encode([
                    'Alkollü içecekler',
                    'Transfer hizmeti (opsiyonel eklenebilir)',
                    'Bahşiş',
                    'Ekstra içecekler ve atıştırmalıklar',
                    'Fotoğraf/video paketi',
                ]),
                'excludes_en'              => json_encode([
                    'Alcoholic beverages',
                    'Transfer service (optional add-on)',
                    'Gratuities',
                    'Extra drinks and snacks',
                    'Photo/video package',
                ]),
                'cancellation_policy_tr'   => $commonCancellationPolicy,
                'important_notes_tr'       => json_encode([
                    'Kalkıştan en az 30 dakika önce iskelede olunuz.',
                    'Çocuklar (2-12 yaş) indirimli fiyattan yararlanır.',
                    'Bebekler (0-2 yaş) ücretsizdir.',
                    'Vegetaryen menü talep üzerine sağlanır.',
                    'Hava koşullarına bağlı olarak rota değiştirilebilir.',
                    'Gemi içinde uygun kıyafet giyilmesi önerilir.',
                ]),
                'hero_image_url'           => 'https://images.pexels.com/photos/3411083/pexels-photo-3411083.jpeg?auto=compress&cs=tinysrgb&w=800',
                'sort_order'               => 10,
                'updated_at'               => now(),
            ]);

        // ── VIP — Akşam Yemeği, Şov ve Alkollü İçecekler ─────────────────
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'vip')
            ->update([
                'name_tr'                  => 'Boğazda Türk Gecesi — VIP',
                'name_en'                  => 'Bosphorus Turkish Night — VIP',
                'summary_tr'               => 'Sahneye yakın VIP masa, 3 kurslu yemek ve sınırsız yerli alkollü+alkolsüz içecekler dahil.',
                'summary_en'               => 'Front-row VIP table, 3-course dinner, unlimited local alcoholic & soft drinks included.',
                'badge_text'               => 'GT Sertifikalı',
                'base_price_per_person'    => 1150.00,
                'original_price_per_person'=> 1440.00,
                'currency'                 => 'TRY',
                'duration_hours'           => $commonDurationHours,
                'departure_times'          => json_encode($commonDepartureTimes),
                'pier_name'                => $commonPier,
                'meeting_point'            => $commonMeetingPoint,
                'max_pax'                  => 200,
                'rating'                   => $commonRating,
                'review_count'             => $commonReviewCount,
                'long_description_tr'      => "VIP paketimizle Türk Gecesi şovunu en iyi açıdan izleyin. Sahneye yakın rezerve masanız, sınırsız yerli alkollü içecekler (rakı, bira, şarap) ve alkolsüz seçeneklerle birlikte özel bir deneyim sunar.\n\nÜç kurslu VIP menünüz; taze deniz ürünleri, köfte ve baklava gibi Türk mutfağının seçkin lezzetlerini içerir. Canlı oryantal dans ve halk oyunları gösterisi sizi 3 saat boyunca eğlendirir.",
                'long_description_en'      => "Experience the Turkish Night show from the best seat in the house with our VIP package. Your reserved front-row table comes with unlimited local alcoholic drinks (raki, beer, wine) and soft beverages.\n\nThe 3-course VIP menu features highlights of Turkish cuisine including fresh seafood, meatballs and baklava. Live oriental dance and folk performances entertain you throughout the 3-hour cruise.",
                'timeline_tr'              => json_encode([
                    ['time' => '18:30', 'event' => 'VIP öncelikli biniş — Kabataş İskelesi D kapısı'],
                    ['time' => '19:00', 'event' => 'Gemi hareketi — İçecek servisi başlangıcı'],
                    ['time' => '19:15', 'event' => 'İlk kurs — Deniz ürünleri ve mezeler'],
                    ['time' => '19:45', 'event' => 'Türk Gecesi Şovu — Oryantal dans & halk oyunları'],
                    ['time' => '20:30', 'event' => 'VIP Ana yemek ve baklava servisi'],
                    ['time' => '21:00', 'event' => 'Serbest eğlence — Fotoğraf çekimi, dans'],
                    ['time' => '21:30', 'event' => 'Kabataş — Dönüş ve iniş'],
                ]),
                'includes_tr'              => json_encode([
                    '3 saatlik Boğaz turu',
                    'Canlı Türk Gecesi Şovu',
                    'VIP masa konumu (sahneye yakın, rezerve)',
                    '3 kurslu VIP akşam yemeği',
                    'Sınırsız yerli alkollü içecekler (rakı, bira, şarap)',
                    'Sınırsız alkolsüz içecekler',
                    'Canlı müzik performansı',
                    'Gemi içi rehberlik',
                ]),
                'includes_en'              => json_encode([
                    '3-hour Bosphorus cruise',
                    'Live Turkish Night Show',
                    'VIP table (front-row, reserved)',
                    '3-course VIP dinner',
                    'Unlimited local alcoholic drinks (raki, beer, wine)',
                    'Unlimited soft drinks',
                    'Live music performance',
                    'On-board guidance',
                ]),
                'excludes_tr'              => json_encode([
                    'İthal ve premium alkollü içkiler',
                    'Transfer hizmeti (opsiyonel eklenebilir)',
                    'Bahşiş',
                    'Özel masa süslemesi',
                    'Fotoğraf/video paketi',
                ]),
                'excludes_en'              => json_encode([
                    'Imported & premium spirits',
                    'Transfer service (optional add-on)',
                    'Gratuities',
                    'Custom table decoration',
                    'Photo/video package',
                ]),
                'cancellation_policy_tr'   => $commonCancellationPolicy,
                'important_notes_tr'       => json_encode([
                    'Kalkıştan en az 30 dakika önce iskelede olunuz.',
                    'VIP masa öncelikli biniş ile sağlanır.',
                    'Çocuklar (2-12 yaş) indirimli fiyattan yararlanır.',
                    'Bebekler (0-2 yaş) ücretsizdir.',
                    'Vegetaryen menü talep üzerine sağlanır.',
                ]),
                'hero_image_url'           => 'https://images.pexels.com/photos/1581384/pexels-photo-1581384.jpeg?auto=compress&cs=tinysrgb&w=800',
                'sort_order'               => 20,
                'updated_at'               => now(),
            ]);

        // ── PREMIUM — Akşam Yemeği, Şov, Sınırsız İçecek + Otelden Alınma
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'premium')
            ->update([
                'name_tr'                  => 'Boğazda Türk Gecesi — Premium + Transfer',
                'name_en'                  => 'Bosphorus Turkish Night — Premium + Transfer',
                'summary_tr'               => 'Pencere kenarı premium masa, açık büfe yemek, sınırsız içecek ve otelden alınma transfer dahil tam paket.',
                'summary_en'               => 'Window-side premium table, open buffet dinner, unlimited drinks and hotel pickup — all inclusive.',
                'badge_text'               => 'Yeni Etkinlik',
                'base_price_per_person'    => 1480.00,
                'original_price_per_person'=> 1850.00,
                'currency'                 => 'TRY',
                'duration_hours'           => $commonDurationHours,
                'departure_times'          => json_encode(['19:30 Biniş / 20:00 Kalkış']),
                'pier_name'                => $commonPier,
                'meeting_point'            => 'Otelinizden alınma ya da Kabataş İskelesi D kapısı önünde buluşma.',
                'max_pax'                  => 80,
                'rating'                   => $commonRating,
                'review_count'             => $commonReviewCount,
                'long_description_tr'      => "Boğaz deneyiminin zirvesi: Premium paketimiz ile otelinizden özel araçla alınarak Kabataş İskelesi'ne ulaşırsınız. Pencere kenarındaki premium masanızdan eşsiz Boğaz manzarasının keyfini çıkarırken açık büfe Türk mutfağı lezzetlerini ve sınırsız içeceğinizi (alkollü + alkolsüz) tüketebilirsiniz.\n\nCanlı Türk Gecesi Şovu; profesyonel oryantal dansçılar, halk oyunları ve özel müzik ekibiyle gece boyunca sürer. Gece sona erdiğinde aynı özel araçla otelinize bırakılırsınız (isteğe bağlı).",
                'long_description_en'      => "The ultimate Bosphorus experience: our Premium package includes hotel pickup by private vehicle to Kabataş Pier. Enjoy the Bosphorus panorama from your window-side premium table while savoring open buffet Turkish cuisine and unlimited drinks (alcoholic & non-alcoholic).\n\nThe live Turkish Night Show features professional oriental dancers, folk performances and a dedicated music ensemble throughout the evening. Optional hotel drop-off available at the end of the night.",
                'timeline_tr'              => json_encode([
                    ['time' => '18:30', 'event' => 'Otelden alınma — Özel araç ile Kabataş\'a transfer'],
                    ['time' => '19:30', 'event' => 'Premium öncelikli biniş — Kabataş İskelesi D kapısı'],
                    ['time' => '20:00', 'event' => 'Gemi hareketi — Karşılama kokteyliyle başlangıç'],
                    ['time' => '20:15', 'event' => 'Açık büfe akşam yemeği servisi başlangıcı'],
                    ['time' => '20:45', 'event' => 'Türk Gecesi Premium Şov'],
                    ['time' => '21:30', 'event' => 'Serbest eğlence & dans pisti'],
                    ['time' => '22:30', 'event' => 'Dönüş — İsteğe bağlı otele transfer'],
                ]),
                'includes_tr'              => json_encode([
                    '3 saatlik Boğaz turu',
                    'Canlı Türk Gecesi Premium Şovu',
                    'Pencere kenarı premium masa (rezerve)',
                    'Açık büfe akşam yemeği (deniz ürünleri dahil)',
                    'Sınırsız alkollü içecekler (rakı, viski, şarap, bira)',
                    'Sınırsız alkolsüz içecekler',
                    'Otelden alınma transfer (shuttle)',
                    'Karşılama kokteyliği',
                    'Gemi içi fotoğraf çekimi (1 adet baskılı kare)',
                ]),
                'includes_en'              => json_encode([
                    '3-hour Bosphorus cruise',
                    'Live Turkish Night Premium Show',
                    'Window-side premium table (reserved)',
                    'Open buffet dinner (including seafood)',
                    'Unlimited alcoholic drinks (raki, whisky, wine, beer)',
                    'Unlimited soft drinks',
                    'Hotel pickup transfer (shuttle)',
                    'Welcome cocktail',
                    'On-board photo (1 printed photo)',
                ]),
                'excludes_tr'              => json_encode([
                    'Premium ithal likör ve viski çeşitleri (ek ücretli)',
                    'Özel kişiye özel susleme',
                    'Profesyonel fotoğraf/video paketi',
                    'Bahşiş',
                ]),
                'excludes_en'              => json_encode([
                    'Premium imported spirits (extra charge)',
                    'Personalised decoration',
                    'Professional photo/video package',
                    'Gratuities',
                ]),
                'cancellation_policy_tr'   => $commonCancellationPolicy,
                'important_notes_tr'       => json_encode([
                    'Transfer için kesin otel adresini rezervasyondan sonra bildirin.',
                    'Kalkıştan en az 15 dakika önce otele aracınız gelir.',
                    'Çocuklar (2-12 yaş) indirimli fiyattan yararlanır.',
                    'Bebekler (0-2 yaş) ücretsizdir.',
                    'Vegetaryen ve vegan menü mevcuttur — önceden belirtin.',
                ]),
                'hero_image_url'           => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=800',
                'sort_order'               => 30,
                'updated_at'               => now(),
            ]);
    }

    public function down(): void
    {
        // Seed data geri alınamaz — mevcut kayıtlar korunur.
    }
};
