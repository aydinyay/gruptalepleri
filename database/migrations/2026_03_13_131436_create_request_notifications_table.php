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
        Schema::create('request_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('requests')->onDelete('cascade');
            $table->string('channel')->default('sms'); // sms, push
            $table->string('recipient')->default('admin'); // admin, acente
            $table->string('recipient_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->string('provider_code')->nullable(); // API response code
            $table->string('delivery_status')->nullable(); // delivered, undelivered, unknown
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_notifications');
    }
};
