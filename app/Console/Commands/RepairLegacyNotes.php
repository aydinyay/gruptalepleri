<?php

namespace App\Console\Commands;

use App\Models\Request as TalepModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairLegacyNotes extends Command
{
    protected $signature = 'legacy:repair-notes {--dry-run : Kaydetmeden onizle}';
    protected $description = 'Eski sistemden acente notu ve yonetici mesaji alanlarini yeni sistemde duzelt';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '--- DRY-RUN NOTE REPAIR ---' : '--- LEGACY NOTE REPAIR ---');

        try {
            $legacy = DB::connection('legacy');
        } catch (\Throwable $e) {
            $this->error('Eski DB baglantisi kurulamadi: ' . $e->getMessage());
            return self::FAILURE;
        }

        $talepler = TalepModel::with('offers')->get();

        $checked = 0;
        $notesUpdated = 0;
        $messagesUpdated = 0;
        $hiddenOffersFixed = 0;
        $missingLegacy = 0;

        $bar = $this->output->createProgressBar($talepler->count());
        $bar->start();

        foreach ($talepler as $talep) {
            $bar->advance();
            $checked++;

            try {
                $legacyRow = $legacy->table('grupmesajlari')
                    ->where('gtpnr', $talep->gtpnr)
                    ->first();
            } catch (\Throwable $e) {
                continue;
            }

            if (! $legacyRow) {
                $missingLegacy++;
                continue;
            }

            $acenteNotu = $this->sanitizeText($legacyRow->notlar ?? null);
            $yoneticiMesaji = $this->sanitizeText($legacyRow->cevapmetni ?? null);

            $hedefNotes = $yoneticiMesaji ? null : $acenteNotu;
            $mevcutNotes = $this->sanitizeText($talep->getRawOriginal('notes'));

            if ($mevcutNotes !== $hedefNotes) {
                $notesUpdated++;
                if (! $dryRun) {
                    $talep->update(['notes' => $hedefNotes]);
                }
            }

            if ($yoneticiMesaji) {
                $offer = $talep->offers->first();
                if ($offer) {
                    $mevcutMesaj = $this->sanitizeText($offer->offer_text);

                    if ($mevcutMesaj !== $yoneticiMesaji) {
                        $messagesUpdated++;
                        if (! $dryRun) {
                            $offer->update(['offer_text' => $yoneticiMesaji]);
                        }
                    }

                    if (! $offer->is_visible) {
                        $hiddenOffersFixed++;
                        if (! $dryRun) {
                            $offer->update(['is_visible' => true]);
                        }
                    }
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Alan', 'Sayi'], [
            ['Kontrol edilen talep', $checked],
            ['Legacy kaydi bulunamayan', $missingLegacy],
            ['Guncellenen acente notu', $notesUpdated],
            ['Guncellenen yonetici mesaji', $messagesUpdated],
            ['Gorunur yapilan teklif', $hiddenOffersFixed],
        ]);

        $this->info($dryRun ? 'Dry-run tamamlandi.' : 'Legacy note repair tamamlandi.');

        return self::SUCCESS;
    }

    private function sanitizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ 	]+/', ' ', $text) ?? $text;
        $text = trim($text);

        return $text !== '' ? $text : null;
    }
}
