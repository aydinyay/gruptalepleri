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

        // VIP paket rozeti GYG → GT
        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'vip')
            ->update(['badge_text' => 'GT Sertifikalı', 'updated_at' => now()]);
    }

    public function down(): void {}
};
