<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->timestamp('tiklanma_at')->nullable()->after('kampanya_etiket');
            $table->unsignedSmallInteger('tiklanma_sayisi')->default(0)->after('tiklanma_at');
        });
    }

    public function down(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->dropColumn(['tiklanma_at', 'tiklanma_sayisi']);
        });
    }
};
