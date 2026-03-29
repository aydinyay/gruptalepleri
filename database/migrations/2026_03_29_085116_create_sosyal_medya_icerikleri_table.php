<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sosyal_medya_icerikleri', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);                 // facebook/instagram/linkedin/x
            $table->string('format', 30);                   // durum/akis/reels/hikaye/gonderi/makale/tweet/thread
            $table->string('tema', 80)->nullable();         // platform_tanitim/ozel_gun/istatistik/...
            $table->string('konu', 200)->nullable();        // serbest açıklama
            $table->text('icerik');                         // üretilen içerik
            $table->longText('gorsel_base64')->nullable();  // base64 görsel (disk yok)
            $table->string('durum', 20)->default('taslak'); // taslak/planli/gonderildi
            $table->timestamp('planlanan_tarih')->nullable();
            $table->timestamp('gonderim_tarihi')->nullable();
            $table->string('ozel_gun_ref', 100)->nullable();// bağlı özel gün adı
            $table->tinyInteger('ai_skor')->nullable();     // 1-5 tahmini etkileşim skoru
            $table->string('buffer_id', 80)->nullable();   // Buffer API post ID (gelecek entegrasyon)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['platform', 'durum']);
            $table->index('planlanan_tarih');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sosyal_medya_icerikleri');
    }
};
