<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AcentelerNormalizeKaynakCommand extends Command
{
    protected $signature = 'acenteler:normalize-kaynak
                            {--dry-run : Değişiklikleri uygulamadan göster}';

    protected $description = 'acenteler.kaynak sütununu ve is_sube değerlerini normalize eder';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN modu — hiçbir değişiklik uygulanmayacak.');
        }

        $this->info('─── Mevcut kaynak dağılımı ───');
        $before = DB::table('acenteler')
            ->selectRaw('COALESCE(kaynak, "NULL") as kaynak, COUNT(*) as toplam')
            ->groupBy('kaynak')
            ->orderByDesc('toplam')
            ->get();

        foreach ($before as $row) {
            $this->line("  {$row->kaynak}: {$row->toplam}");
        }

        $this->newLine();

        // ── 1. kaynak normalizasyonu ───────────────────────────────────────────
        $rules = [
            'tursab'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'",
            'bakanlik' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'",
            'manuel'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'",
        ];

        foreach ($rules as $normalValue => $condition) {
            $affected = DB::table('acenteler')
                ->whereRaw($condition)
                ->where('kaynak', '!=', $normalValue)
                ->count();

            $this->info("kaynak='{$normalValue}' → {$affected} kayıt normalize edilecek");

            if (! $dryRun && $affected > 0) {
                DB::table('acenteler')
                    ->whereRaw($condition)
                    ->where('kaynak', '!=', $normalValue)
                    ->update(['kaynak' => $normalValue]);
            }
        }

        // ── 2. is_sube normalizasyonu ─────────────────────────────────────────
        $subeCount = DB::table('acenteler')
            ->where('is_sube', 0)
            ->where(function ($q) {
                $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")
                  ->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'");
            })
            ->count();

        $this->info("is_sube=1 olması gereken ama 0 olan: {$subeCount} kayıt");

        if (! $dryRun && $subeCount > 0) {
            DB::table('acenteler')
                ->where('is_sube', 0)
                ->where(function ($q) {
                    $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")
                      ->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'");
                })
                ->update(['is_sube' => 1]);
        }

        $this->newLine();

        if (! $dryRun) {
            $this->info('─── Normalizasyon sonrası kaynak dağılımı ───');
            $after = DB::table('acenteler')
                ->selectRaw('COALESCE(kaynak, "NULL") as kaynak, COUNT(*) as toplam')
                ->groupBy('kaynak')
                ->orderByDesc('toplam')
                ->get();

            foreach ($after as $row) {
                $this->line("  {$row->kaynak}: {$row->toplam}");
            }

            $subeTotal = DB::table('acenteler')->where('is_sube', 1)->count();
            $this->info("Toplam is_sube=1: {$subeTotal}");
        }

        $this->info($dryRun ? 'Dry-run tamamlandı. Uygulamak için --dry-run olmadan çalıştır.' : 'Normalizasyon tamamlandı.');

        return self::SUCCESS;
    }
}
