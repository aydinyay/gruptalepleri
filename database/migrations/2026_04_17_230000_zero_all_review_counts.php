<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('catalog_items')->update(['review_count' => 0]);

        if (Schema::hasTable('leisure_package_templates')) {
            DB::table('leisure_package_templates')->update(['review_count' => 0]);
        }
    }

    public function down(): void {}
};
