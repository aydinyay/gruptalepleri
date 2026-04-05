<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistem_olay_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->string('olay_kodu', 100)->unique();
            $table->string('olay_adi', 150);
            $table->string('alici', 50)->default('acente'); // acente, admin, her_ikisi
            $table->string('email_konu', 255)->nullable();
            $table->longText('email_govde')->nullable();
            $table->text('sms_govde')->nullable();
            $table->boolean('email_aktif')->default(true);
            $table->boolean('sms_aktif')->default(true);
            $table->json('degiskenler')->nullable(); // ['gtpnr', 'acente_adi', 'link']
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistem_olay_sablonlari');
    }
};
