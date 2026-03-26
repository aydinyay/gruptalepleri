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
        Schema::table('request_notifications', function (Blueprint $table) {
            // Pencere dışındaki SMS'ler için zamanlama alanı
            $table->timestamp('scheduled_for')->nullable()->after('status');
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::table('request_notifications', function (Blueprint $table) {
            $table->dropColumn('scheduled_for');
        });
    }
};
