<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add new statuses
        DB::statement("ALTER TABLE sigorta_batch_jobs
            MODIFY COLUMN durum ENUM(
                'bekliyor',
                'fiyat_hesaplaniyor',
                'odeme_bekleniyor',
                'isleniyor',
                'tamamlandi',
                'hata'
            ) NOT NULL DEFAULT 'bekliyor'");

        Schema::table('sigorta_batch_jobs', function (Blueprint $table) {
            $table->decimal('total_amount_try', 10, 2)->nullable()->after('durum');
            $table->json('fiyatlanmis_satirlar')->nullable()->after('bekleyen_satirlar');
        });
    }

    public function down(): void
    {
        Schema::table('sigorta_batch_jobs', function (Blueprint $table) {
            $table->dropColumn(['total_amount_try', 'fiyatlanmis_satirlar']);
        });

        DB::statement("ALTER TABLE sigorta_batch_jobs
            MODIFY COLUMN durum ENUM(
                'bekliyor',
                'isleniyor',
                'tamamlandi',
                'hata'
            ) NOT NULL DEFAULT 'bekliyor'");
    }
};
