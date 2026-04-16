<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK, make user_id nullable, re-add FK with SET NULL
        DB::statement('ALTER TABLE leisure_requests MODIFY user_id BIGINT UNSIGNED NULL');

        if (! Schema::hasColumn('leisure_requests', 'source_channel')) {
            Schema::table('leisure_requests', function (Blueprint $table): void {
                $table->string('source_channel', 20)->default('b2b')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leisure_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('leisure_requests', 'source_channel')) {
                $table->dropColumn('source_channel');
            }
        });
    }
};
