<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Acentelerin gruprezervasyonlari.com'a katılım kayıtları
        Schema::create('b2c_agency_subscriptions', function (Blueprint $table): void {
            $table->id();

            // Hangi acente
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Acentenin transfer supplier kaydı (transfer hizmetleri için)
            $table->foreignId('transfer_supplier_id')
                  ->nullable()
                  ->constrained('transfer_suppliers')
                  ->nullOnDelete();

            // Başvuru durumu
            $table->enum('status', [
                'pending',    // Başvuru yapıldı, inceleniyor
                'approved',   // Onaylandı, aktif
                'rejected',   // Reddedildi
                'suspended',  // Askıya alındı (ödeme yok, kural ihlali vb.)
            ])->default('pending');

            // Hangi hizmet türleri için başvuruyor
            $table->json('service_types_json')->nullable(); // ['transfer', 'leisure', 'charter']

            // Kurulum/katılım ücreti
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->char('fee_currency', 3)->default('EUR');
            $table->string('fee_payment_ref')->nullable();  // Paynkolay referansı
            $table->timestamp('fee_paid_at')->nullable();

            // Komisyon ayarı (null ise config/b2c.php'deki varsayılan kullanılır)
            $table->decimal('commission_pct', 5, 2)->nullable()
                  ->comment('Transfer için özel komisyon oranı (%); null = varsayılan');

            // Superadmin işlemleri
            $table->foreignId('reviewed_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();

            // Abonelik geçerlilik (null = süresiz)
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();

            $table->unique('user_id'); // Bir acente tek başvuru
            $table->index(['status', 'transfer_supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2c_agency_subscriptions');
    }
};
