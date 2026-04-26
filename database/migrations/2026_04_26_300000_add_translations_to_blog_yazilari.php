<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_yazilari', function (Blueprint $table) {
            $table->json('baslik_translations')->nullable()->after('baslik');
            $table->json('ozet_translations')->nullable()->after('ozet');
            $table->json('icerik_translations')->nullable()->after('icerik');
            $table->json('meta_baslik_translations')->nullable()->after('meta_baslik');
            $table->json('meta_aciklama_translations')->nullable()->after('meta_aciklama');
        });
    }

    public function down(): void
    {
        Schema::table('blog_yazilari', function (Blueprint $table) {
            $table->dropColumn([
                'baslik_translations', 'ozet_translations', 'icerik_translations',
                'meta_baslik_translations', 'meta_aciklama_translations',
            ]);
        });
    }
};
