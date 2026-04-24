<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2c_price_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->nullable()->index();
            $table->unsignedBigInteger('b2c_user_id')->nullable()->index();
            $table->unsignedBigInteger('catalog_item_id')->index();
            $table->string('slug', 200);
            $table->decimal('price_at_subscription', 10, 2)->nullable();
            $table->string('email', 191)->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('catalog_item_id')->references('id')->on('catalog_items')->onDelete('cascade');
            $table->unique(['session_id', 'catalog_item_id'], 'unique_session_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2c_price_alerts');
    }
};
