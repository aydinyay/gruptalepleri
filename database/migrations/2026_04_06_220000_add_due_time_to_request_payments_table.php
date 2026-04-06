<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('request_payments') && !Schema::hasColumn('request_payments', 'due_time')) {
            Schema::table('request_payments', function (Blueprint $table) {
                $table->time('due_time')->nullable()->after('due_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('request_payments', 'due_time')) {
            Schema::table('request_payments', function (Blueprint $table) {
                $table->dropColumn('due_time');
            });
        }
    }
};
