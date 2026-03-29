<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ozel_gunler', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 150);                          // "Alaçatı Ot Festivali"
            $table->string('kategori', 40);                     // bayram/festival/resmi/turizm/sezon/ulusal
            $table->date('tarih');                              // 2026-03-30
            $table->string('tekrar', 10)->default('yearly');   // yearly/once
            $table->text('aciklama')->nullable();
            $table->string('hizmet_baglantisi', 50)->nullable(); // air_charter/transfer/leisure/platform
            $table->unsignedSmallInteger('hatirlatma_gun')->default(14); // kaç gün önce öner
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['tarih', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ozel_gunler');
    }
};
