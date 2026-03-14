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
        Schema::create('opsiyon_uyari_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->integer('saat_oncesi');        // 48, 24, 4, 1 — opsiyondan kaç saat önce
            $table->boolean('sms_aktif')->default(true);
            $table->boolean('push_aktif')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opsiyon_uyari_ayarlari');
    }
};
