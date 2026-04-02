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
        Schema::table('acenteler', function (Blueprint $table) {
            if (!Schema::hasColumn('acenteler', 'faks')) {
                $table->string('faks', 60)->nullable()->after('telefon');
            }
            if (!Schema::hasColumn('acenteler', 'harita')) {
                $table->string('harita', 500)->nullable()->after('adres');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acenteler', function (Blueprint $table) {
            $table->dropColumn(['faks', 'harita']);
        });
    }
};
