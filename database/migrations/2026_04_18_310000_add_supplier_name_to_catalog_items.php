<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('supplier_name', 150)->nullable()->after('supplier_id');
            $table->string('supplier_logo_url', 500)->nullable()->after('supplier_name');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'supplier_logo_url']);
        });
    }
};
