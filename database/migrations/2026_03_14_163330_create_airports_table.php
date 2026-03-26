<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('iata', 3)->unique();
            $table->string('icao', 4)->nullable()->index();
            $table->string('name');           // Havalimanı adı (İngilizce)
            $table->string('city')->nullable();
            $table->string('country');        // Ülke adı (İngilizce)
            $table->string('country_tr')->nullable(); // Ülke adı (Türkçe)
            $table->string('country_code', 2)->index();
            $table->string('type')->default('large_airport'); // large/medium/small
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
