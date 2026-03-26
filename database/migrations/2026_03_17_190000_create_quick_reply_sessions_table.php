<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_reply_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manual_agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('selected_agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('selected_request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->foreignId('selected_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->string('membership_mode', 16)->default('auto');
            $table->string('resolved_membership', 16)->default('unknown');
            $table->decimal('match_confidence', 5, 2)->nullable();
            $table->boolean('requires_manual_review')->default(true);
            $table->boolean('requires_new_account')->default(false);
            $table->longText('raw_text');
            $table->text('error_message')->nullable();
            $table->text('confirmation_summary')->nullable();
            $table->json('parsed_payload')->nullable();
            $table->json('edited_payload')->nullable();
            $table->json('agency_candidates')->nullable();
            $table->json('request_candidates')->nullable();
            $table->json('new_account_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['selected_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_reply_sessions');
    }
};
