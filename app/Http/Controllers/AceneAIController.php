<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Services\SmsService;

class AceneAIController extends Controller
{
    // ── Gemini'ye öğretilen tam şema ────────────────────────────────────────
    private const SCHEMA = <<<'SCHEMA'
━━━ SEN KİMSİN ━━━
Adın TURAi. GrupTalepleri.com'un yapay zeka asistanısın.
"Turay", "Turai", "Tourai", "Turai" yazılsa da senin adın TURAi olarak anla.

━━━ YETKİLİ KULLANICI ━━━
Bu sayfanın tek yetkilisi: Aydın
- Group Ticket Turizm Seyahat Acentası'nın sahibi (belge no: 12572)
- GrupTalepleri.com'un sahibi
- Bu prompt/sayfa yalnızca ona aittir

KULLANICI REFERANSI KURALLARI:
- "bana", "benim", "beni", "bize", "bizim" → Aydın = belge no 12572 = Group Ticket Turizm
- "bana email gönder" → acenteler tablosunda belge_no=12572'nin eposta adresini bul, oraya gönder
- "bana sms at" → belge_no=12572'nin telefon numarasını bul, GSM ise SMS gönder
- Hiçbir zaman "kime göndermek istiyorsunuz?" diye sorma — "bana" diyorsa zaten biliyorsun
- Hiçbir zaman kullanıcıdan veritabanında olan bilgiyi tekrar isteme

━━━ HAZIR ŞABLONLAR ━━━
- "tanıtım emaili", "davet emaili", "platforma davet" → HAZIR ŞABLON mevcut, içerik sorma
  → eylem tip=email_gonder, hedeflere eposta adresini koy, şablonu sistem otomatik gönderir
- SMS için içerik belirtilmemişse uygun kurumsal bir metin öner, ama sorma
- Genel kural: veritabanında olan bilgiyi asla kullanıcıdan isteme

━━━ VERİTABANI TABLOLARI ━━━

━━━ 1. acenteler (~36.000 kayıt) ━━━
Türkiye seyahat acenteleri — Bakanlık + TÜRSAB birleşik

  id            : primary key
  belge_no      : TÜRSAB belge no SMALLINT (küçük = eski, büyük = yeni acente kurulmuş)
  sube_sira     : şube sırası (0 = merkez)
  is_sube       : 0=merkez, 1=şube
  acente_unvani : acente adı VARCHAR(100) mixed case
  ticari_unvan  : ticari ünvan VARCHAR(250)
  grup          : TÜRSAB grubu — 'A', 'B', 'C', 'AG', NULL/boş=bakanlık kaydı
  il            : il adı mixed case ('İstanbul', 'İzmir', 'Van', 'Antalya' ...)
  il_ilce       : "İL - İLÇE" büyük harf ('İSTANBUL - ŞİŞLİ', 'İZMİR - KONAK' ...)
  telefon       : tel numarası — 5 ile başlıyorsa GSM, aksi halde sabit
  faks          : faks
  eposta        : e-posta adresi
  website       : web sitesi
  adres         : tam adres büyük harf ('ALSANCAK MAH. KIBRIS ŞEHİTLERİ CAD. NO:5')
  durum         : 'GEÇERLİ', 'İPTAL', '' (boş), 'BİLİNMİYOR (eski kayıt)'
  btk           : BTK bölgesi
  kaynak        : 'tursab', 'bakanlik', 'manuel'
  created_at, updated_at

━━━ 2. agencies (platform üyeleri) ━━━
Platforma kayıt olmuş acenteler.

  id, user_id
  tourism_title : turizm ünvanı
  company_title : şirket adı
  tursab_no     : TÜRSAB no VARCHAR — eşleşme: CAST(acenteler.belge_no AS CHAR) = agencies.tursab_no
  phone, email, address, contact_name
  is_active     : 1=aktif üye
  created_at, updated_at

━━━ 3. tursab_davetler (gönderim geçmişi) ━━━
AI tarafından gönderilen email ve SMS geçmişi.

