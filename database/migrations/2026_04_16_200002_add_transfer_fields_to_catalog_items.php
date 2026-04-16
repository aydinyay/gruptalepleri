<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transfer tipindeki catalog item'larına rota bilgisi ekler.
     * Bu alanlar doldurulduğunda ürün sayfasında canlı fiyat sorgulama aktif olur.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('transfer_airport_id')->nullable()->after('reference_id');
            $table->unsignedBigInteger('transfer_zone_id')->nullable()->after('transfer_airport_id');
            $table->string('transfer_direction', 10)->nullable()->after('transfer_zone_id'); // ARR|DEP|BOTH

            $table->foreign('transfer_airport_id')
                  ->references('id')->on('transfer_airports')
                  ->nullOnDelete();

            $table->foreign('transfer_zone_id')
                  ->references('id')->on('transfer_zones')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table): void {
            $table->dropForeign(['transfer_airport_id']);
            $table->dropForeign(['transfer_zone_id']);
            $table->dropColumn(['transfer_airport_id', 'transfer_zone_id', 'transfer_direction']);
        });
    }
};
