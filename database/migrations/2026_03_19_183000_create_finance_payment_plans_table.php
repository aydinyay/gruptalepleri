<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_record_id')->constrained('finance_records')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('title', 255)->nullable();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('TRY');
            $table->enum('status', ['planned', 'partial', 'paid', 'cancelled'])->default('planned');
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['finance_record_id', 'sequence'], 'finance_payment_plans_record_sequence_unique');
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_plans');
    }
};
