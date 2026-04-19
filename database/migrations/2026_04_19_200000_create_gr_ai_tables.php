<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Misafir (anonim) kayıtları — UUID cookie ile tanımlama
        Schema::create('gr_ai_guests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('city', 80)->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();
        });

        // Kalıcı hafıza — kullanıcı/misafir başına öğrenilen tercihler
        Schema::create('gr_ai_memories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2c_user_id')->nullable()->index();
            $table->uuid('guest_uuid')->nullable()->index();
            $table->string('key', 80);           // ör: 'ilgi_alanlari', 'sehir', 'butce'
            $table->text('value');               // ör: 'yat, dinner cruise'
            $table->unsignedTinyInteger('confidence')->default(50); // 0-100
            $table->timestamps();

            // Her kullanıcı/misafir için her key bir kez olmalı
            $table->unique(['b2c_user_id', 'key'], 'gr_memory_user_key');
            $table->unique(['guest_uuid',  'key'], 'gr_memory_guest_key');

            $table->foreign('b2c_user_id')->references('id')->on('b2c_users')->onDelete('cascade');
        });

        // Sohbet geçmişi
        Schema::create('gr_ai_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2c_user_id')->nullable()->index();
            $table->uuid('guest_uuid')->nullable()->index();
            $table->enum('role', ['user', 'assistant']);
            $table->text('message');
            $table->json('suggested_slugs')->nullable(); // GR'nin önerdiği ürün slug'ları
            $table->timestamps();

            $table->foreign('b2c_user_id')->references('id')->on('b2c_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_ai_sessions');
        Schema::dropIfExists('gr_ai_memories');
        Schema::dropIfExists('gr_ai_guests');
    }
};
