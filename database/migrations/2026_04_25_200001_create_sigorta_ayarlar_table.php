<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigorta_ayarlar', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 80)->unique();
            $table->text('deger')->nullable();
            $table->string('aciklama', 200)->nullable();
            $table->timestamps();
        });

        DB::table('sigorta_ayarlar')->insert([
            ['anahtar' => 'b2b_markup_yuzde',  'deger' => '20',  'aciklama' => 'B2B satış markupı (%)',     'created_at' => now(), 'updated_at' => now()],
            ['anahtar' => 'b2c_markup_yuzde',  'deger' => '50',  'aciklama' => 'B2C satış markupı (%)',     'created_at' => now(), 'updated_at' => now()],
            ['anahtar' => 'kur_tamponu_yuzde', 'deger' => '5',   'aciklama' => 'Kur riski tamponu (%)',     'created_at' => now(), 'updated_at' => now()],
            ['anahtar' => 'aktif',             'deger' => '0',   'aciklama' => 'Sigorta modülü aktif mi?',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sigorta_ayarlar');
    }
};
