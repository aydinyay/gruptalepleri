<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('aktif_adim', [
                'teklif_bekleniyor',
                'karar_bekleniyor',
                'odeme_plani_bekleniyor',
                'odeme_bekleniyor',
                'odeme_gecikti',
                'odeme_alindi_devam',
                'biletleme_bekleniyor',
                'tamamlandi',
            ])->nullable()->after('status');

            $table->enum('odeme_durumu', [
                'yok',
                'planli',
                'kismi_odendi',
                'gecikti',
                'tamamlandi',
            ])->default('yok')->after('aktif_adim');
        });

        // Mevcut veriyi dönüştür
        // Her request için aktif_adim ve odeme_durumu hesapla

        // Önce odeme_durumu doldur
        // tamamlandi: tüm paymentlar alindi veya hiç bekleniyor/aktif/taslak yok ama alindi var
        DB::statement("
            UPDATE requests r
            LEFT JOIN (
                SELECT request_id,
                    SUM(CASE WHEN status IN ('aktif','taslak','gecikti') THEN 1 ELSE 0 END) as bekleyen,
                    SUM(CASE WHEN status = 'alindi' THEN 1 ELSE 0 END) as odenen,
                    COUNT(*) as toplam
                FROM request_payments
                GROUP BY request_id
            ) p ON r.id = p.request_id
            SET r.odeme_durumu = CASE
                WHEN p.request_id IS NULL THEN 'yok'
                WHEN p.bekleyen = 0 AND p.odenen > 0 THEN 'tamamlandi'
                WHEN p.bekleyen > 0 AND p.odenen > 0 THEN 'kismi_odendi'
                WHEN p.bekleyen > 0 AND p.odenen = 0 THEN 'planli'
                ELSE 'yok'
            END
        ");

        // gecikti: is_active=true olan payment'ın due_date geçmişse
        DB::statement("
            UPDATE requests r
            INNER JOIN request_payments rp ON rp.request_id = r.id AND rp.is_active = 1
            SET r.odeme_durumu = 'gecikti'
            WHERE rp.due_date < CURDATE() AND rp.status IN ('aktif','gecikti')
        ");

        // aktif_adim doldur
        DB::statement("
            UPDATE requests r
            LEFT JOIN (
                SELECT request_id, COUNT(*) as offer_count
                FROM offers
                WHERE durum != 'reddedildi'
                GROUP BY request_id
            ) o ON r.id = o.request_id
            LEFT JOIN (
                SELECT request_id, COUNT(*) as kabul_count
                FROM offers WHERE durum = 'kabul_edildi'
                GROUP BY request_id
            ) k ON r.id = k.request_id
            SET r.aktif_adim = CASE
                WHEN r.status = 'biletlendi'                        THEN 'tamamlandi'
                WHEN r.status IN ('iptal','olumsuz','iade')         THEN 'tamamlandi'
                WHEN r.odeme_durumu = 'tamamlandi'                  THEN 'biletleme_bekleniyor'
                WHEN r.odeme_durumu = 'gecikti'                     THEN 'odeme_gecikti'
                WHEN r.odeme_durumu = 'kismi_odendi'                THEN 'odeme_alindi_devam'
                WHEN r.odeme_durumu = 'planli'                      THEN 'odeme_bekleniyor'
                WHEN k.kabul_count > 0 AND r.odeme_durumu = 'yok'  THEN 'odeme_plani_bekleniyor'
                WHEN o.offer_count > 0                              THEN 'karar_bekleniyor'
                ELSE 'teklif_bekleniyor'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['aktif_adim', 'odeme_durumu']);
        });
    }
};
