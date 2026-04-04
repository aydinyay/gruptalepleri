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
        Schema::create('kampanya_sablonlar', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 150);
            $table->enum('tip', ['email', 'sms'])->default('email')->index();
            $table->string('konu', 255)->nullable();
            $table->longText('html_icerik')->nullable();
            $table->text('sms_icerik')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('olusturan_id')->nullable();
            $table->foreign('olusturan_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kampanya_sablonlar');
    }
};
