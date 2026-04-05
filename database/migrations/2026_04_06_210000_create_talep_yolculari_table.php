<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('talep_yolculari')) {
            return;
        }

        Schema::create('talep_yolculari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->foreign('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->unsignedSmallInteger('sira')->default(1);
            $table->enum('tur', ['yetiskin', 'cocuk', 'infant'])->default('yetiskin');
            $table->string('ad', 100);
            $table->string('soyad', 100);
            $table->string('kimlik_no', 50)->nullable();
            $table->enum('kimlik_tipi', ['tc', 'pasaport'])->default('tc');
            $table->date('dogum_tarihi')->nullable();
            $table->string('uyruk', 3)->nullable();
            $table->enum('cinsiyet', ['erkek', 'kadin'])->nullable();
            $table->unsignedBigInteger('olusturan_id')->nullable();
            $table->foreign('olusturan_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talep_yolculari');
    }
};
