<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\SigortaPolice;
use App\Services\PaoNetService;
use App\Services\PaoNetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    // ── Form ──────────────────────────────────────────────────────────────────

    public function create()
    {
        if (!$this->aktifMi()) {
            return view('b2c.sigorta.kapali');
        }
        return view('b2c.sigorta.create');
    }

    // ── Teklif AJAX ───────────────────────────────────────────────────────────

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
            'g-recaptcha-response' => 'nullable|string',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta hizmeti şu an kullanılamıyor.'], 503);
        }

        try {
            $svc      = app(PaoNetService::class);
            $kimlikT  = PaoNetHelper::detectKimlikTipi($request->kimlik);
            $urunKodu = PaoNetHelper::urunKodu($kimlikT);

            $strMsg = PaoNetHelper::buildStrMsg([
                'Kimlik'          => $request->kimlik,
                'Adi'             => $request->adi,
                'Soyadi'          => $request->soyadi,
                'DogumTarihi'     => $request->dogum_tarihi,
                'BaslangicTarihi' => $request->baslangic_tarihi,
                'BitisTarihi'     => $request->bitis_tarihi,
                'GidilecekUlke'   => $request->ulke,
            ]);

            $teklif  = $svc->teklifAl($urunKodu, $strMsg);
            $bprim   = (float) ($teklif['Bprim'] ?? $teklif['bprim'] ?? 0);
            $dkuru   = (float) ($teklif['Dkuru'] ?? $teklif['dkuru'] ?? 1);
            $doviz   = $teklif['DovizTuru'] ?? 'USD';

            $markup  = (float) $this->ayar('b2c_markup_yuzde', 50);
            $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
            $fiyat   = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

            return response()->json([
                'ok'         => true,
                'teklif_id'  => $teklif['TeklifId'] ?? '',
                'urun_kodu'  => $urunKodu,
                'doviz_turu' => $doviz,
                'bprim'      => $bprim,
                'dkuru'      => $dkuru,
                'satis_tl'   => $fiyat['satis_fiyat'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Poliçe Üret ───────────────────────────────────────────────────────────

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
            return response()->json(['error' => 'Sigorta hizmeti şu an kullanılamıyor.'], 503);
        }

        $markup  = (float) $this->ayar('b2c_markup_yuzde', 50);
        $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
        $fiyat   = PaoNetHelper::hesaplaSatisFiyati(
            (float) $request->bprim,
            (float) $request->dkuru,
            $markup,
            $tampon
        );

        $b2cUser = auth('b2c')->user();

        $police = SigortaPolice::create([
            'b2c_user_id'      => $b2cUser?->id,
            'kanal'            => 'b2c',
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
            'maliyet_tl'       => $fiyat['maliyet_tl'],
            'b2c_fiyat_tl'     => $fiyat['satis_fiyat'],
            'satilan_fiyat_tl' => $fiyat['satis_fiyat'],
            'net_kar_tl'       => $fiyat['net_kar'],
            'markup_yuzde'     => $markup,
            'kur_tamponu_yuzde'=> $tampon,
            'durum'            => 'police_isleniyor',
        ]);

        try {
            $svc    = app(PaoNetService::class);
            $sonuc  = $svc->policeUret($request->teklif_id);
            $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';

            $police->update(['paonet_referans' => $referans]);

            return response()->json(['ok' => true, 'police_id' => $police->id, 'referans' => $referans]);
        } catch (\RuntimeException $e) {
            $police->update(['durum' => 'hata', 'hata_mesaji' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Üretim Durum Poll ─────────────────────────────────────────────────────

    public function policeUretimDurum(Request $request, SigortaPolice $police)
    {
        // Yalnızca kendi poliçesi (giriş yapmışsa) veya session referansı
        $b2cUser = auth('b2c')->user();
        if ($b2cUser) {
            abort_unless($police->b2c_user_id === $b2cUser->id, 403);
        }

        if ($police->durum === 'tamamlandi') {
            return response()->json(['durum' => 'tamamlandi', 'police_no' => $police->police_no]);
        }

        if (!$this->aktifMi() || empty($police->paonet_referans)) {
            return response()->json(['durum' => $police->durum]);
        }

        try {
            $svc   = app(PaoNetService::class);
            $sonuc = $svc->uretimDurumu($police->paonet_referans);
            $policeNo = $sonuc['PoliceNo'] ?? '';

            if ($policeNo) {
                $pdfData = $svc->pdfGetir($policeNo);
                $police->update([
                    'police_no'          => $policeNo,
                    'durum'              => 'tamamlandi',
                    'pdf_link'           => $pdfData['PdfLink'] ?? '',
                    'makbuz_link'        => $pdfData['MakbuzLink'] ?? '',
                    'sertifika_link'     => $pdfData['SertifikaLink'] ?? '',
                    'ing_sertifika_link' => $pdfData['IngSertifikaLink'] ?? '',
                ]);
                return response()->json(['durum' => 'tamamlandi', 'police_no' => $policeNo]);
            }

            return response()->json(['durum' => 'police_isleniyor']);
        } catch (\RuntimeException $e) {
            return response()->json(['durum' => $police->durum]);
        }
    }

    // ── PDF Proxy ─────────────────────────────────────────────────────────────

    public function belge(SigortaPolice $police, string $tip)
    {
        $b2cUser = auth('b2c')->user();
        if ($b2cUser) {
            abort_unless($police->b2c_user_id === $b2cUser->id, 403);
        }

        $urlMap = [
            'police'       => $police->pdf_link,
            'makbuz'       => $police->makbuz_link,
            'sertifika'    => $police->sertifika_link,
        ];

        $rawUrl = $urlMap[$tip] ?? null;
        abort_if(empty($rawUrl), 404);

        return app(PaoNetService::class)->pdfStream(
            PaoNetHelper::normalizePdfUrl($rawUrl)
        );
    }
}
