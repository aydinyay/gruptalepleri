<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('b2c_wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['session_id', 'catalog_item_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('b2c_wishlist_items'); }
};
