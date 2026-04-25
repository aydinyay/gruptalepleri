<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('short_desc_translations')->nullable()->after('short_desc');
            $table->json('full_desc_translations')->nullable()->after('full_desc');
            $table->json('meta_title_translations')->nullable()->after('meta_title');
            $table->json('meta_description_translations')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn([
                'title_translations',
                'short_desc_translations',
                'full_desc_translations',
                'meta_title_translations',
                'meta_description_translations',
            ]);
        });
    }
};
