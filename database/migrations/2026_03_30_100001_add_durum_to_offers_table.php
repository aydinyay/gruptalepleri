<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. durum kolonu ekle
        Schema::table('offers', function (Blueprint $table) {
            $table->enum('durum', ['beklemede', 'kabul_edildi', 'reddedildi', 'gizlendi'])
                ->default('beklemede')
                ->after('is_accepted');
        });

        // 2. Mevcut veriyi dönüştür
        DB::statement("
            UPDATE offers
            SET durum = CASE
                WHEN is_accepted = 1                        THEN 'kabul_edildi'
                WHEN is_visible  = 0 AND is_accepted = 0   THEN 'gizlendi'
                ELSE 'beklemede'
            END
        ");

        // 3. Eski kolonları kaldır
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'is_accepted', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('offer_text');
            $table->boolean('is_accepted')->default(false)->after('is_visible');
            $table->timestamp('accepted_at')->nullable()->after('is_accepted');
        });

        DB::statement("
            UPDATE offers
            SET is_accepted = (durum = 'kabul_edildi'),
                is_visible  = (durum != 'gizlendi')
        ");

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('durum');
        });
    }
};
