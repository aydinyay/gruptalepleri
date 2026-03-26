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
        Schema::create('request_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->enum('payment_type', ['depozito', 'bakiye', 'full', 'diger'])->default('depozito');
            $table->enum('payment_method', ['FAST', 'EFT', 'havale', 'kart', 'nakit', 'diger'])->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('sender_masked', 100)->nullable();
            $table->string('account_masked', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('TRY');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['bekleniyor', 'alindi', 'iade'])->default('alindi');
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_payments');
    }
};
