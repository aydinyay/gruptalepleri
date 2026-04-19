<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Kategorileri ekle (zaten varsa atla)
        $cats = [
            [
                'name'       => 'Boğaz Turları',
                'slug'       => 'bogaz-turlari',
                'icon'       => 'bi-water',
                'sort_order' => 10,
            ],
            [
                'name'       => 'Günübirlik Turlar',
                'slug'       => 'gunubirlik-turlar',
                'icon'       => 'bi-map-fill',
                'sort_order' => 20,
            ],
            [
                'name'       => 'Yat Kiralama',
                'slug'       => 'yat-kiralama',
                'icon'       => 'bi-tsunami',
                'sort_order' => 30,
            ],
        ];

        foreach ($cats as $cat) {
            $exists = DB::table('catalog_categories')->where('slug', $cat['slug'])->exists();
            if (! $exists) {
                DB::table('catalog_categories')->insert(array_merge($cat, [
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // Kategori id'lerini çek
        $bogaz      = DB::table('catalog_categories')->where('slug', 'bogaz-turlari')->value('id');
        $gunubirlik = DB::table('catalog_categories')->where('slug', 'gunubirlik-turlar')->value('id');
        $yat        = DB::table('catalog_categories')->where('slug', 'yat-kiralama')->value('id');

        // Boğaz turu ürünleri — title'da "boğaz" veya "bosphorus" veya "dinner cruise" geçenler
        if ($bogaz) {
            DB::table('catalog_items')
                ->where(function ($q) {
                    $q->whereRaw("LOWER(title) LIKE '%boğaz%'")
                      ->orWhereRaw("LOWER(title) LIKE '%bosphorus%'")
                      ->orWhereRaw("LOWER(title) LIKE '%dinner cruise%'")
                      ->orWhereRaw("LOWER(title) LIKE '%bogaz%'");
                })
                ->update(['category_id' => $bogaz]);
        }

        // Günübirlik tur ürünleri
        if ($gunubirlik) {
            DB::table('catalog_items')
                ->where(function ($q) {
                    $q->whereRaw("LOWER(title) LIKE '%günübirlik%'")
                      ->orWhereRaw("LOWER(title) LIKE '%gunubirlik%'")
                      ->orWhereRaw("LOWER(slug) LIKE '%gunubirlik%'");
                })
                ->update(['category_id' => $gunubirlik]);
        }

        // Yat kiralama ürünleri
        if ($yat) {
            DB::table('catalog_items')
                ->where(function ($q) {
                    $q->whereRaw("LOWER(title) LIKE '%yat%'")
                      ->orWhereRaw("LOWER(slug) LIKE '%yat%'")
                      ->orWhereRaw("LOWER(title) LIKE '%tekne%'");
                })
                ->update(['category_id' => $yat]);
        }
    }

    public function down(): void
    {
        DB::table('catalog_items')
            ->whereIn('category_id', function ($q) {
                $q->select('id')->from('catalog_categories')
                  ->whereIn('slug', ['bogaz-turlari', 'gunubirlik-turlar', 'yat-kiralama']);
            })
            ->update(['category_id' => null]);

        DB::table('catalog_categories')
            ->whereIn('slug', ['bogaz-turlari', 'gunubirlik-turlar', 'yat-kiralama'])
            ->delete();
    }
};
