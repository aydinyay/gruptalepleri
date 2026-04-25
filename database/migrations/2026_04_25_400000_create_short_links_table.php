<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->text('url');
            $table->string('context', 50)->nullable(); // 'sigorta_police', 'sigorta_sertifika' vs
            $table->unsignedBigInteger('context_id')->nullable(); // police.id vs
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
