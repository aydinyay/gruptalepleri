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
            'baslik1' => 'Keşfedin, karşılaştırın,',
            'baslik2' => 'rezervasyon yapın.',
            'alt'     => "Transfer'den charter'a, dinner cruise'dan yat kiralama'ya — hepsi burada.",
        ],
        [
            'baslik1' => 'Türkiye\'nin dört bir yanında',
            'baslik2' => 'grup seyahati burada.',
            'alt'     => 'Havalimanı transferinden özel yat turuna — en iyi grup fiyatları için doğru adres.',
        ],
        [
            'baslik1' => 'Grubunuzla unutulmaz',
            'baslik2' => 'anlar yaratın.',
            'alt'     => 'Dinner cruise, tur, charter ve daha fazlası — tek platformda, en uygun fiyatla.',
        ],
        [
            'baslik1' => 'Her grup için özel',
            'baslik2' => 'seyahat çözümleri.',
            'alt'     => 'İstanbul\'dan Kapadokya\'ya, transferden yat turuna — grubunuza özel fiyatlar burada.',
        ],
        [
            'baslik1' => 'Seyahat planlamak',
            'baslik2' => 'artık çok kolay.',
            'alt'     => 'Türkiye\'nin en büyük grup seyahat platformunda tüm aktiviteler tek tıkla.',
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

    public function getHeroText(array $ctx): array
    {
        $key = 'hero_' . md5(implode('|', [
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

        $ttl = isset($ctx['user_id']) ? 600 : 1800; // giriş yaptıysa 10dk, misafir 30dk

        return Cache::remember($key, $ttl, function () use ($ctx) {
            return $this->callGemini($ctx) ?? $this->randomFallback();
        });
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

    private function callGemini(array $ctx): ?array
    {
        $key = config('services.gemini.key');
        if (! $key) return null;

        $prompt = $this->buildPrompt($ctx);

        try {
            $response = Http::timeout(8)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$key}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'     => 0.9,
                        'maxOutputTokens' => 200,
                    ],
                ]
            );

            if (! $response->successful()) return null;

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = preg_replace('/```json|```/i', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);

            if (
                is_array($data)
                && isset($data['baslik1'], $data['baslik2'], $data['alt'])
                && mb_strlen($data['baslik1']) <= 40
                && mb_strlen($data['baslik2']) <= 36
                && mb_strlen($data['alt'])     <= 100
            ) {
                return [
                    'baslik1' => htmlspecialchars($data['baslik1'], ENT_QUOTES, 'UTF-8'),
                    'baslik2' => htmlspecialchars($data['baslik2'], ENT_QUOTES, 'UTF-8'),
                    'alt'     => htmlspecialchars($data['alt'],     ENT_QUOTES, 'UTF-8'),
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('HeroTextService Gemini error: ' . $e->getMessage());
            return null;
        }
    }

    private function buildPrompt(array $ctx): string
    {
        $lines = [
            "Sen gruprezervasyonlari.com için anasayfa hero metni yazıyorsun.",
            "Bu, Türkiye'nin lider grup seyahat platformudur (yat, transfer, tur, dinner cruise, charter vb.).",
            "",
            "BAĞLAM:",
            "- Zaman: {$ctx['zaman']} ({$ctx['gun']})",
            "- Mevsim: {$ctx['mevsim']}",
        ];

        if ($ctx['haftasonu_yak']) {
            $lines[] = "- Hafta sonu yaklaşıyor (Perşembe/Cuma)";
        }
        if (in_array($ctx['gun_tur'] ?? '', ['cumartesi', 'pazar'])) {
            $lines[] = "- Bugün hafta sonu";
        }
        if ($ctx['ozel_gun']) {
            $lines[] = "- Özel gün: {$ctx['ozel_gun']}";
        }
        if ($ctx['ad'] && $ctx['cinsiyet'] !== 'bilinmiyor') {
            $unvan  = $ctx['cinsiyet'] === 'erkek' ? 'Bey' : 'Hanım';
            $lines[] = "- Giriş yapmış kullanıcı: {$ctx['ad']} {$unvan}";
        } elseif ($ctx['ad']) {
            $lines[] = "- Giriş yapmış kullanıcı: {$ctx['ad']}";
        }
        if ($ctx['son_kategori']) {
            $lines[] = "- Son baktığı kategori: {$ctx['son_kategori']}";
        }
        if ($ctx['sehir']) {
            $lines[] = "- Kullanıcı şehri: {$ctx['sehir']}";
        }

        $lines = array_merge($lines, [
            "",
            "KISITLAR:",
            "- baslik1: maksimum 32 karakter, merak uyandırıcı, ilk satır",
            "- baslik2: maksimum 28 karakter, turuncu vurgu rengiyle gösterilecek, güçlü kapanış",
            "- alt: maksimum 85 karakter, platformun çeşitliliğini/avantajını 1 cümlede yansıt",
            "- Ton: sıcak, samimi, keşif odaklı (satış baskısı yok)",
            "- Dil: Türkçe, doğal akıcı",
            "- Kişisel hitap varsa doğal kullan, yoksa genel yaz",
            "",
            "SADECE geçerli JSON döndür, başka hiçbir şey yazma:",
            '{"baslik1":"...","baslik2":"...","alt":"..."}',
        ]);

        return implode("\n", $lines);
    }

    private function randomFallback(): array
    {
        return static::$fallbacks[array_rand(static::$fallbacks)];
    }
}
