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
        Schema::create('broadcast_email_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcast_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('token', 64)->unique();            // sha256 hex token
            $table->enum('type', ['open', 'click']);
            $table->string('destination_url')->nullable();  // click için hedef URL
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('triggered_at')->nullable();  // ilk tetiklenme zamanı
            $table->unsignedSmallInt('hit_count')->default(0);
            $table->timestamps();

            $table->index(['broadcast_id', 'user_id']);
            $table->index(['broadcast_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_email_tracks');
    }
};
