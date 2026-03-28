<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcenetelIstatistikController extends Controller
{
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        // ── GENEL BAKIŞ ──────────────────────────────────────────────────────
        $toplam      = DB::table('acenteler')->count();
        $epostaVar   = DB::table('acenteler')->whereNotNull('eposta')->where('eposta', '!=', '')->count();
        $telefonVar  = DB::table('acenteler')->whereNotNull('telefon')->where('telefon', '!=', '')->count();
        $subeCount   = DB::table('acenteler')->where('is_sube', 1)->count();
        $anaMerkez   = $toplam - $subeCount;

        // ── KAYNAK DAĞILIMI ───────────────────────────────────────────────────
        $kaynaklar = DB::table('acenteler')
            ->selectRaw("
                COALESCE(kaynak, 'bilinmiyor') as kaynak,
                COUNT(*) as toplam,
                SUM(eposta  IS NOT NULL AND eposta  != '') as eposta_var,
                SUM(telefon IS NOT NULL AND telefon != '') as telefon_var,
                SUM(adres   IS NOT NULL AND adres   != '') as adres_var,
                SUM(il      IS NOT NULL AND il      != '') as il_var
            ")
            ->groupBy('kaynak')
            ->orderByDesc('toplam')
            ->get();

        // ── İL DAĞILIMI (Top 20) ─────────────────────────────────────────────
        $ilDagilim = DB::table('acenteler')
            ->selectRaw("
                il,
                COUNT(*) as toplam,
                SUM(eposta IS NOT NULL AND eposta != '') as eposta_var
            ")
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')
            ->orderByDesc('toplam')
            ->limit(20)
            ->get();

        // Büyük 3 vs diğerleri
        $buyuk3Iller = ['İstanbul', 'Ankara', 'İzmir'];
        $buyuk3Toplam = DB::table('acenteler')->whereIn('il', $buyuk3Iller)->count();
        $digerIlToplam = $toplam - $buyuk3Toplam;

        // ── GRUP DAĞILIMI (TÜRSAB) ───────────────────────────────────────────
        $grupDagilim = DB::table('acenteler')
            ->selectRaw("
                COALESCE(NULLIF(grup,''), 'Belirtilmemiş') as grup,
                COUNT(*) as toplam,
                SUM(eposta IS NOT NULL AND eposta != '') as eposta_var
            ")
            ->where('kaynak', 'tursab')
            ->groupBy('grup')
            ->orderByDesc('toplam')
            ->get();

        // ── İL BAZINDA E-POSTA ORANI (Top 20) ───────────────────────────────
        $ilEpostaOran = DB::table('acenteler')
            ->selectRaw("
                il,
                COUNT(*) as toplam,
                SUM(eposta IS NOT NULL AND eposta != '') as eposta_var,
                ROUND(SUM(eposta IS NOT NULL AND eposta != '') / COUNT(*) * 100, 1) as oran
            ")
            ->whereNotNull('il')->where('il', '!=', '')
            ->groupBy('il')
            ->having('toplam', '>=', 10)
            ->orderByDesc('oran')
            ->limit(20)
            ->get();

        // ── İLÇE DAĞILIMI (Top 15) ───────────────────────────────────────────
        $ilceDagilim = DB::table('acenteler')
            ->selectRaw('il_ilce, il, COUNT(*) as toplam')
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il_ilce', 'il')
            ->orderByDesc('toplam')
            ->limit(15)
            ->get();

        // ── BELGE NO HİSTOGRAM ────────────────────────────────────────────────
        $belgeNoHistogram = DB::table('acenteler')
            ->selectRaw("
                FLOOR(CAST(belge_no AS UNSIGNED) / 1000) * 1000 as aralik,
                COUNT(*) as toplam
            ")
            ->whereRaw("belge_no REGEXP '^[0-9]+$'")
            ->where('belge_no', '!=', '0')
            ->groupBy('aralik')
            ->orderBy('aralik')
            ->get();

        // ── ŞUBE DAĞILIMI ─────────────────────────────────────────────────────
        $subeDagilim = DB::table('acenteler')
            ->selectRaw('sube_sira, COUNT(*) as toplam')
            ->groupBy('sube_sira')
            ->orderBy('sube_sira')
            ->limit(10)
            ->get();

        // ── VERİ KALİTESİ (Radar için) ───────────────────────────────────────
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
            ->groupBy('kaynak')
            ->get();

        // ── DAVET İSTATİSTİĞİ ─────────────────────────────────────────────────
        $davetTableExists = Schema::hasTable('tursab_davetler');
        $davetEdilen   = $davetTableExists ? DB::table('tursab_davetler')->count() : 0;
        $davetBasarili = $davetTableExists ? DB::table('tursab_davetler')->where('status', 'sent')->count() : 0;
        $davetHatali   = $davetTableExists ? DB::table('tursab_davetler')->where('status', 'failed')->count() : 0;

        // ── TOP 5 İL → İLÇE DRILLDOWN ────────────────────────────────────────
        $top5Iller = $ilDagilim->take(5)->pluck('il');
        $ilceDrilldown = DB::table('acenteler')
            ->selectRaw('il, il_ilce, COUNT(*) as toplam')
            ->whereIn('il', $top5Iller)
            ->whereNotNull('il_ilce')->where('il_ilce', '!=', '')
            ->groupBy('il', 'il_ilce')
            ->orderBy('il')
            ->orderByDesc('toplam')
            ->get()
            ->groupBy('il');

        return view('superadmin.acenteler-istatistik', compact(
            'toplam', 'epostaVar', 'telefonVar', 'subeCount', 'anaMerkez',
            'kaynaklar',
            'ilDagilim', 'buyuk3Toplam', 'digerIlToplam',
            'grupDagilim',
            'ilEpostaOran',
            'ilceDagilim',
            'belgeNoHistogram',
            'subeDagilim',
            'veriKalitesi',
            'davetEdilen', 'davetBasarili', 'davetHatali',
            'ilceDrilldown', 'top5Iller'
        ));
    }
}
