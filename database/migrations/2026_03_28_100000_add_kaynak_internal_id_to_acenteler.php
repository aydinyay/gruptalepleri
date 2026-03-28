<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // kaynak kolonu: hangi kaynaktan geldi? (tursab / bakanlik / manuel)
        if (!Schema::hasColumn('acenteler', 'kaynak')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->string('kaynak', 20)->nullable()->after('btk');
            });
        }

        // internal_id: Bakanlık sitesinin <tr id="..."> değeri — benzersiz dedup için
        if (!Schema::hasColumn('acenteler', 'internal_id')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->string('internal_id', 30)->nullable()->after('kaynak');
            });
        }

        // Mevcut kayıtları 'tursab' olarak işaretle
        DB::table('acenteler')->whereNull('kaynak')->update(['kaynak' => 'tursab']);

        // (belge_no, sube_sira) unique kısıtını kaldır
        // Kullanıcı isteği: belge_no benzersiz olmasın, aynı belge nolu şubeler de indirilsin
        try {
            $exists = DB::select("SHOW INDEX FROM acenteler WHERE Key_name = 'acenteler_belge_sube_unique'");
            if (!empty($exists)) {
                DB::statement("ALTER TABLE acenteler DROP INDEX `acenteler_belge_sube_unique`");
            }
        } catch (\Throwable) {
            // Index yoksa geç
        }

        // internal_id üzerinde index (Bakanlık dedup sorguları için)
        try {
            $idxExists = DB::select("SHOW INDEX FROM acenteler WHERE Key_name = 'acenteler_internal_id_idx'");
            if (empty($idxExists)) {
                DB::statement("ALTER TABLE acenteler ADD INDEX `acenteler_internal_id_idx` (`internal_id`)");
            }
        } catch (\Throwable) {
            // Zaten varsa geç
        }
    }

    public function down(): void
    {
        Schema::table('acenteler', function (Blueprint $table) {
            $table->dropColumn(['kaynak', 'internal_id']);
        });

        // Unique index'i geri koy
        try {
            DB::statement('ALTER TABLE acenteler ADD UNIQUE KEY `acenteler_belge_sube_unique` (`belge_no`, `sube_sira`)');
        } catch (\Throwable) {}
    }
};
