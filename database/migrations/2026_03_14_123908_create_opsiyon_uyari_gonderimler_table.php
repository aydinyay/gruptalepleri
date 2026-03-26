<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opsiyon_uyari_gonderimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->integer('saat_oncesi');        // hangi kural tetikledi
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['offer_id', 'saat_oncesi']); // aynı teklif + aynı kural ikinci kez gitmesin
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opsiyon_uyari_gonderimler');
    }
};
