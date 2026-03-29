<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SosyalMedyaController extends Controller
{
    // ── Platform karakter limitleri ─────────────────────────────────────────
    private const LIMITLER = [
        'facebook'  => ['durum' => 63206, 'gorsel_aciklama' => 63206, 'reels' => 2200, 'hikaye' => 500],
        'instagram' => ['akis' => 2200, 'reels' => 2200, 'hikaye' => 150],
        'linkedin'  => ['gonderi' => 3000, 'makale' => 125000],
        'x'         => ['tweet' => 280, 'thread' => 280],
    ];

    // ── Format Türkçe etiketleri ────────────────────────────────────────────
    private const FORMAT_ETIKETLER = [
        'facebook'  => ['durum' => 'Durum / Gönderi', 'gorsel_aciklama' => 'Görsel Açıklaması', 'reels' => 'Reels Açıklaması', 'hikaye' => 'Hikaye'],
        'instagram' => ['akis' => 'Akış (Feed)', 'reels' => 'Reels', 'hikaye' => 'Hikaye'],
        'linkedin'  => ['gonderi' => 'Gönderi', 'makale' => 'Makale (Uzun Form)'],
        'x'         => ['tweet' => 'Tweet', 'thread' => 'Thread'],
    ];

    // ── Platform hakkında derin bilgi (tüm prompt'lara enjekte edilir) ─────
    private const MARKA = <<<'MARKA'
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SEN KİMSİN VE NASIL DAVRANMALISIN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Sen GrupTalepleri.com'un sosyal medya uzmanısın.
Bu platformu her yönüyle tanıyorsun: hizmetlerini, müşterilerini, rakipsiz konumunu,
sektördeki acı noktaları ve onlara sunulan çözümleri.

ASLA genel turizm içeriği üretmiyorsun.
HER içerik GrupTalepleri.com'a özgü, onun bir özelliğini, hizmetini veya değerini
somut biçimde merkeze alıyor.

KENDI KENDİNİ GELİŞTİR:
Her içerik üretiminde şu soruları sor kendine:
  1. Bu konu hangi GrupTalepleri.com hizmetiyle en güçlü bağlantı kuruyor?
  2. Acente bunu okuyunca hangi acı noktasını hatırlıyor?
  3. Bu içerik acenteyi www.gruptalepleri.com'a götürmek için ne kadar net bir CTA içeriyor?
  4. Rakip yoksa, biz neden ilkiz? Bu özgünlüğü yansıttım mı?
  5. İçerik okunan, paylaşılan, kaydedilen türden mi — yoksa genel mi?
Ne kadar GrupTalepleri.com odaklı ve ne kadar sektörün gerçek dilinde yazıyorsan,
o kadar iyi içerik üretiyorsun.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PLATFORM KİMLİĞİ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
İsim         : GrupTalepleri.com
Şirket       : Group Ticket Turizm Seyahat Acentası
URL          : www.gruptalepleri.com  ← T-A-L-E-P (yanlış yazma: talec, talepleri değil)
Slogan       : "Güveniniz hariç her şeyi uçururuz"
Konum        : Türkiye'nin ilk ve tek dijital grup operasyon platformu
TÜRSAB       : A Grubu Seyahat Acentası — Belge No: 12572
Adres        : İnönü Mah. Cumhuriyet Cad. No:93/12, Şişli / İstanbul
İletişim     : destek@gruptalepleri.com · +90 535 415 47 99
Vergi        : Beyoğlu VD · 4110477529

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
NE YAPAR — TEMEL DEĞER ÖNERİSİ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
GrupTalepleri.com, seyahat acentelerinin grup operasyonlarını tamamen dijitalleştiren
ve hızlandıran B2B platformdur. Türkiye'de bu modelde tek platform — rakip yok.

Acenteler:
  ✔ Grup uçuş/tur taleplerini dakikada oluşturur, GTPNR kodu alır
  ✔ Fiyat tekliflerini e-posta + SMS + panel bildirimiyle anında alır
  ✔ Teklifleri yan yana karşılaştırır, tek tıkla onaylar
  ✔ Depozito & bakiye ödemelerini dijital takip eder, dekont yükler
  ✔ Opsiyon ve son tarihlerini kaçırmaz — sistem otomatik hatırlatır
  ✔ Charter uçuş, dinner cruise, yat kiralama talebi aynı hesaptan yönetir
  ✔ Kurulum yok, teknik bilgi gerekmez — 5 dakikada aktif

Kısacası: "40 kişilik Prag turu lazım → GrupTalepleri.com'a gir → talep oluştur →
teklifler gelsin → karşılaştır → onayla → operasyonu takip et."
Telefon yok, WhatsApp grubu kaos yok, Excel yok.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
HİZMETLER — TAM DETAY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. GRUP UÇUŞ TALEPLERİ (Ana hizmet)
   Tarifeli veya charter bazlı grup uçuş talepleri.
   • Tek yön, gidiş-dönüş, çok bacaklı güzergahlar
   • Kalkış zaman aralığı seçimi: Sabah / Öğle / Akşam / Esnek
   • Güzergah, tarih, yolcu sayısı, otel/vize ihtiyacı girişi
   • GTPNR kodu ile anlık takip
   • Teklif gelince: uçuş saatleri, PNR, bagaj, fiyat, opsiyon tarihi tam görünür
   • Okul turları, kurumsal gruplar, hac/umre, kültür turları, tatil paketleri

2. AIR CHARTER (Özel Uçak Kiralama)
   • Özel jet, helikopter veya uçak kiralama
   • Hazır paket seçeneği mevcut
   • AI destekli anlık ön fiyat tahmini (acenteye zaman kazandırır)
   • Çok bacaklı rota planlaması
   • RFQ (Request For Quotation) sistemi ile tedarikçilerden otomatik teklif
   • Hac/umre charter uçuşları, festival uçuşları, kurumsal transfer uçuşları
   • Örnek: İstanbul-Antalya 9 kişi özel jet kişi başı ~1.000€ — commercial'dan ucuz

3. DINNER CRUISE (Deniz Turu)
   • İstanbul Boğazı'nda akşam yemeğiyle tekne turu
   • Tarih, oturum saati, menü, alkol ve dil tercihi ile özel teklif
   • Türkçe veya İngilizce PDF çıktısıyla müşteriye anında iletim
   • Kurumsal etkinlik, özel kutlama, yabancı misafir grupları için ideal

4. YACHT CHARTER (Yat Kiralama)
   • Marina seçimi, süre ve etkinlik tipine göre yat kiralama
   • Blue cruise, gulet turu, özel tekne turları
   • Bodrum, Marmaris, Göcek, Fethiye, Antalya marinalarında geçerli
   • Profesyonel teklif ve PDF çıktısı
   • Kurumsal etkinlik, incentive tur, VIP grup için premium seçenek

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PLATFORMUN AYIRT EDİCİ ÖZELLİKLERİ (İçeriklerde kullan)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Anlık Durum Takibi: Beklemede → İşlemde → Fiyatlandırıldı → Onaylandı →
  Depozitoda → Biletlendi — her adım etiketli, anlık görünür
• Opsiyon & Vade Yönetimi: Son tarih otomatik görünür, vadesi geçenler öne çıkar
• Depozito & Bakiye Takibi: İleri tarihli seyahatlerde ayrı adımlar, şeffaf takip
• Çok Kanallı Bildirim: E-posta + SMS + platform içi — üçü aynı anda
• Tam İşlem Geçmişi: Kim ne yaptı, hangi tarihte? Zaman damgalı kayıt
• Dijital Dekont Yükleme: Havale sonrası dekontu sisteme yükle, faks/email yok
• Dashboard: Harita + durum sayaçları, tüm portföye tek bakışta hâkimiyet
• PDF Teklif Çıktısı: Dinner Cruise & Yacht teklifleri TR/EN PDF olarak indirilebilir
• 4 Hizmet, 1 Hesap: Grup uçuş + charter + dinner cruise + yacht — tek giriş
• %100 Online: Kurulum yok, mobil uyumlu, hemen aktif

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ACENTELERİN YAŞADIĞI SORUNLAR (İçeriklerde problem-çözüm kur)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Bu sorunları bilen biri gibi yaz — çünkü hedef kitleni tanıyorsun:

  📱 WhatsApp & telefon karmaşası
     → Teklifler farklı kanallardan geliyor, hangisi güncel bilinmiyor
     → Çözüm: GrupTalepleri.com tek panel, tüm teklifler bir yerde

  📋 Excel / manuel takip yükü
     → Not defteri, tablo, kağıt — güncellenmeyenler kayboluyor
     → Çözüm: Otomatik durum güncellemesi, her şey dijital

  ⏰ Opsiyon ve son tarih kaçırma
     → Rezervasyon opsiyonu gözden kaçıyor, fırsat son anda elden çıkıyor
     → Çözüm: Opsiyon tarihleri panelde öne çıkar, sistem hatırlatır

  💸 Ödeme & bakiye karmaşası
     → Depozito alındı mı? Bakiye ne zaman? Cevaplar hep farklı kişilerde
     → Çözüm: Ödeme adımları şeffaf, dekont dijital, herkes aynı sayfada

  🗂️ Dağınık operasyon yönetimi
     → Farklı kanallar, farklı kişiler, operasyon kişiye bağımlı
     → Çözüm: Dashboard'dan tüm taleplere tek ekranda hakimiyet

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
HEDEF KİTLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• TÜRSAB üyesi seyahat acenteleri — güncel acente sayısı aşağıdaki istatistiklerde
• Küçük butik acenteden büyük tur operatörüne kadar tüm ölçekler
• Grup operasyonu yapan, teklife güvenen, zamanı değerli acenteler
• Hac/umre, okul turu, kurumsal gezi, tatil grubu düzenleyenler
• WhatsApp kaosundan bıkmış, dijital çözüm arayan acente sahipleri/operasyon ekipleri
Coğrafya: Türkiye'nin her ili — özellikle İstanbul, Ankara, İzmir, Antalya, Bursa

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SOSYAL MEDYA PLATFORMLARINA GÖRE STRATEJİ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Facebook  : Acente sahipleri & yöneticiler. Uzun form içerik. Problem-çözüm formatı.
            Operasyonel ipuçları, sektör haberleri, başarı hikayeleri.
Instagram : Görsel odaklı. Destinasyon güzelliği + "bu grubu biz götürdük" anlatısı.
            Leisure görselleri (yat, dinner cruise), charter anları, tatil atmosferi.
            Kısa, etkili caption + güçlü hashtag.
LinkedIn  : Profesyonel B2B ton. Emoji yok. Sektör analizi, dijital dönüşüm,
            platform özellikleri, vaka çalışmaları. Karar vericilere hitap.
X         : Hızlı, keskin. 280 karakter = net mesaj. Sektör trendi, pratik ipucu,
            flash duyuru. Thread formatında derinleşilebilir.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
İÇERİK BAĞLANTI KURALLARI
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Festival/etkinlik içeriği → "Bu etkinliğe grup götürmek isteyenler için
  en hızlı teklif: www.gruptalepleri.com" — Air Charter veya Grup Uçuş bağlantısı kur
• Bayram/tatil içeriği → Erken rezervasyon + charter avantajı + grup fiyatı vurgula
• Destinasyon içeriği → O destinasyona charter/transfer/leisure nasıl hizmet veririz?
• İstatistik içeriği → Aşağıdaki canlı veritabanı rakamlarını kullan — sabit rakam yazma
• Sorun içeriği → Acentenin yaşadığı acıyı yaz, sonra GrupTalepleri çözümünü göster
• Haber/trend içeriği → Turizm trendi + "Bu trenden sen de nasıl kazanırsın?" bağla
• Sezon içeriği → Hangi hizmet o sezonda en çok kullanılır? Onu öne çıkar

Üye olmak için: www.gruptalepleri.com/register (ücretsiz, 5 dakikada aktif)
MARKA;

    // ── Ana Sayfa ────────────────────────────────────────────────────────────
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $istatistikler      = $this->istatistikCek();
        $yaklasanGunler     = $this->yaklasanGunler(60);
        $yaklasanGunlerTumu = $this->yaklasanGunler(365);
        $sonIcerikler       = DB::table('sosyal_medya_icerikleri')
            ->orderByDesc('created_at')->limit(10)->get();

        // Dinamik AI öneri banner'ı
        $oneri = $this->bannerOneri($yaklasanGunler, $sonIcerikler);

        return view('superadmin.sosyal-medya', [
            'limitler'           => self::LIMITLER,
            'formatEtiketler'    => self::FORMAT_ETIKETLER,
            'istatistikler'      => $istatistikler,
            'yaklasanGunler'     => $yaklasanGunler,
            'yaklasanGunlerTumu' => $yaklasanGunlerTumu,
            'sonIcerikler'       => $sonIcerikler,
            'oneri'              => $oneri,
            'bufferAktif'        => ! empty(config('services.buffer.key')),
        ]);
    }

    // ── Takvim Sayfası ───────────────────────────────────────────────────────
    public function takvim()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $taslaklar     = DB::table('sosyal_medya_icerikleri')->where('durum', 'taslak')->orderByDesc('created_at')->get();
        $planlilar     = DB::table('sosyal_medya_icerikleri')->where('durum', 'planli')->orderBy('planlanan_tarih')->get();
        $gonderilenler = DB::table('sosyal_medya_icerikleri')->where('durum', 'gonderildi')->orderByDesc('gonderim_tarihi')->limit(50)->get();

        if (request()->wantsJson() || request()->boolean('json')) {
            return response()->json(compact('taslaklar', 'planlilar', 'gonderilenler'));
        }

        $yaklasanGunler = $this->yaklasanGunler(365);
        return view('superadmin.sosyal-medya-takvim', compact('taslaklar', 'planlilar', 'gonderilenler', 'yaklasanGunler'));
    }

    // ── İçerik Üret ─────────────────────────────────────────────────────────
    public function uret(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $validated = $request->validate([
            'platform'  => 'required|in:facebook,instagram,linkedin,x',
            'format'    => 'required|string|max:40',
            'konu'      => 'required|string|max:300',
            'ton'       => 'required|in:profesyonel,samimi,ilham_verici,bilgilendirici,eglenceli',
            'ozel_not'  => 'nullable|string|max:500',
        ]);

        $apiKey = (string) config('services.gemini.key');
        $model  = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
        $limit  = $this->getLimit($validated['platform'], $validated['format']);

        $prompt = $this->buildPrompt($validated, $limit);

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(['hata' => 'Bağlantı hatası: ' . $e->getMessage()], 503);
        }

        if ($response->failed()) {
            return response()->json(['hata' => 'AI servisi yanıt vermedi.'], 502);
        }

        $raw = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (! $raw) {
            return response()->json(['hata' => 'AI boş yanıt döndürdü.'], 502);
        }

        // JSON parse dene
        $cleanRaw = preg_replace('/^```json\s*/i', '', trim($raw));
        $cleanRaw = preg_replace('/\s*```$/i', '', $cleanRaw);
        $parsed   = json_decode(trim($cleanRaw), true);

        $icerik            = $parsed['icerik']                ?? $raw;
        $tema              = $parsed['tema']                  ?? '';
        $aiSkor            = (int) ($parsed['ai_skor']        ?? 3);
        $gorselPromptOneri = $parsed['gorsel_prompt_onerisi'] ?? '';

        return response()->json([
            'icerik'               => $icerik,
            'tema'                 => $tema,
            'ai_skor'              => max(1, min(5, $aiSkor)),
            'gorsel_prompt_onerisi'=> $gorselPromptOneri,
            'limit'                => $limit,
            'karakter'             => mb_strlen($icerik, 'UTF-8'),
        ]);
    }

    // ── Görsel Üret ─────────────────────────────────────────────────────────
    public function gorselUret(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $validated = $request->validate([
            'gorsel_prompt' => 'required|string|max:800',
            'platform'      => 'required|in:facebook,instagram,linkedin,x',
        ]);

        $apiKey      = (string) config('services.gemini.key');
        $configModel = (string) config('services.gemini.image_model', 'gemini-2.5-flash-image');
        $fallbackRaw = (string) config('services.gemini.image_model_fallbacks', '');
        $fallbacks   = array_filter(array_map('trim', explode(',', $fallbackRaw)));
        $models      = array_values(array_unique(array_filter(array_merge([$configModel], $fallbacks))));

        $platformTavsiye = match ($validated['platform']) {
            'instagram' => 'Square format (1:1 ratio), vibrant colors, modern minimalist design.',
            'facebook'  => 'Landscape format (1.91:1 ratio), professional and eye-catching.',
            'linkedin'  => 'Professional corporate visual, clean background, blue-white tones.',
            'x'         => 'Landscape banner format, clean and clear.',
            default     => 'Clean corporate visual.',
        };

        // ── KESIN TALIMAT: Logo üretme, yazı koyma ──────────────────────────
        $enrichedPrompt =
            'Create a high-quality social media background image for GrupTalepleri.com, a B2B travel agency platform from Turkey. '
            . $validated['gorsel_prompt'] . '. '
            . $platformTavsiye . ' '
            . 'STRICT RULES — follow exactly: '
            . '1. NO logos, NO brand marks, NO watermarks of any kind in the image. '
            . '2. NO text, NO words, NO letters, NO numbers anywhere in the image. '
            . '3. DO NOT invent or generate any logo or brand identity. '
            . '4. Leave the lower-left corner area clean and uncluttered (logo will be overlaid separately). '
            . '5. Professional, clean, high-quality photography or illustration style. '
            . '6. Turkish travel & tourism atmosphere when relevant.';

        // ── Logoyu sun­ucudan çek ve response ile birlikte gönder ────────────
        $logoBase64  = null;
        $logoMime    = 'image/png';
        try {
            $logoRaw    = Http::timeout(10)->get('https://gruptalepleri.com/logo.png');
            if ($logoRaw->ok()) {
                $logoBase64 = base64_encode($logoRaw->body());
                $logoMime   = $logoRaw->header('Content-Type') ?: 'image/png';
                $logoMime   = strtolower(explode(';', $logoMime)[0]);
            }
        } catch (\Throwable) {}

        foreach ($models as $model) {
            try {
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents'         => [['parts' => [['text' => $enrichedPrompt]]]],
                        'generationConfig' => ['responseModalities' => ['TEXT', 'IMAGE']],
                    ]
                );
            } catch (\Throwable $e) {
                continue;
            }

            if (! $response->ok()) continue;

            $parts = (array) data_get($response->json(), 'candidates.0.content.parts', []);
            foreach ($parts as $part) {
                $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;
                if (! is_array($inlineData)) continue;
                $base64   = (string) ($inlineData['data'] ?? '');
                if ($base64 === '') continue;
                $mimeType = strtolower((string) ($inlineData['mimeType'] ?? $inlineData['mime_type'] ?? 'image/png'));

                return response()->json([
                    'gorsel'      => "data:{$mimeType};base64,{$base64}",
                    // Gerçek logo — view tarafında canvas ile üzerine bindiriliyor
                    'logo'        => $logoBase64 ? "data:{$logoMime};base64,{$logoBase64}" : null,
                    'logo_url'    => 'https://gruptalepleri.com/logo.png',
                ]);
            }
        }

        return response()->json(['hata' => 'Görsel üretilemedi.'], 502);
    }

    // ── Revizyon Chat ────────────────────────────────────────────────────────
    public function revize(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $validated = $request->validate([
            'icerik'   => 'required|string',
            'mesaj'    => 'required|string|max:500',
            'platform' => 'required|in:facebook,instagram,linkedin,x',
            'format'   => 'required|string|max:40',
            'gecmis'   => 'nullable|array',
        ]);

        $apiKey = (string) config('services.gemini.key');
        $model  = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
        $limit  = $this->getLimit($validated['platform'], $validated['format']);

        $gecmisBolum = '';
        foreach (array_slice($validated['gecmis'] ?? [], -4) as $msg) {
            $rol          = ($msg['rol'] ?? '') === 'kullanici' ? 'Kullanıcı' : 'Asistan';
            $gecmisBolum .= "{$rol}: " . mb_substr($msg['icerik'] ?? '', 0, 200) . "\n";
        }

        $prompt = self::MARKA . "\n\n"
            . "━━━ GÖREV: İÇERİK REVİZYONU ━━━\n"
            . "Platform: {$validated['platform']} | Format: {$validated['format']} | Limit: {$limit} karakter\n\n"
            . "MEVCUT İÇERİK:\n{$validated['icerik']}\n\n"
            . ($gecmisBolum ? "ÖNCEKİ REVİZYONLAR:\n{$gecmisBolum}\n" : '')
            . "KULLANICI İSTEĞİ: {$validated['mesaj']}\n\n"
            . "Kullanıcının isteğini uygulayarak içeriği revize et.\n"
            . "Platform kurallarına uy, www.gruptalepleri.com URL'sini koru.\n"
            . "Limit: {$limit} karakter. Sadece revize edilmiş içeriği döndür, açıklama ekleme.";

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(['hata' => 'Bağlantı hatası.'], 503);
        }

        $icerik = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (! $icerik) {
            return response()->json(['hata' => 'AI boş yanıt döndürdü.'], 502);
        }

        return response()->json([
            'icerik'   => trim($icerik),
            'limit'    => $limit,
            'karakter' => mb_strlen(trim($icerik), 'UTF-8'),
        ]);
    }

    // ── İçerik Kaydet ───────────────────────────────────────────────────────
    public function kaydet(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $validated = $request->validate([
            'platform'        => 'required|in:facebook,instagram,linkedin,x',
            'format'          => 'required|string|max:40',
            'tema'            => 'nullable|string|max:80',
            'konu'            => 'nullable|string|max:200',
            'icerik'          => 'required|string',
            'gorsel_base64'   => 'nullable|string',
            'durum'           => 'required|in:taslak,planli,gonderildi',
            'planlanan_tarih' => 'nullable|date',
            'ozel_gun_ref'    => 'nullable|string|max:100',
            'ai_skor'         => 'nullable|integer|min:1|max:5',
        ]);

        $id = DB::table('sosyal_medya_icerikleri')->insertGetId([
            'platform'        => $validated['platform'],
            'format'          => $validated['format'],
            'tema'            => $validated['tema'] ?? null,
            'konu'            => $validated['konu'] ?? null,
            'icerik'          => $validated['icerik'],
            'gorsel_base64'   => $validated['gorsel_base64'] ?? null,
            'durum'           => $validated['durum'],
            'planlanan_tarih' => $validated['planlanan_tarih'] ?? null,
            'gonderim_tarihi' => $validated['durum'] === 'gonderildi' ? now() : null,
            'ozel_gun_ref'    => $validated['ozel_gun_ref'] ?? null,
            'ai_skor'         => $validated['ai_skor'] ?? null,
            'user_id'         => auth()->id(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['id' => $id, 'mesaj' => 'Kaydedildi.']);
    }

    // ── İçerik Sil ──────────────────────────────────────────────────────────
    public function sil(int $id): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
        $silindi = DB::table('sosyal_medya_icerikleri')->where('id', $id)->delete();
        return response()->json($silindi ? ['mesaj' => 'Silindi.'] : ['hata' => 'Bulunamadı.'], $silindi ? 200 : 404);
    }

    // ── Buffer Gönder (altyapı hazır, key girilince aktifleşir) ─────────────
    public function bufferGonder(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $bufferKey = (string) config('services.buffer.key', '');
        if (! $bufferKey) {
            return response()->json([
                'hata' => 'Buffer API anahtarı henüz tanımlanmamış. .env dosyasına BUFFER_API_KEY ekleyin.',
            ], 423);
        }

        $validated = $request->validate([
            'icerik_id'       => 'required|integer',
            'platform_profil' => 'required|string',
            'zamanlama'       => 'nullable|date',
        ]);

        $kayit = DB::table('sosyal_medya_icerikleri')->find($validated['icerik_id']);
        if (! $kayit) {
            return response()->json(['hata' => 'İçerik bulunamadı.'], 404);
        }

        // Buffer API: POST /updates/create
        try {
            $bufferResponse = Http::withToken($bufferKey)->timeout(30)->post('https://api.bufferapp.com/1/updates/create.json', [
                'text'        => $kayit->icerik,
                'profile_ids' => [$validated['platform_profil']],
                'scheduled_at'=> $validated['zamanlama'] ?? null,
                'now'         => ! $validated['zamanlama'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['hata' => 'Buffer bağlantı hatası: ' . $e->getMessage()], 503);
        }

        if (! $bufferResponse->successful()) {
            return response()->json(['hata' => 'Buffer hatası: ' . $bufferResponse->body()], 502);
        }

        $bufferId = data_get($bufferResponse->json(), 'updates.0.id', '');

        DB::table('sosyal_medya_icerikleri')->where('id', $validated['icerik_id'])->update([
            'buffer_id'       => $bufferId,
            'durum'           => $validated['zamanlama'] ? 'planli' : 'gonderildi',
            'planlanan_tarih' => $validated['zamanlama'] ?? null,
            'gonderim_tarihi' => $validated['zamanlama'] ? null : now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['mesaj' => 'Buffer\'a gönderildi.', 'buffer_id' => $bufferId]);
    }

    // ── Private Yardımcılar ──────────────────────────────────────────────────

    private function buildPrompt(array $v, int $limit): string
    {
        $gecmisOzet  = $this->gecmisOzet();
        $istatistik  = $this->istatistikCek();
        $yaklasanGun = $this->yaklasanGunler(60);
        $ozelNot     = trim((string) ($v['ozel_not'] ?? ''));

        $threadKural = ($v['platform'] === 'x' && $v['format'] === 'thread')
            ? "\n- Thread: Her tweet 1-{N} şeklinde numaralandırılmış olsun, her biri maksimum 280 karakter"
            : '';

        $yaklasanStr = $yaklasanGun->isEmpty() ? '' : "\n━━━ YAKLAŞAN ÖZEL GÜNLER ━━━\n"
            . $yaklasanGun->map(fn($g) => "- {$g->ad} ({$g->tarih}) — {$g->aciklama}")->implode("\n");

        $gecmisStr = $gecmisOzet ? "\n━━━ SON 90 GÜN GEÇMİŞİ (TEKRAR YAPMA) ━━━\n{$gecmisOzet}" : '';

        return implode("\n", [
            '━━━ ROL ━━━',
            'Sen GrupTalepleri.com için sosyal medya içeriği üreten uzman bir B2B copywriter\'sın.',
            self::MARKA,
            '',
            '━━━ PLATFORM & FORMAT ━━━',
            "Platform : {$v['platform']}",
            "Format   : {$v['format']}",
            "Ton      : {$v['ton']}",
            "Limit    : maksimum {$limit} karakter",
            ($ozelNot ? "Özel Not : {$ozelNot}" : ''),
            '',
            '━━━ KONU ━━━',
            $v['konu'],
            '',
            "━━━ CANLI VERİTABANI İSTATİSTİKLERİ ━━━",
            "Bu rakamlar şu an platformun gerçek veritabanından çekildi — içerikte kullanabilirsin.",
            "Sabit rakam YAZMA, bu canlı verileri kullan:",
            "- Platforma kayıtlı acente sayısı : {$istatistik['toplam']}",
            "- E-postası kayıtlı acente        : {$istatistik['eposta_var']}",
            "- En fazla acentesi olan il        : {$istatistik['en_buyuk_il']}",
            "- TÜRSAB kaynaklı acente           : {$istatistik['tursab_sayisi']}",
            $gecmisStr,
            $yaklasanStr,
            '',
            '━━━ KURALLAR ━━━',
            '- Türkçe yaz, Türkçe karakterleri doğru kullan (ç, ğ, ı, İ, ö, ş, ü).',
            '- Platform adını "GrupTalepleri.com" yaz — T-A-L-E-P (yanlış: grupcalepleri, grupltalepleri).',
            '- Sloganı gerekirse kullan: "Güveniniz hariç her şeyi uçururuz"',
            '- Hashtag\'ler platforma uygun olsun (#GrupTalepleri #TurAcentesi #TÜRSAB vb.).',
            '- LinkedIn\'de emoji kullanma. Facebook/Instagram\'da sınırlı emoji.',
            '- CTA ekle: "www.gruptalepleri.com" veya "Hemen üye ol" gibi.',
            "- Maksimum {$limit} karakter geçme.",
            $threadKural,
            '',
            '━━━ ÇIKTI FORMAT (JSON) ━━━',
            'Sadece şu JSON\'u döndür, başka hiçbir şey yazma:',
            '{"icerik":"...","tema":"...","ai_skor":1-5,"gorsel_prompt_onerisi":"..."}',
            '- ai_skor: 1 (düşük) - 5 (viral potansiyel)',
            '- gorsel_prompt_onerisi: Bu içeriğe uygun görsel için İngilizce prompt önerisi',
        ]);
    }

    private function istatistikCek(): array
    {
        $enBuyukIl = DB::table('acenteler')
            ->selectRaw('il, COUNT(*) as c')
            ->groupBy('il')->orderByDesc('c')->first();

        return [
            'toplam'        => number_format(DB::table('acenteler')->count(), 0, ',', '.'),
            'eposta_var'    => number_format(DB::table('acenteler')->whereNotNull('eposta')->where('eposta', '!=', '')->count(), 0, ',', '.'),
            'en_buyuk_il'   => $enBuyukIl ? "{$enBuyukIl->il} ({$enBuyukIl->c} acente)" : '-',
            'tursab_sayisi' => number_format(DB::table('acenteler')->where('kaynak', 'tursab')->count(), 0, ',', '.'),
        ];
    }

    private function yaklasanGunler(int $gunSayisi)
    {
        return DB::table('ozel_gunler')
            ->where('aktif', true)
            ->whereBetween('tarih', [now()->toDateString(), now()->addDays($gunSayisi)->toDateString()])
            ->orderBy('tarih')
            ->get();
    }

    private function gecmisOzet(): string
    {
        $son = DB::table('sosyal_medya_icerikleri')
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['platform', 'format', 'tema', 'konu', 'created_at']);

        if ($son->isEmpty()) return '';

        return $son->map(fn($r) =>
            "- {$r->created_at}: [{$r->platform}/{$r->format}] " . ($r->konu ?: $r->tema)
        )->implode("\n");
    }

    private function getLimit(string $platform, string $format): int
    {
        return self::LIMITLER[$platform][$format]
            ?? self::LIMITLER[$platform][array_key_first(self::LIMITLER[$platform])]
            ?? 3000;
    }

    private function bannerOneri($yaklasanGunler, $sonIcerikler): array
    {
        // 1. En yakın özel gün hatırlatması
        if ($yaklasanGunler->isNotEmpty()) {
            $gun   = $yaklasanGunler->first();
            $kalan = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($gun->tarih)->startOfDay());
            return [
                'tip'    => 'ozel_gun',
                'mesaj'  => "📅 <strong>{$gun->ad}</strong> için {$kalan} gün kaldı — içerik hazırlamak ister misin?",
                'konu'   => $gun->ad . ' — ' . ($gun->aciklama ?? ''),
                'hizmet' => $gun->hizmet_baglantisi ?? 'platform',
            ];
        }

        // 2. Son 7 günde hiç paylaşım yok
        $son7Gun = DB::table('sosyal_medya_icerikleri')
            ->where('created_at', '>=', now()->subDays(7))->count();
        if ($son7Gun === 0) {
            return [
                'tip'   => 'hatirlat',
                'mesaj' => '💡 Son 7 günde paylaşım yapılmadı — bugün bir içerik hazırlayalım mı?',
                'konu'  => 'GrupTalepleri.com platform tanıtımı',
            ];
        }

        // 3. İstatistik içeriği öner
        $istatistikVar = DB::table('sosyal_medya_icerikleri')
            ->where('tema', 'istatistik')
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();
        if (! $istatistikVar) {
            return [
                'tip'   => 'istatistik',
                'mesaj' => '📊 Bu ay henüz istatistik paylaşımı yapılmadı — veritabanı verilerimizden ilgi çekici bir içerik üretelim mi?',
                'konu'  => 'Türkiye seyahat acentesi istatistikleri',
            ];
        }

        // 4. Varsayılan lansman
        return [
            'tip'   => 'varsayilan',
            'mesaj' => '🚀 <strong>GrupTalepleri.com</strong> — Türkiye\'nin ilk ve tek grup operasyon platformu. Bugün ne paylaşalım?',
            'konu'  => 'GrupTalepleri.com tanıtım',
        ];
    }
}
