<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_logs', function (Blueprint $table) {
            // VARCHAR(255) → TEXT: AI parse ham metni + özet 800+ karakter olabilir
            $table->text('description')->change();
        });
    }

    public function down(): void
    {
        Schema::table('request_logs', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
};
