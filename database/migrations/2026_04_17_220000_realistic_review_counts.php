<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog items ve leisure package templates üzerindeki review_count
 * değerlerini yeni site için gerçekçi aralığa (60–110) çeker.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── Catalog items (20 ürün) ────────────────────────────────
        $counts = [
            1  => 87,   // İstanbul transfer
            2  => 74,   // Antalya transfer
            3  => 63,   // İzmir transfer
            4  => 91,   // Trabzon transfer
            5  => 68,   // Jet İstanbul-Paris
            6  => 71,   // Jet Bodrum-Milano
            7  => 83,   // Helikopter
            8  => 96,   // Dinner cruise
            9  => 78,   // Motoryat Bodrum
            10 => 65,   // Gulet Çeşme
            11 => 108,  // Fethiye tekne
            12 => 92,   // Kapadokya turu
            13 => 84,   // Pamukkale & Efes
            14 => 103,  // Antalya rafting
            15 => 72,   // Balkanlar turu
            16 => 69,   // Kapadokya otel
            17 => 88,   // İstanbul otel
            18 => 97,   // Schengen vize
            19 => 76,   // Turistik vize
            20 => 85,   // Kurumsal etkinlik
        ];

        foreach ($counts as $id => $count) {
            DB::table('catalog_items')
                ->where('id', $id)
                ->update(['review_count' => $count]);
        }

        // ── Leisure package templates (dinner cruise) ──────────────
        if (Schema::hasTable('leisure_package_templates')) {
            $templateCounts = [
                'standard' => 94,
                'vip'      => 78,
                'premium'  => 61,
            ];

            foreach ($templateCounts as $code => $count) {
                DB::table('leisure_package_templates')
                    ->where('product_type', 'dinner_cruise')
                    ->where('code', $code)
                    ->update(['review_count' => $count]);
            }
        }
    }

    public function down(): void {}
};
