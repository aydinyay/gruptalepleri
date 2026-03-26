<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tursab_davetler', function (Blueprint $table) {
            $table->id();
            $table->string('belge_no')->nullable()->index();
            $table->string('eposta');
            $table->string('acente_unvani');
            $table->string('il')->nullable();
            $table->string('status')->default('sent'); // sent | failed
            $table->text('hata')->nullable();
            $table->foreignId('gonderen_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['eposta', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tursab_davetler');
    }
};
