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
    Schema::create('flight_segments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('request_id')->constrained()->onDelete('cascade');
        $table->integer('order')->default(0);
        $table->string('from_iata');
        $table->string('from_city')->nullable();
        $table->string('to_iata');
        $table->string('to_city')->nullable();
        $table->date('departure_date');
        $table->time('departure_time')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_segments');
    }
};
