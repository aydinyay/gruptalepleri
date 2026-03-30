<?php

namespace App\Console\Commands;

use App\Models\Request as TalepModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshAktifAdim extends Command
{
    protected $signature = 'aktif-adim:refresh {--dry-run : Değişecek kayıtları listele, DB\'ye yazma}';
    protected $description = 'Tüm aktif request\'lerde aktif_adim ve odeme_durumu yeniden hesapla';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('-- DRY-RUN modu: DB\'ye yazılmıyor --');
        }

        $eskiDurumlar = [
            TalepModel::STATUS_BILETLENDI,
            TalepModel::STATUS_OLUMSUZ,
            TalepModel::STATUS_IADE,
            TalepModel::STATUS_IPTAL,
        ];

        $toplam    = 0;
        $degisen   = 0;
        $ayniKalan = 0;

        TalepModel::whereNotIn('status', $eskiDurumlar)
            ->chunkById(100, function ($talepler) use ($dryRun, &$toplam, &$degisen, &$ayniKalan) {
                foreach ($talepler as $talep) {
                    $eskiAdim  = $talep->aktif_adim;
                    $eskiOdeme = $talep->odeme_durumu;

                    if ($dryRun) {
                        DB::beginTransaction();
                        try {
                            $talep->refreshAktifAdim();
                            $talep->refresh();
                            $yeniAdim  = $talep->aktif_adim;
                            $yeniOdeme = $talep->odeme_durumu;
                        } finally {
                            DB::rollBack();
                        }
                    } else {
                        $talep->refreshAktifAdim();
                        $talep->refresh();
                        $yeniAdim  = $talep->aktif_adim;
                        $yeniOdeme = $talep->odeme_durumu;
                    }

                    if ($eskiAdim !== $yeniAdim || $eskiOdeme !== $yeniOdeme) {
                        $degisen++;
                        if ($dryRun) {
                            $this->line(sprintf(
                                '  %s | adım: %s → %s | ödeme: %s → %s',
                                $talep->gtpnr,
                                $eskiAdim  ?? 'null',
                                $yeniAdim  ?? 'null',
                                $eskiOdeme ?? 'null',
                                $yeniOdeme ?? 'null',
                            ));
                        }
                    } else {
                        $ayniKalan++;
                    }

                    $toplam++;
                }
            });

        $this->newLine();
        $this->info("Toplam işlenen  : {$toplam}");
        $this->info("Güncellenen     : {$degisen}");
        $this->info("Değişmeden kalan: {$ayniKalan}");

        if ($dryRun && $degisen > 0) {
            $this->newLine();
            $this->warn("Gerçek çalıştırmak için: php artisan aktif-adim:refresh");
        }
    }
}
