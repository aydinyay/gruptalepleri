<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HeroTextService
{
    private static array $fallbacks = [
        [
            'baslik1' => 'Boğaz\'da akşam olmak',
            'baslik2' => 'başka bir şeydir.',
            'alt'     => 'Yat turundan dinner cruise\'a, transferden charter\'a — tam istediğiniz gibi.',
        ],
        [
            'baslik1' => 'Plan yok mu?',
            'baslik2' => 'Düzeltelim.',
            'alt'     => 'Tekne, tur, özel jet, viski tadımı... Grubunuz için ne lazımsa burada.',
        ],
        [
            'baslik1' => 'Hafta sonu bu kadar mı?',
            'baslik2' => 'Olmaz öyle.',
            'alt'     => 'Dinner cruise\'dan Kapadokya turuna dakikalar içinde rezervasyon.',
        ],
        [
            'baslik1' => 'Özel hissettirmek için',
            'baslik2' => 'doğru yerdesiniz.',
            'alt'     => 'Yat, charter, transfer ve daha fazlası — grubunuza özel fiyatlarla.',
        ],
        [
            'baslik1' => 'Akşamı kurtarmak mı,',
            'baslik2' => 'haftayı mı?',
            'alt'     => 'Dinner cruise\'dan özel jet\'e, tek platform — anında rezervasyon.',
        ],
    ];

    // Türkçe erkek isimleri (sık kullanılanlar)
    private static array $maleNames = [
        'ahmet','mehmet','mustafa','ali','hüseyin','hasan','ibrahim','ömer','ismail','süleyman',
        'yusuf','adem','osman','murat','ramazan','emre','burak','serkan','tolga','baran',
        'kemal','cem','can','deniz','oğuz','barış','koray','berkay','umut','onur',
        'orhan','kadir','selim','yılmaz','ercan','haydar','fuat','soner','turan','ilhan',
        'ferhat','cihan','erhan','volkan','tarık','necati','recep','şükrü','hamit','celal',
        'sinan','gökhan','alper','ufuk','zafer','kaan','berk','doğan','taner','serhat',
        'fatih','cengiz','savaş','levent','metin','yaşar','zeki','nuri','güven','ilyas',
        'hakan','oktay','uğur','özgür','arda','çağrı','erdem','cenk','serdar','kenan',
    ];

    // Türkçe kadın isimleri (sık kullanılanlar)
    private static array $femaleNames = [
        'ayşe','fatma','zeynep','elif','emine','hatice','sultan','meryem','havva','esra',
        'selin','merve','büşra','tuğba','hilal','gülşen','serap','sevgi','özlem','pınar',
        'neslihan','cansu','damla','melike','dilek','aysun','filiz','gamze','derya','ece',
        'gizem','ilknur','şeyma','yasemin','zeliha','nuray','sibel','perihan','şükran','gülay',
        'nesrin','sabriye','hülya','nurdan','aslı','deniz','ceren','bengü','seda','gül',
        'leyla','nalan','burcu','nurgül','reyhan','songül','tülay','belgin','betül','şebnem',
        'sevda','yeliz','arzu','ferda','tuba','nilgün','nihal','figen','nalan','emel',
        'hacer','meral','münire','raziye','safiye','selma','ümran','vesile','zübeyde','kevser',
    ];

    // Özel günler: "ay_gun" => açıklama
    private static array $specialDays = [
        '1_1'   => 'yilbasi',
        '2_14'  => 'sevgililer_gunu',
        '3_8'   => 'kadinlar_gunu',
        '4_23'  => 'cocuk_bayrami',
        '5_1'   => 'emek_bayrami',
        '5_19'  => 'genclik_bayrami',
        '6_21'  => 'yaz_gundonu',
        '8_30'  => 'zafer_bayrami',
        '10_29' => 'cumhuriyet_bayrami',
        '11_10' => 'ataturk_anma',
        '12_24' => 'noel_arife',
        '12_25' => 'noel',
        '12_31' => 'yilbasi_arife',
    ];

    public function getHeroPool(array $ctx, string $productSummary = ''): array
    {
        $poolKey = 'hero_v4_pool_' . md5(implode('|', [
            $ctx['zaman']         ?? '',
            $ctx['gun']           ?? '',
            $ctx['mevsim']        ?? '',
            $ctx['ozel_gun']      ?? '',
            $ctx['haftasonu_yak'] ?? '',
            $ctx['cinsiyet']      ?? '',
            $ctx['son_kategori']  ?? '',
            $ctx['sehir']         ?? '',
            $ctx['user_id']       ?? 'guest',
            md5($productSummary),
        ]));

        $ttl = isset($ctx['user_id']) ? 600 : 1800;

        return Cache::remember($poolKey, $ttl, function () use ($ctx, $productSummary) {
            return $this->callGeminiPool($ctx, $productSummary);
        });
    }

    public function heroReact(string $query, string $productContext = ''): array
    {
        // Cache key product context'e göre farklılaşır (aynı sorgu, farklı ürün = farklı yanıt)
        $cacheKey = 'hero_react_v3_' . md5(mb_strtolower(trim($query)) . '|' . $productContext);
        $ttl = $productContext ? 900 : 3600; // ürün verisi varsa 15dk, yoksa 1saat

        return Cache::remember($cacheKey, $ttl, function () use ($query, $productContext) {
            return $this->callGeminiReact($query, $productContext) ?? $this->randomFallback();
        });
    }

    public function getHeroText(array $ctx): array
    {
        $poolKey = 'hero_v3_pool_' . md5(implode('|', [
            $ctx['zaman']         ?? '',
            $ctx['gun']           ?? '',
            $ctx['mevsim']        ?? '',
            $ctx['ozel_gun']      ?? '',
            $ctx['haftasonu_yak'] ?? '',
            $ctx['cinsiyet']      ?? '',
            $ctx['son_kategori']  ?? '',
            $ctx['sehir']         ?? '',
            $ctx['user_id']       ?? 'guest',
        ]));

        $ttl  = isset($ctx['user_id']) ? 600 : 1800;
        $pool = Cache::remember($poolKey, $ttl, function () use ($ctx) {
            return $this->callGeminiPool($ctx);
        });

        // Her yenilemede pool'dan farklı bir metin — son kullanılan index session'da
        $sessionKey = 'hero_pool_idx_' . substr($poolKey, -8);
        $lastIdx    = session($sessionKey, -1);
        $count      = count($pool);
        $nextIdx    = ($lastIdx + 1) % $count;
        session([$sessionKey => $nextIdx]);

        return $pool[$nextIdx];
    }

    public static function buildContext(): array
    {
        $now    = Carbon::now('Europe/Istanbul');
        $hour   = $now->hour;
        $month  = $now->month;
        $day    = $now->day;
        $dow    = $now->dayOfWeek; // 0=Pazar, 1=Pt... 6=Ct

        $zaman = match(true) {
            $hour >= 6  && $hour < 12 => 'sabah',
            $hour >= 12 && $hour < 17 => 'öğle',
            $hour >= 17 && $hour < 22 => 'akşam',
            default                   => 'gece',
        };

        $gunler = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
        $gun    = $gunler[$dow];

        $gunTur = match($dow) {
            5       => 'cuma',
            6       => 'cumartesi',
            0       => 'pazar',
            default => 'hafta_ici',
        };

        $mevsim = match(true) {
            $month >= 3 && $month <= 5 => 'ilkbahar',
            $month >= 6 && $month <= 8 => 'yaz',
            $month >= 9 && $month <= 11 => 'sonbahar',
            default                    => 'kış',
        };

        $haftasonuYak = in_array($dow, [4, 5]) ? true : false; // Perşembe veya Cuma

        // Özel gün
        $ozelGun = static::$specialDays["{$month}_{$day}"] ?? null;

        // Auth
        $user   = auth('b2c')->user();
        $userId = $user?->id;
        $ad     = null;
        $cinsiyet = 'bilinmiyor';

        if ($user) {
            $parts = explode(' ', trim($user->name));
            $ad = $parts[0]; // sadece ilk ad
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

    private static function detectGender(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        if (in_array($name, static::$maleNames))   return 'erkek';
        if (in_array($name, static::$femaleNames))  return 'kadın';
        return 'bilinmiyor';
    }

    private function callGeminiReact(string $query, string $productContext = ''): ?array
    {
        $key = config('services.gemini.key');
        if (! $key) return null;

        $productSection = $productContext
            ? "\nPLATFORMDA BULUNAN EŞLEŞEN ÜRÜNLER (bunlara doğal biçimde değin, tarih/fiyat gerçekse kullan):\n{$productContext}\n"
            : '';

        $noMatchNote = str_contains($productContext, 'eşleşen ürün bulunamadı')
            ? "\nBu arama için sistemde ürün yok — ama platformun diğer zenginliklerine yönlendir.\n"
            : '';

        $prompt = <<<PROMPT
Kullanıcı gruprezervasyonlari.com'da arama kutusuna "{$query}" yazdı ve aramayı bitirdi.
Platform: yat, tekne, dinner cruise, havalimanı transferi, özel jet, Boğaz turu, charter, viski tadımı, Kapadokya turu — Türkiye'nin lider grup seyahat sitesi.
{$productSection}{$noMatchNote}
Sana düşen görev: aramayı görünce heyecanlanan, tatil seven, seyahat aşığı bir arkadaş gibi tepki ver.
Eğer gerçek ürün/tarih verisi verilmişse — bunu doğal biçimde cümleye yedir. ("3 gün sonra seans var", fiyat vs.)
Eğer ürün yoksa — başka neler olduğundan hafifçe bahset.

ÖRNEK YAKLAŞIMLAR (kopyalama ama ilham al):
- "bursa turu" (3 gün sonra seans var) → "3 gün sonra Bursa'dasın." / "Erken kalanın yeri var."
- "yat" → "Yatı görünce içim sıkıştı." / "Hadi rezervasyonu at!"
- "transfer" → "Havalimanında bekleme devri bitti." / "Araç kapıda, sen hazır ol."
- "dinner" → "Akşam yemeğini Boğaz'da yesene." / "Masa senin, Boğaz manzarası bedava."

KISITLAR:
- baslik1: max 32 karakter, sıcak ve kişisel
- baslik2: max 28 karakter, turuncu vurgu — punch line burası, en güçlü kısım
- alt: max 85 karakter, "{$query}" temasıyla platforma özgü bir ipucu ya da çağrı (varsa gerçek tarihi/fiyatı yansıt)
- Türkçe, günlük konuşma dili, ama zekice
- Asla kurumsal, asla "burada bulabilirsiniz", asla "hepsi burada"

SADECE JSON döndür:
{"baslik1":"...","baslik2":"...","alt":"..."}
PROMPT;

        try {
            $response = Http::timeout(6)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$key}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 150],
                ]
            );

            if (! $response->successful()) return null;

            $text = trim(preg_replace('/```json|```/i', '', $response->json('candidates.0.content.parts.0.text', '')));
            $data = json_decode($text, true);

            if (is_array($data) && isset($data['baslik1'], $data['baslik2'], $data['alt'])) {
                return [
                    'baslik1' => htmlspecialchars($data['baslik1'], ENT_QUOTES, 'UTF-8'),
                    'baslik2' => htmlspecialchars($data['baslik2'], ENT_QUOTES, 'UTF-8'),
                    'alt'     => htmlspecialchars($data['alt'],     ENT_QUOTES, 'UTF-8'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('HeroTextService react error: ' . $e->getMessage());
        }

        return null;
    }

    private function callGeminiPool(array $ctx, string $productSummary = ''): array
    {
        $key = config('services.gemini.key');
        if (! $key) return static::$fallbacks;

        $prompt = $this->buildPrompt($ctx, 5, $productSummary);

        try {
            $response = Http::timeout(12)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$key}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'     => 1.0,
                        'maxOutputTokens' => 800,
                    ],
                ]
            );

            if (! $response->successful()) return static::$fallbacks;

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = preg_replace('/```json|```/i', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);

            if (! is_array($data)) return static::$fallbacks;

            $pool = [];
            foreach ($data as $item) {
                if (
                    is_array($item)
                    && isset($item['baslik1'], $item['baslik2'], $item['alt'])
                    && mb_strlen($item['baslik1']) <= 40
                    && mb_strlen($item['baslik2']) <= 36
                    && mb_strlen($item['alt'])     <= 100
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
            Log::warning('HeroTextService Gemini error: ' . $e->getMessage());
            return static::$fallbacks;
        }
    }

    private function buildPrompt(array $ctx, int $count = 5, string $productSummary = ''): string
    {
        $bağlam = [];
        $bağlam[] = "Zaman: {$ctx['zaman']} ({$ctx['gun']})";
        $bağlam[] = "Mevsim: {$ctx['mevsim']}";

        if ($ctx['haftasonu_yak']) {
            $bağlam[] = "Hafta sonu yaklaşıyor";
        }
        if (in_array($ctx['gun_tur'] ?? '', ['cumartesi', 'pazar'])) {
            $bağlam[] = "Bugün hafta sonu";
        }
        if ($ctx['ozel_gun']) {
            $bağlam[] = "Özel gün: {$ctx['ozel_gun']}";
        }
        if ($ctx['ad'] && $ctx['cinsiyet'] !== 'bilinmiyor') {
            $unvan    = $ctx['cinsiyet'] === 'erkek' ? 'Bey' : 'Hanım';
            $bağlam[] = "Kullanıcı: {$ctx['ad']} {$unvan} (giriş yapmış)";
        } elseif ($ctx['ad']) {
            $bağlam[] = "Kullanıcı: {$ctx['ad']} (giriş yapmış)";
        }
        if ($ctx['son_kategori']) {
            $bağlam[] = "Az önce baktığı: {$ctx['son_kategori']}";
        }
        if ($ctx['sehir']) {
            $bağlam[] = "Şehir: {$ctx['sehir']}";
        }

        $bağlamStr = implode("\n- ", $bağlam);

        $countLine   = "{$count} FARKLI metin üret (hepsi farklı yaklaşım, farklı ton, farklı yapı).";
        $productLine = $productSummary
            ? "\nPLATFORMDAKİ GERÇEK ÜRÜN/KATEGORİLER (alt yazıda bunlara atıfta bulun):\n{$productSummary}\n"
            : '';

        return <<<PROMPT
Sen gruprezervasyonlari.com'un anasayfa hero başlığını yazıyorsun.
{$countLine}{$productLine}
Platform: Türkiye'nin lider grup seyahat sitesi — yat, tekne, dinner cruise, transfer, özel jet, tur, charter, viski tadımı ve daha fazlası.

BAĞLAM:
- {$bağlamStr}

TON VE KİŞİLİK — BU ÇOK ÖNEMLİ:
Zeki, espirili, kendi kendine güvenen bir ses. Reklam gibi değil, akıllı bir arkadaş gibi konuş.
Sloganlar kısa, vurucu, beklenmedik olmalı. Okuyunca "evet aynen" ya da hafif gülümseme bırakmalı.
Cümle yapısı alışılmadık olabilir. Soru sorabilir. Kontrast kurabilir. İma edebilir.

ASLA YAPMA:
- "Türkiye'nin lider..." gibi kurumsal laflar
- "Burada bulabilirsiniz" / "hepsi burada" gibi bayat kapanışlar
- "Grup seyahati" / "rezervasyon yapın" gibi generik ifadeler
- İki satır aynı ritimde bitmesin ("...yanında / ...burada")
- Klişe motivasyon sloganı ("Hayallerinizi yaşayın" vb.)

İYİ ÖRNEKLER (ilham için, kopyalama):
- "Boğaz'da akşam olmak / başka bir şeydir." / "Yat, cruise, transfer — tam zamanı."
- "Cuma akşamı planı yok mu? / Düzeltelim." / "Dinner cruise'dan yat turuna, dakikalar içinde."
- "Ayşe Hanım, geçen hafta / ne kaçırdınız biliyor musunuz?" / "Yat Turları kategorisine bakın deriz."
- "Kış ortasında Boğaz turu / çılgın mı? Tam da öyle." / "Özel tekne, sıcak çorba, harika manzara."
- "Plan değişti, program bozuldu mu? / Bizimkiler bozulmaz." / "Transfer'den charter'a anında rezervasyon."

KISITLAR:
- baslik1: maksimum 32 karakter
- baslik2: maksimum 28 karakter (turuncu renkte gösterilecek, en kuvvetli kısım burası)
- alt: maksimum 85 karakter, bağlamla ilgili, platforma özgü
- Türkçe, doğal konuşma dili
- Bağlamı kullan ama zorlamadan — doğal geliyorsa kişisel hitap ekle

SADECE JSON array döndür ({$count} eleman), başka hiçbir şey yazma:
[{"baslik1":"...","baslik2":"...","alt":"..."},{"baslik1":"...","baslik2":"...","alt":"..."},...]
PROMPT;
    }

    private function randomFallback(): array
    {
        return static::$fallbacks[array_rand(static::$fallbacks)];
    }
}
