<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportSqlDataSafe extends Command
{
    protected $signature = 'db:import-sql-data-safe
                            {--file= : SQL veya SQL.GZ dosya yolu}
                            {--dry-run : Yazma yapmadan analiz et}
                            {--allow-partial : Eksik tablo olsa da sadece bulunanları içeri al}';

    protected $description = 'SQL dump içinden sadece izinli tablo INSERT verilerini şemaya dokunmadan güvenli şekilde import eder.';

    private array $whitelist = [
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

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Bu komut sadece local ortamda çalışır.');
            return self::FAILURE;
        }

        $file = $this->option('file') ?: storage_path('app/live-sync/live_dump.sql');
        if (! is_file($file)) {
            $this->error("Dosya bulunamadı: {$file}");
            return self::FAILURE;
        }

        $sql = $this->readSql($file);
        if ($sql === null) {
            $this->error('SQL dosyası okunamadı.');
            return self::FAILURE;
        }

        $statements = $this->extractInsertStatements($sql);
        $byTable = [];
        foreach ($statements as $stmt) {
            if (! preg_match('/^INSERT\s+INTO\s+`([^`]+)`/i', $stmt, $m)) {
                continue;
            }
            $table = $m[1];
            if (! in_array($table, $this->whitelist, true)) {
                continue;
            }
            $byTable[$table][] = $stmt;
        }

        $foundTables = array_keys($byTable);
        sort($foundTables);

        $missing = array_values(array_diff($this->whitelist, $foundTables));
        $allowPartial = (bool) $this->option('allow-partial');
        $dryRun = (bool) $this->option('dry-run');

        $summary = [];
        foreach ($this->whitelist as $table) {
            $summary[] = [$table, count($byTable[$table] ?? []), in_array($table, $missing, true) ? 'missing' : 'ok'];
        }
        $this->table(['Table', 'Insert SQL', 'Status'], $summary);

        if (! $allowPartial && count($missing) > 0) {
            $this->error('Dump eksik tablo içeriyor; güvenli durduruldu.');
            $this->line('Eksik: ' . implode(', ', $missing));
            $this->line('Tam export aldıktan sonra tekrar çalıştırın veya --allow-partial kullanın.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry-run tamamlandı.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $truncateOrder = array_reverse($this->whitelist);
            foreach ($truncateOrder as $table) {
                if (! isset($byTable[$table])) {
                    continue;
                }
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            foreach ($this->whitelist as $table) {
                if (! isset($byTable[$table]) || ! Schema::hasTable($table)) {
                    continue;
                }
                foreach ($byTable[$table] as $stmt) {
                    DB::statement($stmt);
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Import tamamlandı.');

        return self::SUCCESS;
    }

    private function readSql(string $file): ?string
    {
        if (str_ends_with(strtolower($file), '.gz')) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                return null;
            }
            $sql = @gzdecode($raw);
            return $sql === false ? null : $sql;
        }

        $sql = @file_get_contents($file);
        return $sql === false ? null : $sql;
    }

    /**
     * INSERT ...; bloklarını çok satırlı şekilde yakalar.
     *
     * @return array<int, string>
     */
    private function extractInsertStatements(string $sql): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $sql) ?: [];
        $statements = [];
        $collecting = false;
        $buffer = '';

        foreach ($lines as $line) {
            $leftTrimmed = ltrim($line);

            if (! $collecting) {
                if (stripos($leftTrimmed, 'INSERT INTO `') === 0) {
                    $collecting = true;
                    $buffer = $line . PHP_EOL;

                    if ($this->isStatementTerminated($line)) {
                        $statements[] = trim($buffer);
                        $collecting = false;
                        $buffer = '';
                    }
                }
                continue;
            }

            $buffer .= $line . PHP_EOL;
            if ($this->isStatementTerminated($line)) {
                $statements[] = trim($buffer);
                $collecting = false;
                $buffer = '';
            }
        }

        return $statements;
    }

    private function isStatementTerminated(string $line): bool
    {
        return str_ends_with(rtrim($line), ';');
    }
}
