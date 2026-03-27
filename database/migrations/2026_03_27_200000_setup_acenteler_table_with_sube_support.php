<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acenteler')) {
            // Tablo yoksa sıfırdan oluştur (lokal dev / temiz kurulum)
            Schema::create('acenteler', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('belge_no', 20)->nullable()->index();
                $table->unsignedSmallInteger('sube_sira')->default(0);
                $table->tinyInteger('is_sube')->default(0);
                $table->string('acente_unvani')->nullable();
                $table->string('ticari_unvan')->nullable();
                $table->string('grup', 5)->nullable();
                $table->string('il', 60)->nullable();
                $table->string('il_ilce', 100)->nullable();
                $table->string('telefon', 60)->nullable();
                $table->string('eposta', 150)->nullable()->index();
                $table->string('adres', 500)->nullable();
                $table->string('btk', 100)->nullable();

                $table->unique(['belge_no', 'sube_sira'], 'acenteler_belge_sube_unique');
            });

            return;
        }

        // Tablo zaten varsa (production) — eksik kolonları ve index'i ekle

        // sube_sira kolonu yoksa ekle
        if (!Schema::hasColumn('acenteler', 'sube_sira')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->unsignedSmallInteger('sube_sira')->default(0)->after('belge_no');
            });
        }

        // is_sube kolonu yoksa ekle
        if (!Schema::hasColumn('acenteler', 'is_sube')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->tinyInteger('is_sube')->default(0)->after('sube_sira');
            });
        }

        // adres ve btk kolonları yoksa ekle (scraper bunları da çekiyor)
        if (!Schema::hasColumn('acenteler', 'adres')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->string('adres', 500)->nullable();
            });
        }

        if (!Schema::hasColumn('acenteler', 'btk')) {
            Schema::table('acenteler', function (Blueprint $table) {
                $table->string('btk', 100)->nullable();
            });
        }

        // Mevcut unique index'i kaldır (belge_no üzerinde varsa)
        try {
            $indexes = DB::select("SHOW INDEX FROM acenteler WHERE Column_name = 'belge_no' AND Non_unique = 0");
            foreach ($indexes as $index) {
                if ($index->Key_name !== 'PRIMARY') {
                    DB::statement("ALTER TABLE acenteler DROP INDEX `{$index->Key_name}`");
                }
            }
        } catch (\Throwable) {
            // Index yoksa hata verme
        }

        // (belge_no, sube_sira) composite unique yoksa ekle
        try {
            $exists = DB::select(
                "SHOW INDEX FROM acenteler WHERE Key_name = 'acenteler_belge_sube_unique'"
            );
            if (empty($exists)) {
                DB::statement('ALTER TABLE acenteler ADD UNIQUE KEY `acenteler_belge_sube_unique` (`belge_no`, `sube_sira`)');
            }
        } catch (\Throwable) {
            // Zaten varsa geç
        }
    }

    public function down(): void
    {
        // Sadece eklenen kolonları geri al (tabloyu silme — legacy data korunur)
        Schema::table('acenteler', function (Blueprint $table) {
            $table->dropColumn(['sube_sira', 'is_sube', 'adres', 'btk']);
        });
    }
};
