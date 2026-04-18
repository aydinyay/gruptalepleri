<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('catalog_categories')->upsert([
            [
                'name'             => 'Etkinlikler & Deneyimler',
                'slug'             => 'etkinlikler-deneyimler',
                'description'      => 'Viski tadımı, workshop, konser, müze turu ve diğer özel deneyim etkinlikleri.',
                'icon'             => 'bi-stars',
                'cover_image'      => null,
                'parent_id'        => null,
                'is_active'        => 1,
                'sort_order'       => 80,
                'meta_title'       => 'Etkinlikler & Deneyimler | gruprezervasyonlari.com',
                'meta_description' => 'Viski tadımı, workshop, özel deneyim etkinlikleri ve daha fazlası.',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ], ['slug'], ['name', 'description', 'icon', 'is_active', 'sort_order', 'updated_at']);
    }
}
