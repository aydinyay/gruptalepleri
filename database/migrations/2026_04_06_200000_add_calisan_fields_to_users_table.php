<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'parent_agency_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agency_id')->nullable()->after('role');
            $table->string('acente_rolu', 20)->nullable()->after('parent_agency_id');
            $table->string('davet_token', 64)->nullable()->unique()->after('acente_rolu');
            $table->timestamp('davet_expires_at')->nullable()->after('davet_token');
            $table->foreign('parent_agency_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_agency_id']);
            $table->dropColumn(['parent_agency_id', 'acente_rolu', 'davet_token', 'davet_expires_at']);
        });
    }
};
