<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // B2C talepler için user_id nullable yapılıyor (acente hesabı olmadan talep gelir)
            $table->unsignedBigInteger('user_id')->nullable()->change();
            // Kanal takibi: b2b (acente paneli) veya b2c (gruprezervasyonlari.com)
            $table->string('source_channel', 20)->default('b2b')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('source_channel');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
