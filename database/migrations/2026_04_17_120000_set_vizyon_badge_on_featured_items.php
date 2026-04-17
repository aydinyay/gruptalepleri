<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('catalog_items')
            ->where('is_featured', true)
            ->whereNull('badge_label')
            ->update(['badge_label' => 'Vizyon']);
    }

    public function down(): void
    {
        DB::table('catalog_items')
            ->where('badge_label', 'Vizyon')
            ->update(['badge_label' => null]);
    }
};
