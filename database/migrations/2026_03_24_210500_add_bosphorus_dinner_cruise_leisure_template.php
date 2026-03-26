<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        $exists = DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('leisure_package_templates')->insert([
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise',
            'level' => 'premium',
            'name_tr' => 'Bosphorus Dinner Cruise',
            'name_en' => 'Bosphorus Dinner Cruise',
            'summary_tr' => 'Bogaz hattinda premium masa, show ve transfer dahil aksam deneyimi.',
            'summary_en' => 'Evening Bosphorus dinner cruise with premium seating, show and transfer support.',
            'includes_tr' => json_encode([
                'Shuttle transfer',
                'Premium menu',
                'Bogaz manzarali premium masa',
                'Canli show programi',
            ]),
            'includes_en' => json_encode([
                'Shuttle transfer',
                'Premium menu',
                'Premium Bosphorus view table',
                'Live show program',
            ]),
            'excludes_tr' => json_encode([
                'Private yacht kapama',
                'Ozel foto-video cekimi',
            ]),
            'excludes_en' => json_encode([
                'Private yacht buyout',
                'Private photo-video production',
            ]),
            'is_active' => true,
            'sort_order' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->delete();
    }
};

