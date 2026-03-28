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

        $queryResult = $this->runSmartQuery($soru);
        $prompt      = $this->buildPrompt($queryResult, $soru);
        $model       = (string) config('services.gemini.text_model', 'gemini-2.5-flash');

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'thinkingConfig' => ['thinkingBudget' => 0],
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

    // ── Soruya göre DB'den gerçek veri çek ─────────────────────────────────
    private function runSmartQuery(string $soru): string
    {
        $s = mb_strtolower($soru, 'UTF-8');
        $s = str_replace(["'", "'", "'"], '', $s);

        // 1. Belge no sorgusu — soruda 3-6 haneli sayı varsa
        if (preg_match('/\b(\d{3,6})\b/', $soru, $m)) {
            $no   = $m[1];
            $rows = DB::table('acenteler')
                ->where('belge_no', $no)
                ->get(['acente_unvani', 'belge_no', 'il', 'il_ilce', 'telefon', 'eposta', 'kaynak', 'is_sube']);

            if ($rows->isEmpty()) {
                return "Belge no {$no} ile kayıt bulunamadı.";
            }

            $lines = $rows->map(function ($r) {
                $parts = array_filter([
                    $r->acente_unvani,
                    "Belge No: {$r->belge_no}",
                    $r->il       ? "İl: {$r->il}"         : null,
                    $r->il_ilce  ? "İlçe: {$r->il_ilce}"  : null,
                    $r->telefon  ? "Tel: {$r->telefon}"   : null,
                    $r->eposta   ? "E-posta: {$r->eposta}" : null,
                    "Kaynak: {$r->kaynak}",
                    $r->is_sube  ? "(Şube)"                : null,
                ]);
                return implode(' | ', $parts);
            })->implode("\n");

            return "Belge no {$no} sorgusu ({$rows->count()} kayıt):\n{$lines}";
        }

        // 2. İl sorgusu — Türk şehir adları
        $ilMap = [
            'adana'          => 'Adana',          'adıyaman'       => 'Adıyaman',
            'afyon'          => 'Afyonkarahisar',  'afyonkarahisar' => 'Afyonkarahisar',
            'ağrı'           => 'Ağrı',            'aksaray'        => 'Aksaray',
            'amasya'         => 'Amasya',          'ankara'         => 'Ankara',
            'antalya'        => 'Antalya',         'ardahan'        => 'Ardahan',
            'artvin'         => 'Artvin',          'aydın'          => 'Aydın',
            'balıkesir'      => 'Balıkesir',       'bartın'         => 'Bartın',
            'batman'         => 'Batman',          'bayburt'        => 'Bayburt',
            'bilecik'        => 'Bilecik',         'bingöl'         => 'Bingöl',
            'bitlis'         => 'Bitlis',          'bolu'           => 'Bolu',
            'burdur'         => 'Burdur',          'bursa'          => 'Bursa',
            'çanakkale'      => 'Çanakkale',       'çankırı'        => 'Çankırı',
            'çorum'          => 'Çorum',           'denizli'        => 'Denizli',
            'diyarbakır'     => 'Diyarbakır',      'düzce'          => 'Düzce',
            'edirne'         => 'Edirne',          'elazığ'         => 'Elazığ',
            'erzincan'       => 'Erzincan',        'erzurum'        => 'Erzurum',
            'eskişehir'      => 'Eskişehir',       'gaziantep'      => 'Gaziantep',
            'giresun'        => 'Giresun',         'gümüşhane'      => 'Gümüşhane',
            'hakkari'        => 'Hakkari',         'hatay'          => 'Hatay',
            'ığdır'          => 'Iğdır',           'iğdır'          => 'Iğdır',
            'ısparta'        => 'Isparta',         'isparta'        => 'Isparta',
            'istanbul'       => 'İstanbul',        'i̇stanbul'       => 'İstanbul',
            'izmir'          => 'İzmir',           'i̇zmir'          => 'İzmir',
            'kahramanmaraş'  => 'Kahramanmaraş',   'karabük'        => 'Karabük',
            'karaman'        => 'Karaman',         'kars'           => 'Kars',
            'kastamonu'      => 'Kastamonu',       'kayseri'        => 'Kayseri',
            'kilis'          => 'Kilis',           'kırıkkale'      => 'Kırıkkale',
            'kırklareli'     => 'Kırklareli',      'kırşehir'       => 'Kırşehir',
            'kocaeli'        => 'Kocaeli',         'konya'          => 'Konya',
            'kütahya'        => 'Kütahya',         'malatya'        => 'Malatya',
            'manisa'         => 'Manisa',          'mardin'         => 'Mardin',
            'mersin'         => 'Mersin',          'muğla'          => 'Muğla',
            'muş'            => 'Muş',             'nevşehir'       => 'Nevşehir',
            'niğde'          => 'Niğde',           'ordu'           => 'Ordu',
            'osmaniye'       => 'Osmaniye',        'rize'           => 'Rize',
            'sakarya'        => 'Sakarya',         'samsun'         => 'Samsun',
            'siirt'          => 'Siirt',           'sinop'          => 'Sinop',
            'sivas'          => 'Sivas',           'şanlıurfa'      => 'Şanlıurfa',
            'urfa'           => 'Şanlıurfa',       'şırnak'         => 'Şırnak',
            'tekirdağ'       => 'Tekirdağ',        'tokat'          => 'Tokat',
            'trabzon'        => 'Trabzon',         'tunceli'        => 'Tunceli',
            'uşak'           => 'Uşak',            'van'            => 'Van',
            'yalova'         => 'Yalova',          'yozgat'         => 'Yozgat',
            'zonguldak'      => 'Zonguldak',
        ];

        foreach ($ilMap as $lower => $proper) {
            if (str_contains($s, $lower)) {
                $total    = DB::table('acenteler')->whereRaw('LOWER(il) = ?', [$proper === 'İstanbul' ? 'istanbul' : mb_strtolower($proper, 'UTF-8')])->count();
                $tursab   = DB::table('acenteler')->whereRaw('LOWER(il) = ?', [$proper === 'İstanbul' ? 'istanbul' : mb_strtolower($proper, 'UTF-8')])->where('kaynak', 'tursab')->count();
                $bakanlik = DB::table('acenteler')->whereRaw('LOWER(il) = ?', [$proper === 'İstanbul' ? 'istanbul' : mb_strtolower($proper, 'UTF-8')])->where('kaynak', 'bakanlik')->count();
                return "{$proper} il sorgusu: Toplam {$total} acente | TÜRSAB: {$tursab} | Bakanlık: {$bakanlik}";
            }
        }

        // 3. Acente adı arama — tırnak içi veya anahtar kelimeden
        $arama = null;

        // Tırnak içi
        if (preg_match('/["\'""](.+?)["\'""]/', $soru, $m)) {
            $arama = trim($m[1]);
        }

        // "X tur / X seyahat / X travel" pattern
        if (! $arama && preg_match('/^([\p{L}\s]{3,40?}?)\s+(?:tur\b|turizm|seyahat|travel|acente)/iu', $soru, $m)) {
            $arama = trim($m[1]);
        }

        // Soru sonunda "nedir/kime ait/kim" → önceki kısım acente adı
        if (! $arama && preg_match('/^([\p{L}\s]{3,40}?)\s+(?:belge\s*no\s*(?:nedir|kaç|ne)?|kime\s+ait|kim|nerede)/iu', $soru, $m)) {
            $arama = trim($m[1]);
        }

        if ($arama && mb_strlen($arama) >= 3) {
            $rows = DB::table('acenteler')
                ->whereRaw('acente_unvani LIKE ?', ['%'.$arama.'%'])
                ->limit(10)
                ->get(['acente_unvani', 'belge_no', 'il', 'il_ilce', 'telefon', 'eposta', 'kaynak']);

            if ($rows->isEmpty()) {
                return "'{$arama}' araması: Kayıt bulunamadı.";
            }

            $lines = $rows->map(fn($r) => implode(' | ', array_filter([
                $r->acente_unvani,
                "Belge No: {$r->belge_no}",
                $r->il      ? "İl: {$r->il}"         : null,
                $r->il_ilce ? "İlçe: {$r->il_ilce}"  : null,
                $r->telefon ? "Tel: {$r->telefon}"   : null,
                $r->eposta  ? "E-posta: {$r->eposta}" : null,
            ])))->implode("\n");

            return "'{$arama}' araması ({$rows->count()} sonuç):\n{$lines}";
        }

        // 4. Genel istatistik
        $toplam   = DB::table('acenteler')->count();
        $tursab   = DB::table('acenteler')->where('kaynak', 'tursab')->count();
        $bakanlik = DB::table('acenteler')->where('kaynak', 'bakanlik')->count();
        $manuel   = DB::table('acenteler')->where('kaynak', 'manuel')->count();

        return "Genel istatistik: Toplam {$toplam} acente | TÜRSAB: {$tursab} | Bakanlık: {$bakanlik} | Manuel: {$manuel}";
    }

    // ── Prompt oluştur ─────────────────────────────────────────────────────
    private function buildPrompt(string $queryResult, string $soru): string
    {
        return <<<PROMPT
Sen bir veri sorgulama motorusun. Veritabanından gelen gerçek sonucu kullanıcıya aktar.

## VERİTABANI SORGU SONUCU
{$queryResult}

## KULLANICI SORUSU
{$soru}

## TALİMATLAR
- Sadece yukarıdaki sorgu sonucunu kullanarak cevap ver.
- Birden fazla kayıt varsa her birini yeni satırda listele.
- Yorum yapma, analiz yapma, açıklama ekleme.
- Cevabı kısa ve net tut.
- Soru dışı bilgi verme.
PROMPT;
    }
}
