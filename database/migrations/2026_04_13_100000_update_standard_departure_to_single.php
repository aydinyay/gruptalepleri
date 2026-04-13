<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Standard dinner cruise paketinin kalkış saatini tek sefer olarak güncelle.
 * 18:30 Biniş / 19:00 Kalkış seçeneği kaldırılır,
 * yalnızca 20:30 Biniş / 21:00 Kalkış kalır.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'standard')
            ->update([
                'departure_times' => json_encode(['20:30 Biniş / 21:00 Kalkış']),
                'updated_at'      => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'standard')
            ->update([
                'departure_times' => json_encode([
                    '18:30 Biniş / 19:00 Kalkış',
                    '20:30 Biniş / 21:00 Kalkış',
                ]),
                'updated_at' => now(),
            ]);
    }
};
