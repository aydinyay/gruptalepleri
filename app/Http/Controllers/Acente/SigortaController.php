<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\SigortaPolice;
use App\Models\SigortaBatchJob;
use App\Services\PaoNetService;
use App\Services\PaoNetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SigortaController extends Controller
{
    private function aktifMi(): bool
    {
        return (bool) DB::table('sigorta_ayarlar')->where('anahtar', 'aktif')->value('deger');
    }

    private function ayar(string $anahtar, mixed $default = null): mixed
    {
        $val = DB::table('sigorta_ayarlar')->where('anahtar', $anahtar)->value('deger');
        return $val !== null ? $val : $default;
    }

    // ── Liste ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $acenteId = $this->acenteActor()->id;

        $q = SigortaPolice::where('acente_id', $acenteId)
            ->where('kanal', 'b2b')
            ->latest();

        if ($request->filled('kimlik'))   $q->where('sigortali_kimlik', 'like', '%' . $request->kimlik . '%');
        if ($request->filled('police_no'))$q->where('police_no', 'like', '%' . $request->police_no . '%');
        if ($request->filled('durum'))    $q->where('durum', $request->durum);
        if ($request->filled('tarih_bas'))$q->whereDate('baslangic_tarihi', '>=', $request->tarih_bas);
        if ($request->filled('tarih_bit'))$q->whereDate('baslangic_tarihi', '<=', $request->tarih_bit);

        $policeler = $q->paginate(25)->withQueryString();

        return view('acente.sigorta.index', compact('policeler'));
    }

    // ── Tekil Yeni Form ───────────────────────────────────────────────────────

    public function create()
    {
        return view('acente.sigorta.create', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    // ── Müşteri Kontrol AJAX (CHK2CST) ────────────────────────────────────────

    public function musteriKontrol(Request $request)
    {
        $request->validate([
            'kimlik'       => 'required|string|max:20',
            'dogum_tarihi' => 'required|date',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        try {
            $svc  = app(PaoNetService::class);
            $data = $svc->musteriKontrol($request->kimlik, $request->dogum_tarihi);
            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Teklif Al AJAX ────────────────────────────────────────────────────────

    public function teklifAl(Request $request)
    {
        $request->validate([
            'kimlik'           => 'required|string|max:20',
            'adi'              => 'required|string|max:80',
            'soyadi'           => 'required|string|max:80',
            'dogum_tarihi'     => 'required|date',
            'baslangic_tarihi' => 'required|date|after_or_equal:today',
            'bitis_tarihi'     => 'required|date|after:baslangic_tarihi',
            'ulke'             => 'required|string|max:80',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        try {
            $svc      = app(PaoNetService::class);
            $kimlikT  = PaoNetHelper::detectKimlikTipi($request->kimlik);
            $urunKodu = PaoNetHelper::urunKodu($kimlikT);

            $msgParams = [
                'Kimlik'          => $request->kimlik,
                'Adi'             => $request->adi,
                'Soyadi'          => $request->soyadi,
                'DogumTarihi'     => $request->dogum_tarihi,
                'BaslangicTarihi' => $request->baslangic_tarihi,
                'BitisTarihi'     => $request->bitis_tarihi,
                'GidilecekUlke'   => $request->ulke,
            ];

            // NPN220 (pasaport) için zorunlu ek alanlar
            if ($kimlikT === 'pasaport') {
                $msgParams['DogumYeri'] = $request->dogum_yeri ?? '';
                $msgParams['Uyruk']     = $request->uyruk ?? '';
                $msgParams['Boy']       = $request->boy ?? '';
                $msgParams['Kilo']      = $request->kilo ?? '';
                $msgParams['IlAdi']     = $request->il_adi ?? '';
                $msgParams['IlceAdi']   = $request->ilce_adi ?? '';
                $msgParams['Adres']     = $request->adres ?? '';
            }

            $strMsg = PaoNetHelper::buildStrMsg($msgParams);
            $teklif = $svc->teklifAl($urunKodu, $strMsg);

            $bprim = (float) ($teklif['Bprim'] ?? $teklif['bprim'] ?? 0);
            $dkuru = (float) ($teklif['Dkuru'] ?? $teklif['dkuru'] ?? 1);
            $doviz = $teklif['DovizTuru'] ?? $teklif['dovizTuru'] ?? 'USD';

            $markup  = (float) $this->ayar('b2b_markup_yuzde', 20);
            $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
            $fiyatlar = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

            return response()->json([
                'ok'         => true,
                'teklif_id'  => $teklif['TeklifId'] ?? $teklif['teklifId'] ?? '',
                'urun_kodu'  => $urunKodu,
                'doviz_turu' => $doviz,
                'bprim'      => $bprim,
                'dkuru'      => $dkuru,
                'maliyet_tl' => $fiyatlar['maliyet_tl'],
                'satis_tl'   => $fiyatlar['satis_fiyat'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Poliçe Üret (Onayla) ─────────────────────────────────────────────────

    public function policeUret(Request $request)
    {
        $request->validate([
            'teklif_id'        => 'required|string',
            'urun_kodu'        => 'required|string',
            'kimlik'           => 'required|string|max:20',
            'adi'              => 'required|string|max:80',
            'soyadi'           => 'required|string|max:80',
            'dogum_tarihi'     => 'required|date',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date',
            'ulke'             => 'required|string|max:80',
            'bprim'            => 'required|numeric',
            'dkuru'            => 'required|numeric',
            'doviz_turu'       => 'required|string|max:5',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        $markup  = (float) $this->ayar('b2b_markup_yuzde', 20);
        $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
        $fiyatlar = PaoNetHelper::hesaplaSatisFiyati(
            (float) $request->bprim,
            (float) $request->dkuru,
            $markup,
            $tampon
        );

        $police = SigortaPolice::create([
            'acente_id'        => $this->acenteActor()->id,
            'kanal'            => 'b2b',
            'paonet_teklif_id' => $request->teklif_id,
            'paonet_urun_kodu' => $request->urun_kodu,
            'sigortali_kimlik' => $request->kimlik,
            'kimlik_tipi'      => PaoNetHelper::detectKimlikTipi($request->kimlik),
            'sigortali_adi'    => $request->adi,
            'sigortali_soyadi' => $request->soyadi,
            'sigortali_dogum'  => $request->dogum_tarihi,
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi'     => $request->bitis_tarihi,
            'gidilecek_ulke'   => $request->ulke,
            'api_doviz_turu'   => $request->doviz_turu,
            'api_doviz_tutar'  => $request->bprim,
            'api_kur'          => $request->dkuru,
            'maliyet_tl'       => $fiyatlar['maliyet_tl'],
            'b2b_fiyat_tl'     => $fiyatlar['satis_fiyat'],
            'satilan_fiyat_tl' => $fiyatlar['satis_fiyat'],
            'net_kar_tl'       => $fiyatlar['net_kar'],
            'markup_yuzde'     => $markup,
            'kur_tamponu_yuzde'=> $tampon,
            'durum'            => 'police_isleniyor',
        ]);

        try {
            $svc    = app(PaoNetService::class);
            $sonuc  = $svc->policeUret($request->teklif_id);
            $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';

            $police->update([
                'paonet_referans' => $referans,
                'durum'           => 'police_isleniyor',
            ]);

            return response()->json(['ok' => true, 'police_id' => $police->id, 'referans' => $referans]);
        } catch (\RuntimeException $e) {
            $police->update(['durum' => 'hata', 'hata_mesaji' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Üretim Durum Poll AJAX (CHKAUTOPOLICY) ─────────────────────────────────

    public function policeUretimDurum(Request $request, SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);

        if ($police->durum === 'tamamlandi') {
            return response()->json(['durum' => 'tamamlandi', 'police_no' => $police->police_no]);
        }

        if (!$this->aktifMi() || empty($police->paonet_referans)) {
            return response()->json(['durum' => $police->durum]);
        }

        try {
            $svc   = app(PaoNetService::class);
            $sonuc = $svc->uretimDurumu($police->paonet_referans);

            $policeNo = $sonuc['PoliceNo'] ?? $sonuc['policeNo'] ?? '';

            if ($policeNo) {
                // PDF linklerini de çek
                $pdfData = $svc->pdfGetir($policeNo);
                $police->update([
                    'police_no'        => $policeNo,
                    'durum'            => 'tamamlandi',
                    'pdf_url_base'     => $pdfData['PdfUrlBase'] ?? '',
                    'pdf_link'         => $pdfData['PdfLink'] ?? '',
                    'makbuz_link'      => $pdfData['MakbuzLink'] ?? '',
                    'sertifika_link'   => $pdfData['SertifikaLink'] ?? '',
                    'ing_sertifika_link' => $pdfData['IngSertifikaLink'] ?? '',
                ]);

                return response()->json(['durum' => 'tamamlandi', 'police_no' => $policeNo]);
            }

            return response()->json(['durum' => 'police_isleniyor']);
        } catch (\RuntimeException $e) {
            return response()->json(['durum' => $police->durum, 'hata' => $e->getMessage()]);
        }
    }

    // ── Poliçe Detay ──────────────────────────────────────────────────────────

    public function show(SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);
        return view('acente.sigorta.show', compact('police'));
    }

    // ── Toplu İşlem Form ─────────────────────────────────────────────────────

    public function toplu()
    {
        return view('acente.sigorta.toplu', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    // ── CSV Şablon İndir ─────────────────────────────────────────────────────

    public function topluSablon()
    {
        $bom     = "\xEF\xBB\xBF"; // Excel için UTF-8 BOM
        $header  = "kimlik,adi,soyadi,baba_adi,dogum_tarihi,dogum_yeri,cinsiyet,uyruk,boy,kilo,il,ilce,adres\n";
        $tc      = "12345678901,Ahmet,Yılmaz,,1985-05-15,,E,,,,,, \n";
        $pspr    = "AB1234567,Ivan,Petrov,Nikolai,1990-03-20,Moskova,E,Rus,180,75,İstanbul,Şişli,\"Cumhuriyet Cad. No:10\"\n";

        return response($bom . $header . $tc . $pspr, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sigorta-sablon.csv"',
        ]);
    }

    // ── Toplu Batch Başlat ────────────────────────────────────────────────────

    public function topluBaslat(Request $request)
    {
        $request->validate([
            'islem_adi'        => 'nullable|string|max:120',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date|after:baslangic_tarihi',
            'ulke'             => 'required|string|max:80',
            'satirlar'         => 'required|array|min:1|max:200',
            'satirlar.*.kimlik'      => 'required|string|max:20',
            'satirlar.*.adi'         => 'required|string|max:80',
            'satirlar.*.soyadi'      => 'required|string|max:80',
            'satirlar.*.dogum_tarihi'=> 'required|date',
        ]);

        $satirlar = array_map(fn ($s) => array_merge($s, [
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi'     => $request->bitis_tarihi,
            'ulke'             => $request->ulke,
            'durum'            => 'bekliyor',
        ]), $request->satirlar);

        $batch = SigortaBatchJob::create([
            'islem_adi'       => $request->islem_adi ?: 'Toplu Sigorta ' . now()->format('d.m.Y H:i'),
            'kanal'           => 'b2b',
            'acente_id'       => $this->acenteActor()->id,
            'toplam'          => count($satirlar),
            'durum'           => 'bekliyor',
            'bekleyen_satirlar' => $satirlar,
        ]);

        return response()->json(['ok' => true, 'batch_id' => $batch->id]);
    }

    // ── Toplu Batch Poll (3-5 kayıt işler) ────────────────────────────────────

    public function topluPoll(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);

        if ($batch->tamamlandiMi()) {
            return response()->json([
                'tamamlandi'  => true,
                'tamamlanan'  => $batch->tamamlanan,
                'basarisiz'   => $batch->basarisiz,
                'toplam'      => $batch->toplam,
                'hatali'      => $batch->hatali_satirlar ?? [],
            ]);
        }

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        $bekleyen = $batch->bekleyen_satirlar ?? [];
        if (empty($bekleyen)) {
            $batch->update(['durum' => 'tamamlandi']);
            return response()->json(['tamamlandi' => true, 'tamamlanan' => $batch->tamamlanan, 'basarisiz' => $batch->basarisiz, 'toplam' => $batch->toplam]);
        }

        $batch->update(['durum' => 'isleniyor']);

        $islenecekler = array_splice($bekleyen, 0, 4); // her poll'da 4 kişi
        $hatali       = $batch->hatali_satirlar ?? [];
        $markup       = (float) $this->ayar('b2b_markup_yuzde', 20);
        $tampon       = (float) $this->ayar('kur_tamponu_yuzde', 5);

        $svc = app(PaoNetService::class);

        foreach ($islenecekler as $satir) {
            try {
                $kimlikT  = PaoNetHelper::detectKimlikTipi($satir['kimlik']);
                $urunKodu = PaoNetHelper::urunKodu($kimlikT);

                $strMsg = PaoNetHelper::buildStrMsg([
                    'Kimlik'          => $satir['kimlik'],
                    'Adi'             => $satir['adi'],
                    'Soyadi'          => $satir['soyadi'],
                    'DogumTarihi'     => $satir['dogum_tarihi'],
                    'BaslangicTarihi' => $satir['baslangic_tarihi'],
                    'BitisTarihi'     => $satir['bitis_tarihi'],
                    'GidilecekUlke'   => $satir['ulke'],
                ]);

                $teklif  = $svc->teklifAl($urunKodu, $strMsg);
                $sonuc   = $svc->policeUret($teklif['TeklifId'] ?? $teklif['teklifId'] ?? '');
                $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';

                $bprim = (float) ($teklif['Bprim'] ?? 0);
                $dkuru = (float) ($teklif['Dkuru'] ?? 1);
                $fiyat = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

                SigortaPolice::create([
                    'batch_job_id'     => $batch->id,
                    'acente_id'        => $batch->acente_id,
                    'kanal'            => 'b2b',
                    'paonet_referans'  => $referans,
                    'paonet_teklif_id' => $teklif['TeklifId'] ?? '',
                    'paonet_urun_kodu' => $urunKodu,
                    'sigortali_kimlik' => $satir['kimlik'],
                    'kimlik_tipi'      => $kimlikT,
                    'sigortali_adi'    => $satir['adi'],
                    'sigortali_soyadi' => $satir['soyadi'],
                    'sigortali_dogum'  => $satir['dogum_tarihi'],
                    'baslangic_tarihi' => $satir['baslangic_tarihi'],
                    'bitis_tarihi'     => $satir['bitis_tarihi'],
                    'gidilecek_ulke'   => $satir['ulke'],
                    'api_doviz_turu'   => $teklif['DovizTuru'] ?? 'USD',
                    'api_doviz_tutar'  => $bprim,
                    'api_kur'          => $dkuru,
                    'maliyet_tl'       => $fiyat['maliyet_tl'],
                    'b2b_fiyat_tl'     => $fiyat['satis_fiyat'],
                    'satilan_fiyat_tl' => $fiyat['satis_fiyat'],
                    'net_kar_tl'       => $fiyat['net_kar'],
                    'markup_yuzde'     => $markup,
                    'kur_tamponu_yuzde'=> $tampon,
                    'durum'            => 'police_isleniyor',
                ]);

                $batch->increment('tamamlanan');
            } catch (\RuntimeException $e) {
                $satir['hata'] = $e->getMessage();
                $hatali[]      = $satir;
                $batch->increment('basarisiz');
                Log::warning('Batch sigorta hatası', ['satir' => $satir, 'hata' => $e->getMessage()]);
            }
        }

        $yeniDurum = empty($bekleyen) ? 'tamamlandi' : 'isleniyor';

        $batch->update([
            'bekleyen_satirlar' => array_values($bekleyen),
            'hatali_satirlar'   => array_values($hatali),
            'durum'             => $yeniDurum,
        ]);

        return response()->json([
            'tamamlandi' => $yeniDurum === 'tamamlandi',
            'tamamlanan' => $batch->tamamlanan,
            'basarisiz'  => $batch->basarisiz,
            'toplam'     => $batch->toplam,
            'kalan'      => count($bekleyen),
            'hatali'     => $hatali,
        ]);
    }

    // ── Batch Retry (Hatalı Kayıtlar) ─────────────────────────────────────────

    public function topluRetry(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);

        $hatali = $batch->hatali_satirlar ?? [];
        if (empty($hatali)) {
            return response()->json(['error' => 'Yeniden denenecek kayıt yok.'], 422);
        }

        $batch->update([
            'bekleyen_satirlar' => array_map(fn ($s) => array_merge($s, ['hata' => null]), $hatali),
            'hatali_satirlar'   => [],
            'basarisiz'         => 0,
            'durum'             => 'bekliyor',
        ]);

        return response()->json(['ok' => true]);
    }

    // ── MBF Form (B2B only) ───────────────────────────────────────────────────

    public function mbf()
    {
        return view('acente.sigorta.mbf', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    public function mbfGonder(Request $request)
    {
        $request->validate([
            'trap_type' => 'required|in:1,2,3',
        ]);

        if (!$this->aktifMi()) {
            return back()->withErrors(['aktif' => 'Sigorta modülü henüz aktif değil.']);
        }

        try {
            $svc  = app(PaoNetService::class);
            $data = $svc->mbfOlustur($request->except('_token'));
            return back()->with('success', 'MBF başarıyla oluşturuldu. Referans: ' . ($data['Referans'] ?? ''));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['mbf' => $e->getMessage()]);
        }
    }

    // ── PDF Belge Proxy ───────────────────────────────────────────────────────

    public function belge(SigortaPolice $police, string $tip)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);

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

    // ── Kâr Raporu (Acente) ───────────────────────────────────────────────────

    public function karRaporu(Request $request)
    {
        $acenteId = $this->acenteActor()->id;

        $buAy = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('COUNT(*) as adet, SUM(satilan_fiyat_tl) as toplam_satis, SUM(net_kar_tl) as toplam_kar')
            ->first();

        $buYil = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->selectRaw('COUNT(*) as adet, SUM(satilan_fiyat_tl) as toplam_satis, SUM(net_kar_tl) as toplam_kar')
            ->first();

        $gunluk = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('DATE(created_at) as tarih, COUNT(*) as adet, SUM(net_kar_tl) as kar')
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get();

        return view('acente.sigorta.kar-raporu', compact('buAy', 'buYil', 'gunluk'));
    }

    // ── İptal ─────────────────────────────────────────────────────────────────

    public function iptal(Request $request, SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);
        abort_unless($police->iptalEdilebilirMi(), 422, 'Bu poliçe iptal edilemez.');

        $request->validate([
            'iptal_nedeni'    => 'required|string|max:255',
            'mukerrer_police' => 'required|string|max:80',
        ]);

        if (!$this->aktifMi()) {
            return back()->withErrors(['aktif' => 'Sigorta modülü henüz aktif değil.']);
        }

        try {
            $svc = app(PaoNetService::class);
            $svc->iptalEkle($police->police_no, $request->mukerrer_police, $request->iptal_nedeni);

            $police->update([
                'durum'              => 'iptal_bekliyor',
                'iptal_nedeni'       => $request->iptal_nedeni,
                'mukerrer_police_no' => $request->mukerrer_police,
                'iptal_tarih'        => now(),
            ]);

            return back()->with('success', 'İptal talebi PAO-Net\'e gönderildi.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['iptal' => $e->getMessage()]);
        }
    }
}
