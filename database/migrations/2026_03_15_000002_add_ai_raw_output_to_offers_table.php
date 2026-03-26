<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // AI'ın ham JSON çıktısını sakla — admin sonradan neyin değiştiğini görebilsin
            $table->json('ai_raw_output')->nullable()->after('admin_raw_note');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('ai_raw_output');
        });
    }
};
