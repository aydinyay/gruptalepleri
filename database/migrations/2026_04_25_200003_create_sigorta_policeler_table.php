<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigorta_policeler', function (Blueprint $table) {
            $table->id();

            // Bağlam
            $table->unsignedBigInteger('batch_job_id')->nullable()->index();
            $table->unsignedBigInteger('acente_id')->nullable()->index();
            $table->unsignedBigInteger('b2c_user_id')->nullable()->index();
            $table->enum('kanal', ['b2b', 'b2c'])->default('b2b')->index();

            // PAO-Net referansları
            $table->string('paonet_referans', 80)->nullable()->index();
            $table->string('paonet_teklif_id', 80)->nullable();
            $table->string('police_no', 80)->nullable()->index();
            $table->string('paonet_urun_kodu', 20)->nullable();  // NPN302 / NPN220

            // Sigortalı bilgileri
            $table->string('sigortali_kimlik', 20)->nullable();   // TC veya pasaport
            $table->string('kimlik_tipi', 10)->default('tc');     // tc / pasaport
            $table->string('sigortali_adi', 120)->nullable();
            $table->string('sigortali_soyadi', 80)->nullable();
            $table->date('sigortali_dogum')->nullable();

            // Seyahat bilgileri
            $table->date('baslangic_tarihi')->nullable()->index();
            $table->date('bitis_tarihi')->nullable();
            $table->string('gidilecek_ulke', 80)->nullable();
            $table->string('gidilecek_ulke_kodu', 10)->nullable();

            // Fiyatlama
            $table->string('api_doviz_turu', 5)->nullable();       // USD / EUR
            $table->decimal('api_doviz_tutar', 10, 2)->nullable(); // Bprim (API'nin verdiği)
            $table->decimal('api_kur', 12, 4)->nullable();         // Dkuru
            $table->decimal('maliyet_tl', 12, 2)->nullable();      // Bprim × Dkuru
            $table->decimal('b2b_fiyat_tl', 12, 2)->nullable();
            $table->decimal('b2c_fiyat_tl', 12, 2)->nullable();
            $table->decimal('satilan_fiyat_tl', 12, 2)->nullable();
            $table->decimal('net_kar_tl', 12, 2)->nullable();
            $table->decimal('markup_yuzde', 6, 2)->nullable();
            $table->decimal('kur_tamponu_yuzde', 6, 2)->nullable();

            // PDF linkleri (PAO-Net'ten gelen ham URL'ler)
            $table->string('pdf_url_base', 500)->nullable();
            $table->text('pdf_link')->nullable();
            $table->text('makbuz_link')->nullable();
            $table->text('sertifika_link')->nullable();
            $table->text('ing_sertifika_link')->nullable();

            // Durum
            $table->enum('durum', [
                'teklif_gonderildi',
                'teklif_alindi',
                'police_isleniyor',
                'tamamlandi',
                'iptal_bekliyor',
                'iptal',
                'hata',
            ])->default('teklif_gonderildi')->index();
            $table->text('hata_mesaji')->nullable();

            // İptal
            $table->string('iptal_nedeni', 255)->nullable();
            $table->timestamp('iptal_tarih')->nullable();
            $table->string('mukerrer_police_no', 80)->nullable();  // iptal için gerekli mükerrer

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigorta_policeler');
    }
};
