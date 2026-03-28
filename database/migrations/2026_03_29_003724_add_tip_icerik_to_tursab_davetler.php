<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->string('tip', 10)->default('email')->after('status');  // 'email' | 'sms'
            $table->text('icerik')->nullable()->after('tip');               // SMS içeriği (email için null)
        });
    }

    public function down(): void
    {
        Schema::table('tursab_davetler', function (Blueprint $table) {
            $table->dropColumn(['tip', 'icerik']);
        });
    }
};
