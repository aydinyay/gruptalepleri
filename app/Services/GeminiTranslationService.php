<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiTranslationService
{
    const SUPPORTED = ['en', 'ar', 'ru', 'de', 'fr', 'fa', 'zh'];

    // Seyahat terminolojisi sözlüğü — locale başına sabit çeviriler
    const GLOSSARY = [
        'Dinner Cruise' => [
            'en' => 'Dinner Cruise',
            'ar' => 'جولة العشاء في البوسفور',
            'ru' => 'Ужин-круиз по Босфору',
            'de' => 'Dinner-Kreuzfahrt',
            'fr' => 'Dîner-croisière',
            'fa' => 'کروز شام',
            'zh' => '博斯普鲁斯晚餐游轮',
        ],
        'Yat Kiralama' => [
            'en' => 'Yacht Charter',
            'ar' => 'استئجار يخت',
            'ru' => 'Аренда яхты',
            'de' => 'Yachtvermietung',
            'fr' => 'Location de yacht',
            'fa' => 'اجاره یخت',
            'zh' => '游艇租赁',
        ],
        'VIP Transfer' => [
            'en' => 'VIP Transfer',
            'ar' => 'نقل VIP',
            'ru' => 'VIP-трансфер',
            'de' => 'VIP-Transfer',
            'fr' => 'Transfert VIP',
            'fa' => 'ترانسفر VIP',
            'zh' => 'VIP 接送',
        ],
        'Havalimanı Transferi' => [
            'en' => 'Airport Transfer',
            'ar' => 'نقل المطار',
            'ru' => 'Трансфер из аэропорта',
            'de' => 'Flughafentransfer',
            'fr' => 'Transfert aéroport',
            'fa' => 'ترانسفر فرودگاه',
            'zh' => '机场接送',
        ],
        'Grup Uçak Bileti' => [
            'en' => 'Group Flight Ticket',
            'ar' => 'تذكرة طيران جماعية',
            'ru' => 'Групповой авиабилет',
            'de' => 'Gruppenflugticket',
            'fr' => 'Billet de groupe',
            'fa' => 'بلیط گروهی هواپیما',
            'zh' => '团体机票',
        ],
        'Seyahat Sigortası' => [
            'en' => 'Travel Insurance',
            'ar' => 'تأمين السفر',
            'ru' => 'Туристическая страховка',
            'de' => 'Reiseversicherung',
            'fr' => 'Assurance voyage',
            'fa' => 'بیمه سفر',
            'zh' => '旅行保险',
        ],
        'Grup Rezervasyonları' => [
            'en' => 'Grup Rezervasyonları',
            'ar' => 'Grup Rezervasyonları',
            'ru' => 'Grup Rezervasyonları',
            'de' => 'Grup Rezervasyonları',
            'fr' => 'Grup Rezervasyonları',
            'fa' => 'Grup Rezervasyonları',
            'zh' => 'Grup Rezervasyonları',
        ],
    ];

    const LOCALE_NAMES = [
        'en' => 'English',
        'ar' => 'Arabic (العربية)',
        'ru' => 'Russian (Русский)',
        'de' => 'German (Deutsch)',
        'fr' => 'French (Français)',
        'fa' => 'Persian (فارسی)',
        'zh' => 'Simplified Chinese (简体中文)',
    ];

    public function generateLangFile(string $locale): array
    {
        if (! in_array($locale, self::SUPPORTED)) {
            return ['error' => "Unsupported locale: $locale"];
        }

        $trStrings = json_decode(
            file_get_contents(resource_path("lang/tr.json")),
            true
        );

        if (! $trStrings) {
            return ['error' => 'tr.json okunamadı'];
        }

        $translated = $this->translateBatch($locale, $trStrings);

        if (isset($translated['error'])) {
            return $translated;
        }

        file_put_contents(
            resource_path("lang/{$locale}.json"),
            json_encode($translated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return ['success' => true, 'locale' => $locale, 'count' => count($translated)];
    }

    private function translateBatch(string $locale, array $strings): array
    {
        $apiKey  = config('services.gemini.key', '');
        $model   = config('services.gemini.text_model', 'gemini-2.5-flash');

        if (! $apiKey) {
            return ['error' => 'GEMINI_API_KEY tanımlı değil'];
        }

        $glossaryLines = '';
        foreach (self::GLOSSARY as $tr => $translations) {
            if (isset($translations[$locale])) {
                $glossaryLines .= "  - \"{$tr}\" → \"{$translations[$locale]}\"\n";
            }
        }

        $isRtl  = in_array($locale, ['ar', 'fa']);
        $rtlNote = $isRtl
            ? "\n- Hedef dil RTL (sağdan sola) okunur. Latin/İngilizce kelimeler, rakamlar ve HTML değişkenleri sırayı bozmamalı."
            : '';

        $jsonInput = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Sen profesyonel bir turizm yerelleştirme (localization) ve uluslararası SEO uzmanısın.
Aşağıdaki JSON formatındaki Türkçe arayüz metinlerini **{$this->localeName($locale)}** diline çevir.

Kurallar:
- Kelime kelime çeviri yapma; hedef dilin doğal arayüz diline, turizm jargonuna ve kültürel bağlamına uygun çevir.
- Marka adını ("Grup Rezervasyonları") ve route key'lerini (nav_activities, footer_services gibi JSON key'lerini) ASLA çevirme.
- JSON key'leri aynen koru, sadece VALUE'ları çevir.
- HTML etiketleri veya {değişken} içerenleri yapısal olarak bozma.
- `meta_title` değerleri max 60 karakter, `meta_desc` değerleri max 160 karakter olsun.{$rtlNote}

Sözlük (bu terimleri tam olarak kullan):
{$glossaryLines}
Sadece geçerli JSON döndür. Başka hiçbir açıklama ekleme.

JSON:
{$jsonInput}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(120)->post($url, [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature'     => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if (! $response->successful()) {
            return ['error' => 'Gemini API hatası: ' . $response->status()];
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // JSON bloğunu temizle
        $text = preg_replace('/^```json\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Gemini geçersiz JSON döndürdü: ' . json_last_error_msg()];
        }

        return $decoded;
    }

    private function localeName(string $locale): string
    {
        return self::LOCALE_NAMES[$locale] ?? $locale;
    }
}
