<?php

namespace App\Services;

use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogSession;
use App\Models\B2C\GrAiMemory;
use App\Models\B2C\GrAiSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrAiService
{
    private const GEMINI_MODEL = 'gemini-1.5-flash';
    private const MAX_HISTORY  = 12; // son kaç mesaj context'e girer

    // ── Ana giriş noktası ────────────────────────────────────────────────────

    /**
     * Kullanıcı mesajını al, context'i kur, Gemini'yi çağır, öğren, kaydet.
     *
     * @return array{reply: string, products: array, error: bool}
     */
    public function chat(string $message, ?int $userId, string $guestUuid): array
    {
        try {
            return $this->doChat($message, $userId, $guestUuid);
        } catch (\Throwable $e) {
            Log::error('GrAiService::chat fatal: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return $this->errorReply('Bir hata oluştu, birazdan tekrar dene.');
        }
    }

    private function doChat(string $message, ?int $userId, string $guestUuid): array
    {
        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            return $this->errorReply('Yapay zeka şu an çevrimdışı.');
        }

        // Kullanıcı mesajını kaydet
        GrAiSession::addMessage($userId, $guestUuid, 'user', $message);

        // Context parçalarını topla
        $memories       = GrAiMemory::getFor($userId, $guestUuid);
        $history        = GrAiSession::historyFor($userId, $guestUuid, self::MAX_HISTORY);
        $relevantItems  = $this->fetchRelevantProducts($message);
        $timeCtx        = $this->buildTimeContext();
        $userCtx        = $this->buildUserContext($userId);

        // Prompt oluştur
        $systemPrompt = $this->buildSystemPrompt($memories, $timeCtx, $userCtx, $relevantItems);

        // Gemini'ye gönder
        $raw = $this->callGemini($apiKey, $systemPrompt, $history, $message);
        if (! $raw) {
            return $this->errorReply('Şu an cevap üretemiyorum, birazdan tekrar dene.');
        }

        // JSON parse
        $data  = json_decode($raw, true);
        $reply = is_array($data) && isset($data['reply']) ? $data['reply'] : $raw;
        $learn = is_array($data) && isset($data['learn']) ? (array) $data['learn'] : [];
        $slugs = is_array($data) && isset($data['products']) ? (array) $data['products'] : [];

        // Yanıtı kaydet
        GrAiSession::addMessage($userId, $guestUuid, 'assistant', $reply, $slugs ?: null);

        // Hafızayı güncelle
        $this->applyLearnings($userId, $guestUuid, $learn);

        // Önerilen ürünlerin detaylarını çek
        $suggestedProducts = $this->fetchProductsBySlug($slugs);

        return [
            'reply'    => $reply,
            'products' => $suggestedProducts,
            'error'    => false,
        ];
    }

    // ── Context builder'lar ──────────────────────────────────────────────────

    private function buildTimeContext(): array
    {
        $now    = Carbon::now('Europe/Istanbul');
        $hour   = $now->hour;
        $dow    = $now->dayOfWeek;
        $month  = $now->month;
        $day    = $now->day;

        $zaman = match (true) {
            $hour >= 6  && $hour < 12 => 'sabah',
            $hour >= 12 && $hour < 17 => 'öğle',
            $hour >= 17 && $hour < 22 => 'akşam',
            default                   => 'gece',
        };
        $gunler  = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
        $mevsim  = match (true) {
            $month >= 3 && $month <= 5  => 'ilkbahar',
            $month >= 6 && $month <= 8  => 'yaz',
            $month >= 9 && $month <= 11 => 'sonbahar',
            default                     => 'kış',
        };

        return [
            'tarih'      => $now->format('d.m.Y'),
            'saat'       => $now->format('H:i'),
            'zaman'      => $zaman,
            'gun'        => $gunler[$dow],
            'mevsim'     => $mevsim,
            'haftasonu'  => in_array($dow, [0, 6]),
        ];
    }

    private function buildUserContext(?int $userId): array
    {
        if (! $userId) return [];

        $user = \App\Models\B2C\B2cUser::find($userId);
        if (! $user) return [];

        $ad = explode(' ', trim($user->name))[0];

        // Basit cinsiyet tespiti (erkek/kadın isimleri)
        $maleNames   = ['ahmet','mehmet','mustafa','ali','hüseyin','emre','burak','murat','kaan','serkan','fatih','hakan','ömer','ibrahim','aydın'];
        $femaleNames = ['ayşe','fatma','zeynep','elif','emine','selin','merve','büşra','damla','cansu','gizem','ceren','ece','aslı','güneş'];
        $nameL    = mb_strtolower($ad, 'UTF-8');
        $cinsiyet = in_array($nameL, $maleNames) ? 'erkek' : (in_array($nameL, $femaleNames) ? 'kadın' : 'bilinmiyor');

        return [
            'ad'       => $ad,
            'cinsiyet' => $cinsiyet,
            'unvan'    => $cinsiyet === 'erkek' ? 'Bey' : ($cinsiyet === 'kadın' ? 'Hanım' : ''),
        ];
    }

    private function fetchRelevantProducts(string $message): array
    {
        // Kısa mesajlarda DB sorgusu yapma
        if (mb_strlen($message) < 3) return [];

        $words = array_filter(
            preg_split('/\s+/', mb_strtolower($message)),
            fn ($w) => mb_strlen($w) >= 3
        );
        if (empty($words)) return [];

        $query = CatalogItem::published()
            ->where(function ($q) use ($message, $words) {
                $q->where('title', 'like', "%{$message}%")
                  ->orWhere('destination_city', 'like', "%{$message}%");
                foreach ($words as $w) {
                    $q->orWhere('title', 'like', "%{$w}%")
                      ->orWhere('destination_city', 'like', "%{$w}%")
                      ->orWhere('short_desc', 'like', "%{$w}%");
                }
            })
            ->limit(4)
            ->get(['id', 'slug', 'title', 'destination_city', 'base_price', 'currency',
                   'duration_days', 'duration_hours', 'product_subtype']);

        if ($query->isEmpty()) return [];

        // Yaklaşan seansları çek
        $itemIds  = $query->pluck('id')->all();
        $sessions = CatalogSession::whereIn('catalog_item_id', $itemIds)
            ->upcoming()->limit(4)
            ->get(['catalog_item_id', 'session_date']);

        $now   = Carbon::now('Europe/Istanbul');
        $lines = [];
        foreach ($query as $item) {
            $line = "• {$item->title}";
            if ($item->destination_city) $line .= " ({$item->destination_city})";
            if ($item->base_price)       $line .= " — {$item->base_price} {$item->currency}";
            if ($item->duration_days)    $line .= ", {$item->duration_days} gün";
            elseif ($item->duration_hours) $line .= ", {$item->duration_hours} saat";

            $sess = $sessions->firstWhere('catalog_item_id', $item->id);
            if ($sess) {
                $diff = (int) $now->startOfDay()->diffInDays(
                    Carbon::parse($sess->session_date)->startOfDay(), false
                );
                if ($diff === 0)     $line .= ' [BUGÜN]';
                elseif ($diff === 1) $line .= ' [YARIN]';
                elseif ($diff > 0)   $line .= " [{$diff} gün sonra]";
            }
            $line  .= " [slug:{$item->slug}]";
            $lines[] = $line;
        }

        return $lines;
    }

    private function fetchProductsBySlug(array $slugs): array
    {
        if (empty($slugs)) return [];

        return CatalogItem::published()
            ->whereIn('slug', array_slice($slugs, 0, 3))
            ->get(['slug', 'title', 'destination_city', 'base_price', 'currency',
                   'cover_image', 'rating', 'duration_days', 'duration_hours'])
            ->toArray();
    }

    // ── Prompt ───────────────────────────────────────────────────────────────

    private function buildSystemPrompt(array $memories, array $time, array $user, array $products): string
    {
        $memoriesText = empty($memories)
            ? 'Henüz öğrenilmiş bir tercih yok.'
            : collect($memories)->map(fn ($v, $k) => "  {$k}: {$v}")->implode("\n");

        $productsText = empty($products)
            ? ''
            : "İLGİLİ ÜRÜNLER (veritabanından):\n" . implode("\n", $products) . "\n\n";

        $userLine = '';
        if (! empty($user['ad'])) {
            $unvan    = $user['unvan'] ?? '';
            $userLine = "Kullanıcı: {$user['ad']}" . ($unvan ? " {$unvan}" : '') . "\n";
        }

        $haftasonu = $time['haftasonu'] ? ' (hafta sonu)' : '';

        return <<<PROMPT
Sen gruprezervasyonlari.com'un yapay zeka asistanısın. Adın GR (okunuşu: Ciar).

Platform: Türkiye'nin lider grup seyahat sitesi — yat turu, dinner cruise, havalimanı transferi, özel jet, charter, Boğaz turu, Kapadokya turu ve daha fazlası.

ŞU ANKİ BAĞLAM:
Tarih/Saat: {$time['tarih']} {$time['saat']} ({$time['zaman']}, {$time['gun']}{$haftasonu})
Mevsim: {$time['mevsim']}
{$userLine}
BU KULLANICIDAN ÖĞRENİLENLER (hafıza):
{$memoriesText}

{$productsText}SEN NASIL KONUŞURSUN:
- Samimi, sıcak, akıllı bir rehber gibi
- Kısa ve öz — roman yazma, 2-3 cümle genellikle yeter
- Ürün/tarih/fiyat bilgisi varsa somut söyle
- Kullanıcının adını biliyorsan ara ara kullan ama her cümlede değil
- Emoji kullanabilirsin ama abartma
- Asla "yapay zeka olarak" veya "bir AI olarak" deme
- Asla "Merhaba! Size nasıl yardımcı olabilirim?" gibi robotik başlangıç yapma

ÇIKTI FORMATI — SADECE JSON döndür:
{
  "reply": "kullanıcıya cevap metni (Türkçe, markdown destekli)",
  "learn": [
    {"key": "anahtar", "value": "öğrenilen değer"}
  ],
  "products": ["slug1", "slug2"]
}

"learn" alanı: bu mesajdan yeni bir şey öğrendiysen doldur. Boş bırakabilirsin.
  Geçerli key'ler: ilgi_alanlari, sehir, butce (dusuk/orta/yuksek), tercih_zaman, grup_boyutu, not
"products": varsa önerilen ürünlerin slug'larını buraya koy (max 3). Öneri yoksa boş bırak.

Sadece JSON döndür, başka hiçbir şey yazma.
PROMPT;
    }

    // ── Gemini çağrısı ───────────────────────────────────────────────────────

    private function callGemini(string $apiKey, string $systemPrompt, array $history, string $userMessage): ?string
    {
        // Geçmiş mesajları (mevcut user mesajı hariç) metin olarak göm
        $historyText = '';
        $past = array_slice($history, 0, -1); // son eleman = az önce kaydedilen user mesajı
        if (! empty($past)) {
            $historyText .= "\n\nSOHBET GEÇMİŞİ:\n";
            foreach ($past as $msg) {
                $who          = $msg['role'] === 'user' ? 'Kullanıcı' : 'GR';
                $historyText .= "{$who}: {$msg['message']}\n";
            }
            $historyText .= "\n";
        }

        $fullPrompt = $systemPrompt . $historyText
            . "\nKullanıcının şu anki mesajı: \"{$userMessage}\"\n"
            . "\nYanıtını SADECE JSON olarak ver: {\"reply\":\"...\",\"learn\":[],\"products\":[]}";

        try {
            $response = Http::timeout(12)->post(
                "https://generativelanguage.googleapis.com/v1/models/" . self::GEMINI_MODEL . ":generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $fullPrompt]]]],
                    'generationConfig' => [
                        'temperature'     => 0.85,
                        'maxOutputTokens' => 600,
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::warning('GrAiService Gemini HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400));
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');
            return trim(preg_replace('/```json|```/i', '', $text));
        } catch (\Throwable $e) {
            Log::warning('GrAiService exception: ' . $e->getMessage());
            return null;
        }
    }

    // ── Öğrenme ──────────────────────────────────────────────────────────────

    private function applyLearnings(?int $userId, string $guestUuid, array $learn): void
    {
        $allowed = ['ilgi_alanlari', 'sehir', 'butce', 'tercih_zaman', 'grup_boyutu', 'not'];

        foreach ($learn as $item) {
            if (! is_array($item)) continue;
            $key   = $item['key'] ?? null;
            $value = $item['value'] ?? null;
            if (! $key || ! $value || ! in_array($key, $allowed)) continue;

            GrAiMemory::upsertFor($userId, $guestUuid, $key, (string) $value, 70);
        }
    }

    // ── Yardımcı ─────────────────────────────────────────────────────────────

    private function errorReply(string $message): array
    {
        return ['reply' => $message, 'products' => [], 'error' => true];
    }
}
