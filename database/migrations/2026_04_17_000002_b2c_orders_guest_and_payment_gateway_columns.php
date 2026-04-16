<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // b2c_orders: guest desteği
        DB::statement('ALTER TABLE b2c_orders MODIFY b2c_user_id BIGINT UNSIGNED NULL');

        Schema::table('b2c_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('b2c_orders', 'guest_name')) {
                $table->string('guest_name', 120)->nullable()->after('b2c_user_id');
            }
            if (! Schema::hasColumn('b2c_orders', 'guest_phone')) {
                $table->string('guest_phone', 30)->nullable()->after('guest_name');
            }
            if (! Schema::hasColumn('b2c_orders', 'guest_email')) {
                $table->string('guest_email', 180)->nullable()->after('guest_phone');
            }
            if (! Schema::hasColumn('b2c_orders', 'item_title')) {
                $table->string('item_title', 255)->nullable()->after('catalog_item_id');
            }
            if (! Schema::hasColumn('b2c_orders', 'product_type')) {
                $table->string('product_type', 30)->nullable()->after('item_title');
            }
            if (! Schema::hasColumn('b2c_orders', 'event_type')) {
                $table->string('event_type', 120)->nullable()->after('notes');
            }
        });

        // b2c_payments: Paynkolay gateway kolonları
        Schema::table('b2c_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('b2c_payments', 'internal_reference')) {
                $table->string('internal_reference', 80)->nullable()->after('reference');
            }
            if (! Schema::hasColumn('b2c_payments', 'charged_try_amount')) {
                $table->decimal('charged_try_amount', 12, 2)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('b2c_payments', 'fx_rate')) {
                $table->decimal('fx_rate', 14, 6)->nullable()->after('charged_try_amount');
            }
            if (! Schema::hasColumn('b2c_payments', 'fx_timestamp')) {
                $table->timestamp('fx_timestamp')->nullable()->after('fx_rate');
            }
            if (! Schema::hasColumn('b2c_payments', 'source_currency')) {
                $table->string('source_currency', 8)->nullable()->after('fx_timestamp');
            }
            if (! Schema::hasColumn('b2c_payments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void {}
};
