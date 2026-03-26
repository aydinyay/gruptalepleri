<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createPackageTemplatesTableIfMissing();
        $this->createExtraOptionsTableIfMissing();
        $this->createMediaAssetsTableIfMissing();
        $this->createRequestExtrasTableIfMissing();
        $this->createSupplierQuotesTableIfMissing();
        $this->createClientOffersTableIfMissing();
        $this->createBookingsTableIfMissing();

        $this->seedPackageTemplatesIfEmpty();
        $this->seedExtraOptionsIfEmpty();
    }

    public function down(): void
    {
        // Self-healing migration: do not drop tables on rollback.
    }

    private function createPackageTemplatesTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_package_templates')) {
            return;
        }

        Schema::create('leisure_package_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('product_type', 30);
            $table->string('code', 40);
            $table->string('level', 30);
            $table->string('name_tr');
            $table->string('name_en');
            $table->string('summary_tr', 255)->nullable();
            $table->string('summary_en', 255)->nullable();
            $table->json('includes_tr')->nullable();
            $table->json('includes_en')->nullable();
            $table->json('excludes_tr')->nullable();
            $table->json('excludes_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->unique(['product_type', 'code']);
            $table->index(['product_type', 'is_active', 'sort_order'], 'lpt_type_active_sort_idx');
        });
    }

    private function createExtraOptionsTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_extra_options')) {
            return;
        }

        Schema::create('leisure_extra_options', function (Blueprint $table): void {
            $table->id();
            $table->string('product_type', 30)->nullable();
            $table->string('category', 40)->default('upsell');
            $table->string('code', 50);
            $table->string('title_tr');
            $table->string('title_en');
            $table->string('description_tr', 255)->nullable();
            $table->string('description_en', 255)->nullable();
            $table->boolean('default_included')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->unique(['code', 'product_type']);
            $table->index(['product_type', 'category', 'is_active']);
        });
    }

    private function createMediaAssetsTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_media_assets')) {
            return;
        }

        Schema::create('leisure_media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('product_type', 30)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('media_type', 20)->default('photo');
            $table->string('source_type', 20)->default('upload');
            $table->string('title_tr');
            $table->string('title_en')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->json('tags_json')->nullable();
            $table->unsignedSmallInteger('capacity_min')->nullable();
            $table->unsignedSmallInteger('capacity_max')->nullable();
            $table->string('luxury_level', 30)->nullable();
            $table->string('usage_type', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index(['product_type', 'category', 'is_active']);
        });
    }

    private function createRequestExtrasTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_request_extras')) {
            return;
        }

        Schema::create('leisure_request_extras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leisure_request_id')->constrained('leisure_requests')->cascadeOnDelete();
            $table->foreignId('extra_option_id')->nullable()->constrained('leisure_extra_options')->nullOnDelete();
            $table->string('title');
            $table->text('agency_note')->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('currency', 8)->default('TRY');
            $table->string('status', 30)->default('requested');
            $table->timestamps();

            $table->index(['leisure_request_id', 'status']);
        });
    }

    private function createSupplierQuotesTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_supplier_quotes')) {
            return;
        }

        Schema::create('leisure_supplier_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leisure_request_id')->constrained('leisure_requests')->cascadeOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_contact_name')->nullable();
            $table->string('supplier_email')->nullable();
            $table->string('supplier_phone', 40)->nullable();
            $table->string('supplier_package_name')->nullable();
            $table->decimal('cost_total', 12, 2);
            $table->string('currency', 8)->default('EUR');
            $table->json('includes_json')->nullable();
            $table->json('excludes_json')->nullable();
            $table->text('supplier_note')->nullable();
            $table->text('operation_note')->nullable();
            $table->string('status', 30)->default('received');
            $table->timestamps();

            $table->index(['leisure_request_id', 'status']);
        });
    }

    private function createClientOffersTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_client_offers')) {
            return;
        }

        Schema::create('leisure_client_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leisure_request_id')->constrained('leisure_requests')->cascadeOnDelete();
            $table->foreignId('supplier_quote_id')->nullable()->constrained('leisure_supplier_quotes')->nullOnDelete();
            $table->foreignId('package_template_id')->nullable()->constrained('leisure_package_templates')->nullOnDelete();
            $table->string('package_label');
            $table->decimal('total_price', 12, 2);
            $table->decimal('per_person_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('EUR');
            $table->json('includes_snapshot')->nullable();
            $table->json('excludes_snapshot')->nullable();
            $table->json('extras_snapshot')->nullable();
            $table->json('media_snapshot')->nullable();
            $table->text('timeline_tr')->nullable();
            $table->text('timeline_en')->nullable();
            $table->text('offer_note_tr')->nullable();
            $table->text('offer_note_en')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['leisure_request_id', 'status']);
        });
    }

    private function createBookingsTableIfMissing(): void
    {
        if (Schema::hasTable('leisure_bookings')) {
            return;
        }

        Schema::create('leisure_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leisure_request_id')->constrained('leisure_requests')->cascadeOnDelete();
            $table->foreignId('client_offer_id')->constrained('leisure_client_offers')->cascadeOnDelete();
            $table->string('status', 30)->default('pending_payment');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->text('operation_note')->nullable();
            $table->timestamps();

            $table->index(['leisure_request_id', 'status']);
        });
    }

    private function seedPackageTemplatesIfEmpty(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        if (DB::table('leisure_package_templates')->count() > 0) {
            return;
        }

        DB::table('leisure_package_templates')->insert([
            [
                'product_type' => 'dinner_cruise',
                'code' => 'standard',
                'level' => 'standard',
                'name_tr' => 'Standart',
                'name_en' => 'Standard',
                'summary_tr' => 'Temel menu ve standart masa duzeni.',
                'summary_en' => 'Core menu and standard table setup.',
                'includes_tr' => json_encode(['Shuttle transfer', 'Standart masa duzeni', 'Aksam yemegi']),
                'includes_en' => json_encode(['Shuttle transfer', 'Standard table setup', 'Dinner service']),
                'excludes_tr' => json_encode(['VIP transfer', 'Ozel susleme']),
                'excludes_en' => json_encode(['VIP transfer', 'Special decoration']),
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => 'dinner_cruise',
                'code' => 'vip',
                'level' => 'vip',
                'name_tr' => 'VIP',
                'name_en' => 'VIP',
                'summary_tr' => 'Daha iyi masa konumu ve gelistirilmis servis.',
                'summary_en' => 'Better table positioning and upgraded service.',
                'includes_tr' => json_encode(['Shuttle transfer', 'On sira masa', 'VIP menu']),
                'includes_en' => json_encode(['Shuttle transfer', 'Front-row table', 'VIP menu']),
                'excludes_tr' => json_encode(['Ozel DJ', 'Premium bar']),
                'excludes_en' => json_encode(['Private DJ', 'Premium bar']),
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => 'dinner_cruise',
                'code' => 'premium',
                'level' => 'premium',
                'name_tr' => 'Premium',
                'name_en' => 'Premium',
                'summary_tr' => 'Ust segment masa, servis ve ozel deneyim.',
                'summary_en' => 'Top-tier seating, service and premium experience.',
                'includes_tr' => json_encode(['Shuttle transfer', 'Premium masa', 'Premium menu']),
                'includes_en' => json_encode(['Shuttle transfer', 'Premium table', 'Premium menu']),
                'excludes_tr' => json_encode(['Private yacht', 'Ozel etkinlik kurulumu']),
                'excludes_en' => json_encode(['Private yacht', 'Custom event setup']),
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => 'yacht',
                'code' => 'standard',
                'level' => 'standard',
                'name_tr' => 'Standart Charter',
                'name_en' => 'Standard Charter',
                'summary_tr' => 'Temel yat kiralama paketi.',
                'summary_en' => 'Core yacht charter package.',
                'includes_tr' => json_encode(['Shuttle transfer', 'Standart catering', 'Temel rota plani']),
                'includes_en' => json_encode(['Shuttle transfer', 'Standard catering', 'Basic route planning']),
                'excludes_tr' => json_encode(['DJ', 'Ozel susleme']),
                'excludes_en' => json_encode(['DJ', 'Special decoration']),
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => 'yacht',
                'code' => 'vip',
                'level' => 'vip',
                'name_tr' => 'VIP Charter',
                'name_en' => 'VIP Charter',
                'summary_tr' => 'Daha guclu servis, iyi rota ve etkinlik deneyimi.',
                'summary_en' => 'Upgraded service, route and event experience.',
                'includes_tr' => json_encode(['Shuttle transfer', 'VIP catering', 'Standart etkinlik kurulumu']),
                'includes_en' => json_encode(['Shuttle transfer', 'VIP catering', 'Standard event setup']),
                'excludes_tr' => json_encode(['Premium bar', 'Ozel sanatci']),
                'excludes_en' => json_encode(['Premium bar', 'Private performer']),
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => 'yacht',
                'code' => 'premium',
                'level' => 'premium',
                'name_tr' => 'Premium Charter',
                'name_en' => 'Premium Charter',
                'summary_tr' => 'Ozel kullanim ve premium servis seti.',
                'summary_en' => 'Private use and premium service set.',
                'includes_tr' => json_encode(['Shuttle transfer', 'Premium catering', 'Ozel event setup']),
                'includes_en' => json_encode(['Shuttle transfer', 'Premium catering', 'Private event setup']),
                'excludes_tr' => json_encode(['Canli muzik', 'Ozel foto-video produksiyon']),
                'excludes_en' => json_encode(['Live music', 'Private photo-video production']),
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedExtraOptionsIfEmpty(): void
    {
        if (! Schema::hasTable('leisure_extra_options')) {
            return;
        }

        if (DB::table('leisure_extra_options')->count() > 0) {
            return;
        }

        DB::table('leisure_extra_options')->insert([
            [
                'product_type' => null,
                'category' => 'transfer',
                'code' => 'shuttle_transfer',
                'title_tr' => 'Shuttle Transfer',
                'title_en' => 'Shuttle Transfer',
                'description_tr' => 'Varsayilan transfer hizmeti, fiyata dahil kabul edilir.',
                'description_en' => 'Default transfer service treated as included.',
                'default_included' => true,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => null,
                'category' => 'transfer',
                'code' => 'vip_transfer',
                'title_tr' => 'VIP Transfer',
                'title_en' => 'VIP Transfer',
                'description_tr' => 'Ust segment ozel transfer secenegi.',
                'description_en' => 'Premium private transfer option.',
                'default_included' => false,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => null,
                'category' => 'transfer',
                'code' => 'private_transfer',
                'title_tr' => 'Private Transfer',
                'title_en' => 'Private Transfer',
                'description_tr' => 'Arac sadece bu grup icin planlanir.',
                'description_en' => 'Dedicated transfer reserved for this group.',
                'default_included' => false,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => null,
                'category' => 'upsell',
                'code' => 'decoration',
                'title_tr' => 'Susleme',
                'title_en' => 'Decoration',
                'description_tr' => 'Masa veya mekan susleme hizmeti.',
                'description_en' => 'Table or venue decoration service.',
                'default_included' => false,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => null,
                'category' => 'upsell',
                'code' => 'dj',
                'title_tr' => 'DJ',
                'title_en' => 'DJ',
                'description_tr' => 'Canli DJ performansi.',
                'description_en' => 'Live DJ performance.',
                'default_included' => false,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_type' => null,
                'category' => 'upsell',
                'code' => 'photo_video',
                'title_tr' => 'Foto / Video',
                'title_en' => 'Photo / Video',
                'description_tr' => 'Profesyonel foto ve video cekimi.',
                'description_en' => 'Professional photo and video coverage.',
                'default_included' => false,
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
