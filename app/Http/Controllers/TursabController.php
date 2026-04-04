<?php

namespace App\Http\Controllers;

use App\Models\Acenteler;
use App\Models\TursabDavet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TursabController extends Controller
{
    private const GUNLUK_LIMIT = 50;

    /**
     * Kayıt sayfası — TÜRSAB belge no ile acente bilgisi sorgula (auth gerekmez).
     */
    public function sorgula(Request $request)
    {
        $belgeNo = trim($request->input('belge_no', ''));

        if (!$belgeNo) {
            return response()->json(['found' => false, 'message' => 'Belge no giriniz.']);
        }

        $acente = Acenteler::where('belge_no', $belgeNo)->first();

        if (!$acente) {
            return response()->json(['found' => false, 'message' => 'Bu belge no ile kayıtlı acente bulunamadı.']);
        }

        return response()->json([
            'found'         => true,
            'acente_unvani' => $acente->acente_unvani ?? '',
            'ticari_unvan'  => $acente->ticari_unvan  ?? '',
            'il'            => $acente->il             ?? '',
            'il_ilce'       => $acente->il_ilce        ?? '',
            'telefon'       => $acente->telefon        ?? '',
            'eposta'        => $acente->eposta         ?? '',
            'belge_no'      => $acente->belge_no       ?? '',
        ]);
    }

    /**
     * Superadmin — TÜRSAB listesinde arama (acenteler sayfası widget'ı için).
     */
    public function ara(Request $request)
    {
        $this->assertSuperadmin();

        $q    = trim($request->input('q', ''));
        $il   = trim($request->input('il', ''));
        $grup = trim($request->input('grup', ''));

        if (strlen($q) < 2 && !$il) {
            return response()->json(['results' => [], 'total' => 0]);
        }

        $query = Acenteler::query();

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('acente_unvani', 'like', "%{$q}%")
                  ->orWhere('ticari_unvan', 'like', "%{$q}%")
                  ->orWhere('belge_no', 'like', "%{$q}%")
                  ->orWhere('eposta', 'like', "%{$q}%");
            });
        }

        if ($il)   $query->where('il', $il);
        if ($grup) $query->where('grup', $grup);

        $total   = $query->count();
        $results = $query->select('id', 'belge_no', 'acente_unvani', 'ticari_unvan', 'grup', 'il', 'il_ilce', 'telefon', 'eposta')
                         ->limit(50)->get();

        return response()->json(['results' => $results, 'total' => $total]);
    }

    /**
     * Superadmin — tek acente davet emaili (acenteler sayfası widget'ından).
     */
    public function davetGonder(Request $request)
    {
        $this->assertSuperadmin();

        $request->validate([
            'eposta'        => 'required|email',
            'acente_unvani' => 'required|string',
        ]);

        $email      = $request->input('eposta');
        $acenteAdi  = $request->input('acente_unvani');
        $belgeNo    = $request->input('belge_no', '');
        $aiParagraf = trim($request->input('ai_paragraf', ''));
        $sablonlar  = ['emails.tursab_davet', 'emails.tursab_davet_yeni_acente'];
        $sablon     = in_array($request->input('sablon'), $sablonlar)
            ? $request->input('sablon')
            : 'emails.tursab_davet';

        try {
            $this->gonder($email, $acenteAdi, $belgeNo, $sablon, $aiParagraf);
            return response()->json(['success' => true, 'message' => "{$acenteAdi} adresine davet gönderildi."]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Email gönderilemedi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Superadmin — Pazarlama kampanya sayfası.
     */
    public function kampanya(Request $request)
    {
        $this->assertSuperadmin();

        // Filtreler
        $il       = trim($request->input('il', ''));
        $grup     = trim($request->input('grup', ''));
        $q        = trim($request->input('q', ''));
        $sadeceDavetEdilmemis = $request->boolean('sadece_yeni', true);
        $sadeceCep = $request->boolean('sadece_cep', false);
        $perPage  = in_array((int) $request->input('per_page', 50), [25, 50, 100, 200])
                    ? (int) $request->input('per_page', 50)
                    : 50;

        // Migrate edilmemiş olabilir — tablo yoksa varsayılan değerler kullan
        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('tursab_davetler');

        // Bugün gönderilen
        $bugunGonderilen = $tableExists ? TursabDavet::whereDate('created_at', today())->count() : 0;
        $kalanHak        = max(0, self::GUNLUK_LIMIT - $bugunGonderilen);

        // Daha önce davet edilen eposta listesi
        $davetEdilenler = $tableExists
            ? TursabDavet::pluck('eposta')->map(fn($e) => strtolower($e))->toArray()
            : [];

        // İl listesi (filtre dropdown için)
        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')
                          ->whereNotNull('eposta')->where('eposta', '!=', '')
                          ->distinct()->orderBy('il')->pluck('il');

        // Acente listesi
        $query = Acenteler::whereNotNull('eposta')->where('eposta', '!=', '');

        if ($q)    $query->where(fn($w) => $w->where('acente_unvani','like',"%{$q}%")->orWhere('belge_no','like',"%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($grup) $query->where('grup', $grup);

        if ($sadeceDavetEdilmemis && count($davetEdilenler)) {
            $placeholders = implode(',', array_fill(0, count($davetEdilenler), '?'));
            $query->whereRaw("LOWER(eposta) NOT IN ({$placeholders})", $davetEdilenler);
        }

        // Sadece cep numarası olanlar (alan kodu 5 ile başlayanlar)
        if ($sadeceCep) {
            $query->whereNotNull('telefon')->where('telefon', '!=', '')
                  ->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'");
        }

        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta','telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')
                           ->paginate($perPage)->withQueryString();

        // Davet geçmişi (son 200)
        $gecmis = $tableExists
            ? TursabDavet::with('gonderen')->orderByDesc('created_at')->limit(200)->get()
            : collect();

        return view('superadmin.tursab-kampanya', compact(
            'acenteler', 'iller', 'bugunGonderilen', 'kalanHak',
            'gecmis', 'il', 'grup', 'q', 'sadeceDavetEdilmemis', 'sadeceCep', 'perPage'
        ));
    }

    /**
     * Superadmin — Toplu davet gönder (kampanya sayfasından).
     */
    public function topluDavet(Request $request)
    {
        $this->assertSuperadmin();

        $secilen = $request->input('secilen', []);

        if (empty($secilen)) {
            return back()->with('error', 'Hiç acente seçilmedi.');
        }

        // Günlük limit kontrolü
        $tableExists     = \Illuminate\Support\Facades\Schema::hasTable('tursab_davetler');
        $bugunGonderilen = $tableExists ? TursabDavet::whereDate('created_at', today())->count() : 0;
        $kalanHak        = self::GUNLUK_LIMIT - $bugunGonderilen;

        if ($kalanHak <= 0) {
            return back()->with('error', 'Bugünkü ' . self::GUNLUK_LIMIT . ' email limitine ulaştınız. Yarın tekrar deneyin.');
        }

        // Limit kadar kes
        $secilen = array_slice($secilen, 0, $kalanHak);

        $sablonlar = ['emails.tursab_davet', 'emails.tursab_davet_yeni_acente'];
        $sablon = in_array($request->input('sablon'), $sablonlar)
            ? $request->input('sablon')
            : 'emails.tursab_davet';

        $basarili = 0;
        $basarisiz = 0;

        foreach ($secilen as $item) {
            [$eposta, $acenteAdi, $belgeNo, $il] = array_pad(explode('||', $item, 5), 4, '');

            if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) continue;
            // Non-ASCII local-part içeren adresler Laravel Mail tarafından reddedilir
            $localPart = explode('@', $eposta)[0] ?? '';
            if (!preg_match('/^[\x20-\x7E]+$/', $localPart)) continue;

            // Daha önce gönderildi mi?
            if ($tableExists && TursabDavet::whereRaw('LOWER(eposta) = ?', [strtolower($eposta)])->exists()) continue;

            try {
                $this->gonder($eposta, $acenteAdi, $belgeNo, $sablon);

                TursabDavet::create([
                    'belge_no'        => $belgeNo ?: null,
                    'eposta'          => $eposta,
                    'acente_unvani'   => $acenteAdi,
                    'il'              => $il ?: null,
                    'status'          => 'sent',
                    'gonderen_user_id'=> auth()->id(),
                ]);
                $basarili++;
            } catch (\Throwable $e) {
                TursabDavet::create([
                    'belge_no'        => $belgeNo ?: null,
                    'eposta'          => $eposta,
                    'acente_unvani'   => $acenteAdi,
                    'il'              => $il ?: null,
                    'status'          => 'failed',
                    'hata'            => $e->getMessage(),
                    'gonderen_user_id'=> auth()->id(),
                ]);
                $basarisiz++;
            }
        }

        $msg = "{$basarili} davet gönderildi.";
        if ($basarisiz) $msg .= " {$basarisiz} adet gönderilemedi.";

        return back()->with('success', $msg);
    }

    /**
     * Superadmin — Toplu SMS gönder (kampanya sayfasından).
     */
    public function topluSms(Request $request)
    {
        $this->assertSuperadmin();

        $secilen = $request->input('secilen', []);
        $mesaj   = trim($request->input('sms_mesaj', ''));

        if (empty($secilen)) {
            return back()->with('error', 'Hiç acente seçilmedi.');
        }
        if (!$mesaj) {
            return back()->with('error', 'SMS metni boş olamaz.');
        }
        if (mb_strlen($mesaj) > 160) {
            return back()->with('error', 'SMS metni 160 karakteri geçemez.');
        }

        $smsService = app(\App\Services\SmsService::class);
        $basarili   = 0;
        $atlandi    = 0;

        foreach ($secilen as $item) {
            [$eposta, $acenteAdi, $belgeNo, $il, $telefon] = array_pad(explode('||', $item, 5), 5, '');

            $telefon = $this->normalizeTelefon($telefon);
            if (!$telefon) { $atlandi++; continue; }

            try {
                $smsService->send(null, 'acente', $acenteAdi, $telefon, $mesaj);
                TursabDavet::create([
                    'belge_no'         => $belgeNo ?: null,
                    'eposta'           => $eposta  ?: null,
                    'acente_unvani'    => $acenteAdi,
                    'il'               => $il      ?: null,
                    'tip'              => 'sms',
                    'status'           => 'sent',
                    'gonderen_user_id' => auth()->id(),
                ]);
                $basarili++;
            } catch (\Throwable $e) {
                TursabDavet::create([
                    'belge_no'         => $belgeNo ?: null,
                    'eposta'           => $eposta  ?: null,
                    'acente_unvani'    => $acenteAdi,
                    'il'               => $il      ?: null,
                    'tip'              => 'sms',
                    'status'           => 'failed',
                    'hata'             => $e->getMessage(),
                    'gonderen_user_id' => auth()->id(),
                ]);
                $atlandi++;
            }
        }

        $msg = "{$basarili} acenteye SMS gönderildi.";
        if ($atlandi) $msg .= " {$atlandi} adet atlandı (telefon yok veya geçersiz format).";

        return back()->with('success', $msg);
    }

    /**
     * Türk cep numarasını normalize eder. Geçersizse '' döner.
     * Çıktı: 11 haneli 0XXXXXXXXXX formatı (toplusmsyolla için).
     */
    private function normalizeTelefon(string $telefon): string
    {
        $digits = preg_replace('/[^0-9]/', '', $telefon);
        if (!$digits) return '';

        // +905XXXXXXXXX (12 hane, +90 ile başlayan)
        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            return '0' . substr($digits, 2); // 0XXXXXXXXXX
        }
        // 05XXXXXXXXX (11 hane, 0 ile başlayan)
        if (strlen($digits) === 11 && str_starts_with($digits, '05')) {
            return $digits;
        }
        // 5XXXXXXXXX (10 hane)
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '0' . $digits;
        }

        return '';
    }

    /**
     * Superadmin — AI kişiselleştirme + email önizleme (JSON).
     */
    public function davetAiOnizle(Request $request)
    {
        $this->assertSuperadmin();

        $unvan  = trim($request->input('unvan', ''));
        $ticari = trim($request->input('ticari', ''));
        $il     = trim($request->input('il', ''));
        $ilce   = trim($request->input('ilce', ''));
        $eposta = trim($request->input('eposta', ''));
        $belge  = trim($request->input('belge', ''));
        $grup   = trim($request->input('grup', ''));
        $sablonlar = ['emails.tursab_davet', 'emails.tursab_davet_yeni_acente'];
        $sablon = in_array($request->input('sablon'), $sablonlar)
            ? $request->input('sablon')
            : 'emails.tursab_davet';

        // E-posta domain'inden marka ipucu
        $domain = '';
        if ($eposta && str_contains($eposta, '@')) {
            $domain = explode('@', $eposta)[1] ?? '';
            $domain = preg_replace('/\.(com|net|org|com\.tr|net\.tr|org\.tr|tr)$/i', '', $domain);
        }

        $paragraf = $this->aiKisiselParagraf($unvan, $ticari, $il, $ilce, $domain, $grup);

        $konu = $sablon === 'emails.tursab_davet_yeni_acente'
            ? 'Hayırlı Olsun! GrupTalepleri\'nden tebrikler 🎉'
            : 'GrupTalepleri.com — Platforma Davet';

        $html = \View::make($sablon, [
            'acenteUnvani'   => $unvan,
            'belgeNo'        => $belge,
            'kayitUrl'       => route('register'),
            'aiParagraf'     => $paragraf,
        ])->render();

        return response()->json([
            'success'  => true,
            'paragraf' => $paragraf,
            'konu'     => $konu,
            'html'     => $html,
        ]);
    }

    /**
     * Gemini ile acente için kişisel 2 cümle üretir. Hata varsa boş döner.
     */
    private function aiKisiselParagraf(string $unvan, string $ticari, string $il, string $ilce, string $domain, string $grup): string
    {
        $apiKey = (string) config('services.gemini.key', '');
        if (!$apiKey) return '';

        $model = (string) config('services.gemini.text_model', 'gemini-2.5-flash');

        $prompt = implode("\n", [
            'Sen bir B2B email uzmanısın. Aşağıdaki seyahat acentesi verisine bakarak TAM OLARAK 2 cümle yaz.',
            '',
            'Veri:',
            '- Acenta adı: ' . $unvan,
            '- Ticari unvan: ' . ($ticari ?: '-'),
            '- Şehir: ' . $il . ($ilce ? ' / ' . $ilce : ''),
            '- Email domain: ' . ($domain ?: '-'),
            '- TÜRSAB grubu: ' . ($grup ?: '-'),
            '',
            'KURALLAR (ihlal etme):',
            '1. TAM OLARAK 2 cümle yaz. Ne fazla ne eksik.',
            '2. Cümle 1: Acentanın adından, bulunduğu lokasyondan veya email domain\'inden somut bir gözlem.',
            '   Eğer addan anlam çıkaramıyorsan şehri kullan.',
            '3. Cümle 2: "GrupTalepleri\'nde talebinizi oluşturun, en iyi teklifler size gelsin." ile bitir. Bu cümleyi değiştirme.',
            '4. ASLA "düşünüyorum", "sanırım", "belki", "muhtemelen", "tahmin ediyorum" yazma.',
            '5. ASLA spekülatif veya yanlış bilgi üretme.',
            '6. Çıktı: Sadece düz metin. HTML yok, tırnak yok, madde işareti yok, açıklama yok.',
            '7. Eğer anlamlı kişiselleştirme yapamıyorsan sadece şunu yaz: DEFAULT',
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));
            if (!$text || strtoupper($text) === 'DEFAULT') return '';
            return $text;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Private — email gönderim ortak metod.
     */
    private function gonder(string $email, string $acenteAdi, string $belgeNo = '', string $sablon = 'emails.tursab_davet', string $aiParagraf = ''): void
    {
        // Non-ASCII karakter içeren email adresleri reddedilir (local-part kısmı)
        $localPart = explode('@', $email)[0] ?? '';
        if (!preg_match('/^[\x20-\x7E]+$/', $localPart)) {
            throw new \InvalidArgumentException("Geçersiz email adresi (ASCII dışı karakter): {$email}");
        }

        $konu = $sablon === 'emails.tursab_davet_yeni_acente'
            ? 'Hayırlı Olsun! GrupTalepleri\'nden tebrikler 🎉'
            : 'GrupTalepleri.com — Platforma Davet';

        $vars = ['acenteUnvani' => $acenteAdi, 'belgeNo' => $belgeNo, 'kayitUrl' => route('register'), 'aiParagraf' => $aiParagraf];

        Mail::send(
            $sablon,
            $vars,
            fn($mail) => $mail->to($email, $acenteAdi)->bcc('aydinyay@gmail.com')->subject($konu)
        );
    }

    /**
     * Superadmin — Scraper başlat (bir batch çalıştır), JSON döndür.
     */
    public function scrapeStart(Request $request)
    {
        $this->assertSuperadmin();

        $start  = $request->input('start');   // null = kaldığı yerden
        $end    = (int) ($request->input('end', 20000));
        $batch  = max(1, (int) ($request->input('batch', 50)));
        $beyond = $request->boolean('beyond');
        $reset  = $request->boolean('reset');

        $args = [
            '--end'   => (string) $end,
            '--batch' => (string) $batch,
        ];
        if ($start)  $args['--start']  = (string) $start;
        if ($beyond) $args['--beyond'] = true;
        if ($reset)  $args['--reset']  = true;

        \Artisan::call('tursab:scrape', $args);

        return $this->scrapeStatus();
    }

    /**
     * Superadmin — Bakanlık scraper başlat.
     */
    public function bakanlikScrapeStart(Request $request)
    {
        $this->assertSuperadmin();

        $batch = max(1, (int) ($request->input('batch', 20)));
        $start = $request->input('start');
        $end   = (int) ($request->input('end', 20000));
        $reset = $request->boolean('reset');

        $args = ['--batch' => (string) $batch, '--end' => (string) $end];
        if ($start) $args['--start'] = (string) $start;
        if ($reset) $args['--reset'] = true;

        \Artisan::call('bakanlik:scrape', $args);

        return $this->bakanlikScrapeStatus();
    }

    /**
     * Superadmin — Bakanlık scraper durum bilgisi.
     */
    public function bakanlikScrapeStatus()
    {
        $this->assertSuperadmin();

        $currentNo = (int)    \App\Models\SistemAyar::get('bakanlik_scrape_current_no', '1');
        $found     = (int)    \App\Models\SistemAyar::get('bakanlik_scrape_found',      '0');
        $status    = (string) \App\Models\SistemAyar::get('bakanlik_scrape_status',     'idle');
        $at        = (string) \App\Models\SistemAyar::get('bakanlik_scrape_at',         '');
        $endNo     = (int)    \App\Models\SistemAyar::get('bakanlik_scrape_end',        '20000');

        $total = \App\Models\Acenteler::where('kaynak', 'bakanlik')->count();
        $done  = ($currentNo > $endNo);

        return response()->json([
            'status'     => $status,
            'current_no' => $currentNo,
            'end_no'     => $endNo,
            'found'      => $found,
            'db_total'   => $total,
            'at'         => $at,
            'done'       => $done,
            'percent'    => $endNo > 0 ? round(($currentNo - 1) / $endNo * 100, 1) : 0,
        ]);
    }

    // ── Bakanlık Tam Senkronizasyon ──────────────────────────────────────────

    public function aceneSyncBaslat(Request $request)
    {
        $this->assertSuperadmin();

        $batch = max(1, (int) ($request->input('batch', 30)));
        $reset = $request->boolean('reset');
        $skip  = $request->boolean('skip_cleanup');

        $args = ['--batch' => (string) $batch, '--delay' => '300'];
        if ($reset) $args['--reset']        = true;
        if ($skip)  $args['--skip-cleanup'] = true;

        \Artisan::call('acenteler:sync', $args);

        return $this->aceneSyncStatus();
    }

    public function aceneSyncStatus()
    {
        $this->assertSuperadmin();

        $status    = (string) \App\Models\SistemAyar::get('acente_sync_status',     'idle');
        $gecis     = (int)    \App\Models\SistemAyar::get('acente_sync_gecis',      '1');
        $currentNo = (int)    \App\Models\SistemAyar::get('acente_sync_current_no', '1');
        $found     = (int)    \App\Models\SistemAyar::get('acente_sync_found',      '0');
        $at        = (string) \App\Models\SistemAyar::get('acente_sync_at',         '');
        $startedAt = (string) \App\Models\SistemAyar::get('acente_sync_started_at', '');
        $endNo     = 20000;

        // Genel ilerleme: Geçiş 1 = 0-50%, Geçiş 2 = 50-100%
        $gecisPercent = $endNo > 0 ? min(100, round(($currentNo - 1) / $endNo * 100, 1)) : 0;
        $totalPercent = $gecis === 1
            ? round($gecisPercent / 2, 1)
            : round(50 + $gecisPercent / 2, 1);

        $gecerli = \DB::table('acenteler')->where('kaynak', 'bakanlik')->where('durum', 'GEÇERLİ')->count();
        $iptal   = \DB::table('acenteler')->where('kaynak', 'bakanlik')->where('durum', 'İPTAL')->count();
        $tursab  = \DB::table('acenteler')->where('kaynak', 'tursab')->count();

        return response()->json([
            'status'        => $status,
            'gecis'         => $gecis,
            'gecis_label'   => $gecis === 1 ? 'GEÇERLİ taranıyor' : 'İPTAL taranıyor',
            'current_no'    => $currentNo,
            'end_no'        => $endNo,
            'found'         => $found,
            'percent'       => $totalPercent,
            'at'            => $at,
            'started_at'    => $startedAt,
            'done'          => $status === 'done',
            'gecerli_count' => $gecerli,
            'iptal_count'   => $iptal,
            'tursab_count'  => $tursab,
        ]);
    }

    /**
     * Superadmin — Scraper durum bilgisi.
     */
    public function scrapeStatus()
    {
        $this->assertSuperadmin();

        $lastNo  = (int)    \App\Models\SistemAyar::get('tursab_scrape_last_no', '0');
        $found   = (int)    \App\Models\SistemAyar::get('tursab_scrape_found',   '0');
        $status  = (string) \App\Models\SistemAyar::get('tursab_scrape_status',  'idle');
        $at      = (string) \App\Models\SistemAyar::get('tursab_scrape_at',      '');
        $endNo   = (int)    \App\Models\SistemAyar::get('tursab_scrape_end',     '18804');

        $total   = \App\Models\Acenteler::count();
        $done    = ($lastNo > 0 && $lastNo >= $endNo);

        return response()->json([
            'status'   => $status,
            'last_no'  => $lastNo,
            'end_no'   => $endNo,
            'found'    => $found,
            'db_total' => $total,
            'at'       => $at,
            'done'     => $done,
            'percent'  => $endNo > 0 ? round($lastNo / $endNo * 100, 1) : 0,
        ]);
    }

    /**
     * Superadmin — El ile acente ekle / güncelle.
     */
    public function manuelEkle(Request $request)
    {
        $this->assertSuperadmin();

        $data = $request->validate([
            'belge_no'      => 'required|string|max:20',
            'acente_unvani' => 'required|string|max:255',
            'telefon'       => 'nullable|string|max:50',
            'eposta'        => 'nullable|email|max:255',
            'il'            => 'nullable|string|max:100',
            'grup'          => 'nullable|string|max:50',
            'adres'         => 'nullable|string|max:500',
            'btk'           => 'nullable|string|max:20',
        ]);

        $payload = array_filter($data, fn($v) => $v !== null && $v !== '');
        $payload['kaynak'] = 'manuel';

        Acenteler::updateOrCreate(
            ['belge_no' => $data['belge_no'], 'kaynak' => 'manuel'],
            $payload
        );

        return back()->with('success', $data['belge_no'] . ' — ' . $data['acente_unvani'] . ' eklendi / güncellendi.');
    }

    // ── CSV Import (Web) ─────────────────────────────────────────────────────

    public function csvImportForm()
    {
        $this->assertSuperadmin();
        $toplam = Acenteler::count();
        return view('superadmin.kampanya-csv-import', compact('toplam'));
    }

    public function csvImportYukle(Request $request)
    {
        $this->assertSuperadmin();

        $request->validate([
            'csv_dosya' => 'required|file|max:102400',
        ]);

        $file = $request->file('csv_dosya');
        $path = storage_path('app/import/acenteler.csv');

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $file->move(dirname($path), 'acenteler.csv');
        $noTruncate = $request->boolean('no_truncate', false);
        $mod = $noTruncate ? 'no_truncate=1' : '';

        return back()->with('success',
            "✅ CSV dosyası sunucuya yüklendi.\n\n" .
            "Şimdi deploy-run.php üzerinden import'u başlatın:\n" .
            url('/deploy-run.php') . "?key=gtp2026deploy&action=csv-import" . ($mod ? "&{$mod}" : '')
        );
    }

    // ── Email Kampanya ────────────────────────────────────────────────────────

    public function emailKampanya(Request $request)
    {
        $this->assertSuperadmin();

        $il       = trim($request->input('il', ''));
        $ilce     = trim($request->input('ilce', ''));
        $grup     = trim($request->input('grup', ''));
        $q        = trim($request->input('q', ''));
        $sadeceDavetEdilmemis = $request->boolean('sadece_yeni', true);
        $perPage  = in_array((int) $request->input('per_page', 50), [25, 50, 100, 200])
                    ? (int) $request->input('per_page', 50) : 50;

        $tableExists     = \Illuminate\Support\Facades\Schema::hasTable('tursab_davetler');
        $bugunGonderilen = $tableExists ? TursabDavet::whereDate('created_at', today())->count() : 0;
        $kalanHak        = max(0, self::GUNLUK_LIMIT - $bugunGonderilen);

        $davetEdilenler = $tableExists
            ? TursabDavet::pluck('eposta')->map(fn($e) => strtolower($e))->toArray()
            : [];

        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')
                          ->distinct()->orderBy('il')->pluck('il');

        $query = Acenteler::whereNotNull('eposta')->where('eposta', '!=', '');

        if ($q)    $query->where(fn($w) => $w->where('acente_unvani','like',"%{$q}%")->orWhere('belge_no','like',"%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($ilce) $query->where('il_ilce', $ilce);
        if ($grup) $query->where('grup', $grup);

        if ($sadeceDavetEdilmemis && count($davetEdilenler)) {
            $placeholders = implode(',', array_fill(0, count($davetEdilenler), '?'));
            $query->whereRaw("LOWER(eposta) NOT IN ({$placeholders})", $davetEdilenler);
        }

        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta','telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')
                           ->paginate($perPage)->withQueryString();

        $gecmis = $tableExists
            ? TursabDavet::with('gonderen')->orderByDesc('created_at')->limit(100)->get()
            : collect();

        return view('superadmin.kampanya-email', compact(
            'acenteler','iller','bugunGonderilen','kalanHak',
            'gecmis','il','ilce','grup','q','sadeceDavetEdilmemis','perPage'
        ));
    }

    // ── SMS Kampanya ──────────────────────────────────────────────────────────

    public function smsKampanya(Request $request)
    {
        $this->assertSuperadmin();

        $il      = trim($request->input('il', ''));
        $ilce    = trim($request->input('ilce', ''));
        $grup    = trim($request->input('grup', ''));
        $q       = trim($request->input('q', ''));
        $sadeceCep = $request->boolean('sadece_cep', true);
        $perPage = in_array((int) $request->input('per_page', 50), [25, 50, 100, 200])
                   ? (int) $request->input('per_page', 50) : 50;

        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')
                          ->distinct()->orderBy('il')->pluck('il');

        $query = Acenteler::query();

        if ($q)    $query->where(fn($w) => $w->where('acente_unvani','like',"%{$q}%")->orWhere('belge_no','like',"%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($ilce) $query->where('il_ilce', $ilce);
        if ($grup) $query->where('grup', $grup);

        if ($sadeceCep) {
            $query->whereNotNull('telefon')->where('telefon', '!=', '')
                  ->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'");
        }

        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta','telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')
                           ->paginate($perPage)->withQueryString();

        return view('superadmin.kampanya-sms', compact(
            'acenteler','iller','il','ilce','grup','q','sadeceCep','perPage'
        ));
    }

    // ── Otomatik Zamanlama ────────────────────────────────────────────────────

    public function zamanlamaForm()
    {
        $this->assertSuperadmin();

        $emailAyar = $this->zamanlamaAyar('email');
        $smsAyar   = $this->zamanlamaAyar('sms');

        $emailLog = $this->zamanlamaLog('email');
        $smsLog   = $this->zamanlamaLog('sms');

        // Cron heartbeat — önce dosyadan, yoksa DB'den oku
        $heartbeatFile = storage_path('app/cron_heartbeat.txt');
        $cronHeartbeat = file_exists($heartbeatFile)
            ? trim(file_get_contents($heartbeatFile))
            : \App\Models\SistemAyar::get('cron_heartbeat');
        $cronAktif      = false;
        $cronSonCalisma = null;
        if ($cronHeartbeat) {
            $cronSonCalisma = \Carbon\Carbon::parse($cronHeartbeat)->timezone('Europe/Istanbul');
            $cronAktif = $cronSonCalisma->diffInMinutes(now()) <= 3;
        }

        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')
                          ->distinct()->orderBy('il')->pluck('il');

        return view('superadmin.kampanya-zamanlama', compact(
            'emailAyar', 'smsAyar', 'emailLog', 'smsLog', 'iller',
            'cronAktif', 'cronSonCalisma'
        ));
    }

    public function zamanlamaKaydet(Request $request)
    {
        $this->assertSuperadmin();

        $tip = $request->input('tip'); // 'email' veya 'sms'
        abort_unless(in_array($tip, ['email', 'sms']), 422);

        $slotSaatleri = $request->input('slot_saat', []);
        $slotAdetler  = $request->input('slot_adet', []);
        $slotAktifler = $request->input('slot_aktif', []);

        $slotlar = [];
        foreach ($slotSaatleri as $i => $saat) {
            if (!preg_match('/^\d{2}:\d{2}$/', $saat)) continue;
            $slotlar[] = [
                'saat'  => $saat,
                'adet'  => max(1, min(500, (int) ($slotAdetler[$i] ?? 50))),
                'aktif' => in_array((string)$i, array_keys($slotAktifler)),
            ];
        }

        $baslangic = $request->input('baslangic_tarihi', '');
        $bitis     = $request->input('bitis_tarihi', '');

        $ayar = [
            'aktif'            => $request->boolean('aktif'),
            'baslangic_tarihi' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $baslangic) ? $baslangic : '',
            'bitis_tarihi'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', $bitis)     ? $bitis     : '',
            'slotlar'          => $slotlar,
            'filtre'           => [
                'il'   => trim($request->input('filtre_il', '')),
                'ilce' => trim($request->input('filtre_ilce', '')),
                'grup' => trim($request->input('filtre_grup', '')),
            ],
        ];

        if ($tip === 'email') {
            $ayar['filtre']['sablon'] = in_array($request->input('filtre_sablon'), ['emails.tursab_davet', 'emails.tursab_davet_yeni_acente'])
                ? $request->input('filtre_sablon')
                : 'emails.tursab_davet';
            $ayar['filtre']['sadece_yeni'] = $request->boolean('filtre_sadece_yeni');
        }

        if ($tip === 'sms') {
            $mesaj = trim($request->input('sms_mesaj', ''));
            if (mb_strlen($mesaj) > 160) {
                return back()->with('error', 'SMS metni 160 karakteri geçemez.');
            }
            $ayar['mesaj'] = $mesaj;
        }

        $key = $tip === 'email' ? 'kampanya_email_zamanlama' : 'kampanya_sms_zamanlama';
        \App\Models\SistemAyar::set($key, json_encode($ayar, JSON_UNESCAPED_UNICODE));

        return back()->with('success', ($tip === 'email' ? 'Email' : 'SMS') . ' kampanya zamanlaması kaydedildi.');
    }

    public function zamanlamaTestGonder(Request $request)
    {
        $this->assertSuperadmin();

        $tip = $request->input('tip', 'email');
        abort_unless(in_array($tip, ['email', 'sms']), 422);

        $command = $tip === 'email' ? 'kampanya:email-otomatik' : 'kampanya:sms-otomatik';
        $dryRun  = $request->boolean('dry_run', true);

        $args = ['--force' => true];
        if ($dryRun) $args['--dry-run'] = true;

        \Artisan::call($command, $args);
        $out = trim(\Artisan::output());

        return response()->json(['success' => true, 'output' => $out ?: 'Komut tamamlandı (çıktı yok).']);
    }

    public function slotSil(Request $request)
    {
        $this->assertSuperadmin();
        $tip  = $request->input('tip');
        $saat = $request->input('saat');
        abort_unless(in_array($tip, ['email', 'sms']) && preg_match('/^\d{2}:\d{2}$/', $saat), 422);

        $key  = $tip === 'email' ? 'kampanya_email_zamanlama' : 'kampanya_sms_zamanlama';
        $ayar = json_decode(\App\Models\SistemAyar::get($key, '{}'), true) ?? [];
        $ayar['slotlar'] = array_values(array_filter(
            $ayar['slotlar'] ?? [],
            fn($s) => ($s['saat'] ?? '') !== $saat
        ));
        \App\Models\SistemAyar::set($key, json_encode($ayar, JSON_UNESCAPED_UNICODE));

        return back()->with('success', "$saat slotu silindi.");
    }

    private function zamanlamaAyar(string $tip): array
    {
        $key = $tip === 'email' ? 'kampanya_email_zamanlama' : 'kampanya_sms_zamanlama';
        $json = \App\Models\SistemAyar::get($key, '');
        if (!$json) {
            return [
                'aktif'  => false,
                'slotlar' => [['saat' => '09:00', 'adet' => 50, 'aktif' => false]],
                'filtre' => ['il' => '', 'ilce' => '', 'grup' => '', 'sablon' => 'emails.tursab_davet'],
                'mesaj'  => '',
            ];
        }
        $a = json_decode($json, true) ?? [];
        if (!isset($a['slotlar']) || empty($a['slotlar'])) {
            $a['slotlar'] = [['saat' => '09:00', 'adet' => 50, 'aktif' => false]];
        }
        return $a;
    }

    private function zamanlamaLog(string $tip): array
    {
        $key  = $tip === 'email' ? 'kampanya_email_calisma_log' : 'kampanya_sms_calisma_log';
        $json = \App\Models\SistemAyar::get($key, '{}');
        return json_decode($json, true) ?? [];
    }

    // ── AJAX: İlçe Listesi ───────────────────────────────────────────────────

    public function ilceler(Request $request)
    {
        $this->assertSuperadmin();

        $il = trim($request->input('il', ''));
        if (!$il) {
            return response()->json([]);
        }

        $ilceler = Acenteler::where('il', $il)
                            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
                            ->distinct()->orderBy('il_ilce')->pluck('il_ilce');

        return response()->json($ilceler);
    }

    // ── Acente Listesi (Tümü) ────────────────────────────────────────────────

    public function acenteListesi(Request $request)
    {
        $this->assertSuperadmin();

        $il      = trim($request->input('il', ''));
        $ilce    = trim($request->input('ilce', ''));
        $grup    = trim($request->input('grup', ''));
        $q       = trim($request->input('q', ''));
        $perPage = in_array((int) $request->input('per_page', 50), [25, 50, 100, 200])
                   ? (int) $request->input('per_page', 50) : 50;

        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('tursab_davetler');

        // Davet edilmiş e-postalar (email ve sms ayrı)
        $emailDavetler = $tableExists
            ? TursabDavet::where('tip', 'email')->pluck('eposta')->map(fn($e) => strtolower(trim($e)))->flip()->all()
            : [];
        $smsDavetler = $tableExists
            ? TursabDavet::where('tip', 'sms')->pluck('eposta')->map(fn($e) => strtolower(trim($e)))->flip()->all()
            : [];

        $iller = Acenteler::whereNotNull('il')->where('il', '!=', '')
                          ->distinct()->orderBy('il')->pluck('il');

        $query = Acenteler::query();

        if ($q)    $query->where(fn($w) => $w->where('acente_unvani', 'like', "%{$q}%")->orWhere('belge_no', 'like', "%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($ilce) $query->where('il_ilce', $ilce);
        if ($grup) $query->where('grup', $grup);

        $acenteler = $query->select('id', 'belge_no', 'acente_unvani', 'grup', 'il', 'il_ilce', 'eposta', 'telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')
                           ->paginate($perPage)->withQueryString();

        return view('superadmin.acente-listesi', compact(
            'acenteler', 'iller', 'il', 'ilce', 'grup', 'q', 'perPage',
            'emailDavetler', 'smsDavetler'
        ));
    }

    private function assertSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }
}
