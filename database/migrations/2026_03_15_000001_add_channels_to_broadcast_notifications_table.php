<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_notifications', function (Blueprint $table) {
            // ['push', 'sms', 'email'] — null = sadece push (geriye dönük uyumluluk)
            $table->json('channels')->nullable()->after('target_user_ids');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_notifications', function (Blueprint $table) {
            $table->dropColumn('channels');
        });
    }
};
