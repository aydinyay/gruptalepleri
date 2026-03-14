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
    Schema::create('offers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('request_id')->constrained()->onDelete('cascade');
        $table->string('airline')->nullable();
        $table->string('currency')->default('USD');
        $table->decimal('price_per_pax', 10, 2)->nullable();
        $table->decimal('total_price', 10, 2)->nullable();
        $table->decimal('cost_price', 10, 2)->nullable();
        $table->decimal('profit_amount', 10, 2)->nullable();
        $table->decimal('profit_percent', 5, 2)->nullable();
        $table->decimal('deposit_rate', 5, 2)->nullable();
        $table->decimal('deposit_amount', 10, 2)->nullable();
        $table->date('option_date')->nullable();
        $table->time('option_time')->nullable();
        $table->text('offer_text')->nullable();
        $table->boolean('is_visible')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
