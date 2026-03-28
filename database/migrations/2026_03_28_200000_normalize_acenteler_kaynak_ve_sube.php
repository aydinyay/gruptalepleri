<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // kaynak normalizasyonu
        foreach ([
            'tursab'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'",
            'bakanlik' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'",
            'manuel'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'",
        ] as $val => $cond) {
            DB::table('acenteler')
                ->whereRaw($cond)
                ->where('kaynak', '!=', $val)
                ->update(['kaynak' => $val]);
        }

        // is_sube normalizasyonu
        DB::table('acenteler')
            ->where('is_sube', 0)
            ->where(function ($q) {
                $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")
                  ->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'");
            })
            ->update(['is_sube' => 1]);
    }

    public function down(): void {}
};
