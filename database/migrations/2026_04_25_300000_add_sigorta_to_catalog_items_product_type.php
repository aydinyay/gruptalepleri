<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE catalog_items MODIFY COLUMN product_type ENUM('transfer','charter','leisure','tour','hotel','visa','sigorta','other') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE catalog_items MODIFY COLUMN product_type ENUM('transfer','charter','leisure','tour','hotel','visa','other') NOT NULL DEFAULT 'other'");
    }
};
