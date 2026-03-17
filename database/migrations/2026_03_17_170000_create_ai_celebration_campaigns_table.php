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
        Schema::create('ai_celebration_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key')->nullable()->index();
            $table->string('event_name');
            $table->date('event_date')->nullable()->index();
            $table->string('category', 50)->default('genel');
            $table->string('status', 30)->default('draft')->index();

            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('cta_text', 120)->nullable();
            $table->string('cta_url')->nullable();

            $table->text('topic_prompt')->nullable();
            $table->text('visual_prompt')->nullable();
            $table->json('ai_payload')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_ai_generated')->default(false);

            $table->string('display_mode', 30)->default('banner');
            $table->boolean('show_on_public')->default(false);
            $table->boolean('show_on_authenticated')->default(true);
            $table->unsignedSmallInteger('frequency_cap')->default(1);
            $table->unsignedSmallInteger('priority')->default(100);

            $table->dateTime('publish_starts_at')->nullable()->index();
            $table->dateTime('publish_ends_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();

            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dismissed_at')->nullable();
            $table->string('dismiss_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_key', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_celebration_campaigns');
    }
};

