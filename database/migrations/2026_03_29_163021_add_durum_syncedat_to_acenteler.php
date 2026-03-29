<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acenteler', function (Blueprint $table) {
            if (!Schema::hasColumn('acenteler', 'durum')) {
                $table->string('durum', 20)->nullable()->after('internal_id');
            }
            if (!Schema::hasColumn('acenteler', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('durum');
            }
        });

        // Mevcut bakanlik kayıtları GEÇERLİ (scraper sadece GEÇERLİ çekiyordu)
        DB::table('acenteler')
            ->where('kaynak', 'bakanlik')
            ->update(['durum' => 'GEÇERLİ']);
    }

    public function down(): void
    {
        Schema::table('acenteler', function (Blueprint $table) {
            $table->dropColumn(['durum', 'synced_at']);
        });
    }
};