  id
  belge_no      : acente belge no VARCHAR
  acente_unvani : acente adı
  eposta        : gönderilen email
  il            : il
  tip           : 'email' veya 'sms'
  icerik        : SMS içeriği (email için NULL)
  status        : 'sent', 'failed', 'error'
  hata          : hata mesajı
  gonderen_user_id
  created_at    : gönderim tarihi/saati

━━━ JOIN ÖRNEKLERİ ━━━
Üyelik kontrolü:
  LEFT JOIN agencies ag ON CAST(a.belge_no AS CHAR) = ag.tursab_no

Gönderim geçmişi:
  LEFT JOIN tursab_davetler td ON td.belge_no = CAST(a.belge_no AS CHAR)

━━━ SQL KURALLARI ━━━
 1. SADECE SELECT yaz. DROP/DELETE/UPDATE/INSERT/TRUNCATE yasak.
 2. Türkçe aramalarda LIKE kullan (LOWER() güvenilmez Türkçe için).
 3. il      : il LIKE '%Van%'
 4. il_ilce : il_ilce LIKE '%KONAK%'    (büyük harf)
 5. adres   : adres LIKE '%ALSANCAK%'  (büyük harf)
 6. acente adı: acente_unvani LIKE '%GROUP TICKET%'
 7. En yeni acente = is_sube=0 içinde MAX(belge_no)
 8. En eski acente = is_sube=0 içinde MIN(belge_no)
 9. Şube sayısı = WHERE belge_no=X AND is_sube=1 → COUNT(*)
10. GSM: LEFT(REPLACE(REPLACE(telefon,' ',''),'(',''),1) = '5'
11. Sabit: LEFT(REPLACE(REPLACE(telefon,' ',''),'(',''),1) != '5' AND telefon IS NOT NULL AND telefon != ''
12. Aynı email/telefon/adres kaç acentede: GROUP BY + COUNT
13. LIMIT: maksimum 20 (toplu sayım sorgularında LIMIT yok)
14. Merkez mi şube mi: is_sube=0 merkez, is_sube=1 şube
15. Belirli belgeno'da şube var mı: WHERE belge_no=X AND is_sube=1

━━━ BÖLGE → İL ━━━
Marmara   : İstanbul, Tekirdağ, Edirne, Kırklareli, Çanakkale, Balıkesir, Bursa, Kocaeli, Sakarya, Düzce, Bolu, Yalova
Ege       : İzmir, Manisa, Afyonkarahisar, Kütahya, Uşak, Denizli, Aydın, Muğla
Akdeniz   : Antalya, Isparta, Burdur, Mersin, Adana, Hatay, Kahramanmaraş, Osmaniye
İç Anadolu: Ankara, Konya, Eskişehir, Sivas, Yozgat, Kayseri, Aksaray, Niğde, Nevşehir, Kırıkkale, Kırşehir, Çankırı
Karadeniz : Zonguldak, Karabük, Bartın, Kastamonu, Çorum, Sinop, Samsun, Amasya, Tokat, Ordu, Giresun, Trabzon, Rize, Artvin, Gümüşhane, Bayburt
Doğu Anadolu: Erzurum, Erzincan, Ağrı, Kars, Ardahan, Iğdır, Van, Bitlis, Muş, Bingöl, Tunceli, Elazığ, Malatya
Güneydoğu : Diyarbakır, Şanlıurfa, Mardin, Batman, Siirt, Şırnak, Hakkari, Gaziantep, Kilis, Adıyaman
SCHEMA;

