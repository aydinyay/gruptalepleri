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
    Schema::create('requests', function (Blueprint $table) {
        $table->id();
        $table->string('gtpnr')->unique();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('type')->default('group_flight');
        $table->string('status')->default('beklemede');
        $table->string('agency_name')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->integer('pax_total')->default(0);
        $table->integer('pax_adult')->default(0);
        $table->integer('pax_child')->default(0);
        $table->integer('pax_infant')->default(0);
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
