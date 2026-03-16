<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportJsonSnapshot extends Command
{
    protected $signature = 'db:import-json-snapshot
                            {--file= : JSON snapshot dosya yolu}
                            {--dry-run : Sadece kontrol et, yazma yapma}';

    protected $description = 'JSON snapshot verisini local DB\'ye sadece veri olarak import eder (şemayı değiştirmez).';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Bu komut sadece local ortamda çalışır.');
            return self::FAILURE;
        }

        $file = $this->option('file') ?: storage_path('app/live-sync/live_export.json');
        if (! is_file($file)) {
            $this->error("Dosya bulunamadı: {$file}");
            return self::FAILURE;
        }

        $json = file_get_contents($file);
        $payload = json_decode($json, true);
        if (! is_array($payload) || ! isset($payload['tables']) || ! is_array($payload['tables'])) {
            $this->error('Geçersiz snapshot formatı.');
            return self::FAILURE;
        }

        $tables = [
            'users',
            'agencies',
            'requests',
            'flight_segments',
            'offers',
            'request_logs',
            'request_payments',
            'request_notifications',
            'sms_notification_settings',
            'opsiyon_uyari_ayarlari',
            'opsiyon_uyari_gonderimler',
            'kullanici_bildirimleri',
            'broadcast_notifications',
            'sistem_ayarlari',
        ];

        $sourceTables = $payload['tables'];
        $dryRun = (bool) $this->option('dry-run');
        $summary = [];

        if (! $dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table) || ! array_key_exists($table, $sourceTables)) {
                    continue;
                }

                $rows = is_array($sourceTables[$table]) ? $sourceTables[$table] : [];
                $localCols = Schema::getColumnListing($table);
                $localColMap = array_flip($localCols);

                $filtered = [];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $out = array_intersect_key($row, $localColMap);
                    if (! empty($out)) {
                        $filtered[] = $out;
                    }
                }

                if ($dryRun) {
                    $summary[] = [$table, count($filtered), 'dry-run'];
                    continue;
                }

                DB::table($table)->truncate();

                foreach (array_chunk($filtered, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                $summary[] = [$table, count($filtered), 'imported'];
            }
        } finally {
            if (! $dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $this->table(['Table', 'Rows', 'Status'], $summary);
        $this->info($dryRun ? 'Dry-run tamamlandı.' : 'Import tamamlandı.');

        return self::SUCCESS;
    }
}

