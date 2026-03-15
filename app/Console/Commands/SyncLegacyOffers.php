<?php

namespace App\Console\Commands;

use App\Models\Request as TalepModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyOffers extends Command
{
    protected $signature = 'legacy:sync-offers {--dry-run : Kaydetmeden önizle}';
    protected $description = 'Yeni sistemdeki talepler için eski DB\'den opsiyon/fiyat bilgisini offers tablosuna aktar';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '--- DRY-RUN ---' : '--- GERÇEK AKTARIM ---');

        try {
            $legacy = DB::connection('legacy');
        } catch (\Throwable $e) {
            $this->error('Eski DB bağlantısı kurulamadı: ' . $e->getMessage());
            return 1;
        }

        // Yeni sistemdeki tüm talepleri al
        $talepler = TalepModel::with('offers')->get();
        $this->info("Yeni sistemde {$talepler->count()} talep bulundu.");

        $synced  = 0;
        $skipped = 0;
        $noData  = 0;

        $bar = $this->output->createProgressBar($talepler->count());
        $bar->start();

        foreach ($talepler as $talep) {
            $bar->advance();

            // Zaten offers'ta option_date varsa atla
            if ($talep->offers->whereNotNull('option_date')->count() > 0) {
                $skipped++;
                continue;
            }

            // Eski DB'de bul
            try {
                $legacy_r = $legacy->table('grupmesajlari')
                    ->where('gtpnr', $talep->gtpnr)
                    ->first();
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }

            if (!$legacy_r || empty($legacy_r->opsiyontarihi)) {
                $noData++;
                continue;
            }

            // Saati normalize et
            $rawSaat = trim($legacy_r->opsiyonsaati ?? '');
            if (preg_match('/^(\d{1,2}):(\d{2})/', $rawSaat, $m)) {
                $rawSaat = sprintf('%02d:%02d', $m[1], $m[2]);
            } elseif (preg_match('/^\d{1,2}$/', $rawSaat) && (int)$rawSaat > 0) {
                $rawSaat = sprintf('%02d:00', (int)$rawSaat);
            } else {
                $rawSaat = '23:59';
            }

            $fiyat     = floatval($legacy_r->toplamodeme ?? 0);
            $depRani   = floatval($legacy_r->depozitorani ?? 0);
            $depTutari = floatval($legacy_r->depozitotutari ?? 0);
            $currency  = strtoupper(trim($legacy_r->parabirimi ?? 'TRY')) ?: 'TRY';
            $pax       = max(1, (int)($legacy_r->pax ?? $legacy_r->kisisayisi ?? 1));
            $perPax    = ($fiyat > 0 && $pax > 0) ? round($fiyat / $pax, 2) : null;

            $this->newLine();
            $this->line(sprintf(
                '  %s → opsiyon: %s %s | fiyat: %s %s',
                $talep->gtpnr,
                $legacy_r->opsiyontarihi,
                $rawSaat,
                $fiyat ?: '—',
                $currency
            ));

            if (!$dryRun) {
                // Mevcut offer varsa güncelle, yoksa oluştur
                $offer = $talep->offers->first();
                if ($offer) {
                    $offer->update([
                        'option_date'    => $legacy_r->opsiyontarihi,
                        'option_time'    => $rawSaat,
                        'deposit_rate'   => $depRani   > 0 ? $depRani   : $offer->deposit_rate,
                        'deposit_amount' => $depTutari > 0 ? $depTutari : $offer->deposit_amount,
                    ]);
                } else {
                    $talep->offers()->create([
                        'option_date'    => $legacy_r->opsiyontarihi,
                        'option_time'    => $rawSaat,
                        'price_per_pax'  => $perPax,
                        'total_price'    => $fiyat > 0 ? $fiyat : null,
                        'currency'       => $currency,
                        'deposit_rate'   => $depRani   > 0 ? $depRani   : null,
                        'deposit_amount' => $depTutari > 0 ? $depTutari : null,
                        'is_visible'     => false, // Admin onaylayana kadar gizli
                    ]);
                }
            }

            $synced++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['', 'Sayı'], [
            ['Senkronize edildi', $synced],
            ['Zaten data var (atlandı)', $skipped],
            ['Eski DB\'de veri yok', $noData],
        ]);

        if ($dryRun) {
            $this->warn('DRY-RUN tamamlandı. Gerçek sync için --dry-run olmadan çalıştırın.');
        } else {
            $this->info('Sync tamamlandı.');
        }

        return 0;
    }
}
