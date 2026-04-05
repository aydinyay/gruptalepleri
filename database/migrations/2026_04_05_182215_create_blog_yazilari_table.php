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
        Schema::create('blog_yazilari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->nullable()->constrained('blog_kategorileri')->nullOnDelete();
            $table->string('baslik');
            $table->string('slug')->unique();
            $table->text('ozet');
            $table->longText('icerik');
            $table->string('kapak_gorseli')->nullable();
            $table->string('meta_baslik')->nullable();
            $table->string('meta_aciklama', 320)->nullable();
            $table->string('yazar')->default('GrupTalepleri Editör');
            $table->enum('durum', ['taslak', 'yayinda'])->default('taslak');
            $table->timestamp('yayinlanma_tarihi')->nullable();
            $table->unsignedInteger('goruntuleme')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_yazilari');
    }
};