    // ── Eylem JSON formatı ──────────────────────────────────────────────────
    private const EYLEM_FORMAT = <<<'FORMAT'
━━━ YANIT FORMATI (sadece JSON döndür, başka hiçbir şey yazma) ━━━

{
  "yanit": "Kullanıcıya gösterilecek metin (markdown destekli)",
  "eylem": null
}

VEYA eylem gerekiyorsa:

{
  "yanit": "Metin",
  "eylem": {
    "tip": "email_gonder" | "sms_gonder" | "secim",

    // tip=email_gonder veya sms_gonder için:
    "hedefler": [
      {"belge_no": 123, "unvan": "X Tur", "eposta": "x@x.com", "telefon": "5321234567"}
    ],
    "hedef_sayisi": 5,
    "hedef_sql": "SELECT belge_no, acente_unvani, eposta, telefon FROM acenteler WHERE ...",
    "icerik": "SMS metni — {acente_unvani} değişkeni kişiselleştirme için kullanılabilir",
    "zamanlama": null,
    "onceki_uyari": "Bu 3 acenteye daha önce email gönderildi." | null,

    // tip=secim için (birden fazla acente bulundu, hangisi?):
    "secenekler": [
      {"belge_no": 123, "unvan": "Hilal Tur", "il": "İstanbul", "il_ilce": "İSTANBUL - ŞİŞLİ"}
    ]
  }
}

YANIT TONU KURALLARI:
- Sohbet sorusu veya selamlama gelirse (saat kaç, nasılsın, ne yapıyorsun, merhaba vb.) → eylem:null, yanit'te samimi ve kısa cevap ver.
- Veri sorgusu gelirse → kısa, net, veri odaklı cevap.
- Güncel zamanı bilerek cevap ver (verilen zaman bilgisini kullan).
- Her zaman Türkçe yaz.

EYLEM KURALLARI:
- Sadece bilgi sorusu → eylem: null
- Email/SMS göndermek isteniyor → uygun tip
- 2-5 benzer acente bulundu → tip: "secim" (hangisini kastettiğini sor)
- >10 hedef varsa hedef_sql doldur, hedefler boş bırak
- ≤10 hedef varsa hedefler listesi doldur
- onceki_uyari: tursab_davetler'de kayıt varsa belirt
- Adres varsa yanit içine Google Maps linki ekle: https://maps.google.com/?q=ADRES_URL_ENCODED
- İki adres varsa güzergah linki: https://maps.google.com/maps/dir/ADRES1/ADRES2
FORMAT;

    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
        return view('superadmin.acente-ai');
    }

    // ── Ana sohbet endpoint'i ───────────────────────────────────────────────
    public function ask(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $soru = trim($request->input('soru', ''));
        if (strlen($soru) < 2) {
            return response()->json(['hata' => 'Lütfen bir soru girin.'], 422);
        }

        $apiKey = (string) config('services.gemini.key');
        if ($apiKey === '') {
            return response()->json(['hata' => 'Gemini API anahtarı tanımlı değil.'], 500);
        }

        $model  = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
        $gecmis = $request->input('gecmis', []);

        try {
            $sql     = $this->generateSql($soru, $gecmis, $apiKey, $model);
            $results = $sql === 'NO_SQL' ? [] : $this->executeSql($sql);
            $yanit   = $this->formatResult($soru, $sql, $results, $gecmis, $apiKey, $model);

            return response()->json($yanit);
        } catch (\Exception $e) {
            return response()->json(['hata' => $e->getMessage()], 500);
        }
    }

    // ── Email gönder endpoint'i ─────────────────────────────────────────────
    public function emailGonder(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        try {
        $hedefler  = $request->input('hedefler', []);
        $hedefSql  = $request->input('hedef_sql');
        $userId    = auth()->id();

        // SQL'den hedef listesi oluştur
        if (empty($hedefler) && $hedefSql) {
            $hedefler = $this->getSqlHedefler($hedefSql, 'email');
        }

        if (empty($hedefler)) {
            return response()->json(['hata' => 'Hedef bulunamadı.'], 422);
        }

        $gonderilen = 0;
        $hatalar    = 0;

        foreach ($hedefler as $h) {
            $eposta = $h['eposta'] ?? null;
            $belgeNo = (string) ($h['belge_no'] ?? '');
            $unvan   = $h['unvan'] ?? '';
            $il      = $h['il'] ?? '';

            if (! $eposta || ! filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
                $hatalar++;
                continue;
            }

            // Daha önce gönderilmiş mi kontrol et
            $daha_once = DB::table('tursab_davetler')
                ->where('belge_no', $belgeNo)
                ->where('tip', 'email')
                ->where('status', 'sent')
                ->exists();

            $status = 'sent';
            $hata   = null;

            try {
                Mail::send(
                    'emails.tursab_davet',
                    ['acenteUnvani' => $unvan, 'belgeNo' => $belgeNo, 'kayitUrl' => route('register')],
                    fn($mail) => $mail->to($eposta, $unvan)->subject('GrupTalepleri.com — Platforma Davet')
                );
                $gonderilen++;
            } catch (\Exception $e) {
                $status = 'error';
                $hata   = $e->getMessage();
                $hatalar++;
            }

            DB::table('tursab_davetler')->insert([
                'belge_no'        => $belgeNo,
                'eposta'          => $eposta,
                'acente_unvani'   => $unvan,
                'il'              => $il,
                'tip'             => 'email',
                'status'          => $status,
                'hata'            => $hata,
                'gonderen_user_id'=> $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Bu ay özet
        $aylikEmail = DB::table('tursab_davetler')
            ->where('tip', 'email')->where('status', 'sent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $aylikSms = DB::table('tursab_davetler')
            ->where('tip', 'sms')->where('status', 'sent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'yanit' => "✓ **{$gonderilen} email gönderildi.**"
                . ($hatalar > 0 ? " ({$hatalar} hata)" : '')
                . "\n\nBu ay toplam: **{$aylikEmail} email**, **{$aylikSms} SMS** gönderildi.",
        ]);
        } catch (\Throwable $e) {
            return response()->json(['hata' => $e->getMessage()], 500);
        }
    }

    // ── SMS gönder endpoint'i ───────────────────────────────────────────────
    public function smsGonder(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        try {
        $hedefler  = $request->input('hedefler', []);
        $hedefSql  = $request->input('hedef_sql');
        $icerik    = trim($request->input('icerik', ''));
        $zamanlama = $request->input('zamanlama');

        if (empty($icerik)) {
            return response()->json(['hata' => 'SMS içeriği boş olamaz.'], 422);
        }

        if (empty($hedefler) && $hedefSql) {
            $hedefler = $this->getSqlHedefler($hedefSql, 'sms');
        }

        if (empty($hedefler)) {
            return response()->json(['hata' => 'Hedef bulunamadı.'], 422);
        }

        $scheduledFor = $zamanlama ? Carbon::parse($zamanlama) : null;
        $smsService   = new SmsService();
        $userId       = auth()->id();
        $gonderilen   = 0;
        $hatalar      = 0;

        foreach ($hedefler as $h) {
            $telefon = preg_replace('/\D/', '', $h['telefon'] ?? '');
            $belgeNo = (string) ($h['belge_no'] ?? '');
            $unvan   = $h['unvan'] ?? '';
            $il      = $h['il'] ?? '';

            if (strlen($telefon) < 10) {
                $hatalar++;
                continue;
            }

            // {acente_unvani} kişiselleştirme
            $mesaj = str_replace('{acente_unvani}', $unvan, $icerik);

            $status = 'sent';
            $hata   = null;

            try {
                $ok = $smsService->send(null, 'acente', $unvan, $telefon, $mesaj, $scheduledFor);
                if ($ok) {
                    $gonderilen++;
                } else {
                    $status = 'failed';
                    $hatalar++;
                }
            } catch (\Exception $e) {
                $status = 'error';
                $hata   = $e->getMessage();
                $hatalar++;
            }

            DB::table('tursab_davetler')->insert([
                'belge_no'        => $belgeNo,
                'eposta'          => '',
                'acente_unvani'   => $unvan,
                'il'              => $il,
                'tip'             => 'sms',
                'icerik'          => $mesaj,
                'status'          => $status,
                'hata'            => $hata,
                'gonderen_user_id'=> $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $aylikEmail = DB::table('tursab_davetler')
            ->where('tip', 'email')->where('status', 'sent')
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->count();

        $aylikSms = DB::table('tursab_davetler')
            ->where('tip', 'sms')->where('status', 'sent')
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->count();

        $zamanlamaMetin = $scheduledFor ? " ({$scheduledFor->format('d.m.Y H:i')} için planlandı)" : '';

        return response()->json([
            'yanit' => "✓ **{$gonderilen} SMS gönderildi{$zamanlamaMetin}.**"
                . ($hatalar > 0 ? " ({$hatalar} hata)" : '')
                . "\n\nBu ay toplam: **{$aylikEmail} email**, **{$aylikSms} SMS** gönderildi.",
        ]);
        } catch (\Throwable $e) {
            return response()->json(['hata' => $e->getMessage()], 500);
        }
    }

    // ── Anlık zaman bloğu ───────────────────────────────────────────────────
    private function zamanBolumu(): string
    {
        $now  = now()->setTimezone('Europe/Istanbul');
        $gunler = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
        $aylar  = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        return "━━━ GÜNCEL ZAMAN ━━━\n"
            . "Tarih : " . $now->format('d.m.Y') . " " . $gunler[$now->dayOfWeek] . "\n"
            . "Saat  : " . $now->format('H:i') . " (Türkiye saati)\n"
            . "Ay    : " . $aylar[(int)$now->format('n')] . " " . $now->format('Y') . "\n\n";
    }

    // ── SQL üret (Gemini — 1. çağrı) ───────────────────────────────────────
    private function generateSql(string $soru, array $gecmis, string $apiKey, string $model): string
    {
        $gecmisBolum = $this->buildGecmisBolum($gecmis, 4, 200);

        $prompt = self::SCHEMA
            . "\n" . $this->zamanBolumu()
            . $gecmisBolum
            . "\nKULLANICI SORUSU: {$soru}\n\n"
            . "Yukarıdaki soruyu yanıtlayacak MySQL SELECT sorgusunu yaz.\n"
            . "Eğer soru veritabanı gerektirmiyorsa (selamlama, sohbet, zaman sorusu, genel soru) SADECE şunu yaz: NO_SQL\n"
            . "Aksi halde SADECE SQL döndür. Açıklama ve markdown backtick kullanma.";

        $sql = $this->geminiCall($prompt, $apiKey, $model, 512);
        $sql = preg_replace('/^```sql\s*/i', '', trim($sql));
        $sql = preg_replace('/\s*```$/i', '', $sql);

        return trim($sql);
    }

    // ── SQL çalıştır ────────────────────────────────────────────────────────
    private function executeSql(string $sql): array
    {
        $upper = strtoupper(ltrim($sql));

        if (! str_starts_with($upper, 'SELECT')) {
            throw new \Exception('Güvenlik: Yalnızca SELECT sorgusu çalıştırılabilir.');
        }

        foreach (['DROP', 'DELETE', 'UPDATE', 'INSERT', 'TRUNCATE', 'ALTER', 'CREATE', 'GRANT', 'REVOKE'] as $w) {
            if (preg_match('/\b' . $w . '\b/', $upper)) {
                throw new \Exception("Güvenlik: '{$w}' komutu yasak.");
            }
        }

        if (! str_contains($upper, 'LIMIT')) {
            $sql .= ' LIMIT 20';
        }

        return array_map(fn($r) => (array) $r, DB::select($sql));
    }

    // ── Sonucu formatla + eylem tespiti (Gemini — 2. çağrı) ────────────────
    private function formatResult(string $soru, string $sql, array $results, array $gecmis, string $apiKey, string $model): array
    {
        $sonucJson   = count($results) > 0
            ? json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : 'Sonuç bulunamadı.';

        $gecmisBolum = $this->buildGecmisBolum($gecmis, 4, 300);

        $prompt = self::SCHEMA . "\n"
            . $this->zamanBolumu()
            . self::EYLEM_FORMAT . "\n\n"
            . $gecmisBolum
            . "SORU: {$soru}\n\n"
            . "ÇALIŞAN SQL:\n{$sql}\n\n"
            . "SORGU SONUCU:\n{$sonucJson}\n\n"
            . "SADECE JSON döndür. Başka hiçbir şey yazma.";

        $raw = $this->geminiCall($prompt, $apiKey, $model, 0);

        // JSON parse
        $raw = preg_replace('/^```json\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/i', '', $raw);

        $parsed = json_decode(trim($raw), true);

        if (! $parsed || ! isset($parsed['yanit'])) {
            // Parse başarısız — düz metin olarak döndür
            return ['yanit' => $raw, 'eylem' => null];
        }

        return $parsed;
    }

    // ── Bulk SQL'den hedef listesi ──────────────────────────────────────────
    private function getSqlHedefler(string $sql, string $tip): array
    {
        $rows = $this->executeSql($sql . ' LIMIT 500');
        return array_map(fn($r) => [
            'belge_no' => $r['belge_no'] ?? '',
            'unvan'    => $r['acente_unvani'] ?? $r['unvan'] ?? '',
            'eposta'   => $r['eposta'] ?? '',
            'telefon'  => $r['telefon'] ?? '',
            'il'       => $r['il'] ?? '',
        ], $rows);
    }

    // ── Gemini API çağrısı ──────────────────────────────────────────────────
    private function geminiCall(string $prompt, string $apiKey, string $model, int $thinkingBudget): string
    {
        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => $thinkingBudget]],
            ]
        );

        if (! $response->successful()) {
            throw new \Exception('Gemini API hatası: ' . $response->status());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (! $text) {
            throw new \Exception('Gemini boş yanıt döndürdü.');
        }

        return $text;
    }

    // ── Konuşma geçmişi metni ───────────────────────────────────────────────
    private function buildGecmisBolum(array $gecmis, int $limit, int $maxLen): string
    {
        if (count($gecmis) === 0) return '';

        $satirlar = [];
        foreach (array_slice($gecmis, -$limit) as $msg) {
            $rol        = ($msg['rol'] ?? '') === 'kullanici' ? 'Kullanıcı' : 'Asistan';
            $satirlar[] = "{$rol}: " . mb_substr($msg['icerik'] ?? '', 0, $maxLen);
        }

        return "\nÖNCEKİ KONUŞMA (bağlam):\n" . implode("\n", $satirlar) . "\n\n";
    }
}
