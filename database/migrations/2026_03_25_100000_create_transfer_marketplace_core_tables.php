<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_airports', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('city');
            $table->string('country')->default('Turkey');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('transfer_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('airport_id')->constrained('transfer_airports')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('city');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
            $table->unique(['airport_id', 'slug']);
        });

        Schema::create('transfer_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(12.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('transfer_vehicle_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedInteger('max_passengers')->default(3);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('transfer_supplier_coverages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->foreignId('airport_id')->constrained('transfer_airports')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('transfer_zones')->cascadeOnDelete();
            $table->string('direction', 20)->default('BOTH');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'airport_id', 'zone_id', 'direction'], 'transfer_supplier_coverage_unique');
        });

        Schema::create('transfer_pricing_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->foreignId('airport_id')->constrained('transfer_airports')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('transfer_zones')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('transfer_vehicle_types')->cascadeOnDelete();
            $table->string('direction', 20)->default('BOTH');
            $table->string('currency', 8)->default('TRY');
            $table->decimal('base_fare', 12, 2)->default(0);
            $table->decimal('per_km', 12, 2)->default(0);
            $table->decimal('per_minute', 12, 2)->default(0);
            $table->decimal('minimum_fare', 12, 2)->default(0);
            $table->time('night_start')->nullable();
            $table->time('night_end')->nullable();
            $table->decimal('night_multiplier', 5, 2)->default(1.00);
            $table->decimal('peak_multiplier', 5, 2)->default(1.00);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['airport_id', 'zone_id', 'direction', 'is_active'], 'transfer_pricing_rules_search_idx');
        });

        Schema::create('transfer_cancellation_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->unsignedInteger('free_cancel_before_minutes')->default(180);
            $table->decimal('refund_percent_after_deadline', 5, 2)->default(0.00);
            $table->decimal('no_show_refund_percent', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('supplier_id');
        });

        Schema::create('transfer_quote_locks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->foreignId('airport_id')->constrained('transfer_airports')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('transfer_zones')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('transfer_vehicle_types')->cascadeOnDelete();
            $table->string('direction', 20)->default('FROM_AIRPORT');
            $table->string('currency', 8)->default('TRY');
            $table->unsignedInteger('pax')->default(1);
            $table->dateTime('pickup_at');
            $table->dateTime('return_at')->nullable();
            $table->decimal('distance_km', 10, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('price_breakdown_json')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['expires_at', 'consumed_at'], 'transfer_quote_locks_expiry_idx');
        });

        Schema::create('transfer_bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_ref', 30)->unique();
            $table->foreignId('quote_lock_id')->nullable()->constrained('transfer_quote_locks')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->foreignId('agency_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('airport_id')->constrained('transfer_airports')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('transfer_zones')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('transfer_vehicle_types')->cascadeOnDelete();
            $table->string('direction', 20)->default('FROM_AIRPORT');
            $table->unsignedInteger('pax')->default(1);
            $table->dateTime('pickup_at');
            $table->dateTime('return_at')->nullable();
            $table->string('status', 30)->default('payment_pending');
            $table->string('currency', 8)->default('TRY');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('refundable_amount', 12, 2)->nullable();
            $table->json('price_snapshot_json')->nullable();
            $table->json('supplier_policy_snapshot_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['agency_user_id', 'status'], 'transfer_bookings_agency_status_idx');
            $table->index(['supplier_id', 'status'], 'transfer_bookings_supplier_status_idx');
        });

        Schema::create('transfer_payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_booking_id')->constrained('transfer_bookings')->cascadeOnDelete();
            $table->string('reference', 60)->unique();
            $table->string('provider', 40)->default('paynkolay');
            $table->string('provider_transaction_id', 80)->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('TRY');
            $table->json('request_payload_json')->nullable();
            $table->json('response_payload_json')->nullable();
            $table->json('callback_payload_json')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('transfer_settlement_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_booking_id')->constrained('transfer_bookings')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('transfer_suppliers')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('TRY');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'status'], 'transfer_settlement_supplier_status_idx');
        });

        $this->seedInitialCatalog();
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_settlement_entries');
        Schema::dropIfExists('transfer_payment_transactions');
        Schema::dropIfExists('transfer_bookings');
        Schema::dropIfExists('transfer_quote_locks');
        Schema::dropIfExists('transfer_cancellation_policies');
        Schema::dropIfExists('transfer_pricing_rules');
        Schema::dropIfExists('transfer_supplier_coverages');
        Schema::dropIfExists('transfer_vehicle_types');
        Schema::dropIfExists('transfer_suppliers');
        Schema::dropIfExists('transfer_zones');
        Schema::dropIfExists('transfer_airports');
    }

    private function seedInitialCatalog(): void
    {
        $now = now();

        $airports = [
            [
                'code' => 'IST',
                'name' => 'Istanbul Airport',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'latitude' => 41.2608,
                'longitude' => 28.7414,
                'sort_order' => 10,
            ],
            [
                'code' => 'SAW',
                'name' => 'Sabiha Gokcen Airport',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'latitude' => 40.8986,
                'longitude' => 29.3092,
                'sort_order' => 20,
            ],
        ];

        foreach ($airports as $airport) {
            DB::table('transfer_airports')->insert([
                ...$airport,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $airportIdByCode = DB::table('transfer_airports')->pluck('id', 'code')->all();

        $zoneSeeds = [
            ['airport_code' => 'IST', 'name' => 'Besiktas', 'city' => 'Istanbul', 'latitude' => 41.0430, 'longitude' => 29.0053, 'sort_order' => 10],
            ['airport_code' => 'IST', 'name' => 'Kadikoy', 'city' => 'Istanbul', 'latitude' => 40.9919, 'longitude' => 29.0280, 'sort_order' => 20],
            ['airport_code' => 'IST', 'name' => 'Taksim', 'city' => 'Istanbul', 'latitude' => 41.0369, 'longitude' => 28.9850, 'sort_order' => 30],
            ['airport_code' => 'IST', 'name' => 'Sisli', 'city' => 'Istanbul', 'latitude' => 41.0605, 'longitude' => 28.9872, 'sort_order' => 40],
            ['airport_code' => 'SAW', 'name' => 'Kadikoy', 'city' => 'Istanbul', 'latitude' => 40.9919, 'longitude' => 29.0280, 'sort_order' => 10],
            ['airport_code' => 'SAW', 'name' => 'Pendik', 'city' => 'Istanbul', 'latitude' => 40.8750, 'longitude' => 29.2345, 'sort_order' => 20],
            ['airport_code' => 'SAW', 'name' => 'Atasehir', 'city' => 'Istanbul', 'latitude' => 40.9833, 'longitude' => 29.1167, 'sort_order' => 30],
            ['airport_code' => 'SAW', 'name' => 'Besiktas', 'city' => 'Istanbul', 'latitude' => 41.0430, 'longitude' => 29.0053, 'sort_order' => 40],
        ];

        foreach ($zoneSeeds as $zone) {
            $airportId = (int) ($airportIdByCode[$zone['airport_code']] ?? 0);
            if ($airportId <= 0) {
                continue;
            }

            DB::table('transfer_zones')->insert([
                'airport_id' => $airportId,
                'name' => $zone['name'],
                'slug' => Str::slug($zone['name']),
                'city' => $zone['city'],
                'latitude' => $zone['latitude'],
                'longitude' => $zone['longitude'],
                'is_active' => true,
                'sort_order' => $zone['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $vehicleTypes = [
            ['code' => 'sedan', 'name' => 'Sedan', 'max_passengers' => 3, 'sort_order' => 10],
            ['code' => 'business_van', 'name' => 'Business Van', 'max_passengers' => 6, 'sort_order' => 20],
            ['code' => 'minibus', 'name' => 'Minibus', 'max_passengers' => 12, 'sort_order' => 30],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            DB::table('transfer_vehicle_types')->insert([
                ...$vehicleType,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};

