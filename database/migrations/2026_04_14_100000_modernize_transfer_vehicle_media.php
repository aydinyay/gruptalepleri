<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // transfer_vehicle_types tablosuna medya/donanim/fiyat kolonlari ekle
        Schema::table('transfer_vehicle_types', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('sort_order');
            $table->json('amenities_json')->nullable()->after('description');
            $table->decimal('suggested_retail_price', 12, 2)->nullable()->after('amenities_json');
            $table->unsignedInteger('luggage_capacity')->nullable()->after('suggested_retail_price');
        });

        // Araç tipi medya tablosu (6 foto + video destekli)
        Schema::create('transfer_vehicle_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('transfer_vehicle_types')->cascadeOnDelete();
            $table->string('media_type', 20)->default('photo'); // photo | video
            $table->string('source_type', 20)->default('upload'); // upload | link
            $table->text('file_path')->nullable();
            $table->text('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['vehicle_type_id', 'is_active', 'sort_order'], 'tvm_vehicle_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_vehicle_media');

        Schema::table('transfer_vehicle_types', function (Blueprint $table): void {
            $table->dropColumn(['description', 'amenities_json', 'suggested_retail_price', 'luggage_capacity']);
        });
    }
};
