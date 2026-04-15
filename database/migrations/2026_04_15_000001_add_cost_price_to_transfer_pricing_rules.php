<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_pricing_rules', function (Blueprint $table): void {
            $table->decimal('cost_price', 12, 2)->nullable()->after('minimum_fare')
                ->comment('Tedarikci maliyet fiyati (sadece superadmin gorur)');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_pricing_rules', function (Blueprint $table): void {
            $table->dropColumn('cost_price');
        });
    }
};
