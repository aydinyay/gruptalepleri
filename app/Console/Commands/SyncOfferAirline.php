<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncOfferAirline extends Command
{
    protected $signature   = 'offers:sync-airline {--dry-run : Sadece göster, kaydetme}';
    protected $description = 'Airline boş olan teklifleri flight_number veya AI ile doldur';

    public function handle(): int
    {
        $offers = Offer::whereNull('airline')
            ->orWhere('airline', '')
            ->get();

        if ($offers->isEmpty()) {
            $this->info('Airline boş teklif yok.');
            return 0;
        }

        $this->info("Airline boş: {$offers->count()} teklif");

        $fixed    = 0;
        $aiFixed  = 0;
        $skipped  = 0;

        foreach ($offers as $offer) {
            $airline = null;

            // 1. flight_number'dan regex ile çıkar: "VF3002" → "VF"
            if (!empty($offer->flight_number)) {
                if (preg_match('/^([A-Z0-9]{2})\d+/i', strtoupper(trim($offer->flight_number)), $m)) {
                    $airline = strtoupper($m[1]);
                }
            }

            // 2. airline_pnr'dan regex ile çıkar: "TK1234" formatı
            if (!$airline && !empty($offer->airline_pnr)) {
                if (preg_match('/^([A-Z]{2})\d+/i', strtoupper(trim($offer->airline_pnr)), $m)) {
                    $airline = strtoupper($m[1]);
                }
            }

            // 3. offer_text veya admin_raw_note'dan regex: ilk uçuş numarası kalıbı
            $metin = trim(($offer->offer_text ?? '') . ' ' . ($offer->admin_raw_note ?? ''));
            if (!$airline && $metin) {
                if (preg_match('/\b([A-Z]{2})\d{3,4}\b/', $metin, $m)) {
                    $airline = strtoupper($m[1]);
                }
            }

            // 4. Hâlâ bulunamadıysa ve metin varsa → Gemini AI
            if (!$airline && $metin) {
                $airline = $this->askGemini($metin);
                if ($airline) {
                    $aiFixed++;
                }
            }

            if ($airline) {
                $this->line("  ✓ Offer #{$offer->id} (request_id:{$offer->request_id}) → {$airline}");
                if (!$this->option('dry-run')) {
                    $offer->timestamps = false;
                    $offer->update(['airline' => $airline]);
                }
                $fixed++;
            } else {
                $this->warn("  ? Offer #{$offer->id} (request_id:{$offer->request_id}) — bulunamadı");
                $skipped++;
            }
        }

        $this->info("Tamamlandı: {$fixed} düzeltildi ({$aiFixed} AI ile), {$skipped} atlandı.");
        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN modu — hiçbir şey kaydedilmedi.');
        }

        return 0;
    }

    private function askGemini(string $text): ?string
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return null;
        }

        $prompt = 'Bu operasyon notundan sadece havayolu IATA kodunu (2 harf) çıkar. '
            . 'Sadece 2 harfli kodu yaz, başka hiçbir şey yazma. Bulamazsan boş bırak. '
            . 'Metin: ' . mb_substr($text, 0, 500);

        try {
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );

            $result = trim($response->json('candidates.0.content.parts.0.text') ?? '');
            if (preg_match('/^[A-Z0-9]{2}$/i', $result)) {
                return strtoupper($result);
            }
        } catch (\Throwable) {
            // AI erişilemiyorsa geç
        }

        return null;
    }
}
