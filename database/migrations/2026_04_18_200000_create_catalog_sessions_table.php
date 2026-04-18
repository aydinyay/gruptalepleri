<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('session_time')->nullable();          // null = tüm gün / sadece tarih
            $table->unsignedSmallInteger('capacity')->nullable(); // null = sınırsız
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->decimal('price_override', 10, 2)->nullable(); // null = ürün fiyatını kullan
            $table->string('label', 100)->nullable();          // "Türkçe Seans", "VIP" gibi
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['catalog_item_id', 'session_date', 'is_active']);
        });

        Schema::table('b2c_orders', function (Blueprint $table) {
            $table->foreignId('session_id')
                ->nullable()
                ->after('catalog_item_id')
                ->constrained('catalog_sessions')
                ->nullOnDelete();
            $table->string('session_label', 150)->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('b2c_orders', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropColumn(['session_id', 'session_label']);
        });
        Schema::dropIfExists('catalog_sessions');
    }
};
