<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM'u genişletmek için ALTER TABLE kullan
        DB::statement("
            ALTER TABLE request_payments
            MODIFY COLUMN status ENUM('taslak','aktif','gecikti','alindi','iade')
            NOT NULL DEFAULT 'taslak'
        ");

        // is_active kolonu ekle
        Schema::table('request_payments', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('status');
        });

        // Mevcut veriyi dönüştür
        // bekleniyor + due_date var → aktif
        DB::statement("
            UPDATE request_payments
            SET status = 'aktif'
            WHERE status = 'bekleniyor' AND due_date IS NOT NULL
        ");

        // bekleniyor + due_date yok → taslak
        DB::statement("
            UPDATE request_payments
            SET status = 'taslak'
            WHERE status = 'bekleniyor' AND due_date IS NULL
        ");

        // Her request için: sequence en küçük aktif payment → is_active=true
        DB::statement("
            UPDATE request_payments rp
            INNER JOIN (
                SELECT request_id, MIN(sequence) as min_seq
                FROM request_payments
                WHERE status IN ('aktif', 'gecikti')
                GROUP BY request_id
            ) sub ON rp.request_id = sub.request_id AND rp.sequence = sub.min_seq
            SET rp.is_active = 1
            WHERE rp.status IN ('aktif', 'gecikti')
        ");
    }

    public function down(): void
    {
        // is_active kaldır
        Schema::table('request_payments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // ENUM'u eski haline döndür
        DB::statement("
            UPDATE request_payments SET status = 'bekleniyor'
            WHERE status IN ('taslak', 'aktif', 'gecikti')
        ");

        DB::statement("
            ALTER TABLE request_payments
            MODIFY COLUMN status ENUM('bekleniyor','alindi','iade')
            NOT NULL DEFAULT 'alindi'
        ");
    }
};
