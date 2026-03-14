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
        Schema::table('requests', function (Blueprint $table) {
            $table->text('ai_analysis')->nullable()->after('notes');
            $table->string('ai_analysis_hash', 64)->nullable()->after('ai_analysis');
            $table->timestamp('ai_analysis_updated_at')->nullable()->after('ai_analysis_hash');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'ai_analysis_hash', 'ai_analysis_updated_at']);
        });
    }
};
