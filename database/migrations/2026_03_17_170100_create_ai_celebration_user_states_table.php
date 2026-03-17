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
        Schema::create('ai_celebration_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ai_celebration_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('seen_count')->default(0);
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('clicked_at')->nullable();
            $table->string('last_action', 30)->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'seen_count']);
            $table->index(['campaign_id', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_celebration_user_states');
    }
};

