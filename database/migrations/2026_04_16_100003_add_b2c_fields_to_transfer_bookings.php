<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_bookings', function (Blueprint $table): void {
            // Kaynak: B2B (acente paneli) mi B2C (gruprezervasyonlari.com) mi?
            $table->enum('source', ['b2b', 'b2c'])->default('b2b')->after('booking_ref');

            // B2C müşterisi (b2c_users tablosundan)
            $table->unsignedBigInteger('b2c_user_id')->nullable()->after('source');
            $table->foreign('b2c_user_id')
                  ->references('id')->on('b2c_users')
                  ->nullOnDelete();

            // B2C'de agency_user_id olmayacak, nullable yapıyoruz
            $table->unsignedBigInteger('agency_user_id')->nullable()->change();

            // B2C müşteri iletişim bilgileri (price_snapshot_json içinde de var ama direkt alan olsun)
            $table->string('b2c_contact_name')->nullable()->after('b2c_user_id');
            $table->string('b2c_contact_phone', 40)->nullable()->after('b2c_contact_name');
            $table->string('b2c_contact_email')->nullable()->after('b2c_contact_phone');

            $table->index(['b2c_user_id', 'status'], 'transfer_bookings_b2c_user_status_idx');
            $table->index('source', 'transfer_bookings_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_bookings', function (Blueprint $table): void {
            $table->dropForeign(['b2c_user_id']);
            $table->dropIndex('transfer_bookings_b2c_user_status_idx');
            $table->dropIndex('transfer_bookings_source_idx');
            $table->dropColumn(['source', 'b2c_user_id', 'b2c_contact_name', 'b2c_contact_phone', 'b2c_contact_email']);
            $table->unsignedBigInteger('agency_user_id')->nullable(false)->change();
        });
    }
};
