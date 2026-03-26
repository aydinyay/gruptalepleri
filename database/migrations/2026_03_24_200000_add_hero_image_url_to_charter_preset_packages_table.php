<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('charter_preset_packages', 'hero_image_url')) {
            Schema::table('charter_preset_packages', function (Blueprint $table): void {
                $table->string('hero_image_url')->nullable()->after('currency');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('charter_preset_packages', 'hero_image_url')) {
            Schema::table('charter_preset_packages', function (Blueprint $table): void {
                $table->dropColumn('hero_image_url');
            });
        }
    }
};
