<?php

namespace App\Console\Commands;

use App\Models\CharterPayment;
use App\Models\RequestPayment;
use App\Services\Finance\FinanceSyncService;
use Illuminate\Console\Command;

class SyncFinanceCore extends Command
{
    protected $signature = 'finance:sync-core {--chunk=200 : Batch size for sync operations}';
    protected $description = 'Backfill legacy request/charter payment data into finance core tables';

    public function handle(FinanceSyncService $financeSyncService): int
    {
        $chunk = (int) $this->option('chunk');
        if ($chunk < 50) {
            $chunk = 50;
        }

        $this->info('Finance core sync started...');

        $requestCount = 0;
        RequestPayment::query()->orderBy('id')->chunkById($chunk, function ($payments) use (&$requestCount, $financeSyncService): void {
            foreach ($payments as $payment) {
                $financeSyncService->syncRequestPayment($payment);
                $requestCount++;
            }
        });
        $this->line('Legacy request payments synced: ' . $requestCount);

        $charterCount = 0;
        CharterPayment::query()->orderBy('id')->chunkById($chunk, function ($payments) use (&$charterCount, $financeSyncService): void {
            foreach ($payments as $payment) {
                $financeSyncService->syncCharterPayment($payment);
                $charterCount++;
            }
        });
        $this->line('Charter payments synced: ' . $charterCount);

        $this->info('Finance core sync completed.');

        return self::SUCCESS;
    }
}
