<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesaj_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->string('sablon_adi');
            $table->string('email_konu')->nullable();
            $table->longText('email_govde')->nullable();
            $table->text('sms_govde')->nullable();
            $table->json('kanallar')->nullable(); // ['email', 'sms', 'push']
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesaj_sablonlari');
    }
};
