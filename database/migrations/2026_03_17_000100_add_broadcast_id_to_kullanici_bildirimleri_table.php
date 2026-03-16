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
        Schema::table('kullanici_bildirimleri', function (Blueprint $table) {
            if (! Schema::hasColumn('kullanici_bildirimleri', 'broadcast_id')) {
                $table->unsignedBigInteger('broadcast_id')->nullable()->after('user_id');
                $table->index('broadcast_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kullanici_bildirimleri', function (Blueprint $table) {
            if (Schema::hasColumn('kullanici_bildirimleri', 'broadcast_id')) {
                $table->dropIndex(['broadcast_id']);
                $table->dropColumn('broadcast_id');
            }
        });
    }
};

