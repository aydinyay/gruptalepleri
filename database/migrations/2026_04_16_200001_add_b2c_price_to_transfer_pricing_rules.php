<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_pricing_rules', function (Blueprint $table): void {
            // B2C müşteriye gösterilecek perakende fiyat (TL veya rule currency cinsinden).
            // NULL ise mevcut base_fare + km/min formülünden hesaplanır.
            // Ayarlanmışsa bu değer müşteriye yansıtılır; tedarikçi base_fare'i alır.
            $table->decimal('b2c_price', 12, 2)->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_pricing_rules', function (Blueprint $table): void {
            $table->dropColumn('b2c_price');
        });
    }
};
