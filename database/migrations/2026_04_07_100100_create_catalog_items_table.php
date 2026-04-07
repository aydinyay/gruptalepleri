<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();

            // Kategori ilişkisi
            $table->foreignId('category_id')->nullable()->constrained('catalog_categories')->nullOnDelete();

            // Ürün sahibi
            // owner_type = 'platform' → Grup Talepleri'nin kendi ürünü
            // owner_type = 'supplier' → tedarikçi acenteye ait (supplier_id = users.id)
            $table->enum('owner_type', ['platform', 'supplier'])->default('platform');
            $table->unsignedBigInteger('supplier_id')->nullable(); // → users.id (acente)
            $table->index('supplier_id');

            // Ürün tipi — hangi hizmet kategorisinde
            $table->enum('product_type', [
                'transfer',
                'charter',
                'leisure',
                'tour',
                'hotel',
                'visa',
                'other',
            ])->default('other');

            // Mevcut tablolara pointer (veri kopyalanmaz, işaret edilir)
            // transfer  → transfer_zones.id veya transfer_airports.id
            // charter   → charter_preset_packages.id
            // leisure   → leisure_package_templates.id
            // tour/hotel/visa → gelecekte eklenecek tablolara
            $table->string('reference_type')->nullable(); // ör: 'transfer_zone', 'charter_preset'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->index(['reference_type', 'reference_id']);

            // İçerik
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_desc')->nullable();
            $table->longText('full_desc')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery_json')->nullable();        // Çoklu görsel URL dizisi

            // Fiyatlandırma tipi
            // fixed   → sepete ekle, direkt satın al
            // quote   → "Fiyat Al" formu → admin fiyat döndürür → müşteri onaylar
            // request → talep oluştur → operasyon devralır (grup uçuş gibi)
            $table->enum('pricing_type', ['fixed', 'quote', 'request'])->default('quote');
            $table->decimal('base_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('TRY');

            // Yayın durumu
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);    // Ana sayfada öne çıkar
            $table->boolean('is_published')->default(false);   // Admin onayı olmadan false kalır
            $table->timestamp('published_at')->nullable();

            // Konum / destinasyon
            $table->string('destination_city')->nullable();
            $table->string('destination_country')->nullable();

            // Süre
            $table->unsignedTinyInteger('duration_days')->nullable();
            $table->unsignedTinyInteger('duration_hours')->nullable();

            // Kapasite
            $table->unsignedSmallInteger('min_pax')->nullable();
            $table->unsignedSmallInteger('max_pax')->nullable();

            // Sıralama ve SEO
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamps();

            // Sık kullanılan sorgular için index
            $table->index(['is_published', 'is_active', 'sort_order']);
            $table->index(['product_type', 'is_published']);
            $table->index(['destination_city', 'is_published']);
            $table->index(['is_featured', 'is_published']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
