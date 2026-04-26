<?php

namespace App\Console\Commands;

use App\Models\B2C\CatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TranslateCatalogItems extends Command
{
    protected $signature = 'gr:translate-catalog
                            {--locale= : Tek locale (en, ar, ru, de, fr, fa, zh) — boş bırakılırsa hepsi}
                            {--force : Zaten çevrilmiş olanları da yeniden çevir}
                            {--id= : Sadece bu ID\'yi çevir (test için)}';

    protected $description = 'Gemini ile catalog_items çeviri kolonlarını doldurur';

    const SUPPORTED = ['en', 'ar', 'ru', 'de', 'fr', 'fa', 'zh'];

    const LOCALE_NAMES = [
        'en' => 'English',
        'ar' => 'Arabic (العربية)',
        'ru' => 'Russian (Русский)',
        'de' => 'German (Deutsch)',
        'fr' => 'French (Français)',
        'fa' => 'Persian (فارسی)',
        'zh' => 'Simplified Chinese (简体中文)',
    ];

    public function handle(): int
    {
        $apiKey = config('services.gemini.key', '');
        $model  = config('services.gemini.text_model', 'gemini-2.5-flash');

        if (! $apiKey) {
            $this->error('GEMINI_API_KEY tanımlı değil.');
            return 1;
        }

        $locales = $this->option('locale')
            ? [trim($this->option('locale'))]
            : self::SUPPORTED;

        $query = CatalogItem::where('is_active', true);
        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }
        $items = $query->get();

        if ($items->isEmpty()) {
            $this->warn('Hiç aktif ürün bulunamadı.');
            return 0;
        }

        $this->info("Toplam {$items->count()} ürün × " . count($locales) . " dil işlenecek.");

        foreach ($locales as $locale) {
            $this->info("\n[{$locale}] çeviriliyor...");
            $done = 0;
            $skip = 0;

            foreach ($items as $item) {
                $existing = $item->title_translations ?? [];

                if (! $this->option('force') && ! empty($existing[$locale])) {
                    $skip++;
                    continue;
                }

                $fields = array_filter([
                    'title'            => $item->title,
                    'short_desc'       => $item->short_desc,
                    'full_desc'        => $item->full_desc ? strip_tags($item->full_desc) : null,
                    'meta_title'       => $item->meta_title,
                    'meta_description' => $item->meta_description,
                ]);

                if (empty($fields)) {
                    $skip++;
                    continue;
                }

                $translated = $this->translateFields($apiKey, $model, $locale, $fields);

                if (isset($translated['error'])) {
                    $this->warn("  ID:{$item->id} HATA — {$translated['error']}");
                    continue;
                }

                $titleT = array_merge($item->title_translations ?? [], [$locale => $translated['title'] ?? $item->title]);
                $shortT = array_merge($item->short_desc_translations ?? [], [$locale => $translated['short_desc'] ?? null]);
                $fullT  = array_merge($item->full_desc_translations ?? [], [$locale => $translated['full_desc'] ?? null]);
                $metaTT = array_merge($item->meta_title_translations ?? [], [$locale => $translated['meta_title'] ?? null]);
                $metaDT = array_merge($item->meta_description_translations ?? [], [$locale => $translated['meta_description'] ?? null]);

                $item->update([
                    'title_translations'        => $titleT,
                    'short_desc_translations'   => $shortT,
                    'full_desc_translations'    => $fullT,
                    'meta_title_translations'   => $metaTT,
                    'meta_description_translations' => $metaDT,
                ]);

                $done++;
                $this->line("  ✓ ID:{$item->id} {$item->title}");
            }

            $this->info("[{$locale}] Tamamlandı — {$done} çevrildi, {$skip} atlandı.");
        }

        return 0;
    }

    private function translateFields(string $apiKey, string $model, string $locale, array $fields): array
    {
        $isRtl   = in_array($locale, ['ar', 'fa']);
        $rtlNote = $isRtl ? "\n- Hedef dil RTL. Numaralar ve HTML tag'ları sırayı bozmamalı." : '';
        $name    = self::LOCALE_NAMES[$locale] ?? $locale;

        $jsonInput = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Sen profesyonel bir turizm yerelleştirme uzmanısın.
Aşağıdaki JSON alanlarını **{$name}** diline çevir.

Kurallar:
- Kelime kelime değil; doğal, kültüre uygun turizm dili kullan.
- Marka adı "Grup Rezervasyonları" çevrilmez.
- JSON key'leri aynen koru, sadece value'ları çevir.
- HTML etiketleri veya {değişken} yapısı bozulmamalı.
- meta_title max 60 karakter, meta_description max 160 karakter.{$rtlNote}

Sadece geçerli JSON döndür, başka açıklama ekleme.

JSON:
{$jsonInput}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(30)->post($url, [
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json'],
        ]);

        if (! $response->successful()) {
            return ['error' => 'HTTP ' . $response->status()];
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');
        $text = preg_replace('/^```json\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Geçersiz JSON: ' . json_last_error_msg()];
        }

        return $decoded;
    }
}
