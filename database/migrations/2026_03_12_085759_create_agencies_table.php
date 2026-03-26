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
    Schema::create('agencies', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('tourism_title')->nullable();
        $table->string('company_title')->nullable();
        $table->string('tax_number')->nullable();
        $table->string('tax_office')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('address')->nullable();
        $table->string('contact_name')->nullable();
        $table->string('tursab_no')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
