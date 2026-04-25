<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SigortaPolice;
use App\Models\SigortaBatchJob;
use App\Services\PaoNetService;
use App\Services\PaoNetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SigortaController extends Controller
{
    // ── Tüm Poliçeler ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $q = SigortaPolice::with('acente')->latest();

        if ($request->filled('kimlik'))    $q->where('sigortali_kimlik', 'like', '%' . $request->kimlik . '%');
        if ($request->filled('police_no')) $q->where('police_no', 'like', '%' . $request->police_no . '%');
        if ($request->filled('kanal'))     $q->where('kanal', $request->kanal);
        if ($request->filled('durum'))     $q->where('durum', $request->durum);
        if ($request->filled('acente_id')) $q->where('acente_id', $request->acente_id);
        if ($request->filled('tarih_bas')) $q->whereDate('baslangic_tarihi', '>=', $request->tarih_bas);
        if ($request->filled('tarih_bit')) $q->whereDate('baslangic_tarihi', '<=', $request->tarih_bit);

        $policeler = $q->paginate(30)->withQueryString();

        return view('admin.sigorta.index', compact('policeler'));
    }

    // ── Poliçe Detay ─────────────────────────────────────────────────────────

    public function show(SigortaPolice $police)
    {
        $police->load('acente', 'batchJob');
        return view('admin.sigorta.show', compact('police'));
    }

    // ── Markup Yönetimi ───────────────────────────────────────────────────────

    public function markup()
    {
        $ayarlar = DB::table('sigorta_ayarlar')->pluck('deger', 'anahtar');
        return view('admin.sigorta.markup', compact('ayarlar'));
    }

    public function markupGuncelle(Request $request)
    {
        $request->validate([
            'b2b_markup_yuzde'  => 'required|numeric|min:0|max:200',
            'b2c_markup_yuzde'  => 'required|numeric|min:0|max:200',
            'kur_tamponu_yuzde' => 'required|numeric|min:0|max:50',
            'aktif'             => 'boolean',
        ]);

        $guncellenecekler = [
            'b2b_markup_yuzde'  => $request->b2b_markup_yuzde,
            'b2c_markup_yuzde'  => $request->b2c_markup_yuzde,
            'kur_tamponu_yuzde' => $request->kur_tamponu_yuzde,
            'aktif'             => $request->boolean('aktif') ? '1' : '0',
        ];

        foreach ($guncellenecekler as $anahtar => $deger) {
            DB::table('sigorta_ayarlar')
                ->where('anahtar', $anahtar)
                ->update(['deger' => $deger, 'updated_at' => now()]);
        }

        return back()->with('success', 'Sigorta ayarları güncellendi.');
    }

    // ── Kâr Raporu ───────────────────────────────────────────────────────────

    public function karRaporu(Request $request)
    {
        $donem  = $request->get('donem', 'ay');
        $yil    = (int) $request->get('yil', now()->year);
        $ay     = (int) $request->get('ay', now()->month);

        $q = SigortaPolice::where('durum', 'tamamlandi');

        if ($donem === 'gun') {
            $q->whereDate('created_at', $request->get('tarih', today()));
        } elseif ($donem === 'ay') {
            $q->whereYear('created_at', $yil)->whereMonth('created_at', $ay);
        } elseif ($donem === 'yil') {
            $q->whereYear('created_at', $yil);
        }

        $ozet = $q->selectRaw('
            kanal,
            COUNT(*) as adet,
            SUM(maliyet_tl) as toplam_maliyet,
            SUM(satilan_fiyat_tl) as toplam_satis,
            SUM(net_kar_tl) as toplam_kar
        ')->groupBy('kanal')->get();

        $gunluk = SigortaPolice::where('durum', 'tamamlandi')
            ->whereYear('created_at', $yil)
            ->whereMonth('created_at', $ay)
            ->selectRaw('DATE(created_at) as tarih, COUNT(*) as adet, SUM(net_kar_tl) as kar')
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get();

        return view('admin.sigorta.kar-raporu', compact('ozet', 'gunluk', 'donem', 'yil', 'ay'));
    }

    // ── Batch Job Listesi ─────────────────────────────────────────────────────

    public function batchler(Request $request)
    {
        $batchler = SigortaBatchJob::with('acente')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.sigorta.batchler', compact('batchler'));
    }

    // ── İptal Bekleyenleri Kontrol Et (Manuel Tetikleme) ─────────────────────

    public function iptalKontrolCalistir()
    {
        if (empty(config('services.paonet.api_key'))) {
            return response()->json(['error' => 'PAO-Net API anahtarı tanımlanmamış.'], 422);
        }

        $bekleyenler = SigortaPolice::where('durum', 'iptal_bekliyor')
            ->whereNotNull('police_no')
            ->get();

        $guncellenen = 0;
        foreach ($bekleyenler as $police) {
            try {
                $svc   = app(PaoNetService::class);
                $sonuc = $svc->iptalKontrol($police->police_no);
                $durum = $sonuc['IptalDurum'] ?? $sonuc['iptalDurum'] ?? '';
                if (in_array(strtolower($durum), ['iptal', 'cancelled', 'onaylandi', '1', 'true'])) {
                    $police->update(['durum' => 'iptal']);
                    $guncellenen++;
                }
            } catch (\Throwable $e) {
                // Tek hata tüm döngüyü durdurmasın
            }
        }

        return response()->json([
            'ok'          => true,
            'kontrol'     => $bekleyenler->count(),
            'guncellenen' => $guncellenen,
        ]);
    }

    // ── PDF Belge Proxy (admin erişimi) ───────────────────────────────────────

    public function belge(SigortaPolice $police, string $tip)
    {
        abort_unless(in_array($tip, ['police', 'makbuz', 'sertifika', 'ing-sertifika']), 404);

        $urlMap = [
            'police'       => $police->pdf_link,
            'makbuz'       => $police->makbuz_link,
            'sertifika'    => $police->sertifika_link,
            'ing-sertifika'=> $police->ing_sertifika_link,
        ];

        $rawUrl = $urlMap[$tip] ?? null;
        abort_if(empty($rawUrl), 404, 'Belge bulunamadı.');

        return app(PaoNetService::class)->pdfStream(
            PaoNetHelper::normalizePdfUrl($rawUrl)
        );
    }
}
