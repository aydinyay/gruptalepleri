<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
            $table->string('iata', 2)->nullable()->index();
            $table->string('icao', 3)->nullable()->index();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('callsign')->nullable();
            $table->string('country')->nullable();
            $table->string('country_tr')->nullable();
            $table->boolean('active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airlines');
    }
};
