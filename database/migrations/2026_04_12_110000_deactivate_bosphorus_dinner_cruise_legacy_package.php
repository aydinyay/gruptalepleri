<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * bosphorus_dinner_cruise kodu eski bir template — yeni katalogda
 * standard/vip/premium paketleri kullanıldığından bu kayıt devre dışı bırakılır.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->update([
                'is_active'  => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->update(['is_active' => true, 'updated_at' => now()]);
    }
};
