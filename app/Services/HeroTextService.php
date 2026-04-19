<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HeroTextService
{
    private const POOL_VER  = 'v6';
    private const REACT_VER = 'v6';

    // ── Fallback metinler (API yoksa veya hata varsa) ───────────────────────
    private static array $fallbacks = [
        ['baslik1' => "Boğaz'da akşam olmak",       'baslik2' => 'başka bir şeydir.',         'alt' => "Yat turundan dinner cruise'a, transferden charter'a — tam istediğiniz gibi."],
        ['baslik1' => 'Plan yok mu?',                'baslik2' => 'Düzeltelim.',               'alt' => 'Tekne, tur, özel jet, viski tadımı... Grubunuz için ne lazımsa burada.'],
        ['baslik1' => 'Hafta sonu bu kadar mı?',     'baslik2' => 'Olmaz öyle.',               'alt' => "Dinner cruise'dan Kapadokya turuna dakikalar içinde rezervasyon."],
        ['baslik1' => 'Özel hissettirmek için',      'baslik2' => 'doğru yerdesiniz.',         'alt' => 'Yat, charter, transfer ve daha fazlası — grubunuza özel fiyatlarla.'],
        ['baslik1' => 'Akşamı kurtarmak mı,',        'baslik2' => 'haftayı mı?',               'alt' => "Dinner cruise'dan özel jet'e, tek platform — anında rezervasyon."],
    ];

    // ── Cinsiyet tespiti ─────────────────────────────────────────────────────
    private static array $maleNames = [
        'ahmet','mehmet','mustafa','ali','hüseyin','hasan','ibrahim','ömer','ismail','süleyman',
        'yusuf','adem','osman','murat','ramazan','emre','burak','serkan','tolga','baran',
        'kemal','cem','can','deniz','oğuz','barış','koray','berkay','umut','onur',
        'orhan','kadir','selim','yılmaz','ercan','haydar','fuat','soner','turan','ilhan',
        'ferhat','cihan','erhan','volkan','tarık','necati','recep','şükrü','hamit','celal',
        'sinan','gökhan','alper','ufuk','zafer','kaan','berk','doğan','taner','serhat',
        'fatih','cengiz','savaş','levent','metin','yaşar','zeki','nuri','güven','ilyas',
        'hakan','oktay','uğur','özgür','arda','çağrı','erdem','cenk','serdar','kenan',
        'aydın','aydin',
    ];

    private static array $femaleNames = [
        'ayşe','fatma','zeynep','elif','emine','hatice','sultan','meryem','havva','esra',
        'selin','merve','büşra','tuğba','hilal','gülşen','serap','sevgi','özlem','pınar',
        'neslihan','cansu','damla','melike','dilek','aysun','filiz','gamze','derya','ece',
        'gizem','ilknur','şeyma','yasemin','zeliha','nuray','sibel','perihan','şükran','gülay',
        'nesrin','sabriye','hülya','nurdan','aslı','ceren','bengü','seda','gül',
        'leyla','nalan','burcu','nurgül','reyhan','songül','tülay','belgin','betül','şebnem',
        'sevda','yeliz','arzu','ferda','tuba','nilgün','nihal','figen','emel',
        'hacer','meral','münire','raziye','safiye','selma','ümran','vesile','zübeyde','kevser',
        'güneş','gunes',
    ];

    // ── Özel günler ──────────────────────────────────────────────────────────
    private static array $specialDays = [
        '1_1'   => 'yilbasi',        '2_14'  => 'sevgililer_gunu',
        '3_8'   => 'kadinlar_gunu',  '4_23'  => 'cocuk_bayrami',
        '5_1'   => 'emek_bayrami',   '5_19'  => 'genclik_bayrami',
        '6_21'  => 'yaz_gundonu',    '8_30'  => 'zafer_bayrami',
        '10_29' => 'cumhuriyet_bayrami', '11_10' => 'ataturk_anma',
        '12_24' => 'noel_arife',     '12_25' => 'noel',
        '12_31' => 'yilbasi_arife',
    ];

    // ── Public API ───────────────────────────────────────────────────────────

    /** Anasayfa için 5'li metin havuzu — bağlama göre cache'lenir */
    public function getHeroPool(array $ctx, string $productSummary = ''): array
    {
        $cacheKey = self::POOL_VER . '_pool_' . md5(implode('|', [
            $ctx['zaman'] ?? '', $ctx['gun'] ?? '', $ctx['mevsim'] ?? '',
            $ctx['ozel_gun'] ?? '', (int) ($ctx['haftasonu_yak'] ?? 0),
            $ctx['cinsiyet'] ?? '', $ctx['son_kategori'] ?? '',
            $ctx['sehir'] ?? '', $ctx['user_id'] ?? 'guest',
            md5($productSummary),
        ]));

        $ttl = isset($ctx['user_id']) ? 600 : 1800;

        return Cache::remember($cacheKey, $ttl, fn () => $this->callGeminiPool($ctx, $productSummary));
    }

    /** Arama kutusu tepkisi — query + productContext bazında 15dk cache */
    public function heroReact(string $query, string $productContext = ''): array
    {
        $cacheKey = self::REACT_VER . '_react_' . md5(mb_strtolower(trim($query)) . '||' . $productContext);

        return Cache::remember($cacheKey, 900, function () use ($query, $productContext) {
            return $this->callGeminiReact($query, $productContext) ?? $this->randomFallback();
        });
    }

    /** Bağlam sinyallerini topla */
    public static function buildContext(): array
    {
        $now   = Carbon::now('Europe/Istanbul');
        $hour  = $now->hour;
        $month = $now->month;
        $day   = $now->day;
        $dow   = $now->dayOfWeek; // 0=Pazar … 6=Cumartesi

        $zaman = match (true) {
            $hour >= 6  && $hour < 12 => 'sabah',
            $hour >= 12 && $hour < 17 => 'öğle',
            $hour >= 17 && $hour < 22 => 'akşam',
            default                   => 'gece',
        };

        $gunler  = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        $gun     = $gunler[$dow];
        $gunTur  = match ($dow) { 5 => 'cuma', 6 => 'cumartesi', 0 => 'pazar', default => 'hafta_ici' };
        $mevsim  = match (true) {
            $month >= 3 && $month <= 5  => 'ilkbahar',
            $month >= 6 && $month <= 8  => 'yaz',
            $month >= 9 && $month <= 11 => 'sonbahar',
            default                     => 'kış',
        };

        $ozelGun      = static::$specialDays["{$month}_{$day}"] ?? null;
        $haftasonuYak = in_array($dow, [4, 5]);

        $user     = auth('b2c')->user();
        $userId   = $user?->id;
        $ad       = null;
        $cinsiyet = 'bilinmiyor';

        if ($user) {
            $ad       = explode(' ', trim($user->name))[0];
            $cinsiyet = static::detectGender($ad);
        }

        return [
            'zaman'         => $zaman,
            'gun'           => $gun,
            'gun_tur'       => $gunTur,
            'mevsim'        => $mevsim,
            'ozel_gun'      => $ozelGun,
            'haftasonu_yak' => $haftasonuYak,
            'ad'            => $ad,
            'cinsiyet'      => $cinsiyet,
            'user_id'       => $userId,
            'son_kategori'  => session('b2c_last_category'),
            'sehir'         => session('b2c_user_city'),
        ];
    }

    // ── Özel metotlar ────────────────────────────────────────────────────────

    private static function detectGender(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        if (in_array($name, static::$maleNames))  return 'erkek';
        if (in_array($name, static::$femaleNames)) return 'kadın';
        return 'bilinmiyor';
    }

    private function callGeminiReact(string $query, string $productContext = ''): ?array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) return null;

        // productContext'ten ürün sayısını çek (ör: "3 ürün bulundu")
        $count = 0;
        if (preg_match('/(\d+) ürün bulundu/', $productContext, $m)) {
            $count = (int) $m[1];
        }
        $hasProducts = $count > 0;

        if ($hasProducts) {
            $countText = $count === 1 ? '1 eşleşen ürün var' : "{$count} eşleşen ürün var";
            $prompt    = <<<PROMPT
Sen gruprezervasyonlari.com anasayfasını CANLI izleyen bir asistansın.
Kullanıcı "{$query}" yazdı ve durdu. Sistemi az önce kontrol ettin, sonuç:

{$productContext}

GÖREV: "dur baktım, işte buldum!" enerjiyle kişisel ve SOMUT bir cevap yaz.

ZORUNLU KURALLAR:
1. "{$query}" kelimesi veya yakın varyasyonu baslik1 VEYA baslik2'de MUTLAKA geçmeli
2. "{$countText}" — bu sayıyı cümleye yedir ("2 seçenek", "tam 3 tur" vb.)
3. Tarih veya fiyat bilgisi varsa alt metninde KULLAN
4. Ton: az önce mesaj atmış, heyecanlı arkadaş — "oha, baktım!" enerjisi
5. ASLA: "burada bulabilirsiniz", "hemen rezervasyon yapın", robotik/kurumsal laf

İLHAM ÖRNEKLER (kopyalama, sadece fikir):
"Sapanca Turu" 2 tur → "Sapanca için tam 2 seçenek!" / "Hafta sonu gitmeye değer." / "850 TRY'den — hafta sonu boş mu?"
"Boğaz Turu" 2 tur 55 EUR → "Boğaz turu bekliyordu seni." / "2 seçenek, 55 EUR'dan." / "Alkollü veya alkolsüz — ikisi de sahane."
"yat" 3 tur, 3 gün sonra kalkış → "Yat mı? 3 seçeneğim var." / "3 gün sonra kalkış da var!" / "Günlük, haftalık ya da Boğaz turu."

KISITLAR (AŞILMAZ):
- baslik1: maksimum 32 karakter
- baslik2: maksimum 28 karakter (turuncu gösterilir — punch line)
- alt: maksimum 85 karakter — somut bilgi içermeli, fiyat/tarih tercih edilir
- Türkçe, samimi konuşma dili

SADECE JSON döndür, başka hiçbir şey yazma:
{"baslik1":"...","baslik2":"...","alt":"..."}
PROMPT;
        } else {
            $prompt = <<<PROMPT
Sen gruprezervasyonlari.com anasayfasını CANLI izleyen bir asistansın.
Kullanıcı "{$query}" yazdı ama bu isimle eşleşen ürün sistemde yok.

ZORUNLU KURALLAR:
1. "{$query}" kelimesi baslik1'de MUTLAKA geçmeli
2. Dürüst ve nazikçe "yok ama benzerine bak" de
3. Platformdaki alternatiflerden söz et (yat, dinner cruise, Kapadokya, Sapanca vb.)
4. Pozitif kal — hayal kırıklığı yaratma

ÖRNEK: "Konya Turu" yok → baslik1="Konya turu henüz yok," baslik2="ama benzerine bak." alt="Sapanca, Kapadokya veya Boğaz turlarına göz at."

KISITLAR: baslik1 max 32 | baslik2 max 28 (turuncu) | alt max 85 | Türkçe

SADECE JSON döndür:
{"baslik1":"...","baslik2":"...","alt":"..."}
PROMPT;
        }

        try {
            $response = Http::timeout(8)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.95, 'maxOutputTokens' => 200],
                ]
            );

            if (! $response->successful()) return null;

            $raw  = $response->json('candidates.0.content.parts.0.text', '');
            $raw  = trim(preg_replace('/```json|```/i', '', $raw));
            $data = json_decode($raw, true);

            if (is_array($data) && isset($data['baslik1'], $data['baslik2'], $data['alt'])) {
                return [
                    'baslik1' => htmlspecialchars(mb_substr($data['baslik1'], 0, 36), ENT_QUOTES, 'UTF-8'),
                    'baslik2' => htmlspecialchars(mb_substr($data['baslik2'], 0, 32), ENT_QUOTES, 'UTF-8'),
                    'alt'     => htmlspecialchars(mb_substr($data['alt'],     0, 100), ENT_QUOTES, 'UTF-8'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('HeroTextService::heroReact error: ' . $e->getMessage());
        }

        return null;
    }

    private function callGeminiPool(array $ctx, string $productSummary = ''): array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) return static::$fallbacks;

        $prompt = $this->buildPoolPrompt($ctx, 5, $productSummary);

        try {
            $response = Http::timeout(12)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 900],
                ]
            );

            if (! $response->successful()) return static::$fallbacks;

            $raw  = trim(preg_replace('/```json|```/i', '', $response->json('candidates.0.content.parts.0.text', '')));
            $data = json_decode($raw, true);

            if (! is_array($data)) return static::$fallbacks;

            $pool = [];
            foreach ($data as $item) {
                if (
                    is_array($item)
                    && isset($item['baslik1'], $item['baslik2'], $item['alt'])
                    && mb_strlen($item['baslik1']) <= 42
                    && mb_strlen($item['baslik2']) <= 36
                    && mb_strlen($item['alt'])     <= 105
                ) {
                    $pool[] = [
                        'baslik1' => htmlspecialchars($item['baslik1'], ENT_QUOTES, 'UTF-8'),
                        'baslik2' => htmlspecialchars($item['baslik2'], ENT_QUOTES, 'UTF-8'),
                        'alt'     => htmlspecialchars($item['alt'],     ENT_QUOTES, 'UTF-8'),
                    ];
                }
            }

            return count($pool) >= 2 ? $pool : static::$fallbacks;
        } catch (\Throwable $e) {
            Log::warning('HeroTextService::pool error: ' . $e->getMessage());
            return static::$fallbacks;
        }
    }

    private function buildPoolPrompt(array $ctx, int $count, string $productSummary): string
    {
        $lines   = ["Zaman: {$ctx['zaman']} ({$ctx['gun']})", "Mevsim: {$ctx['mevsim']}"];
        if ($ctx['haftasonu_yak'])                             $lines[] = 'Hafta sonu yaklaşıyor';
        if (in_array($ctx['gun_tur'] ?? '', ['cumartesi', 'pazar'])) $lines[] = 'Bugün hafta sonu';
        if ($ctx['ozel_gun'])                                  $lines[] = "Özel gün: {$ctx['ozel_gun']}";
        if ($ctx['ad']) {
            $unvan   = $ctx['cinsiyet'] === 'erkek' ? 'Bey' : ($ctx['cinsiyet'] === 'kadın' ? 'Hanım' : '');
            $lines[] = "Kullanıcı: {$ctx['ad']}" . ($unvan ? " {$unvan} (giriş yapmış)" : ' (giriş yapmış)');
        }
        if ($ctx['son_kategori']) $lines[] = "Az önce baktığı: {$ctx['son_kategori']}";
        if ($ctx['sehir'])        $lines[] = "Şehir: {$ctx['sehir']}";

        $bağlamStr   = '- ' . implode("\n- ", $lines);
        $productLine = $productSummary
            ? "\nPLATFORMDAKİ GERÇEK ÜRÜN/KATEGORİLER (alt yazıda bunlara atıfta bulun):\n{$productSummary}\n"
            : '';

        return <<<PROMPT
Sen gruprezervasyonlari.com anasayfa hero başlığını yazıyorsun.
{$count} FARKLI metin üret (her biri farklı yaklaşım, farklı ton, farklı yapı).
Platform: Türkiye'nin lider grup seyahat sitesi — yat, tekne, dinner cruise, transfer, özel jet, tur, charter, viski tadımı ve daha fazlası.
{$productLine}
BAĞLAM:
{$bağlamStr}

TON VE KİŞİLİK (ÇOK ÖNEMLİ):
Zeki, espirili, kendi kendine güvenen bir ses. Reklam değil — akıllı bir arkadaş.
Sloganlar kısa, vurucu, beklenmedik. Okuyunca "evet aynen" veya hafif gülümseme bırakmalı.
Soru sorabilir. Kontrast kurabilir. İma edebilir. Alışılmadık cümle yapısı olabilir.

ASLA YAPMA:
- "Türkiye'nin lider..." gibi kurumsal laflar
- "Burada bulabilirsiniz" / "hepsi burada" gibi bayat kapanışlar
- "Grup seyahati" / "rezervasyon yapın" gibi generik ifadeler
- İki satır aynı ritimde bitmesi ("...yanında / ...burada")
- "Hayallerinizi yaşayın" tarzı klişe motivasyon

İYİ ÖRNEKLER (ilham için, kopyalama):
- "Boğaz'da akşam olmak / başka bir şeydir." / "Yat, cruise, transfer — tam zamanı."
- "Cuma akşamı planı yok mu? / Düzeltelim." / "Dinner cruise'dan yat turuna dakikalar içinde."
- "Kış ortasında Boğaz turu / çılgın mı? Tam da öyle." / "Özel tekne, sıcak çorba, harika manzara."
- "Plan değişti, program bozuldu mu? / Bizimkiler bozulmaz." / "Transfer'den charter'a anında."

KISITLAR:
- baslik1: maksimum 32 karakter
- baslik2: maksimum 28 karakter (turuncu renkte gösterilecek, en kuvvetli kısım)
- alt: maksimum 85 karakter, bağlamla ilgili, platforma özgü
- Türkçe, doğal konuşma dili

SADECE JSON array döndür ({$count} eleman), başka hiçbir şey yazma:
[{"baslik1":"...","baslik2":"...","alt":"..."},...]
PROMPT;
    }

    private function randomFallback(): array
    {
        return static::$fallbacks[array_rand(static::$fallbacks)];
    }
}
