<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class B2cSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Kategoriler ────────────────────────────────────────────────
        $cats = [
            ['name'=>'Havalimanı Transferi', 'slug'=>'transfer',        'icon'=>'bi-car-front-fill',   'sort'=>1],
            ['name'=>'Özel Jet & Charter',   'slug'=>'ozel-jet',        'icon'=>'bi-airplane-fill',    'sort'=>2],
            ['name'=>'Helikopter',           'slug'=>'helikopter',      'icon'=>'bi-helicopter',       'sort'=>3],
            ['name'=>'Dinner Cruise',        'slug'=>'dinner-cruise',   'icon'=>'bi-water',            'sort'=>4],
            ['name'=>'Yat Kiralama',         'slug'=>'yat-kiralama',    'icon'=>'bi-tsunami',          'sort'=>5],
            ['name'=>'Yurt İçi Turlar',      'slug'=>'yurt-ici-turlar', 'icon'=>'bi-map-fill',         'sort'=>6],
            ['name'=>'Yurt Dışı Turlar',     'slug'=>'yurt-disi-turlar','icon'=>'bi-globe-americas',   'sort'=>7],
            ['name'=>'Vize Hizmetleri',      'slug'=>'vize',            'icon'=>'bi-passport',         'sort'=>8],
        ];

        $catIds = [];
        foreach ($cats as $cat) {
            // Zaten varsa güncelle, yoksa ekle
            DB::table('catalog_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                [
                    'parent_id'  => null,
                    'name'       => $cat['name'],
                    'icon'       => $cat['icon'],
                    'is_active'  => true,
                    'sort_order' => $cat['sort'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $catIds[$cat['slug']] = DB::table('catalog_categories')->where('slug', $cat['slug'])->value('id');
        }

        // ── Catalog Items ──────────────────────────────────────────────
        $items = [
            // Dinner Cruise
            [
                'category_slug'    => 'dinner-cruise',
                'product_type'     => 'leisure',
                'title'            => 'İstanbul: Türk Gecesi Gösterisi ile Boğazda Akşam Yemeği Gezisi',
                'short_desc'       => 'Eşsiz Boğaz manzarası eşliğinde Türk mutfağı ve canlı gösteri.',
                'pricing_type'     => 'fixed',
                'base_price'       => 1284,
                'currency'         => 'TRY',
                'destination_city' => 'İstanbul',
                'duration_hours'   => 3,
                'min_pax'          => 1,
                'max_pax'          => 500,
                'rating_avg'       => 4.6,
                'review_count'     => 2040,
                'is_featured'      => true,
            ],
            [
                'category_slug'    => 'dinner-cruise',
                'product_type'     => 'leisure',
                'title'            => 'İstanbul: Boğaz Gün Batımı Yat Turu & Açık Büfe',
                'short_desc'       => 'Gün batımında lüks yatla Boğaz turu ve açık büfe akşam yemeği.',
                'pricing_type'     => 'fixed',
                'base_price'       => 1850,
                'currency'         => 'TRY',
                'destination_city' => 'İstanbul',
                'duration_hours'   => 4,
                'min_pax'          => 2,
                'rating_avg'       => 4.8,
                'review_count'     => 876,
                'is_featured'      => true,
            ],
            // Yat Kiralama
            [
                'category_slug'    => 'yat-kiralama',
                'product_type'     => 'leisure',
                'title'            => 'Bodrum: Günlük Özel Yat Turu & Mavi Yolculuk',
                'short_desc'       => 'Bodrum koylarında özel yat ile unutulmaz bir gün.',
                'pricing_type'     => 'fixed',
                'base_price'       => 850,
                'currency'         => 'TRY',
                'destination_city' => 'Bodrum',
                'duration_hours'   => 8,
                'min_pax'          => 4,
                'max_pax'          => 12,
                'rating_avg'       => 4.9,
                'review_count'     => 523,
                'is_featured'      => true,
            ],
            [
                'category_slug'    => 'yat-kiralama',
                'product_type'     => 'leisure',
                'title'            => 'Göcek: 7 Gece 8 Gün Mavi Tur — Özel Gulet',
                'short_desc'       => 'Göcek\'ten Fethiye\'ye uzanan eşsiz mavi yolculuk.',
                'pricing_type'     => 'quote',
                'base_price'       => null,
                'currency'         => 'EUR',
                'destination_city' => 'Göcek',
                'duration_days'    => 8,
                'min_pax'          => 6,
                'max_pax'          => 16,
                'rating_avg'       => 4.7,
                'review_count'     => 91,
                'is_featured'      => false,
            ],
            // Charter
            [
                'category_slug'    => 'ozel-jet',
                'product_type'     => 'charter',
                'title'            => 'İstanbul - Antalya Özel Jet Kiralama',
                'short_desc'       => 'Hızlı, konforlu ve özel özel jet transferi.',
                'pricing_type'     => 'quote',
                'base_price'       => null,
                'currency'         => 'USD',
                'destination_city' => 'İstanbul',
                'duration_hours'   => 1,
                'min_pax'          => 1,
                'max_pax'          => 8,
                'rating_avg'       => 4.8,
                'review_count'     => 137,
                'is_featured'      => true,
            ],
            // Helikopter
            [
                'category_slug'    => 'helikopter',
                'product_type'     => 'charter',
                'title'            => 'İstanbul: Helikopterle Boğaz Panorama Turu',
                'short_desc'       => '15 dakikalık helikopter turu ile İstanbul\'u kuşbakışı görün.',
                'pricing_type'     => 'fixed',
                'base_price'       => 4200,
                'currency'         => 'TRY',
                'destination_city' => 'İstanbul',
                'duration_hours'   => 1,
                'min_pax'          => 1,
                'max_pax'          => 4,
                'rating_avg'       => 4.9,
                'review_count'     => 312,
                'is_featured'      => false,
            ],
            // Transfer
            [
                'category_slug'    => 'transfer',
                'product_type'     => 'transfer',
                'title'            => 'İstanbul Havalimanı (İST) — Taksim / Şişli Özel Transfer',
                'short_desc'       => 'Güvenli ve konforlu havalimanı karşılama & transfer.',
                'pricing_type'     => 'fixed',
                'base_price'       => 450,
                'currency'         => 'TRY',
                'destination_city' => 'İstanbul',
                'duration_hours'   => 1,
                'min_pax'          => 1,
                'max_pax'          => 7,
                'rating_avg'       => 4.5,
                'review_count'     => 1823,
                'is_featured'      => false,
            ],
            // Yurt İçi Tur
            [
                'category_slug'    => 'yurt-ici-turlar',
                'product_type'     => 'tour',
                'title'            => 'Kapadokya: Gün Doğarken Sıcak Hava Balonu Uçuşu',
                'short_desc'       => 'Kapadokya\'nın büyülü vadileri üzerinde şafak vaktinde balon turu.',
                'pricing_type'     => 'fixed',
                'base_price'       => 5351,
                'currency'         => 'TRY',
                'destination_city' => 'Nevşehir',
                'duration_hours'   => 3,
                'min_pax'          => 1,
                'max_pax'          => 20,
                'rating_avg'       => 5.0,
                'review_count'     => 5045,
                'is_featured'      => true,
            ],
            [
                'category_slug'    => 'yurt-ici-turlar',
                'product_type'     => 'tour',
                'title'            => 'Efes & Şirince: Tam Gün Tarihi Tur (Kuşadası\'ndan)',
                'short_desc'       => 'Antik Efes şehri ve büyüleyici Şirince köyünü keşfedin.',
                'pricing_type'     => 'fixed',
                'base_price'       => 980,
                'currency'         => 'TRY',
                'destination_city' => 'Kuşadası',
                'duration_hours'   => 8,
                'min_pax'          => 1,
                'rating_avg'       => 4.7,
                'review_count'     => 2341,
                'is_featured'      => false,
            ],
            // Yurt Dışı Tur
            [
                'category_slug'    => 'yurt-disi-turlar',
                'product_type'     => 'tour',
                'title'            => 'Dubai: 5 Gece 6 Gün Tam Paket — Burj Khalifa & Safari',
                'short_desc'       => 'Dubai\'nin simge mekanları, çöl safarisi ve lüks konaklama.',
                'pricing_type'     => 'fixed',
                'base_price'       => 18500,
                'currency'         => 'TRY',
                'destination_city' => 'Dubai',
                'duration_days'    => 6,
                'min_pax'          => 2,
                'rating_avg'       => 4.6,
                'review_count'     => 418,
                'is_featured'      => true,
            ],
        ];

        foreach ($items as $item) {
            $slug = Str::slug($item['title']);
            // Zaten varsa atla
            if (DB::table('catalog_items')->where('slug', $slug)->exists()) continue;

            DB::table('catalog_items')->insert([
                'category_id'      => $catIds[$item['category_slug']] ?? null,
                'owner_type'       => 'platform',
                'supplier_id'      => null,
                'product_type'     => $item['product_type'],
                'title'            => $item['title'],
                'slug'             => $slug,
                'short_desc'       => $item['short_desc'] ?? null,
                'full_desc'        => null,
                'cover_image'      => null,
                'pricing_type'     => $item['pricing_type'],
                'base_price'       => $item['base_price'] ?? null,
                'currency'         => $item['currency'] ?? 'TRY',
                'destination_city' => $item['destination_city'] ?? null,
                'destination_country' => $item['destination_country'] ?? 'Türkiye',
                'duration_days'    => $item['duration_days'] ?? null,
                'duration_hours'   => $item['duration_hours'] ?? null,
                'min_pax'          => $item['min_pax'] ?? 1,
                'max_pax'          => $item['max_pax'] ?? null,
                'is_active'        => true,
                'is_featured'      => $item['is_featured'] ?? false,
                'is_published'     => true,
                'published_at'     => now(),
                'sort_order'       => 0,
                'rating_avg'       => $item['rating_avg'] ?? 0,
                'review_count'     => $item['review_count'] ?? 0,
                'meta_title'       => $item['title'],
                'meta_description' => $item['short_desc'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('✓ ' . count($cats) . ' kategori, ' . count($items) . ' ürün eklendi.');
    }
}
