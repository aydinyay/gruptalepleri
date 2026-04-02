<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcenetelIstatistikController extends Controller
{
    // Türkiye 2023 nüfus (TÜİK)
    private const TURKIYE_NUFUS = 85_279_553;

    // 7 coğrafi bölge → il eşlemesi
    private const BOLGELER = [
        'Marmara'        => ['İstanbul','Tekirdağ','Edirne','Kırklareli','Çanakkale','Balıkesir','Bursa','Kocaeli','Sakarya','Düzce','Bolu','Yalova'],
        'Ege'            => ['İzmir','Manisa','Afyonkarahisar','Kütahya','Uşak','Denizli','Aydın','Muğla'],
        'Akdeniz'        => ['Antalya','Isparta','Burdur','Mersin','Adana','Hatay','Kahramanmaraş','Osmaniye'],
        'İç Anadolu'     => ['Ankara','Konya','Eskişehir','Sivas','Yozgat','Kayseri','Aksaray','Niğde','Nevşehir','Kırıkkale','Kırşehir','Çankırı'],
        'Karadeniz'      => ['Zonguldak','Karabük','Bartın','Kastamonu','Çorum','Sinop','Samsun','Amasya','Tokat','Ordu','Giresun','Trabzon','Rize','Artvin','Gümüşhane','Bayburt'],
        'Doğu Anadolu'   => ['Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır','Van','Bitlis','Muş','Bingöl','Tunceli','Elazığ','Malatya'],
        'Güneydoğu'      => ['Diyarbakır','Şanlıurfa','Mardin','Batman','Siirt','Şırnak','Hakkari','Gaziantep','Kilis','Adıyaman'],
    ];

    // Önemli turizm destinasyonları
    private const DESTINASYONLAR = [
        'Antalya','Muğla','İstanbul','İzmir','Nevşehir','Trabzon',
        'Bursa','Ankara','Mersin','Adana','Edirne','Samsun',
    ];

    // 81 il canonical kontrolü — mb_strtolower ile Turkish-safe eşleşme
    private static function canonicalIl(string $ilRaw): ?string
    {
        static $tum = null;
        if ($tum === null) {
            $tum = array_merge(...array_values(self::BOLGELER));
            $tum = array_merge($tum, ['Bilecik', 'Karaman']);
        }
        $aranan = mb_strtolower(trim($ilRaw), 'UTF-8');
        foreach ($tum as $il) {
            if (mb_strtolower($il, 'UTF-8') === $aranan) return $il;
        }
        return null;
    }

    // İlçe adını normalize et: "IL - ILCE" → "ILCE", "ILCE / IL" → "ILCE"
    private static function normalizeIlce(string $ilce): string
    {
        $ilce = trim($ilce);
        if (str_contains($ilce, ' - ')) {
            $parts = explode(' - ', $ilce, 2);
            $after = trim($parts[1]);
            return $after !== '' ? $after : trim($parts[0]);
        }
        if (str_contains($ilce, ' / ')) {
            return trim(explode(' / ', $ilce, 2)[0]);
        }
        return $ilce;
    }

    // Koleksiyon üzerinde ilçe adlarını normalize edip yeniden aggregate et
    // $rows: [{il_ilce, toplam, ...}], $key: gruplama anahtarı(ları)
    private static function reAggregateIlce($rows, array $extraKeys = []): \Illuminate\Support\Collection
    {
        $map = [];
        foreach ($rows as $row) {
            $norm = self::normalizeIlce($row->il_ilce ?? '');
            $groupKey = $norm;
            foreach ($extraKeys as $k) $groupKey .= '|' . ($row->$k ?? '');

            if (!isset($map[$groupKey])) {
                $map[$groupKey] = clone $row;
                $map[$groupKey]->il_ilce = $norm;
            } else {
                $map[$groupKey]->toplam += $row->toplam;
            }
        }
        return collect(array_values($map));
    }

    public function normalizeKaynak(string $mode)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
        abort_unless(in_array($mode, ['dry-run', 'run']), 404);
        $dryRun = $mode === 'dry-run';
        Artisan::call('acenteler:normalize-kaynak', $dryRun ? ['--dry-run' => true] : []);
        $output = Artisan::output();
        return response('<pre style="font-family:monospace;padding:20px;background:#1a1a2e;color:#0f0;font-size:14px;">' . htmlspecialchars($output) . '</pre>');
    }

    public function normalize(\Illuminate\Http\Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
        $dryRun = $request->query('mode', 'dry') !== 'run';
        $log = [$dryRun ? '🔍 DRY-RUN — değişiklik yok' : '✅ UYGULANIYOR...', ''];

        $log[] = '─── Mevcut dağılım ───';
        foreach (DB::table('acenteler')->selectRaw("COALESCE(kaynak,'NULL') as k, COUNT(*) as t")->groupBy('k')->orderByDesc('t')->get() as $r) {
            $log[] = "  {$r->k}: {$r->t}";
        }
        $log[] = '';

        $rules = [
            'tursab'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'",
            'bakanlik' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'",
            'manuel'   => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'",
        ];
        $log[] = '─── kaynak normalize ───';
        foreach ($rules as $val => $cond) {
            $n = DB::table('acenteler')->whereRaw($cond)->where('kaynak', '!=', $val)->count();
            $log[] = "  '{$val}' → {$n} kayıt";
            if (!$dryRun && $n > 0) DB::table('acenteler')->whereRaw($cond)->where('kaynak', '!=', $val)->update(['kaynak' => $val]);
        }

        $log[] = '';
        $log[] = '─── is_sube normalize ───';
        $n = DB::table('acenteler')->where('is_sube', 0)->where(fn($q) => $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'"))->count();
        $log[] = "  is_sube=0 ama adında ŞUBE geçen: {$n}";
        if (!$dryRun && $n > 0) DB::table('acenteler')->where('is_sube', 0)->where(fn($q) => $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'"))->update(['is_sube' => 1]);

        if (!$dryRun) {
            $log[] = ''; $log[] = '─── Sonuç ───';
            foreach (DB::table('acenteler')->selectRaw("COALESCE(kaynak,'NULL') as k, COUNT(*) as t")->groupBy('k')->orderByDesc('t')->get() as $r) {
                $log[] = "  {$r->k}: {$r->t}";
            }
            $log[] = '  is_sube=1: ' . DB::table('acenteler')->where('is_sube', 1)->count();
        }

        $btn = $dryRun ? '<br><a href="?mode=run" style="background:#e94560;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-family:monospace;">▶ Uygulamak için tıkla</a>' : '';
        return response('<pre style="background:#1a1a2e;color:#0f0;padding:20px;font-size:14px;margin:0;">' . htmlspecialchars(implode("\n", $log)) . '</pre>' . $btn);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        // ── GEÇİCİ: Normalizasyon (?normalize=dry veya ?normalize=run) ───────
        if ($request->has('normalize')) {
            $dryRun = $request->query('normalize') !== 'run';
            $log = [$dryRun ? '🔍 DRY-RUN' : '✅ UYGULANIYOR', ''];
            $log[] = '─── Mevcut dağılım ───';
            foreach (DB::table('acenteler')->selectRaw("COALESCE(kaynak,'NULL') as k, COUNT(*) as t")->groupBy('k')->orderByDesc('t')->get() as $r) {
                $log[] = "  {$r->k}: {$r->t}";
            }
            $log[] = '';
            $rules = ['tursab' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%tursab%'", 'bakanlik' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%bakanl%'", 'manuel' => "LOWER(CONVERT(kaynak USING utf8mb4)) LIKE '%manuel%'"];
            foreach ($rules as $val => $cond) {
                $n = DB::table('acenteler')->whereRaw($cond)->where('kaynak', '!=', $val)->count();
                $log[] = "  '{$val}' → {$n} kayıt";
                if (!$dryRun && $n > 0) DB::table('acenteler')->whereRaw($cond)->where('kaynak', '!=', $val)->update(['kaynak' => $val]);
            }
            $log[] = '';
            $n = DB::table('acenteler')->where('is_sube', 0)->where(fn($q) => $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'"))->count();
            $log[] = "  is_sube=0 ama ŞUBE geçen: {$n}";
            if (!$dryRun && $n > 0) DB::table('acenteler')->where('is_sube', 0)->where(fn($q) => $q->whereRaw("UPPER(acente_unvani) LIKE '%ŞUBE%'")->orWhereRaw("UPPER(acente_unvani) LIKE '%SUBE%'"))->update(['is_sube' => 1]);
            if (!$dryRun) {
                $log[] = ''; $log[] = '─── Sonuç ───';
                foreach (DB::table('acenteler')->selectRaw("COALESCE(kaynak,'NULL') as k, COUNT(*) as t")->groupBy('k')->orderByDesc('t')->get() as $r) { $log[] = "  {$r->k}: {$r->t}"; }
                $log[] = '  is_sube=1: ' . DB::table('acenteler')->where('is_sube', 1)->count();
            }
            $btn = $dryRun ? '<br><br><a href="?normalize=run" style="background:#e94560;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-size:16px;">▶ UYGULA</a>' : '';
            return response('<pre style="background:#1a1a2e;color:#0f0;padding:24px;font-size:14px;margin:0;min-height:100vh;">' . htmlspecialchars(implode("\n", $log)) . '</pre>' . $btn);
        }

        // ── GENEL BAKIŞ ──────────────────────────────────────────────────────
        $toplam      = DB::table('acenteler')->count();
        $epostaVar   = DB::table('acenteler')->whereNotNull('eposta')->where('eposta', '!=', '')->count();
        $telefonVar  = DB::table('acenteler')->whereNotNull('telefon')->where('telefon', '!=', '')->count();
        $subeCount   = DB::table('acenteler')->where('is_sube', 1)->count();
        $anaMerkez   = $toplam - $subeCount;

        // ── İL DAĞILIMI — PHP'de normalize + re-aggregate ───────────────────
        $ilHam = DB::table('acenteler')
            ->selectRaw('il, COUNT(*) as toplam')
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')
            ->get();

        $ilMap = [];
        $ilVeriSorunu = 0; // canonical olmayan il değeri olan kayıt sayısı
        foreach ($ilHam as $row) {
            $canonical = self::canonicalIl($row->il);
            if ($canonical !== null) {
                $ilMap[$canonical] = ($ilMap[$canonical] ?? 0) + (int) $row->toplam;
            } else {
                $ilVeriSorunu += (int) $row->toplam;
            }
        }
        arsort($ilMap);

        $ilDagilimTumu = collect(array_map(
            fn ($il, $sayi) => (object) ['il' => $il, 'toplam' => $sayi],
            array_keys($ilMap),
            array_values($ilMap)
        ));

        $ilDagilim = $ilDagilimTumu->take(20);

        // En az acenteli iller — artık sadece gerçek iller (BODRUM / FATİH gibi değerler hariç)
        $enAzIller = $ilDagilimTumu->sortBy('toplam')->values()->take(15);

        // ── BÖLGE DAĞILIMI — normalize edilmiş $ilMap kullan ─────────────────
        $bolgeVerisi = [];
        foreach (self::BOLGELER as $bolge => $iller) {
            $sayi = array_sum(array_map(fn ($il) => $ilMap[$il] ?? 0, $iller));
            $bolgeVerisi[] = ['bolge' => $bolge, 'toplam' => $sayi, 'il_sayisi' => count($iller)];
        }
        usort($bolgeVerisi, fn ($a, $b) => $b['toplam'] - $a['toplam']);

        // ── DESTİNASYON ANALİZİ — normalize edilmiş $ilMap kullan ──────────
        $destinasyonlar = collect(self::DESTINASYONLAR)
            ->map(fn ($il) => (object) ['il' => $il, 'toplam' => $ilMap[$il] ?? 0])
            ->filter(fn ($r) => $r->toplam > 0)
            ->sortByDesc('toplam')
            ->values();

        // ── BÜYÜK 3 vs DİĞER — normalize edilmiş $ilMap kullan ──────────────
        $buyuk3Iller  = ['İstanbul', 'Ankara', 'İzmir'];
        $buyuk3Toplam = array_sum(array_map(fn ($il) => $ilMap[$il] ?? 0, $buyuk3Iller));
        $digerIlToplam = $toplam - $buyuk3Toplam;

        // ── BAKANLIK KAYIT DURUMU ─────────────────────────────────────────────
        $durumDagilim = DB::table('acenteler')
            ->selectRaw("COALESCE(NULLIF(durum,''),'BELİRTİLMEMİŞ') as durum, COUNT(*) as toplam")
            ->groupBy('durum')
            ->orderByDesc('toplam')
            ->get();

        // ── CEP TELEFONU SAYISI ───────────────────────────────────────────────
        $cepVar = DB::table('acenteler')
            ->whereNotNull('telefon')->where('telefon', '!=', '')
            ->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'")
            ->count();

        // ── GRUP DAĞILIMI (Bakanlık) ─────────────────────────────────────────
        $grupDagilim = DB::table('acenteler')
            ->selectRaw("
                COALESCE(NULLIF(grup,''), 'Belirtilmemiş') as grup,
                COUNT(*) as toplam,
                SUM(eposta IS NOT NULL AND eposta != '') as eposta_var
            ")
            ->groupBy('grup')
            ->orderByDesc('toplam')
            ->get();

        // ── İLÇE DAĞILIMI (Top 15) — normalize edilmiş ───────────────────────
        $ilceDagilimRaw = DB::table('acenteler')
            ->selectRaw('il_ilce, il, COUNT(*) as toplam')
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il_ilce', 'il')
            ->get();
        $ilceDagilim = self::reAggregateIlce($ilceDagilimRaw, ['il'])
            ->sortByDesc('toplam')->take(15)->values();

        // ── TOP 5 İL → İLÇE DRILLDOWN — normalize edilmiş ───────────────────
        $top5Iller = $ilDagilim->take(5)->pluck('il');
        $ilceDrilldownRaw = DB::table('acenteler')
            ->selectRaw('il, il_ilce, COUNT(*) as toplam')
            ->whereIn('il', $top5Iller)
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il', 'il_ilce')
            ->get();
        $ilceDrilldown = self::reAggregateIlce($ilceDrilldownRaw, ['il'])
            ->groupBy('il')
            ->map(fn($g) => $g->sortByDesc('toplam')->values());

        // ── DAVET İSTATİSTİĞİ ─────────────────────────────────────────────────
        $davetTableExists = Schema::hasTable('tursab_davetler');
        $davetBasarili = $davetTableExists ? DB::table('tursab_davetler')->where('status', 'sent')->count() : 0;

        // ── BELGE NO HİSTOGRAM ────────────────────────────────────────────────
        $belgeNoHistogram = DB::table('acenteler')
            ->selectRaw("FLOOR(CAST(belge_no AS UNSIGNED) / 1000) * 1000 as aralik, COUNT(*) as toplam")
            ->whereRaw("belge_no REGEXP '^[0-9]+$'")->where('belge_no', '!=', '0')
            ->groupBy('aralik')->orderBy('aralik')->get();

        // ── ŞUBE DAĞILIMI ─────────────────────────────────────────────────────
        $subeDagilim = DB::table('acenteler')
            ->selectRaw('sube_sira, COUNT(*) as toplam')
            ->groupBy('sube_sira')->orderBy('sube_sira')->limit(10)->get();

        // ── EN ÇOK ŞUBELİ ACENTELER ──────────────────────────────────────────
        $enCokSubeliAcenteler = DB::table('acenteler')
            ->selectRaw("
                belge_no,
                MAX(CASE WHEN is_sube = 0 THEN acente_unvani ELSE NULL END) as unvan,
                MAX(CASE WHEN is_sube = 0 THEN il ELSE NULL END) as il,
                SUM(CASE WHEN is_sube = 1 THEN 1 ELSE 0 END) as sube_sayisi
            ")
            ->whereNotNull('belge_no')->where('belge_no', '!=', '')->where('belge_no', '!=', '0')
            ->whereRaw("belge_no REGEXP '^[0-9]+$'")
            ->groupBy('belge_no')
            ->having('sube_sayisi', '>', 0)
            ->orderByDesc('sube_sayisi')
            ->limit(15)
            ->get();

        // ── İL BAZLI ŞUBE YOĞUNLUĞU ──────────────────────────────────────────
        $ilSubeYogunluk = DB::table('acenteler')
            ->selectRaw('il, COUNT(*) as toplam_sube')
            ->where('is_sube', 1)
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')
            ->orderByDesc('toplam_sube')
            ->limit(15)
            ->get();

        // ── İLÇE BAZLI ŞUBE YOĞUNLUĞU — normalize edilmiş ──────────────────
        $ilceSubeYogunlukRaw = DB::table('acenteler')
            ->selectRaw('il, il_ilce, COUNT(*) as toplam_sube')
            ->where('is_sube', 1)
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il', 'il_ilce')
            ->get()
            ->map(function ($r) { $r->toplam = $r->toplam_sube; return $r; });
        $ilceSubeYogunluk = self::reAggregateIlce($ilceSubeYogunlukRaw, ['il'])
            ->map(function ($r) { $r->toplam_sube = $r->toplam; return $r; })
            ->sortByDesc('toplam_sube')->take(15)->values();

        // ── KAYNAK DAĞILIMI (Veri sekmesi için) ──────────────────────────────
        $kaynaklar = DB::table('acenteler')
            ->selectRaw("
                COALESCE(kaynak, 'bilinmiyor') as kaynak,
                COUNT(*) as toplam,
                SUM(eposta  IS NOT NULL AND eposta  != '') as eposta_var,
                SUM(telefon IS NOT NULL AND telefon != '') as telefon_var,
                SUM(adres   IS NOT NULL AND adres   != '') as adres_var,
                SUM(il      IS NOT NULL AND il      != '') as il_var
            ")
            ->groupBy('kaynak')->orderByDesc('toplam')->get();

        $veriKalitesi = DB::table('acenteler')
            ->selectRaw("
                COALESCE(kaynak, 'bilinmiyor') as kaynak,
                COUNT(*) as toplam,
                ROUND(SUM(eposta  IS NOT NULL AND eposta  != '') / COUNT(*) * 100, 1) as eposta_pct,
                ROUND(SUM(telefon IS NOT NULL AND telefon != '') / COUNT(*) * 100, 1) as telefon_pct,
                ROUND(SUM(adres   IS NOT NULL AND adres   != '') / COUNT(*) * 100, 1) as adres_pct,
                ROUND(SUM(il      IS NOT NULL AND il      != '') / COUNT(*) * 100, 1) as il_pct,
                ROUND(SUM(grup    IS NOT NULL AND grup    != '') / COUNT(*) * 100, 1) as grup_pct,
                ROUND(SUM(ticari_unvan IS NOT NULL AND ticari_unvan != '') / COUNT(*) * 100, 1) as ticari_pct
            ")
            ->groupBy('kaynak')->get();

        $ilEpostaOran = DB::table('acenteler')
            ->selectRaw("
                il, COUNT(*) as toplam,
                SUM(eposta IS NOT NULL AND eposta != '') as eposta_var,
                ROUND(SUM(eposta IS NOT NULL AND eposta != '') / COUNT(*) * 100, 1) as oran
            ")
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')->having('toplam', '>=', 10)
            ->orderByDesc('oran')->limit(20)->get();

        // ── NÜFUS BAŞINA ACENTE ───────────────────────────────────────────────
        $nufusBasinaAcente = $toplam > 0 ? round(self::TURKIYE_NUFUS / $toplam) : 0;

        return view('superadmin.acenteler-istatistik', compact(
            'toplam', 'epostaVar', 'telefonVar', 'subeCount', 'anaMerkez',
            'kaynaklar', 'veriKalitesi', 'ilEpostaOran',
            'ilDagilim', 'ilDagilimTumu', 'enAzIller', 'ilVeriSorunu',
            'buyuk3Toplam', 'digerIlToplam',
            'bolgeVerisi',
            'destinasyonlar',
            'grupDagilim',
            'ilceDagilim', 'ilceDrilldown', 'top5Iller',
            'belgeNoHistogram', 'subeDagilim',
            'enCokSubeliAcenteler', 'ilSubeYogunluk', 'ilceSubeYogunluk',
            'davetBasarili',
            'nufusBasinaAcente',
            'durumDagilim', 'cepVar'
        ));
    }

    // ── Tanısal: veri kalitesi / yineleme raporu ─────────────────────────────
    public function tani(): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        // 1. Kaynak bazında satır sayısı
        $kaynakBazinda = DB::table('acenteler')
            ->selectRaw('COALESCE(kaynak, "bilinmiyor") as kaynak, COUNT(*) as adet')
            ->groupBy('kaynak')
            ->orderByDesc('adet')
            ->get();

        // 2. Merkez vs Şube
        $merkez = DB::table('acenteler')->where('is_sube', 0)->count();
        $sube   = DB::table('acenteler')->where('is_sube', 1)->count();
        $toplam = $merkez + $sube;

        // 3. Gerçek benzersiz acenta (merkez, distinct belge_no)
        $benzersiz = DB::table('acenteler')
            ->where('is_sube', 0)
            ->whereNotNull('belge_no')
            ->where('belge_no', '!=', '')
            ->distinct('belge_no')
            ->count('belge_no');

        // 4. Çapraz yineleme: aynı belge_no'da hem tursab hem bakanlik
        $caprazYineleme = DB::table(DB::raw('(
            SELECT belge_no FROM acenteler
            WHERE belge_no IS NOT NULL AND belge_no != ""
            GROUP BY belge_no
            HAVING COUNT(DISTINCT kaynak) > 1
        ) t'))->count();

        // 5. Sadece tursab'da olanlar (bakanlik'ta yok)
        $sadeceTursab = DB::table('acenteler')
            ->where('kaynak', 'tursab')
            ->where('is_sube', 0)
            ->whereNotIn('belge_no', function ($q) {
                $q->select('belge_no')->from('acenteler')->where('kaynak', 'bakanlik');
            })
            ->count();

        // 6. Sadece bakanlik'ta olanlar (tursab'da yok)
        $sadeceBakanlik = DB::table('acenteler')
            ->where('kaynak', 'bakanlik')
            ->whereNotIn('belge_no', function ($q) {
                $q->select('belge_no')->from('acenteler')->where('kaynak', 'tursab');
            })
            ->count();

        return response()->json([
            'toplam_satir'       => $toplam,
            'merkez'             => $merkez,
            'sube'               => $sube,
            'benzersiz_acenta'   => $benzersiz,
            'capraz_yineleme'    => $caprazYineleme,
            'sadece_tursab'      => $sadeceTursab,
            'sadece_bakanlik'    => $sadeceBakanlik,
            'her_ikisinde'       => $caprazYineleme,
            'kaynak_dagilimi'    => $kaynakBazinda,
            'yorum'              => [
                'neden_36k'      => "Aynı acenta hem 'tursab' hem 'bakanlik' kaydıyla {$caprazYineleme} kez yineleniyor. Şubeler ({$sube} adet) de ayrı satır.",
                'gercek_sayi'    => "Benzersiz merkez acenta: {$benzersiz} (Bakanlık ~18.804 ile karşılaştır)",
            ],
        ]);
    }
}
