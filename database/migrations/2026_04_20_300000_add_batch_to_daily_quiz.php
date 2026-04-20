<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_quiz_questions', function (Blueprint $table) {
            $table->json('batch_json')->nullable()->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('daily_quiz_questions', function (Blueprint $table) {
            $table->dropColumn('batch_json');
        });
    }
};
