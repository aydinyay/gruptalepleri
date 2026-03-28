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

        $gecmis      = $request->input('gecmis', []);
        $queryResult = $this->runSmartQuery($soru, $gecmis);
        $prompt      = $this->buildPrompt($queryResult, $soru, $gecmis);
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
    private function runSmartQuery(string $soru, array $gecmis = []): string
    {
        $s = mb_strtolower($soru, 'UTF-8');
        $s = str_replace(["'", "'", "'"], '', $s);

        // 0. Referanslı soru tespiti — "bu", "onun", "aynı", "o acente" gibi ifadeler
        $referansKelimeler = ['bu acent', 'bu firma', 'bunun', 'bu şirket', 'o acent', 'onun', 'aynı acent', 'bu kayıt'];
        $referansli = false;
        foreach ($referansKelimeler as $k) {
            if (str_contains($s, $k)) { $referansli = true; break; }
        }
        // Çok kısa sorular da referanslı olabilir ("nerde?", "telefonu?", "e-postası?")
        if (! $referansli && mb_strlen(trim($soru)) < 30 && count($gecmis) > 0) {
            $referansKelimeler2 = ['nerd', 'nerede', 'telefon', 'e-posta', 'eposta', 'mail', 'adres', 'il', 'ilçe', 'kaynak', 'şube'];
            foreach ($referansKelimeler2 as $k) {
                if (str_contains($s, $k) && ! preg_match('/\b\d{3,6}\b/', $soru)) {
                    $referansli = true; break;
                }
            }
        }

        if ($referansli && count($gecmis) > 0) {
            // Önceki AI mesajlarından belge no çıkar
            foreach (array_reverse($gecmis) as $msg) {
                if (($msg['rol'] ?? '') === 'ai') {
                    if (preg_match('/belge\s*no[:\s]+(\d{3,6})/i', $msg['icerik'], $m)) {
                        $no   = $m[1];
                        $rows = DB::table('acenteler')
                            ->where('belge_no', $no)
                            ->get(['acente_unvani', 'belge_no', 'il', 'il_ilce', 'telefon', 'eposta', 'kaynak', 'is_sube']);
                        if ($rows->isNotEmpty()) {
                            $lines = $rows->map(function ($r) {
                                return implode(' | ', array_filter([
                                    $r->acente_unvani,
                                    "Belge No: {$r->belge_no}",
                                    $r->il      ? "İl: {$r->il}"         : null,
                                    $r->il_ilce ? "İlçe: {$r->il_ilce}"  : null,
                                    $r->telefon ? "Tel: {$r->telefon}"   : null,
                                    $r->eposta  ? "E-posta: {$r->eposta}" : null,
                                    "Kaynak: {$r->kaynak}",
                                    $r->is_sube ? "(Şube)"                : null,
                                ]));
                            })->implode("\n");
                            return "Belge no {$no} - önceki konuşmadan bağlam ({$rows->count()} kayıt):\n{$lines}";
                        }
                    }
                    break;
                }
            }
        }

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

        // 2. Bölge sorgusu — "ege bölgesi", "marmara", "karadeniz" vb.
        $bolgeMap = [
            'marmara'      => ['İstanbul','Tekirdağ','Edirne','Kırklareli','Çanakkale','Balıkesir','Bursa','Kocaeli','Sakarya','Düzce','Bolu','Yalova'],
            'ege'          => ['İzmir','Manisa','Afyonkarahisar','Kütahya','Uşak','Denizli','Aydın','Muğla'],
            'akdeniz'      => ['Antalya','Isparta','Burdur','Mersin','Adana','Hatay','Kahramanmaraş','Osmaniye'],
            'iç anadolu'   => ['Ankara','Konya','Eskişehir','Sivas','Yozgat','Kayseri','Aksaray','Niğde','Nevşehir','Kırıkkale','Kırşehir','Çankırı'],
            'ic anadolu'   => ['Ankara','Konya','Eskişehir','Sivas','Yozgat','Kayseri','Aksaray','Niğde','Nevşehir','Kırıkkale','Kırşehir','Çankırı'],
            'karadeniz'    => ['Zonguldak','Karabük','Bartın','Kastamonu','Çorum','Sinop','Samsun','Amasya','Tokat','Ordu','Giresun','Trabzon','Rize','Artvin','Gümüşhane','Bayburt'],
            'doğu anadolu' => ['Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır','Van','Bitlis','Muş','Bingöl','Tunceli','Elazığ','Malatya'],
            'dogu anadolu' => ['Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır','Van','Bitlis','Muş','Bingöl','Tunceli','Elazığ','Malatya'],
            'güneydoğu'    => ['Diyarbakır','Şanlıurfa','Mardin','Batman','Siirt','Şırnak','Hakkari','Gaziantep','Kilis','Adıyaman'],
            'guneydogu'    => ['Diyarbakır','Şanlıurfa','Mardin','Batman','Siirt','Şırnak','Hakkari','Gaziantep','Kilis','Adıyaman'],
        ];

        foreach ($bolgeMap as $bolgeAdi => $iller) {
            if (str_contains($s, $bolgeAdi)) {
                $total    = DB::table('acenteler')->whereIn('il', $iller)->count();
                $tursab   = DB::table('acenteler')->whereIn('il', $iller)->where('kaynak', 'tursab')->count();
                $bakanlik = DB::table('acenteler')->whereIn('il', $iller)->where('kaynak', 'bakanlik')->count();
                $ilDetay  = DB::table('acenteler')
                    ->selectRaw('il, COUNT(*) as toplam')
                    ->whereIn('il', $iller)
                    ->groupBy('il')->orderByDesc('toplam')->get()
                    ->map(fn($r) => "{$r->il}: {$r->toplam}")->implode(', ');
                $proper = ucwords($bolgeAdi === 'ic anadolu' ? 'İç Anadolu' : ($bolgeAdi === 'dogu anadolu' ? 'Doğu Anadolu' : ($bolgeAdi === 'guneydogu' ? 'Güneydoğu' : $bolgeAdi)));
                return "{$proper} Bölgesi sorgusu: Toplam {$total} acente | TÜRSAB: {$tursab} | Bakanlık: {$bakanlik}\nİl detayı: {$ilDetay}";
            }
        }

        // 3. İl sorgusu — Türk şehir adları (LIKE kullan, LOWER() Türkçe İ sorununu çözer)
        $ilMap = [
            'adana'          => 'Adana',          'adiyaman'       => 'Adıyaman',
            'afyon'          => 'Afyonkarahisar',  'afyonkarahisar' => 'Afyonkarahisar',
            'agri'           => 'Ağrı',            'aksaray'        => 'Aksaray',
            'amasya'         => 'Amasya',          'ankara'         => 'Ankara',
            'antalya'        => 'Antalya',         'ardahan'        => 'Ardahan',
            'artvin'         => 'Artvin',          'aydin'          => 'Aydın',
            'balikesir'      => 'Balıkesir',       'bartin'         => 'Bartın',
            'batman'         => 'Batman',          'bayburt'        => 'Bayburt',
            'bilecik'        => 'Bilecik',         'bingol'         => 'Bingöl',
            'bitlis'         => 'Bitlis',          'bolu'           => 'Bolu',
            'burdur'         => 'Burdur',          'bursa'          => 'Bursa',
            'canakkale'      => 'Çanakkale',       'cankiri'        => 'Çankırı',
            'corum'          => 'Çorum',           'denizli'        => 'Denizli',
            'diyarbakir'     => 'Diyarbakır',      'duzce'          => 'Düzce',
            'edirne'         => 'Edirne',          'elazig'         => 'Elazığ',
            'erzincan'       => 'Erzincan',        'erzurum'        => 'Erzurum',
            'eskisehir'      => 'Eskişehir',       'gaziantep'      => 'Gaziantep',
            'giresun'        => 'Giresun',         'gumushane'      => 'Gümüşhane',
            'hakkari'        => 'Hakkari',         'hatay'          => 'Hatay',
            'igdir'          => 'Iğdır',           'isparta'        => 'Isparta',
            'istanbul'       => 'İstanbul',        'izmir'          => 'İzmir',
            'kahramanmaras'  => 'Kahramanmaraş',   'karabuk'        => 'Karabük',
            'karaman'        => 'Karaman',         'kars'           => 'Kars',
            'kastamonu'      => 'Kastamonu',       'kayseri'        => 'Kayseri',
            'kilis'          => 'Kilis',           'kirikkale'      => 'Kırıkkale',
            'kirklareli'     => 'Kırklareli',      'kirsehir'       => 'Kırşehir',
            'kocaeli'        => 'Kocaeli',         'konya'          => 'Konya',
            'kutahya'        => 'Kütahya',         'malatya'        => 'Malatya',
            'manisa'         => 'Manisa',          'mardin'         => 'Mardin',
            'mersin'         => 'Mersin',          'mugla'          => 'Muğla',
            'mus'            => 'Muş',             'nevsehir'       => 'Nevşehir',
            'nigde'          => 'Niğde',           'ordu'           => 'Ordu',
            'osmaniye'       => 'Osmaniye',        'rize'           => 'Rize',
            'sakarya'        => 'Sakarya',         'samsun'         => 'Samsun',
            'siirt'          => 'Siirt',           'sinop'          => 'Sinop',
            'sivas'          => 'Sivas',           'sanliurfa'      => 'Şanlıurfa',
            'urfa'           => 'Şanlıurfa',       'sirnak'         => 'Şırnak',
            'tekirdag'       => 'Tekirdağ',        'tokat'          => 'Tokat',
            'trabzon'        => 'Trabzon',         'tunceli'        => 'Tunceli',
            'usak'           => 'Uşak',            'van'            => 'Van',
            'yalova'         => 'Yalova',          'yozgat'         => 'Yozgat',
            'zonguldak'      => 'Zonguldak',
        ];

        // Soruyu ASCII'ye dönüştür (Türkçe harf sorununu bypass et)
        $sAscii = strtr($s, [
            'ş'=>'s','ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c','ı'=>'i','İ'=>'i','Ş'=>'s','Ğ'=>'g','Ü'=>'u','Ö'=>'o','Ç'=>'c',
        ]);

        foreach ($ilMap as $ascii => $proper) {
            if (str_contains($sAscii, $ascii)) {
                // LIKE ile sorgula — Türkçe karakter duyarsız
                $total    = DB::table('acenteler')->where('il', 'LIKE', $proper)->count();
                $tursab   = DB::table('acenteler')->where('il', 'LIKE', $proper)->where('kaynak', 'tursab')->count();
                $bakanlik = DB::table('acenteler')->where('il', 'LIKE', $proper)->where('kaynak', 'bakanlik')->count();
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
    private function buildPrompt(string $queryResult, string $soru, array $gecmis = []): string
    {
        $gecmisBolum = '';
        if (count($gecmis) > 0) {
            $satirlar = [];
            foreach (array_slice($gecmis, -6) as $msg) {
                $rol = ($msg['rol'] ?? '') === 'kullanici' ? 'Kullanıcı' : 'Asistan';
                $satirlar[] = "{$rol}: " . ($msg['icerik'] ?? '');
            }
            $gecmisBolum = "\n## ÖNCEKİ KONUŞMA\n" . implode("\n", $satirlar) . "\n";
        }

        return <<<PROMPT
Sen bir veri sorgulama motorusun. Veritabanından gelen gerçek sonucu kullanıcıya aktar.
{$gecmisBolum}
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
