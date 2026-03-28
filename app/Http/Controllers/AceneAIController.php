<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AceneAIController extends Controller
{
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
        return view('superadmin.acente-ai');
    }

    public function ask(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $soru = trim($request->input('soru', ''));
        if (strlen($soru) < 3) {
            return response()->json(['hata' => 'Lütfen bir soru girin.'], 422);
        }

        $apiKey = (string) config('services.gemini.key');
        if ($apiKey === '') {
            return response()->json(['hata' => 'Gemini API anahtarı tanımlı değil.'], 500);
        }

        // ── Veritabanından bağlam verileri çek ──────────────────────────────
        $context = $this->buildContext();

        // ── Gemini'ye gönderilecek prompt ───────────────────────────────────
        $prompt = $this->buildPrompt($context, $soru);

        $model = (string) config('services.gemini.text_model', 'gemini-2.5-flash');

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'thinkingConfig' => ['thinkingBudget' => 1024],
                    ],
                ]
            );

            if (! $response->successful()) {
                return response()->json(['hata' => 'Gemini API hatası: '.$response->status()], 500);
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            if (! $text) {
                return response()->json(['hata' => 'Yanıt alınamadı.'], 500);
            }

            return response()->json(['yanit' => $text]);
        } catch (\Exception $e) {
            return response()->json(['hata' => 'Bağlantı hatası: '.$e->getMessage()], 500);
        }
    }

    // ── Veritabanı bağlam verilerini hazırla ────────────────────────────────
    private function buildContext(): array
    {
        $toplam     = DB::table('acenteler')->count();
        $tursab     = DB::table('acenteler')->where('kaynak', 'tursab')->count();
        $bakanlik   = DB::table('acenteler')->where('kaynak', 'bakanlik')->count();
        $manuel     = DB::table('acenteler')->where('kaynak', 'manuel')->count();
        $epostaVar  = DB::table('acenteler')->whereNotNull('eposta')->where('eposta', '!=', '')->count();
        $telefonVar = DB::table('acenteler')->whereNotNull('telefon')->where('telefon', '!=', '')->count();
        $subeCount  = DB::table('acenteler')->where('is_sube', 1)->count();

        $ilDagilim = DB::table('acenteler')
            ->selectRaw('il, COUNT(*) as toplam')
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')->orderByDesc('toplam')->limit(20)->get();

        $grupDagilim = DB::table('acenteler')
            ->selectRaw("COALESCE(NULLIF(grup,''), 'Belirtilmemiş') as grup, COUNT(*) as toplam")
            ->where('kaynak', 'tursab')
            ->groupBy('grup')->orderByDesc('toplam')->get();

        $ilceDagilim = DB::table('acenteler')
            ->selectRaw('il, il_ilce, COUNT(*) as toplam')
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il', 'il_ilce')->orderByDesc('toplam')->limit(20)->get();

        $enCokSubeli = DB::table('acenteler')
            ->selectRaw("belge_no,
                MAX(CASE WHEN is_sube = 0 THEN acente_unvani ELSE NULL END) as unvan,
                MAX(CASE WHEN is_sube = 0 THEN il ELSE NULL END) as il,
                SUM(CASE WHEN is_sube = 1 THEN 1 ELSE 0 END) as sube_sayisi")
            ->whereNotNull('belge_no')->where('belge_no', '!=', '')->where('belge_no', '!=', '0')
            ->whereRaw("belge_no REGEXP '^[0-9]+$'")
            ->groupBy('belge_no')->having('sube_sayisi', '>', 0)
            ->orderByDesc('sube_sayisi')->limit(10)->get();

        $bolgeMap = [
            'Marmara'      => ['İstanbul','Tekirdağ','Edirne','Kırklareli','Çanakkale','Balıkesir','Bursa','Kocaeli','Sakarya','Düzce','Bolu','Yalova'],
            'Ege'          => ['İzmir','Manisa','Afyonkarahisar','Kütahya','Uşak','Denizli','Aydın','Muğla'],
            'Akdeniz'      => ['Antalya','Isparta','Burdur','Mersin','Adana','Hatay','Kahramanmaraş','Osmaniye'],
            'İç Anadolu'   => ['Ankara','Konya','Eskişehir','Sivas','Yozgat','Kayseri','Aksaray','Niğde','Nevşehir','Kırıkkale','Kırşehir','Çankırı'],
            'Karadeniz'    => ['Zonguldak','Karabük','Bartın','Kastamonu','Çorum','Sinop','Samsun','Amasya','Tokat','Ordu','Giresun','Trabzon','Rize','Artvin','Gümüşhane','Bayburt'],
            'Doğu Anadolu' => ['Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır','Van','Bitlis','Muş','Bingöl','Tunceli','Elazığ','Malatya'],
            'Güneydoğu'    => ['Diyarbakır','Şanlıurfa','Mardin','Batman','Siirt','Şırnak','Hakkari','Gaziantep','Kilis','Adıyaman'],
        ];
        $bolgeler = [];
        foreach ($bolgeMap as $b => $iller) {
            $bolgeler[$b] = DB::table('acenteler')->whereIn('il', $iller)->count();
        }
        arsort($bolgeler);

        return compact(
            'toplam', 'tursab', 'bakanlik', 'manuel',
            'epostaVar', 'telefonVar', 'subeCount',
            'ilDagilim', 'grupDagilim', 'ilceDagilim',
            'enCokSubeli', 'bolgeler'
        );
    }

    // ── Prompt oluştur ─────────────────────────────────────────────────────
    private function buildPrompt(array $ctx, string $soru): string
    {
        $ilSatirlar = $ctx['ilDagilim']->map(fn($r) => "  {$r->il}: {$r->toplam}")->implode("\n");
        $ilceSatirlar = $ctx['ilceDagilim']->map(fn($r) => "  {$r->il_ilce} ({$r->il}): {$r->toplam}")->implode("\n");
        $grupSatirlar = $ctx['grupDagilim']->map(fn($r) => "  Grup {$r->grup}: {$r->toplam}")->implode("\n");
        $bolgeSatirlar = collect($ctx['bolgeler'])->map(fn($s, $b) => "  {$b}: {$s}")->implode("\n");
        $subeSatirlar = $ctx['enCokSubeli']->map(fn($r) => "  {$r->unvan} ({$r->il}): {$r->sube_sayisi} şube")->implode("\n");

        $epostaPct  = $ctx['toplam'] ? round($ctx['epostaVar']  / $ctx['toplam'] * 100, 1) : 0;
        $telefonPct = $ctx['toplam'] ? round($ctx['telefonVar'] / $ctx['toplam'] * 100, 1) : 0;
        $subePct    = $ctx['toplam'] ? round($ctx['subeCount']  / $ctx['toplam'] * 100, 1) : 0;

        return <<<PROMPT
Sen bir veri sorgulama motorusun. Yorum yapma, analiz yapma, açıklama yapma. Sadece verilen veriden sonuç döndür.

## VERİTABANI (Güncel Gerçek Veriler)

**Genel Özet:**
- Toplam acente kaydı: {$ctx['toplam']}
- TÜRSAB kaynaklı: {$ctx['tursab']}
- Bakanlık kaynaklı: {$ctx['bakanlik']}
- Manuel kayıt: {$ctx['manuel']}
- E-posta olan: {$ctx['epostaVar']} (%{$epostaPct})
- Telefon olan: {$ctx['telefonVar']} (%{$telefonPct})
- Şube sayısı: {$ctx['subeCount']} (%{$subePct})

**Bölge Bazlı Dağılım (Türkiye 7 coğrafi bölge):**
{$bolgeSatirlar}

**İl Bazlı Dağılım (Top 20):**
{$ilSatirlar}

**İlçe Bazlı Dağılım (Top 20):**
{$ilceSatirlar}

**TÜRSAB Grup Dağılımı:**
{$grupSatirlar}

**En Çok Şubeli Acenteler (Top 10):**
{$subeSatirlar}

**Referans Verileri (Harici Kaynaklardan):**
- Türkiye 2023 TÜRSAB toplam: 15.678 acente (tarihsel büyüme serisi mevcut)
- 2000 yılında 4.077 acenteyle başlanmış, 23 yılda %284 büyüme
- Avrupa genelinde 2005-2021 arasında -26% düşüş yaşanırken Türkiye +162% büyüdü
- Türkiye nüfusu: 85.279.553 (TÜİK 2023)

---

## KULLANICI SORUSU
{$soru}

---

## TALİMATLAR

Sen bir veri sorgulama motorusun.

Görevin:
Kullanıcının sorduğu soruya sadece veritabanındaki karşılığıyla cevap vermek.

Kurallar:
- Asla yorum yapma.
- Asla açıklama yapma.
- Asla analiz yapma.
- Asla ekstra bilgi verme.
- Asla ikinci cümle kurma.
- Asla soru sorma.
- Asla kendini tanıtma.

Cevap formatı zorunludur:

Eğer kayıt varsa:
{acenta adı} belge no {numara}

Eğer kayıt yoksa:
{acenta adı} kayıtlı değil

Kurallar:
- Cevap tek satır olacak.
- Nokta, virgül, açıklama ekleme.
- Sadece sonuç yaz.
- Format dışına çıkmak yasaktır.

Örnekler:

Soru: hilal tur belge no nedir
Cevap: hilal tur belge no 1244

Soru: xyz tur belge no nedir
Cevap: xyz tur kayıtlı değil
PROMPT;
    }
}

