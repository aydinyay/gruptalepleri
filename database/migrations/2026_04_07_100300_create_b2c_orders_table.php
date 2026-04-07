<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Siparişler
        Schema::create('b2c_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_ref')->unique();              // Örn: GRZ-2026-00001
            $table->foreignId('b2c_user_id')->constrained('b2c_users')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained('catalog_items');

            $table->enum('status', [
                'pending',          // Ödeme bekleniyor
                'pending_quote',    // Admin'den fiyat bekleniyor (quote tipi)
                'quote_sent',       // Admin fiyat gönderdi, müşteri onayı bekleniyor
                'confirmed',        // Onaylandı, ödeme alındı
                'in_operation',     // Operasyon başladı
                'completed',        // Tamamlandı
                'cancelled',        // İptal edildi
                'refunded',         // İade edildi
            ])->default('pending');

            $table->unsignedSmallInteger('pax_count')->default(1);
            $table->date('service_date')->nullable();
            $table->text('notes')->nullable();                 // Müşteri notu

            // Fiyat
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('TRY');

            // Ödeme durumu
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();

            // Operasyon
            $table->timestamp('supplier_notified_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('admin_note')->nullable();            // İç not (müşteriye görünmez)

            $table->timestamps();

            $table->index(['b2c_user_id', 'status']);
            $table->index(['status', 'payment_status']);
            $table->index('catalog_item_id');
        });

        // Sipariş yolcuları
        Schema::create('b2c_order_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2c_order_id')->constrained('b2c_orders')->cascadeOnDelete();
            $table->enum('type', ['yetiskin', 'cocuk', 'infant'])->default('yetiskin');
            $table->string('ad');
            $table->string('soyad');
            $table->string('kimlik_no')->nullable();
            $table->enum('kimlik_tipi', ['tc', 'pasaport'])->nullable();
            $table->date('dogum_tarihi')->nullable();
            $table->string('uyruk')->nullable();
            $table->enum('cinsiyet', ['erkek', 'kadin'])->nullable();
            $table->timestamps();
        });

        // B2C Ödemeleri
        Schema::create('b2c_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2c_order_id')->constrained('b2c_orders')->cascadeOnDelete();
            $table->string('reference')->unique();             // İç referans no
            $table->string('provider')->default('paynkolay');
            $table->string('provider_transaction_id')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'refunded',
                'partial_refunded',
            ])->default('pending');

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('TRY');

            // Gateway payload'ları (debugging ve iade için)
            $table->json('request_payload_json')->nullable();
            $table->json('response_payload_json')->nullable();
            $table->json('callback_payload_json')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['b2c_order_id', 'status']);
        });

        // Tedarikçi başvuruları (gruprezervasyonlari.com'dan gelen)
        Schema::create('supplier_applications', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_name');
            $table->string('company_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->json('service_types_json')->nullable();   // Hangi hizmet tipi sunacaklar
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable(); // → users.id
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Hızlı teklif lead'leri (ana sayfadan gelen)
        Schema::create('b2c_quick_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('service_type')->nullable();       // Hangi hizmet türü
            $table->text('notes')->nullable();
            $table->boolean('is_contacted')->default(false);
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2c_quick_leads');
        Schema::dropIfExists('supplier_applications');
        Schema::dropIfExists('b2c_payments');
        Schema::dropIfExists('b2c_order_passengers');
        Schema::dropIfExists('b2c_orders');
    }
};
