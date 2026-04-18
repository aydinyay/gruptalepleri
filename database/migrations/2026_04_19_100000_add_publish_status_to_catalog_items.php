<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('publish_status', 10)->default('draft')->after('is_published');
        });

        // Mevcut veriyi migrate et: is_published=true → 'b2c', false → 'draft'
        DB::statement("UPDATE catalog_items SET publish_status = CASE WHEN is_published = 1 THEN 'b2c' ELSE 'draft' END");
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('publish_status');
        });
    }
};
