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
        ];

        foreach ($gunler as $gun) {
            DB::table('ozel_gunler')->insert(array_merge($gun, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
