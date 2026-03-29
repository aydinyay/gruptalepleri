<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OzelGunlerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ozel_gunler')->truncate();

        $gunler = [
            // ── Site Lansmanı (tek seferlik, hemen) ─────────────────────────
            [
                'ad'                 => 'GrupTalepleri.com Yenilendi — Lansman',
                'kategori'           => 'platform',
                'tarih'              => now()->toDateString(),
                'tekrar'             => 'once',
                'aciklama'           => 'Türkiye\'nin ilk ve tek grup operasyon platformu yeni arayüzü ve özellikleriyle yayında.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 0,
                'aktif'              => true,
            ],

            // ── Bayramlar 2026 ───────────────────────────────────────────────
            [
                'ad'                 => 'Ramazan Bayramı 2026',
                'kategori'           => 'bayram',
                'tarih'              => '2026-03-20',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Ramazan Bayramı grup turları ve özel tur paketleri için erken rezervasyon zamanı.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Kurban Bayramı 2026',
                'kategori'           => 'bayram',
                'tarih'              => '2026-05-27',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Kurban Bayramı tatili için hac ve umre turları, yurt içi/dışı grup paketleri.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Yılbaşı 2027',
                'kategori'           => 'sezon',
                'tarih'              => '2027-01-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Yılbaşı grup turları ve özel charter uçuşları için planlama zamanı.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],

            // ── Resmi Tatiller ───────────────────────────────────────────────
            [
                'ad'                 => 'Cumhuriyet Bayramı',
                'kategori'           => 'resmi',
                'tarih'              => '2026-10-29',
                'tekrar'             => 'yearly',
                'aciklama'           => '29 Ekim Cumhuriyet Bayramı için yurt içi grup tur fırsatları.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 7,
                'aktif'              => true,
            ],
            [
                'ad'                 => '23 Nisan Ulusal Egemenlik',
                'kategori'           => 'resmi',
                'tarih'              => '2026-04-23',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Çocuk ve aile grupları için özel tur fırsatları.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 7,
                'aktif'              => true,
            ],

            // ── Turizm Festivalleri ──────────────────────────────────────────
            [
                'ad'                 => 'Alaçatı Ot Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-03-14',
                'tekrar'             => 'yearly',
                'aciklama'           => 'İzmir Alaçatı\'da her yıl Mart ayında düzenlenen gastronomi festivali. Grup rezervasyonları için erken planlama.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Cappadox Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-06-05',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Kapadokya\'da sanat, müzik ve doğa festivali. Grup charter uçuşları için ideal.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'İstanbul Film Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-04-10',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Uluslararası İstanbul Film Festivali döneminde şehir grubu turları.',
                'hizmet_baglantisi'  => 'transfer',
                'hatirlatma_gun'     => 14,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'EMITT Turizm Fuarı',
                'kategori'           => 'turizm',
                'tarih'              => '2027-01-22',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Doğu Akdeniz Uluslararası Turizm ve Seyahat Fuarı — sektörün en büyük buluşması.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 14,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Dünya Turizm Günü',
                'kategori'           => 'ulusal',
                'tarih'              => '2026-09-27',
                'tekrar'             => 'yearly',
                'aciklama'           => 'UNWTO Dünya Turizm Günü — sektör farkındalığı içerikleri için ideal.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 7,
                'aktif'              => true,
            ],

            // ── Sezonsal ─────────────────────────────────────────────────────
            [
                'ad'                 => 'Yaz Charter Sezonu Başlıyor',
                'kategori'           => 'sezon',
                'tarih'              => '2026-05-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Yaz sezonu charter uçuşları ve grup tur operasyonları başlıyor.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'İstanbul Boğaz ve Yat Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-06-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Boğaz sunset dinner cruise, yat kiralama ve leisure turları için sezon açılıyor.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 14,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Kayak Sezonu Başlıyor',
                'kategori'           => 'sezon',
                'tarih'              => '2026-12-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Uludağ, Palandöken, Kartalkaya kayak grup turları için rezervasyon zamanı.',
                'hizmet_baglantisi'  => 'transfer',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Okul Çıkışı Grup Turları',
                'kategori'           => 'sezon',
                'tarih'              => '2026-06-15',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Okul tatili başlıyor — aile ve okul grubu turları için yoğun sezon.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],

            // ── Özel İçerik Günleri ──────────────────────────────────────────
            [
                'ad'                 => 'Özel Jet ile Türkiye Turu',
                'kategori'           => 'turizm',
                'tarih'              => '2026-04-15',
                'tekrar'             => 'once',
                'aciklama'           => 'İstanbul-Antalya özel jet örneği: 9 kişi, kişi başı ~1.000€. Charter düşündüğünüzden ucuz olabilir.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 0,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Boğaz\'da Sunset Dinner Cruise',
                'kategori'           => 'turizm',
                'tarih'              => '2026-08-01',
                'tekrar'             => 'once',
                'aciklama'           => 'Ağustos\'ta İstanbul Boğazında gün batımı eşliğinde dinner cruise — grup rezervasyonları açık.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 14,
                'aktif'              => true,
            ],

            // ══════════════════════════════════════════════════════════════════
            // ULUSLARARASI ETKİNLİKLER — Avrupa & Dünya Turizmi
            // ══════════════════════════════════════════════════════════════════

            // ── Avrupa Kış / Yılbaşı ─────────────────────────────────────────
            [
                'ad'                 => 'Avrupa Noel Pazarları',
                'kategori'           => 'festival',
                'tarih'              => '2026-12-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Viyana, Prag, Strasbourg, Nuremberg Noel pazarları. Aralık\'ta Avrupa\'ya grup turları için en popüler sezon.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Venedik Karnavalı',
                'kategori'           => 'festival',
                'tarih'              => '2027-02-07',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dünyanın en ünlü karnavalı — Venedik. Maskelerin ve gondollerin şehrine kültür grubu turları için mükemmel.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Viyana Balo Sezonu',
                'kategori'           => 'festival',
                'tarih'              => '2027-01-08',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Ocak-Şubat Viyana balo sezonu: Opera Balosundan Filarmoni Balosuna. Kültür grubu turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],

            // ── Avrupa İlkbahar ───────────────────────────────────────────────
            [
                'ad'                 => 'Hollanda Lale Sezonu (Keukenhof)',
                'kategori'           => 'festival',
                'tarih'              => '2026-03-20',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Hollanda Keukenhof lale bahçeleri Mart-Mayıs açık. Avrupa\'nın en renkli baharı — grup tur talebi için ideal.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Cannes Film Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-05-13',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Cannes\'da Fransız Rivierası. Film festivali döneminde Güney Fransa grup turları ve yat kiralama.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Prag Bahar Müzik Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-05-12',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Prag\'ın en önemli klasik müzik festivali — kültür amaçlı Doğu Avrupa grup turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Monaco Grand Prix',
                'kategori'           => 'festival',
                'tarih'              => '2026-05-24',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Formula 1 Monaco Grand Prix — Côte d\'Azur\'da VIP grup turları ve yat kiralama için en prestijli etkinlik.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Dublin Aziz Patrick Günü',
                'kategori'           => 'festival',
                'tarih'              => '2026-03-17',
                'tekrar'             => 'yearly',
                'aciklama'           => 'İrlanda\'nın milli günü — Dublin\'de büyük kutlamalar. İrlanda kültür grubu turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],

            // ── Avrupa Yaz ───────────────────────────────────────────────────
            [
                'ad'                 => 'Edinburgh Fringe Festival',
                'kategori'           => 'festival',
                'tarih'              => '2026-08-07',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dünyanın en büyük sahne sanatları festivali — İskoçya. Ağustos\'ta İngiliz adaları kültür grubu turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Salzburg Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-07-18',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Avusturya Salzburg\'da Mozart\'ın şehri — dünya kalibresinde opera ve klasik müzik festivali. Kültür turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Yunanistan Yaz Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-06-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Santorini, Mykonos, Rodos, Korfu... Yunan adaları yaz sezonu. Grup yat turları ve charter uçuşları.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'İspanya San Fermín — Pamplona Koşusu',
                'kategori'           => 'festival',
                'tarih'              => '2026-07-07',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Pamplona boğa koşusu ve San Fermín festivali. İspanya kültür-macera grubu turları için ikonik etkinlik.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Amalfi Kıyısı ve İtalya Yaz Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-06-15',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Roma, Floransa, Amalfi, Capri, Sicilya... İtalya\'nın altın sezonu. Lüks grup turları ve yat kiralama.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],

            // ── Avrupa Sonbahar ───────────────────────────────────────────────
            [
                'ad'                 => 'Oktoberfest — Münih',
                'kategori'           => 'festival',
                'tarih'              => '2026-09-19',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dünyanın en büyük bira festivali — Münih. Her yıl 6 milyon ziyaretçi. Almanya grup turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Barselona La Mercè Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-09-24',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Barselona\'nın en büyük sokak festivali. Katalonya kültürü, ücretsiz konserler, insan kuleleri.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],

            // ── Avrupa Turizm Fuarları ────────────────────────────────────────
            [
                'ad'                 => 'ITB Berlin — Dünya Turizm Fuarı',
                'kategori'           => 'turizm',
                'tarih'              => '2027-03-04',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dünyanın en büyük turizm fuarı — Berlin. Seyahat sektörünün nabzını tutmak için acenteler için kritik.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'WTM London — Dünya Seyahat Pazarı',
                'kategori'           => 'turizm',
                'tarih'              => '2026-11-02',
                'tekrar'             => 'yearly',
                'aciklama'           => 'World Travel Market — Londra. Küresel turizm sektörünün buluşması. Acenteler için networking fırsatı.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Arabian Travel Market — Dubai',
                'kategori'           => 'turizm',
                'tarih'              => '2026-05-04',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Orta Doğu\'nun en büyük turizm fuarı — Dubai. Körfez pazarı ve Arap tur grupları için stratejik.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'FITUR Madrid — Turizm Fuarı',
                'kategori'           => 'turizm',
                'tarih'              => '2027-01-21',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Feria Internacional de Turismo — Madrid. İspanya ve Latin Amerika turizm pazarına açılım.',
                'hizmet_baglantisi'  => 'platform',
                'hatirlatma_gun'     => 21,
                'aktif'              => true,
            ],

            // ── Orta Doğu & Körfez ───────────────────────────────────────────
            [
                'ad'                 => 'Dubai Kış Turizm Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-10-15',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Ekim-Mart Dubai ve Körfez\'in altın sezonu. Grup turları, safari, lüks oteller — charter uçuşlar için yoğun dönem.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Dubai Expo Bölgesi & Turizm',
                'kategori'           => 'turizm',
                'tarih'              => '2026-10-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dubai World Trade Centre ve Expo bölgesi etkinlik yoğunluğu. Grup transferleri ve charter uçuşları için kritik dönem.',
                'hizmet_baglantisi'  => 'transfer',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],

            // ── Asya & Uzakdoğu ──────────────────────────────────────────────
            [
                'ad'                 => 'Japonya Kiraz Çiçeği (Sakura) Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-03-25',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Japonya\'nın en ikonik turizm sezonu — Tokyo, Kyoto, Osaka. Kültür grubu turları için rüya dönem. Erken rezervasyon şart.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Bali & Güneydoğu Asya Kış Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-11-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Bali, Tayland, Vietnam, Singapur — Kasım-Nisan ideal iklim. Lüks grup turları ve charter uçuşları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],

            // ── Dini Turlar ───────────────────────────────────────────────────
            [
                'ad'                 => 'Hac Sezonu 2026',
                'kategori'           => 'bayram',
                'tarih'              => '2026-06-05',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Mekke Hac sezonu. Hac ve umre grup turları için en kritik dönem — charter uçuşlar için rezervasyon çok erken açılır.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 90,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Kudüs & Kutsal Topraklar Paskalya',
                'kategori'           => 'festival',
                'tarih'              => '2026-04-05',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Hristiyan grupları için Kudüs, Beytüllahim, Nasıra turları — Paskalya döneminde talep zirve yapar.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Vatikan & Roma Paskalya',
                'kategori'           => 'festival',
                'tarih'              => '2026-04-05',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Papa ile Paskalya ayin ve kutlamaları — Vatikan. Avrupa kültür ve dini grup turları için özel zaman.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],

            // ── Amerika ───────────────────────────────────────────────────────
            [
                'ad'                 => 'Rio Karnavalı',
                'kategori'           => 'festival',
                'tarih'              => '2027-02-13',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Dünyanın en büyük karnavalı — Rio de Janeiro. Latin Amerika\'ya grup charter uçuşları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'New York Yılbaşı — Times Square',
                'kategori'           => 'festival',
                'tarih'              => '2026-12-31',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Times Square\'de yılbaşı gece yarısı kutlaması. ABD\'ye grup turları için en ikonik an.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],

            // ── Afrika & Doğa Turları ─────────────────────────────────────────
            [
                'ad'                 => 'Kenya & Tanzania Safari Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-07-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Büyük göç sezonu — Masai Mara ve Serengeti. Afrika safari grup turları için yıl\'ın en iyi dönemi.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 60,
                'aktif'              => true,
            ],

            // ── Akdeniz Cruise ────────────────────────────────────────────────
            [
                'ad'                 => 'Akdeniz Cruise Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-04-01',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Akdeniz cruise sezonu açılıyor — İstanbul, Atina, Roma, Barselona. Grup cruise rezervasyonları için ideal dönem.',
                'hizmet_baglantisi'  => 'leisure',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],

            // ── Hindistan ─────────────────────────────────────────────────────
            [
                'ad'                 => 'Holi Festivali — Hindistan',
                'kategori'           => 'festival',
                'tarih'              => '2026-03-03',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Hindistan\'ın renk festivali — Delhi, Jaipur, Mathura. Kültürel grup turları için eşsiz deneyim.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],
            [
                'ad'                 => 'Diwali — Işık Festivali',
                'kategori'           => 'festival',
                'tarih'              => '2026-10-20',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Hindistan Diwali ışık festivali. Jaipur, Varanasi, Mumbai\'de muhteşem kutlamalar — kültür grubu turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 30,
                'aktif'              => true,
            ],

            // ── İskandinav & Kuzey Avrupa ────────────────────────────────────
            [
                'ad'                 => 'Norveç Kuzey Işıkları Sezonu',
                'kategori'           => 'sezon',
                'tarih'              => '2026-11-15',
                'tekrar'             => 'yearly',
                'aciklama'           => 'Norveç ve İzlanda\'da Aurora Borealis (Kuzey Işıkları) sezonu. Skandinavya grup turları.',
                'hizmet_baglantisi'  => 'air_charter',
                'hatirlatma_gun'     => 45,
                'aktif'              => true,
            ],
        ];

        foreach ($gunler as $gun) {
            DB::table('ozel_gunler')->insert(array_merge($gun, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
