<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leisure_media_assets', function (Blueprint $table): void {
            $table->string('package_code', 40)->nullable()->after('product_type');
            $table->index(['product_type', 'package_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('leisure_media_assets', function (Blueprint $table): void {
            $table->dropIndex(['product_type', 'package_code', 'is_active']);
            $table->dropColumn('package_code');
        });
    }
};
