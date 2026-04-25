<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigorta_batch_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('islem_adi', 120)->nullable();
            $table->enum('kanal', ['b2b', 'b2c'])->default('b2b');
            $table->unsignedBigInteger('acente_id')->nullable()->index();
            $table->unsignedBigInteger('b2c_user_id')->nullable()->index();
            $table->unsignedInteger('toplam')->default(0);
            $table->unsignedInteger('tamamlanan')->default(0);
            $table->unsignedInteger('basarisiz')->default(0);
            $table->enum('durum', ['bekliyor', 'isleniyor', 'tamamlandi', 'hata'])->default('bekliyor');
            $table->json('bekleyen_satirlar')->nullable();  // henüz işlenmemiş satırlar
            $table->json('hatali_satirlar')->nullable();    // retry için hata alanlar
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigorta_batch_jobs');
    }
};
