<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('charter_requests', function (Blueprint $table): void {
            $table->string('preset_package_code', 80)->nullable()->after('notes');
            $table->string('preset_package_title')->nullable()->after('preset_package_code');
            $table->decimal('preset_package_price', 12, 2)->nullable()->after('preset_package_title');
            $table->string('preset_package_currency', 8)->nullable()->after('preset_package_price');
            $table->json('preset_package_snapshot')->nullable()->after('preset_package_currency');

            $table->index(['preset_package_code', 'status'], 'charter_requests_preset_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('charter_requests', function (Blueprint $table): void {
            $table->dropIndex('charter_requests_preset_status_idx');
            $table->dropColumn([
                'preset_package_code',
                'preset_package_title',
                'preset_package_price',
                'preset_package_currency',
                'preset_package_snapshot',
            ]);
        });
    }
};
