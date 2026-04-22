<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('venue_address', 500)->nullable()->after('destination_country');
            $table->decimal('venue_lat', 10, 7)->nullable()->after('venue_address');
            $table->decimal('venue_lng', 10, 7)->nullable()->after('venue_lat');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['venue_address', 'venue_lat', 'venue_lng']);
        });
    }
};
