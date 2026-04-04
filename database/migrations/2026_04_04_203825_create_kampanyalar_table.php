<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanyalar', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 150);
            $table->text('aciklama')->nullable();
            $table->enum('tip', ['email', 'sms'])->default('email');
            $table->unsignedBigInteger('sablon_id')->nullable();
            $table->foreign('sablon_id')->references('id')->on('kampanya_sablonlar')->nullOnDelete();
            // Hedef kitle filtreleri (JSON)
            $table->json('hedef')->nullable(); // {il, ilce, grup, sadece_yeni}
            // Zamanlama (JSON)
            $table->json('zamanlama')->nullable(); // {baslangic, bitis, slotlar:[{saat,adet}]}
            $table->enum('durum', ['taslak', 'aktif', 'durduruldu', 'tamamlandi'])->default('taslak')->index();
            $table->string('etiket', 100)->unique(); // kampanya_etiket — tursab_davetler'e yazılır
            $table->unsignedBigInteger('olusturan_id')->nullable();
            $table->foreign('olusturan_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampanyalar');
    }
};
