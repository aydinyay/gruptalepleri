<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charter_rfq_suppliers', function (Blueprint $table): void {
            $table->string('supplier_kind', 30)->default('operator')->after('service_types');
            $table->json('charter_models')->nullable()->after('supplier_kind');
            $table->unsignedSmallInteger('min_pax')->nullable()->after('charter_models');
            $table->unsignedSmallInteger('max_pax')->nullable()->after('min_pax');
            $table->unsignedSmallInteger('priority')->default(100)->after('max_pax');
            $table->boolean('is_premium_only')->default(false)->after('priority');
            $table->boolean('is_cargo_operator')->default(false)->after('is_premium_only');
            $table->unsignedSmallInteger('min_notice_hours')->nullable()->after('is_cargo_operator');

            $table->index(['is_active', 'priority']);
            $table->index(['supplier_kind', 'is_cargo_operator']);
            $table->index(['min_pax', 'max_pax']);
        });
    }

    public function down(): void
    {
        Schema::table('charter_rfq_suppliers', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'priority']);
            $table->dropIndex(['supplier_kind', 'is_cargo_operator']);
            $table->dropIndex(['min_pax', 'max_pax']);

            $table->dropColumn([
                'supplier_kind',
                'charter_models',
                'min_pax',
                'max_pax',
                'priority',
                'is_premium_only',
                'is_cargo_operator',
                'min_notice_hours',
            ]);
        });
    }
};

