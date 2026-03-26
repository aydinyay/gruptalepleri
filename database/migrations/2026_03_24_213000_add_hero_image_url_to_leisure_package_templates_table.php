<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        if (Schema::hasColumn('leisure_package_templates', 'hero_image_url')) {
            return;
        }

        Schema::table('leisure_package_templates', function (Blueprint $table): void {
            $table->string('hero_image_url')->nullable()->after('summary_en');
        });

        DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->whereNull('hero_image_url')
            ->update([
                'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        if (! Schema::hasColumn('leisure_package_templates', 'hero_image_url')) {
            return;
        }

        Schema::table('leisure_package_templates', function (Blueprint $table): void {
            $table->dropColumn('hero_image_url');
        });
    }
};
