<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->nullable()->after('base_price');
            $table->text('pricing_notes')->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'pricing_notes']);
        });
    }
};
