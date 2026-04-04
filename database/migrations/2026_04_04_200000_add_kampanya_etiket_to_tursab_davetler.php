<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->string('kampanya_etiket', 100)->nullable()->after('tip')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->dropIndex(['kampanya_etiket']);
            $table->dropColumn('kampanya_etiket');
        });
    }
};
