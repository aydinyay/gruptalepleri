<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('requests', function (Blueprint $table) {
        $table->string('group_company_name')->nullable()->after('agency_name');
        $table->string('flight_purpose')->nullable()->after('email');
        $table->string('trip_type')->default('one_way')->after('flight_purpose');
        $table->string('preferred_airline')->nullable()->after('trip_type');
        $table->boolean('hotel_needed')->default(false)->after('preferred_airline');
        $table->boolean('visa_needed')->default(false)->after('hotel_needed');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            //
        });
    }
};
