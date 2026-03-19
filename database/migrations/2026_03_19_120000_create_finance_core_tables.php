<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agency_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();

            $table->enum('scope_type', ['service', 'manual'])->default('service');
            $table->string('service_type', 50)->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('document_type', 50)->default('manual');
            $table->string('document_ref', 100)->nullable();

            $table->string('title');
            $table->string('currency', 8)->default('TRY');
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'open', 'partial', 'paid', 'refunded', 'cancelled'])->default('open');

            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_type', 'service_id']);
            $table->index(['agency_user_id', 'status', 'due_date']);
            $table->unique(['service_type', 'service_id'], 'finance_records_service_unique');
        });

        Schema::create('finance_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_record_id')->nullable()->constrained('finance_records')->nullOnDelete();

            $table->string('source_key', 120)->nullable()->unique();
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('payer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('method', ['card', 'bank_transfer', 'eft', 'cash', 'manual', 'other'])->default('manual');
            $table->enum('direction', ['in', 'out'])->default('in');

            $table->decimal('gross_amount', 12, 2);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->string('currency', 8)->default('TRY');

            $table->enum('status', ['pending', 'awaiting_validation', 'approved', 'rejected', 'cancelled', 'refunded'])->default('pending');
            $table->date('payment_date')->nullable();

            $table->string('provider', 30)->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('sender_name', 120)->nullable();
            $table->string('sender_reference', 120)->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['finance_record_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index(['payment_date', 'status']);
        });

        Schema::create('finance_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_record_id')->constrained('finance_records')->cascadeOnDelete();
            $table->foreignId('finance_transaction_id')->constrained('finance_transactions')->cascadeOnDelete();
            $table->enum('allocation_type', ['payment', 'deposit', 'balance', 'refund', 'adjustment'])->default('payment');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('TRY');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['finance_record_id', 'finance_transaction_id'], 'finance_allocations_record_transaction_unique');
        });

        Schema::create('finance_receipt_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_record_id')->nullable()->constrained('finance_records')->nullOnDelete();
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->foreignId('agency_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('TRY');
            $table->date('payment_date')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('sender_name', 120)->nullable();
            $table->string('sender_reference', 120)->nullable();
            $table->string('receipt_path')->nullable();

            $table->enum('status', ['pending', 'matched', 'needs_review', 'rejected', 'insufficient_data'])->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_date']);
        });

        Schema::create('finance_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_record_id')->nullable()->constrained('finance_records')->nullOnDelete();
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->foreignId('refund_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('TRY');
            $table->enum('method', ['card', 'bank_transfer', 'eft', 'cash', 'manual', 'other'])->default('manual');
            $table->enum('status', ['requested', 'approved', 'rejected', 'processed', 'cancelled'])->default('requested');
            $table->text('reason')->nullable();

            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('finance_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->text('note')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audit_logs');
        Schema::dropIfExists('finance_refunds');
        Schema::dropIfExists('finance_receipt_submissions');
        Schema::dropIfExists('finance_allocations');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_records');
    }
};
