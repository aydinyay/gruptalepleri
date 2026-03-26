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

            // Eski DB'de bul
            try {
                $legacy_r = $legacy->table('grupmesajlari')
                    ->where('gtpnr', $talep->gtpnr)
                    ->first();
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }

            if (!$legacy_r) {
                $skipped++;
                continue;
            }

            // Notes alanını HER ZAMAN düzelt (offer skip'ten bağımsız)
            $cevapMetniCheck = trim($legacy_r->cevapmetni ?? '') ?: null;
            if ($cevapMetniCheck) {
                // cevapmetni varsa notlar da admin yazmıştır → notes temizle
                if ($talep->notes !== null) {
                    $talep->update(['notes' => null]);
                }
            } else {
                $acenteNotu = trim($legacy_r->notlar ?? '') ?: null;
                if ($acenteNotu !== null && $talep->notes !== $acenteNotu) {
                    $talep->update(['notes' => $acenteNotu]);
                }
            }

            // Zaten görünür offer varsa offer sync'i atla
            $rawTarih = trim($legacy_r->opsiyontarihi ?? '');
            if ($talep->offers->where('is_visible', true)->whereNotNull('option_date')->count() > 0) {
                $skipped++;
                continue;
            }

            if (!$rawTarih || $rawTarih === '0000-00-00' || str_starts_with($rawTarih, '0000')) {
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

            $fiyat      = floatval($legacy_r->toplamodeme ?? 0);
            $depRani    = floatval($legacy_r->depozitorani ?? 0);
            $depTutari  = floatval($legacy_r->depozitotutari ?? 0);
            $currency   = strtoupper(trim($legacy_r->parabirimi ?? 'TRY')) ?: 'TRY';
            $pax        = max(1, (int)($legacy_r->pax ?? $legacy_r->kisisayisi ?? 1));
            $perPax     = ($fiyat > 0 && $pax > 0) ? round($fiyat / $pax, 2) : null;
            $cevapMetni = $cevapMetniCheck;

            $this->newLine();
            $this->line(sprintf(
                '  %s → opsiyon: %s %s | fiyat: %s %s',
                $talep->gtpnr,
                $rawTarih,
                $rawSaat,
                $fiyat ?: '—',
                $currency
            ));

            if (!$dryRun) {
                // requests.notes'u düzelt:
                // Kural: cevapmetni doluysa, notlar alanı da admin tarafından doldurulmuştur
                // → notes temizle. Sadece cevapmetni boş olan kayıtlarda notlar gerçek acente notudur.
                if ($cevapMetni) {
                    $talep->update(['notes' => null]);
                } else {
                    $acenteNotu = trim($legacy_r->notlar ?? '') ?: null;
                    if ($acenteNotu !== null) {
                        $talep->update(['notes' => $acenteNotu]);
                    }
                }

                // Mevcut offer varsa güncelle, yoksa oluştur
                $offer = $talep->offers->first();
                if ($offer) {
                    $offer->update([
                        'option_date'    => $rawTarih,
                        'option_time'    => $rawSaat,
                        'price_per_pax'  => $perPax ?: $offer->price_per_pax,
                        'total_price'    => $fiyat > 0 ? $fiyat : $offer->total_price,
                        'currency'       => $currency,
                        'deposit_rate'   => $depRani   > 0 ? $depRani   : $offer->deposit_rate,
                        'deposit_amount' => $depTutari > 0 ? $depTutari : $offer->deposit_amount,
                        'offer_text'     => $cevapMetni ?: $offer->offer_text,
                        'is_visible'     => true,
                    ]);
                } else {
                    $talep->offers()->create([
                        'option_date'    => $rawTarih,
                        'option_time'    => $rawSaat,
                        'price_per_pax'  => $perPax,
                        'total_price'    => $fiyat > 0 ? $fiyat : null,
                        'currency'       => $currency,
                        'deposit_rate'   => $depRani   > 0 ? $depRani   : null,
                        'deposit_amount' => $depTutari > 0 ? $depTutari : null,
                        'offer_text'     => $cevapMetni,
                        'is_visible'     => true,
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
