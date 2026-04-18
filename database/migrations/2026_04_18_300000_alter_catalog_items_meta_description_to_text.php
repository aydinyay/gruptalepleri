<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->text('meta_description')->nullable()->change();
            $table->string('meta_title', 500)->nullable()->change();
            $table->string('cover_image', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('meta_description')->nullable()->change();
            $table->string('meta_title')->nullable()->change();
            $table->string('cover_image')->nullable()->change();
        });
    }
};
