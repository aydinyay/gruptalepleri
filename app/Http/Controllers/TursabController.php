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

        $email     = $request->input('eposta');
        $acenteAdi = $request->input('acente_unvani');
        $belgeNo   = $request->input('belge_no', '');

        try {
            $this->gonder($email, $acenteAdi, $belgeNo);
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
        $il   = trim($request->input('il', ''));
        $grup = trim($request->input('grup', ''));
        $q    = trim($request->input('q', ''));
        $sadeceDavetEdilmemis = $request->boolean('sadece_yeni', true);

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

        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')
                           ->paginate(100)->withQueryString();

        // Davet geçmişi (son 200)
        $gecmis = $tableExists
            ? TursabDavet::with('gonderen')->orderByDesc('created_at')->limit(200)->get()
            : collect();

        return view('superadmin.tursab-kampanya', compact(
            'acenteler', 'iller', 'bugunGonderilen', 'kalanHak',
            'gecmis', 'il', 'grup', 'q', 'sadeceDavetEdilmemis'
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

        $basarili = 0;
        $basarisiz = 0;

        foreach ($secilen as $item) {
            [$eposta, $acenteAdi, $belgeNo, $il] = array_pad(explode('||', $item, 4), 4, '');

            if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) continue;

            // Daha önce gönderildi mi?
            if ($tableExists && TursabDavet::whereRaw('LOWER(eposta) = ?', [strtolower($eposta)])->exists()) continue;

            try {
                $this->gonder($eposta, $acenteAdi, $belgeNo);

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
     * Private — email gönderim ortak metod.
     */
    private function gonder(string $email, string $acenteAdi, string $belgeNo = ''): void
    {
        Mail::send(
            'emails.tursab_davet',
            ['acenteUnvani' => $acenteAdi, 'belgeNo' => $belgeNo, 'kayitUrl' => route('register')],
            fn($mail) => $mail->to($email, $acenteAdi)->subject('GrupTalepleri.com — Platforma Davet')
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
        $reset = $request->boolean('reset');

        $args = ['--batch' => (string) $batch];
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
        $endNo     = (int)    \App\Models\SistemAyar::get('bakanlik_scrape_end',        '18804');

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

    private function assertSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }
}
