<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sigorta Ödemeleri ─────────────────────────────────────────────────
        Schema::create('sigorta_odemeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sigorta_police_id')
                  ->nullable()
                  ->constrained('sigorta_policeler')
                  ->nullOnDelete();
            $table->foreignId('sigorta_batch_job_id')
                  ->nullable()
                  ->constrained('sigorta_batch_jobs')
                  ->nullOnDelete();
            $table->string('kanal', 10)->default('b2c');          // b2b / b2c
            $table->string('internal_reference', 80)->unique();    // SPY-... veya BPY-...
            $table->string('provider_reference', 120)->nullable(); // Paynkolay ref kodu
            $table->decimal('amount_try', 10, 2);                  // Ödenen TL tutarı
            $table->string('status', 20)->default('pending');      // pending/approved/rejected
            $table->json('request_payload_json')->nullable();
            $table->json('callback_payload_json')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        // ── sigorta_policeler.durum ENUM'a ödeme durumları ekle ──────────────
        DB::statement("ALTER TABLE sigorta_policeler MODIFY COLUMN durum ENUM(
            'odeme_bekleniyor',
            'odeme_basarisiz',
            'teklif_gonderildi',
            'teklif_alindi',
            'police_isleniyor',
            'tamamlandi',
            'iptal_bekliyor',
            'iptal',
            'hata'
        ) NOT NULL DEFAULT 'teklif_gonderildi'");
    }

    public function down(): void
    {
        Schema::dropIfExists('sigorta_odemeler');

        DB::statement("ALTER TABLE sigorta_policeler MODIFY COLUMN durum ENUM(
            'teklif_gonderildi',
            'teklif_alindi',
            'police_isleniyor',
            'tamamlandi',
            'iptal_bekliyor',
            'iptal',
            'hata'
        ) NOT NULL DEFAULT 'teklif_gonderildi'");
    }
};
