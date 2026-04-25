<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('locale', 5)->default('tr')->after('source_channel');
        });

        Schema::table('transfer_bookings', function (Blueprint $table) {
            $table->string('locale', 5)->default('tr')->after('id');
        });

        Schema::table('b2c_quick_leads', function (Blueprint $table) {
            $table->string('locale', 5)->default('tr')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('requests', fn($t) => $t->dropColumn('locale'));
        Schema::table('transfer_bookings', fn($t) => $t->dropColumn('locale'));
        Schema::table('b2c_quick_leads', fn($t) => $t->dropColumn('locale'));
    }
};
