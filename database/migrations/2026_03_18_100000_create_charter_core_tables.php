<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('charter_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_type', 20)->default('agency'); // public|agency
            $table->string('transport_type', 20); // jet|helicopter|airliner
            $table->string('status', 40)->default('lead');

            $table->string('name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('from_iata', 10)->nullable();
            $table->string('to_iata', 10)->nullable();
            $table->date('departure_date')->nullable();
            $table->unsignedSmallInteger('pax')->nullable();

            $table->boolean('is_flexible')->default(false);
            $table->string('group_type')->nullable();
            $table->text('notes')->nullable();

            // AI pre-quote output (deterministic services + analysis)
            $table->string('ai_suggested_model')->nullable();
            $table->decimal('ai_price_min', 12, 2)->nullable();
            $table->decimal('ai_price_max', 12, 2)->nullable();
            $table->string('ai_currency', 8)->default('EUR');
            $table->json('ai_risk_flags')->nullable();
            $table->text('ai_comment')->nullable();
            $table->string('aircraft_image_url')->nullable();

            $table->timestamps();

            $table->index(['transport_type', 'status']);
            $table->index(['requester_type', 'status']);
            $table->index(['departure_date', 'pax']);
        });

        Schema::create('charter_jet_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->unique()->constrained('charter_requests')->cascadeOnDelete();
            $table->unsignedSmallInteger('flight_hours_estimate')->nullable();
            $table->boolean('round_trip')->default(false);
            $table->boolean('pet_onboard')->default(false);
            $table->boolean('vip_catering')->default(false);
            $table->boolean('wifi_required')->default(false);
            $table->boolean('special_luggage')->default(false);
            $table->unsignedSmallInteger('luggage_count')->nullable();
            $table->string('cabin_preference')->nullable();
            $table->string('airport_slot_note')->nullable();
            $table->json('specs_json')->nullable(); // 22+ alan için genişleme noktası
            $table->timestamps();
        });

        Schema::create('charter_helicopter_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->unique()->constrained('charter_requests')->cascadeOnDelete();
            $table->string('pickup')->nullable();
            $table->string('dropoff')->nullable();
            $table->text('landing_details')->nullable();
            $table->timestamps();
        });

        Schema::create('charter_airliner_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->unique()->constrained('charter_requests')->cascadeOnDelete();
            $table->boolean('date_flexible')->default(false);
            $table->string('group_type')->nullable();
            $table->text('route_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('aircraft_images', function (Blueprint $table): void {
            $table->id();
            $table->string('service_type', 20); // jet|helicopter|airliner
            $table->string('model_code')->nullable();
            $table->string('model_name');
            $table->string('image_url');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['service_type', 'model_name']);
            $table->index(['service_type', 'is_active', 'priority']);
        });

        Schema::create('charter_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->constrained('charter_requests')->cascadeOnDelete();
            $table->string('quote_type', 30); // ai_preview|supplier|sales|rfq
            $table->string('status', 30)->default('draft');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['charter_request_id', 'quote_type']);
        });

        Schema::create('charter_supplier_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->constrained('charter_requests')->cascadeOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_channel', 20)->default('manual'); // manual|email|sms|push
            $table->string('model_name')->nullable();
            $table->string('aircraft_image_url')->nullable();
            $table->decimal('supplier_price', 12, 2);
            $table->string('currency', 8)->default('EUR');
            $table->text('supplier_note')->nullable();
            $table->text('whatsapp_text')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->string('status', 30)->default('received');
            $table->timestamps();

            $table->index(['charter_request_id', 'status']);
        });

        Schema::create('charter_sales_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->constrained('charter_requests')->cascadeOnDelete();
            $table->foreignId('supplier_quote_id')->nullable()->constrained('charter_supplier_quotes')->nullOnDelete();
            $table->decimal('base_supplier_price', 12, 2);
            $table->decimal('markup_percent', 6, 2)->default(0);
            $table->decimal('min_profit', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2);
            $table->string('currency', 8)->default('EUR');
            $table->boolean('is_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->string('status', 30)->default('sent'); // sent|accepted|rejected|expired
            $table->timestamps();

            $table->index(['charter_request_id', 'status']);
        });

        Schema::create('charter_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->constrained('charter_requests')->cascadeOnDelete();
            $table->foreignId('sales_quote_id')->constrained('charter_sales_quotes')->cascadeOnDelete();
            $table->string('status', 30)->default('pending_payment'); // pending_payment|partial_paid|paid|operation_started
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('charter_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_booking_id')->constrained('charter_bookings')->cascadeOnDelete();
            $table->string('method', 20); // card|bank_transfer
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('EUR');
            $table->string('status', 30)->default('pending'); // pending|approved|rejected
            $table->string('provider', 30)->nullable(); // iyzico|stripe|paytr|manual
            $table->string('provider_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['charter_booking_id', 'status']);
        });

        Schema::create('charter_extras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charter_request_id')->constrained('charter_requests')->cascadeOnDelete();
            $table->string('title');
            $table->text('agency_note')->nullable();
            $table->decimal('admin_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('EUR');
            $table->string('status', 30)->default('pending_pricing'); // pending_pricing|priced|rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charter_extras');
        Schema::dropIfExists('charter_payments');
        Schema::dropIfExists('charter_bookings');
        Schema::dropIfExists('charter_sales_quotes');
        Schema::dropIfExists('charter_supplier_quotes');
        Schema::dropIfExists('charter_quotes');
        Schema::dropIfExists('aircraft_images');
        Schema::dropIfExists('charter_airliner_requests');
        Schema::dropIfExists('charter_helicopter_requests');
        Schema::dropIfExists('charter_jet_requests');
        Schema::dropIfExists('charter_requests');
    }
};

