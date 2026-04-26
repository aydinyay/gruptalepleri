<?php

namespace App\Console\Commands;

use App\Models\BlogYazisi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TranslateBlogPosts extends Command
{
    protected $signature = 'gr:translate-blog
                            {--locale= : Tek locale — boş bırakılırsa hepsi}
                            {--force : Zaten çevrilmiş olanları da yeniden çevir}
                            {--id= : Sadece bu ID\'yi çevir}';

    protected $description = 'Gemini ile blog_yazilari çeviri kolonlarını doldurur';

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

        $query = BlogYazisi::yayinda();
        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }
        $items = $query->get();

        if ($items->isEmpty()) {
            $this->warn('Hiç yayında blog yazısı bulunamadı.');
            return 0;
        }

        foreach ($locales as $locale) {
            $this->info("\n[{$locale}] çeviriliyor...");
            $done = 0;
            $skip = 0;

            foreach ($items as $item) {
                $existing = $item->baslik_translations ?? [];

                if (! $this->option('force') && ! empty($existing[$locale])) {
                    $skip++;
                    continue;
                }

                // Kısa alanlar — tek istek
                $shortFields = array_filter([
                    'baslik'        => $item->baslik,
                    'ozet'          => $item->ozet,
                    'meta_baslik'   => $item->meta_baslik,
                    'meta_aciklama' => $item->meta_aciklama,
                ]);

                $translated = $this->translateFields($apiKey, $model, $locale, $shortFields);

                if (isset($translated['error'])) {
                    $this->warn("  ID:{$item->id} HATA — {$translated['error']}");
                    continue;
                }

                // icerik ayrı istek — max 2000 karakter
                $icerikTranslated = null;
                if ($item->icerik) {
                    $icerikText   = mb_substr(strip_tags($item->icerik), 0, 2000);
                    $icerikResult = $this->translateFields($apiKey, $model, $locale, ['icerik' => $icerikText]);
                    $icerikTranslated = $icerikResult['icerik'] ?? null;
                }

                $item->update([
                    'baslik_translations'        => array_merge($item->baslik_translations ?? [], [$locale => $translated['baslik'] ?? $item->baslik]),
                    'ozet_translations'          => array_merge($item->ozet_translations ?? [], [$locale => $translated['ozet'] ?? null]),
                    'icerik_translations'        => array_merge($item->icerik_translations ?? [], [$locale => $icerikTranslated]),
                    'meta_baslik_translations'   => array_merge($item->meta_baslik_translations ?? [], [$locale => $translated['meta_baslik'] ?? null]),
                    'meta_aciklama_translations' => array_merge($item->meta_aciklama_translations ?? [], [$locale => $translated['meta_aciklama'] ?? null]),
                ]);

                $done++;
                $this->line("  ✓ ID:{$item->id} {$item->baslik}");
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
Sen profesyonel bir turizm ve seyahat blog yerelleştirme uzmanısın.
Aşağıdaki JSON alanlarını **{$name}** diline çevir.

Kurallar:
- Doğal, akıcı, okuyucuya hitap eden blog dili kullan.
- "Grup Rezervasyonları", "GrupRezervasyonlari.com" marka adları çevrilmez.
- JSON key'leri aynen koru, sadece value'ları çevir.
- meta_baslik max 60 karakter, meta_aciklama max 160 karakter.{$rtlNote}

Sadece geçerli JSON döndür, başka açıklama ekleme.

JSON:
{$jsonInput}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(30)->post($url, [
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.2, 'responseMimeType' => 'application/json'],
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
