<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('request_payments', 'offer_id')) {
                $table->foreignId('offer_id')
                      ->nullable()
                      ->after('request_id')
                      ->constrained('offers')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('request_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('request_payments', 'offer_id')) {
                $table->dropForeign(['offer_id']);
                $table->dropColumn('offer_id');
            }
        });
    }
};
