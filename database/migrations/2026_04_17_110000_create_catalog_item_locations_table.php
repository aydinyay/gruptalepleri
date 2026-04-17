<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_item_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->enum('type', ['belde', 'ilce', 'il', 'bolge', 'ulke'])->default('il');
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->timestamps();

            $table->index(['catalog_item_id', 'type']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_locations');
    }
};
