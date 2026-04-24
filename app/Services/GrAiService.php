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
    private const GEMINI_MODEL = 'gemini-2.5-flash';
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
        $systemPrompt = $this->buildSystemPrompt($memories, $timeCtx, $userCtx, $relevantItems, $userId === null);

        // Gemini'ye gönder
        $raw = $this->callGemini($apiKey, $systemPrompt, $history, $message);
        if (! $raw) {
            return $this->errorReply('Şu an cevap üretemiyorum, birazdan tekrar dene.');
        }

        // JSON parse — kesik gelirse regex ile reply'ı kurtar
        $data  = json_decode($raw, true);
        if (is_array($data) && isset($data['reply'])) {
            $reply        = $data['reply'];
            $learn        = isset($data['learn']) ? (array) $data['learn'] : [];
            $slugs        = isset($data['products']) ? (array) $data['products'] : [];
            $redirect     = isset($data['redirect']) ? (string) $data['redirect'] : null;
            $wishlistAdd  = isset($data['wishlist_add']) && $data['wishlist_add'] ? (string) $data['wishlist_add'] : null;
            $priceAlert   = isset($data['price_alert']) && $data['price_alert'] ? (string) $data['price_alert'] : null;
        } elseif (preg_match('/"reply"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/su', $raw, $m)) {
            $reply        = stripslashes($m[1]);
            $learn        = [];
            $slugs        = [];
            $redirect     = null;
            $wishlistAdd  = null;
            $priceAlert   = null;
        } else {
            return $this->errorReply('Şu an cevap üretemiyorum, birazdan tekrar dene.');
        }

        // Yanıtı kaydet
        GrAiSession::addMessage($userId, $guestUuid, 'assistant', $reply, $slugs ?: null);

        // Hafızayı güncelle
        $this->applyLearnings($userId, $guestUuid, $learn);

        // Önerilen ürünlerin detaylarını çek
        $suggestedProducts = $this->fetchProductsBySlug($slugs);

        // Redirect doğrula — sabit sayfalar direkt geçer, ürün slug'ları DB'den kontrol edilir
        $staticPages = ['/grup-ucak-talebi', '/transfer', '/hizmetler', '/blog', '/hakkimizda', '/iletisim'];
        if ($redirect) {
            $redirectPath = parse_url($redirect, PHP_URL_PATH) ?? $redirect;
            if (in_array($redirectPath, $staticPages)) {
                $redirect = $redirectPath; // sabit sayfa — doğrulama gerekmiyor
            } else {
            $rSlug = trim(str_replace('/urun/', '', $redirectPath), '/');
            if (! CatalogItem::published()->where('slug', $rSlug)->exists()) {
                $fallbackSlug = null;
                // 1) $slugs içinde geçerli bir slug ara
                foreach ($slugs as $s) {
                    if (CatalogItem::published()->where('slug', $s)->exists()) {
                        $fallbackSlug = $s; break;
                    }
                }
                // 2) Bu mesajdaki ürün context'inden al
                if (! $fallbackSlug) {
                    foreach ($relevantItems as $line) {
                        if (preg_match('/\[slug:([^\]]+)\]/', $line, $sm)) {
                            $fallbackSlug = $sm[1]; break;
                        }
                    }
                }
                // 3) Son asistan mesajındaki önerilen slug'ları tara
                if (! $fallbackSlug) {
                    $lastAssistant = collect($history)->last(fn($m) => $m['role'] === 'assistant');
                    $prevSlugs = $lastAssistant['suggested_slugs'] ?? [];
                    if (is_string($prevSlugs)) $prevSlugs = json_decode($prevSlugs, true) ?? [];
                    foreach ((array)$prevSlugs as $s) {
                        if (CatalogItem::published()->where('slug', $s)->exists()) {
                            $fallbackSlug = $s; break;
                        }
                    }
                }
                $redirect = $fallbackSlug ? '/urun/' . $fallbackSlug : null;
            }
            } // end else (ürün sayfası doğrulama)
        }

        return [
            'reply'        => $reply,
            'products'     => $suggestedProducts,
            'redirect'     => $redirect ?? null,
            'wishlist_add' => $wishlistAdd ?? null,
            'price_alert'  => $priceAlert  ?? null,
            'error'        => false,
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
        if (mb_strlen($message) < 3) return [];

        $stopwords = ['bir', 'var', 'mış', 'mı', 'mi', 'mu', 'mü', 'ne', 'bu', 'da', 'de',
                      've', 'ile', 'için', 'ama', 'ben', 'sen', 'biz', 'siz', 'selam',
                      'merhaba', 'evet', 'hayır', 'tamam', 'acaba', 'gibi', 'çok', 'daha',
                      'varmış', 'istiyorum', 'arıyorum', 'bulmak', 'nedir', 'nasıl', 'hala'];

        $allWords = preg_split('/\s+/', mb_strtolower($message, 'UTF-8'));
        // Önce stopword'suz anlamlı kelimeler
        $meaningful = array_values(array_filter($allWords, fn($w) =>
            mb_strlen($w, 'UTF-8') >= 3 && !in_array($w, $stopwords)
        ));
        // Fallback: tüm kelimeler
        $words = !empty($meaningful) ? $meaningful : array_filter($allWords, fn($w) => mb_strlen($w) >= 3);
        if (empty($words)) return [];

        // Her anlamlı kelime için ayrı sorgu — böylece "viski" "turu" tarafından ezilmez
        $collected = collect();
        foreach ($words as $w) {
            if ($collected->count() >= 4) break;
            $hits = CatalogItem::published()
                ->where(function ($q) use ($w) {
                    $q->where('title', 'like', "%{$w}%")
                      ->orWhere('destination_city', 'like', "%{$w}%")
                      ->orWhere('short_desc', 'like', "%{$w}%");
                })
                ->whereNotIn('id', $collected->pluck('id')->all())
                ->limit(4 - $collected->count())
                ->get(['id', 'slug', 'title', 'destination_city', 'base_price', 'currency',
                       'duration_days', 'duration_hours', 'product_subtype']);
            $collected = $collected->merge($hits);
        }
        $query = $collected->unique('id');

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
                   'cover_image', 'duration_days', 'duration_hours'])
            ->toArray();
    }

    // ── Prompt ───────────────────────────────────────────────────────────────

    private function buildGuestUpsellBlock(): string
    {
        return <<<'UPSELL'

ÜYELİK TEŞVİKİ (SADECE KAYITSIZ KULLANICILARA):
Bu kullanıcı henüz üye değil. Doğru anda — sıkmadan, zorlamadan — üye olmanın ne kazandıracağını anlat.

DOĞRU AN NE ZAMAN?
Şu sinyalleri gör gördüm teklif et:
- Kullanıcı bir ürün/hizmet hakkında fiyat, tarih veya müsaitlik sordu (gerçek ilgi sinyali)
- "Bildirim al", "haberdar ol", "takip et", "hatırlat" gibi bir şey söyledi
- 2+ soru sordu (sohbet derinleşti, engagement yüksek)
- Belirli bir tarih, grup boyutu veya bütçe bildirdi (ciddi planlama sinyali)
- İndirim, kampanya veya özel fiyat sordu
- "Daha önce de baktım", "geçen sefer" gibi ifade kullandı (geri dönen ziyaretçi)

NASIL TEKLİF EDECEKSİN?
- Cevabının SONUNA doğal bir geçişle ekle — asla cevabın başına koyma
- 1-2 cümle max, özlü ve kişisel (o an sordukları şeyle direkt bağlantılı)
- Kaydol bağlantısını markdown link olarak ver: [Ücretsiz üye ol →](https://gruprezervasyonlari.com/kayit)
- Aynı konuşmada birden fazla kez teklif etme — bir kez söyle, bırak

ÜYELİK AVANTAJLARI — KONUYA GÖRE HANGİSİNİ SEÇECEĞİNİ BELİRLE:
(Hepsini sayma — sadece o an en alakalı 1-2 tanesini söyle)

Fiyat / ürün sorusunda:
→ "Üye olursan bu tur için fiyat düşünce veya yeni tarih açılınca sana bildirim gönderebilirim."
→ "Üye olursan [kategori] için özel kampanyaları kaçırmazsın, ilk sen haberdar olursun."

Tekrarlayan ziyaret / "daha önce de baktım" sinyalinde:
→ "Üye olursan bir sonraki gelişinde seni tanırım — sıfırdan başlamayız, kaldığın yerden devam ederiz."
→ "Baktığın ve beğendiğin her şeyi istek listene ekleyebilirsin, kaybolmaz."

Grup / tarih / bütçe bildirdiğinde:
→ "Üye olursan bu bilgileri kaydederim ve sana özel seçenekler sunabilirim."
→ "Konumuna göre en yakın ve en uygun fiyatlı seçenekleri daha isabetli öneririm."

İndirim / kampanya sorusunda:
→ "Üyelere zaman zaman özel fiyatlar ve erken rezervasyon indirimleri sunuyoruz — üye olursan bunları kaçırmazsın."

Genel engagement yüksekse (2+ soru):
→ "Üye olursan seninle olan tüm konuşmaları hatırlarım ve sana özel tatil asistanın gibi yardımcı olurum."
→ "Adını bilirsem sana isminle hitap ederim, tercihlerini öğrenirsem çok daha kişisel öneriler gelirim."

ÖNEMLİ:
- Hiç sinyal yoksa (ilk kısa mesaj, genel selamlama) TEKLİF ETME
- "Üye ol" demekten kaçın — "üye olursan şunu yapabilirim" formatını tercih et (fayda öne)
- Bağlantıyı her zaman markdown ile ver, çıplak URL yazma
UPSELL;
    }

    private function buildSystemPrompt(array $memories, array $time, array $user, array $products, bool $isGuest = false): string
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

        $haftasonu  = $time['haftasonu'] ? ' (hafta sonu)' : '';
        $guestBlock = $isGuest ? $this->buildGuestUpsellBlock() : '';

        return <<<PROMPT
Sen gruprezervasyonlari.com'un yapay zeka asistanısın. Adın GR (okunuşu: Ciar).

PLATFORM KAPSAMI:
Gruprezervasyonlari.com — Türkiye'nin lider grup seyahat ve etkinlik platformu.
Sunulan hizmetler:
• Dinner Cruise (alkollü/alkolsüz) — İstanbul Boğazı'nda akşam yemeği + eğlence
• Yat kiralama (küçük 1-10 kişi, orta boy 10-20 kişi) — saatlik kiralama
• Havalimanı & şehirlerarası transfer (minibüs, VIP van)
• Özel jet charter — şehirlerarası uçuş
• Boğaz turları — tekne turları
• Günübirlik turlar (Sapanca/Masukkiye, Bursa vb.)
• Etkinlikler & Deneyimler (viski tadımı, gastronomi vb.)
• GRUP UÇAK TALEBİ — 10+ kişilik gruplar için özel uçuş teklifi alma formu → /grup-ucak-talebi
• TRANSFER HİZMETİ — havalimanı & şehir transferleri için fiyat sorgulama → /transfer

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
- Grup uçuşu / charter uçak / 10+ kişi uçuş gibi konularda /grup-ucak-talebi sayfasını öner
- Transfer / havalimanı / araç sorunlarında /transfer sayfasını öner
{$guestBlock}
ÇIKTI FORMATI — SADECE JSON döndür:
{
  "reply": "kullanıcıya cevap metni (Türkçe, markdown destekli)",
  "learn": [
    {"key": "anahtar", "value": "öğrenilen değer"}
  ],
  "products": ["slug1", "slug2"],
  "redirect": "/urun/slug",
  "wishlist_add": "slug",
  "price_alert": "slug"
}

"learn" alanı: bu mesajdan yeni bir şey öğrendiysen doldur. Boş bırakabilirsin.
  Geçerli key'ler: ilgi_alanlari, sehir, butce (dusuk/orta/yuksek), tercih_zaman, grup_boyutu, not
"products": varsa önerilen ürünlerin slug'larını buraya koy (max 3). Öneri yoksa boş bırak.
"redirect": kullanıcı bir sayfaya gitmek istediğinde doldur:
  - Ürün sayfası: "/urun/SLUG" — SLUG mutlaka yukarıdaki [slug:...] listesinden alınmalı, asla uydurma
  - Grup uçak talebi: "/grup-ucak-talebi"
  - Transfer sorgulama: "/transfer"
  - Sayfa otomatik açılır. Sadece kullanıcı açıkça onay verdiğinde doldur.
"wishlist_add": kullanıcı bir ürünü beğendiğini / kaydetmek istediğini belirtirse o ürünün SLUG'ını yaz.
  Sistem otomatik olarak istek listesine ekler ve kullanıcıya bildirir. Boş bırakabilirsin.
"price_alert": kullanıcı bir ürün için fiyat alarmı / bildirim almak isterse o ürünün SLUG'ını yaz.
  Sistem fiyat değişince kullanıcıya e-posta/bildirim gönderir. Boş bırakabilirsin.

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
                "https://generativelanguage.googleapis.com/v1beta/models/" . self::GEMINI_MODEL . ":generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $fullPrompt]]]],
                    'generationConfig' => [
                        'temperature'     => 0.85,
                        'maxOutputTokens' => 2048,
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::warning('GrAiService Gemini HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400));
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = trim(preg_replace('/```json|```/i', '', $text));
            // JSON bloğunu çıkar (bazen Gemini önüne/sonuna text ekler)
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $text = $m[0];
            }
            return $text;
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
