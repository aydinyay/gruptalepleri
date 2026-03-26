<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendRequestPaymentsTable();
        $this->extendCharterPaymentsTable();
        $this->createLeisurePaymentsTable();
    }

    public function down(): void
    {
        if (Schema::hasTable('leisure_payments')) {
            Schema::dropIfExists('leisure_payments');
        }

        if (Schema::hasTable('charter_payments')) {
            Schema::table('charter_payments', function (Blueprint $table): void {
                foreach ([
                    'internal_reference',
                    'request_payload_json',
                    'response_payload_json',
                    'callback_payload_json',
                    'failure_reason',
                    'processed_at',
                    'paid_at',
                    'failed_at',
                    'charged_try_amount',
                    'fx_rate',
                    'fx_timestamp',
                    'source_currency',
                ] as $column) {
                    if (Schema::hasColumn('charter_payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('request_payments')) {
            Schema::table('request_payments', function (Blueprint $table): void {
                foreach ([
                    'gateway_provider',
                    'gateway_internal_reference',
                    'gateway_provider_reference',
                    'gateway_status',
                    'request_payload_json',
                    'response_payload_json',
                    'callback_payload_json',
                    'failure_reason',
                    'processed_at',
                    'paid_at',
                    'failed_at',
                    'charged_try_amount',
                    'fx_rate',
                    'fx_timestamp',
                    'source_currency',
                ] as $column) {
                    if (Schema::hasColumn('request_payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function extendRequestPaymentsTable(): void
    {
        if (! Schema::hasTable('request_payments')) {
            return;
        }

        Schema::table('request_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('request_payments', 'gateway_provider')) {
                $table->string('gateway_provider', 40)->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('request_payments', 'gateway_internal_reference')) {
                $table->string('gateway_internal_reference', 80)->nullable()->after('gateway_provider');
            }
            if (! Schema::hasColumn('request_payments', 'gateway_provider_reference')) {
                $table->string('gateway_provider_reference', 120)->nullable()->after('gateway_internal_reference');
            }
            if (! Schema::hasColumn('request_payments', 'gateway_status')) {
                $table->string('gateway_status', 30)->nullable()->after('gateway_provider_reference');
            }
            if (! Schema::hasColumn('request_payments', 'request_payload_json')) {
                $table->json('request_payload_json')->nullable()->after('gateway_status');
            }
            if (! Schema::hasColumn('request_payments', 'response_payload_json')) {
                $table->json('response_payload_json')->nullable()->after('request_payload_json');
            }
            if (! Schema::hasColumn('request_payments', 'callback_payload_json')) {
                $table->json('callback_payload_json')->nullable()->after('response_payload_json');
            }
            if (! Schema::hasColumn('request_payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('callback_payload_json');
            }
            if (! Schema::hasColumn('request_payments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('failure_reason');
            }
            if (! Schema::hasColumn('request_payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('processed_at');
            }
            if (! Schema::hasColumn('request_payments', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('request_payments', 'charged_try_amount')) {
                $table->decimal('charged_try_amount', 12, 2)->nullable()->after('failed_at');
            }
            if (! Schema::hasColumn('request_payments', 'fx_rate')) {
                $table->decimal('fx_rate', 14, 6)->nullable()->after('charged_try_amount');
            }
            if (! Schema::hasColumn('request_payments', 'fx_timestamp')) {
                $table->timestamp('fx_timestamp')->nullable()->after('fx_rate');
            }
            if (! Schema::hasColumn('request_payments', 'source_currency')) {
                $table->string('source_currency', 8)->nullable()->after('fx_timestamp');
            }
        });
    }

    private function extendCharterPaymentsTable(): void
    {
        if (! Schema::hasTable('charter_payments')) {
            return;
        }

        Schema::table('charter_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('charter_payments', 'internal_reference')) {
                $table->string('internal_reference', 80)->nullable()->after('provider_reference');
            }
            if (! Schema::hasColumn('charter_payments', 'request_payload_json')) {
                $table->json('request_payload_json')->nullable()->after('internal_reference');
            }
            if (! Schema::hasColumn('charter_payments', 'response_payload_json')) {
                $table->json('response_payload_json')->nullable()->after('request_payload_json');
            }
            if (! Schema::hasColumn('charter_payments', 'callback_payload_json')) {
                $table->json('callback_payload_json')->nullable()->after('response_payload_json');
            }
            if (! Schema::hasColumn('charter_payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('callback_payload_json');
            }
            if (! Schema::hasColumn('charter_payments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('failure_reason');
            }
            if (! Schema::hasColumn('charter_payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('processed_at');
            }
            if (! Schema::hasColumn('charter_payments', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('charter_payments', 'charged_try_amount')) {
                $table->decimal('charged_try_amount', 12, 2)->nullable()->after('failed_at');
            }
            if (! Schema::hasColumn('charter_payments', 'fx_rate')) {
                $table->decimal('fx_rate', 14, 6)->nullable()->after('charged_try_amount');
            }
            if (! Schema::hasColumn('charter_payments', 'fx_timestamp')) {
                $table->timestamp('fx_timestamp')->nullable()->after('fx_rate');
            }
            if (! Schema::hasColumn('charter_payments', 'source_currency')) {
                $table->string('source_currency', 8)->nullable()->after('fx_timestamp');
            }
        });
    }

    private function createLeisurePaymentsTable(): void
    {
        if (Schema::hasTable('leisure_payments') || ! Schema::hasTable('leisure_bookings')) {
            return;
        }

        Schema::create('leisure_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leisure_booking_id')->constrained('leisure_bookings')->cascadeOnDelete();
            $table->string('reference', 60)->unique();
            $table->string('method', 20)->default('card');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('TRY');
            $table->string('status', 30)->default('pending');
            $table->string('provider', 40)->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->string('internal_reference', 80)->nullable();
            $table->json('request_payload_json')->nullable();
            $table->json('response_payload_json')->nullable();
            $table->json('callback_payload_json')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->decimal('charged_try_amount', 12, 2)->nullable();
            $table->decimal('fx_rate', 14, 6)->nullable();
            $table->timestamp('fx_timestamp')->nullable();
            $table->string('source_currency', 8)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['leisure_booking_id', 'status']);
            $table->index('provider_reference');
            $table->index('internal_reference');
        });
    }
};

