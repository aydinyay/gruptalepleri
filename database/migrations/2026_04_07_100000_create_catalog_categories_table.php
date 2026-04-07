<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();

            // Hiyerarşik kategori (üst kategori → alt kategori)
            // Örn: Turlar → Yurt İçi Turlar, Yurt Dışı Turlar
            $table->foreignId('parent_id')->nullable()->constrained('catalog_categories')->nullOnDelete();

            $table->string('name');                         // Görünen ad: "Havalimanı Transferi"
            $table->string('slug')->unique();               // URL: "havalimanı-transferi"
            $table->text('description')->nullable();
            $table->string('icon')->nullable();             // Lucide veya Font Awesome icon adı
            $table->string('cover_image')->nullable();      // Kategori kapak görseli

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_categories');
    }
};
