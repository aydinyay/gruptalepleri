<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tedarikçi araç filosu — gerçek kapasite yönetimi için
        Schema::create('transfer_vehicle_fleet', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('supplier_id')
                  ->constrained('transfer_suppliers')
                  ->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')
                  ->constrained('transfer_vehicle_types')
                  ->cascadeOnDelete();

            // Günlük kapasite: bu araç tipinden kaç adet var
            $table->unsignedSmallInteger('quantity')->default(1)
                  ->comment('Sahip olunan araç adedi');

            // Günlük maksimum rezervasyon (quantity * günlük sefer sayısı)
            // Örn: 2 araç × 4 sefer = 8 max günlük rezervasyon
            $table->unsignedSmallInteger('max_daily_bookings')->default(4)
                  ->comment('Günde kabul edilen max rezervasyon');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Bir supplier + vehicle_type kombinasyonu tek kayıt
            $table->unique(['supplier_id', 'vehicle_type_id'], 'fleet_supplier_vehicle_unique');
            $table->index(['supplier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_vehicle_fleet');
    }
};
